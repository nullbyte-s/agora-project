<?php
/**
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/*
 * MODELE DES UTILISATEURS
 */
class MdlUser extends MdlPerson
{
	const moduleName="user";
	const objectType="user";
	const dbTable="ap_user";
	const isSelectable=true;
	public static $requiredFields=["login","name"];//password facultatif si modification de l'user
	public static $sortFields=["name@@asc","name@@desc","firstName@@asc","firstName@@desc","civility@@asc","civility@@desc","postalCode@@asc","postalCode@@desc","city@@asc","city@@desc","country@@asc","country@@desc","function@@asc","function@@desc","companyOrganization@@asc","companyOrganization@@desc","dateCrea@@desc","dateCrea@@asc","lastConnection@@asc","lastConnection@@desc"];
	//Valeurs en cache
	private $_userSpaces=null;
	private $_isAdminCurSpace=null;
	private $_usersVisibles=null;

	/*******************************************************************************************
	 * PHOTO D'UN UTILISATEUR
	 *******************************************************************************************/
	public function pathImgThumb()
	{
		return PATH_MOD_USER.$this->_id."_thumb.jpg";
	}

	/*******************************************************************************************
	 * VERIFIE SI L'USER EST BIEN IDENTIFIÉ (..SINON C'EST UN INVITÉ/GUEST)
	 *******************************************************************************************/
	public function isUser()
	{
		return (!empty($this->_id));
	}

	/*******************************************************************************************
	 * VERIFIE S'IL S'AGIT D'UN ADMINISTRATEUR GÉNÉRAL
	 *******************************************************************************************/
	public function isAdminGeneral()
	{
		return (!empty($this->generalAdmin));
	}

	/*******************************************************************************************
	 * VERIFIE SI L'USER EST UN ADMINISTRATEUR DE L'ESPACE COURANT
	 *******************************************************************************************/
	public function isAdminSpace()
	{
		if($this->_isAdminCurSpace===null)	{$this->_isAdminCurSpace=(Ctrl::$curSpace->accessRightUser($this)==2);}
		return $this->_isAdminCurSpace;
	}

	/*******************************************************************************************
	 * SURCHARGE : VERIF SI L'AUTEUR DE L'OBJET == L'USER CONNECTÉ
	 *******************************************************************************************/
	public function isAutor(){
		return (Ctrl::$curUser->isUser() && ($this->_id==Ctrl::$curUser->_id || $this->_idUser==Ctrl::$curUser->_id));
	}

	/*******************************************************************************************
	 * SURCHARGE : DROIT D'ACCÈS À L'OBJET
	 *******************************************************************************************/
	public function accessRight()
	{
		//Init la mise en cache
		if($this->_accessRight===null)
		{
			//Droit par défaut
			$this->_accessRight=parent::accessRight();
			//Pas d'accès : Ajoute l'accès en lecture si l'objet user fait partie des "usersVisibles" de l'user connecté (cf. messenger)
			if(empty($this->_accessRight)){
				foreach(Ctrl::$curUser->usersVisibles() as $tmpUser){
					if($this->_id==$tmpUser->_id)  {$this->_accessRight=1;}
				}
			}
		}
		return $this->_accessRight;
	}

	/*******************************************************************************************
	 * SURCHARGE : DROIT D'ÉDITION (ACCÈS TOTAL UNIQUEMENT)
	 *******************************************************************************************/
	public function editRight()
	{
		return ($this->accessRight()==3);
	}

	/*******************************************************************************************
	 * SURCHARGE : DROIT DE SUPPRESSION
	 *******************************************************************************************/
	public function deleteRight()
	{
		//Accès total  &&  Autre user que celui en cours  &&  Pas dernier adminGeneral
		$deleteRight=false;
		if(parent::fullRight() && $this->_id!=Ctrl::$curUser->_id){
			if($this->isAdminGeneral()==false || Db::getVal("SELECT count(*) FROM ap_user WHERE generalAdmin=1")>1)  {$deleteRight=true;}
		}
		//Retourne le droit d'Accès
		return $deleteRight;
	}

	/*******************************************************************************************
	 * DROIT DE DESAFFECTATION DE L'ESPACE
	 *******************************************************************************************/
	public function deleteFromCurSpaceRight()
	{
		return (Ctrl::$curUser->isAdminSpace() && Ctrl::$curSpace->allUsersAffected()==false);
	}

	/*******************************************************************************************
	 * LE DROIT "ADMIN GENERAL" PEUT ÊTRE ÉDITÉ PAR L'USER COURANT?
	 *******************************************************************************************/
	public function editAdminGeneralRight()
	{
		return (Ctrl::$curUser->isAdminGeneral() && Ctrl::$curUser->_id!=$this->_id);
	}

	/*******************************************************************************************
	 * L'USER COURANT PEUT ENVOYER DES INVITATIONS (SUR UN ESPACE DONNÉ) ?
	****************************************************************************************** */
	public function sendInvitationRight($objSpace=null)
	{
		if($objSpace==null)	{$objSpace=Ctrl::$curSpace;}
		return (function_exists("mail") && ($this->isAdminSpace() || (!empty($objSpace->usersInvitation) && $this->isUser())));
	}

	/*******************************************************************************************
	 * MESSENGER ACTIVÉ POUR L'USER COURANT (CF. PARAMETRAGE "actionUserEditMessenger()") ?
	 *******************************************************************************************/
	public function messengerEnabled()
	{
		if(empty($_SESSION["curUserMessengerEnabled"]))  {$_SESSION["curUserMessengerEnabled"]=($this->messengerAvailable() && Db::getVal("SELECT count(*) FROM ap_userMessenger WHERE _idUserMessenger=".$this->_id)>0);}
		return $_SESSION["curUserMessengerEnabled"];
	}

	/*******************************************************************************************
	 * MESSENGER DISPONIBLE POUR L'USER COURANT (ET PARAMETRABLE) ?
	 *******************************************************************************************/
	public function messengerAvailable()
	{
		return ($this->isUser() && empty(Ctrl::$agora->messengerDisabled));
	}

	/*******************************************************************************************
	 * SURCHARGE : SELECTIONNE LES USERS DE TOUT LE SITE OU LES USERS DE L'ESPACE COURANT
	 *******************************************************************************************/
	public static function sqlDisplay($containerObj=null, $keyId=null)
	{
		return ($_SESSION["displayUsers"]=="all")  ?  "1"  :  "_id IN (".Ctrl::$curSpace->getUsers("idsSql").")";
	}

	/*******************************************************************************************
	 * ESPACES AUXQUELS EST AFFECTÉ L'UTILISATEUR
	 * Retourne un tableau "objects" ou "ids"
	 *******************************************************************************************/
	public function getSpaces($return="objects")
	{
		//Initialise la liste des objets "space"
		if($this->_userSpaces===null){
			if($this->isAdminGeneral())	{$sqlQuery="SELECT * FROM ap_space ORDER BY name ASC";}//Admin général : tous les espaces
			elseif($this->isUser())		{$sqlQuery="SELECT DISTINCT T1.* FROM ap_space T1 LEFT JOIN ap_joinSpaceUser T2 ON T1._id=T2._idSpace WHERE T2._idUser=".$this->_id." OR T2.allUsers=1 ORDER BY name ASC";}//User lambda : espaces affectés
			else						{$sqlQuery="SELECT * FROM ap_space WHERE public=1 ORDER BY name ASC";}//Guest : espaces publics
			$this->_userSpaces=Db::getObjTab("space",$sqlQuery);
		}
		// Retourne un tableau d'objets OU d'identifiants
		if($return=="objects")  {return $this->_userSpaces;}
		else{
			$tabIds=[];
			foreach($this->_userSpaces as $objSpace)  {$tabIds[]=$objSpace->_id;}
			return $tabIds;
		}
	}

	/*******************************************************************************************
	 * SURCHARGE : SUPPRIME UN USER DÉFINITIVEMENT (ADMIN GÉNÉRAL UNIQUEMENT!)
	 *******************************************************************************************/
	public function delete()
	{
		if($this->deleteRight())
		{
			if($this->hasImg())  {unlink($this->pathImgThumb());}
			// Suppression des tables de jointures et tables annexes
			Db::query("DELETE FROM ap_joinSpaceUser			WHERE _idUser=".$this->_id);
			Db::query("DELETE FROM ap_objectTarget			WHERE target=".Db::format("U".$this->_id));
			Db::query("DELETE FROM ap_userLivecouter		WHERE _idUser=".$this->_id);
			Db::query("DELETE FROM ap_userMessenger			WHERE _idUserMessenger=".$this->_id." OR _idUser=".$this->_id);
			Db::query("DELETE FROM ap_userMessengerMessage	WHERE _idUser=".$this->_id);
			Db::query("DELETE FROM ap_userPreference		WHERE _idUser=".$this->_id);
			Db::query("DELETE FROM ap_objectLike			WHERE _idUser=".$this->_id);
			//Suppr l'agenda
			$objCalendar=new MdlCalendar(Db::getVal("SELECT _id FROM ap_calendar WHERE _idUser=".$this->_id." AND type='user'"));
			$objCalendar::$persoCalendarDeleteRight=true;//cf. "deleteRight()" du "MdlCalendar"
			$objCalendar->delete();
			//Suppr l'user
			parent::delete();
		}
	}

	/*******************************************************************************************
	 * SURCHARGE : DÉSAFFECTE/SUPPRIME UN USER D'UN ESPACE (ADMIN D'ESPACE UNIQUEMENT!)
	 *******************************************************************************************/
	public function deleteFromCurSpace($_idSpace)
	{
		if(Ctrl::$curUser->isAdminSpace()){
			Db::query("DELETE FROM ap_joinSpaceUser WHERE _idUser=".$this->_id." AND _idSpace=".(int)$_idSpace);
			if(Db::getVal("SELECT count(*) FROM ap_joinSpaceUser WHERE _idSpace=".(int)$_idSpace." AND allUsers=1")>0)  {Ctrl::notify("USER_allUsersOnSpaceNotif");}
		}
	}

	/*******************************************************************************************
	 *  AUTRES USERS QUE L'USER COURANT PEUT VOIR : SUR L'ENSEMBLE DE SES ESPACES
	 *******************************************************************************************/
	public function usersVisibles($mailFilter=false)
	{
		//Init
		if($this->_usersVisibles===null){
			$idsSql=null;
			foreach($this->getSpaces() as $objSpace)  {$idsSql.=",".$objSpace->getUsers("idsSql");}
			$this->_usersVisibles=Db::getObjTab("user", "SELECT * FROM ap_user WHERE _id IN (".trim($idsSql,",").") ORDER BY ".Ctrl::$agora->personsSort);
		}
		//Par défaut, on enlève l'user courant  /  "mailFilter" => garde uniquement les users avec mail (cf. notifMailUsers)
		$usersVisibles=$this->_usersVisibles;
		if($mailFilter==false)  {unset($usersVisibles[Ctrl::$curUser->_id]);}
		else{
			foreach($usersVisibles as $tmpUser){
				if(empty($tmpUser->mail))  {unset($usersVisibles[$tmpUser->_id]);}
			}
		}
		return $usersVisibles;
	}

	/*********************************************************************************************************
	 * VERIFIE SI LE LOGIN EXISTE DÉJÀ CHEZ UN USER (OU CHEZ UN AUTRE USER, SI ON MODIFIE LE LOGIN D'UN USER)
	 *********************************************************************************************************/
	public static function loginExists($login, $_idUserIgnore=null)
	{
		$sql_idUserIgnore=(!empty($_idUserIgnore))  ?  "AND _id!=".(int)$_idUserIgnore  :  null;
		return (!empty($login)  &&  Db::getVal("SELECT count(*) FROM ap_user WHERE (login=".Db::format($login)." OR mail=".Db::format($login).") ".$sql_idUserIgnore)>0);
	}

	/*******************************************************************************************
	 * NOMBRE D'UTILISATEURS MAXI DÉJÀ ATTEINT?
	 *******************************************************************************************/
	public static function usersQuotaOk($notify=true)
	{
		//Quota Ok ...sinon on ajoute une notif?
		if(self::usersQuotaRemaining()>0)  {return true;}
		else{
			if($notify==true){
				$msgUgrade=(Ctrl::isHost()) ? Host::notifUpgradeUsers() : null;//Propose l'upgrade?
				Ctrl::notify(Txt::trad("NOTIF_usersNb")." ".limite_nb_users.$msgUgrade);
			}
			return false;
		}
	}

	/*******************************************************************************************
	 * NOMBRE D'UTILISATEURS RESTANT
	 *******************************************************************************************/
	public static function usersQuotaRemaining()
	{
		if(defined("limite_nb_users"))  {return (int)(limite_nb_users - Db::getVal("SELECT count(*) FROM ap_user"));}
	}

	/*******************************************************************************************
	 * SURCHARGE : AJOUT/MODIF D'UTILISATEUR
	 *******************************************************************************************/
	public function createUpdate($sqlProperties, $login=null, $password=null, $spaceId=null)
	{
		////	Controles : quota atteint ? Login existe déjà ?
		if($this->isNew() && static::usersQuotaOk()==false)  {return false;}
		if(self::loginExists($login,$this->_id))   {Ctrl::notify(Txt::trad("USER_loginExists")." (".$login.")");  return false;}
		////	Ajoute le login, le password? si l'agenda perso est désactivé?
		$sqlProperties=trim(trim($sqlProperties),",");
		$sqlProperties.=", `login`=".Db::format($login);
		if(!empty($password))  {$sqlProperties.=", `password`=".Db::format(self::passwordSha1($password));}
		////	Nouvel User : ajoute le parametrage du messenger, l'agenda perso, et si besoin affecte l'user à un Espace.
		$reloadedObj=parent::createUpdate($sqlProperties);
		if($reloadedObj->isNewlyCreated()){
			Db::query("INSERT INTO ap_userMessenger SET _idUserMessenger=".$reloadedObj->_id.", allUsers=1");//Affecte l'user à tout le monde sur le messenger
			Db::query("INSERT INTO ap_calendar SET _idUser=".$reloadedObj->_id.", type='user'");//créé l'agenda, même si l'agenda est désactivé par défaut
			if(!empty($spaceId)){
				$tmpSpace=Ctrl::getObj("space",$spaceId);
				if($tmpSpace->allUsersAffected()==false)  {Db::query("INSERT INTO ap_joinSpaceUser SET _idSpace=".(int)$spaceId.", _idUser=".$reloadedObj->_id.", accessRight=1");}
			}
		}
		////	Retourne l'objet rechargé
		return $reloadedObj;
	}

	/*******************************************************************************************
	 * CRYPTE LE PASSWORD EN SHA1() + SALT
	 *******************************************************************************************/
	public static function passwordSha1($password, $specificSalt=null)
	{
		$SALT=(!empty($specificSalt))  ?  $specificSalt  :  self::getSalt();
		return sha1($SALT.sha1($password));
	}

	/*******************************************************************************************
	 * IDENTIFIANT TEMPORAIRE POUR LA RÉINITIALISATION DU PASSWORD
	 *******************************************************************************************/
	public function resetPasswordId()
	{
		return sha1($this->login.$this->password);
	}

	/*******************************************************************************************
	 * ENVOI DU MAIL DE RESET DE PASSWORD
	 *******************************************************************************************/
	public function resetPasswordSendMail()
	{
		//Récupère l'email (login en priorité)
		$mailTo=(Txt::isMail($this->login))  ?  $this->login  :  $this->mail;
		//Email non spécifié / Envoi du mail de reset de password
		if(Txt::isMail($mailTo)==false)  {Ctrl::notify("email not specified");}
		else
		{
			$resetPasswordUrl=Req::getCurUrl()."/?ctrl=offline&resetPasswordMail=".urlencode($mailTo)."&resetPasswordId=".$this->resetPasswordId();
			$subject=Txt::trad("resetPasswordMailTitle");
			$message=Txt::trad("MAIL_hello").",<br><br>".
					 "<b>".Txt::trad("resetPasswordMailPassword")." <a href=\"".$resetPasswordUrl."\" target='_blank'>".Txt::trad("resetPasswordMailPassword2")."</a></b>".
					 "<br><br>".Txt::trad("resetPasswordMailLoginRemind")." : <i>".$this->login."</i>";
			return Tool::sendMail($mailTo, $subject, $message, "noNotify");
		}
	}

	/*******************************************************************************************
	 * ENVOI DU MAIL DES COORDONNÉES DE CONNEXION POUR UN NOUVEL UTILISATEUR
	 *******************************************************************************************/
	public function newUserCoordsSendMail($clearPassword)
	{
		//Récupère l'email (login en priorité)
		$mailTo=(Txt::isMail($this->login))  ?  $this->login  :  $this->mail;
		//Email non spécifié / Envoi du mail de reset de password
		if(Txt::isMail($mailTo)==false)  {Ctrl::notify("email not specified");}
		else
		{
			$subject=Txt::trad("USER_mailNotifObject")." ".ucfirst(Ctrl::$agora->name);//"Bienvenue sur Mon-espace"
			$message=Txt::trad("USER_mailNotifContent")." <i>".Ctrl::$agora->name."</i> (".Req::getCurUrl(false).")<br><br>".//"Votre compte utilisateur vient d'être créé sur <i>Mon-espace</i>"
					 "<a href=\"".Req::getCurUrl()."/?login=".$this->login."\" target='_blank'>".Txt::trad("USER_mailNotifContent2")."</a> :<br><br>".//"Connectez-vous ici avec les coordonnées suivantes" (lien vers l'espace)
					 Txt::trad("login")." : <b>".$this->login."</b><br>".//"Login : Mon-login"
					 Txt::trad("passwordToModify")." : <b>".$clearPassword."</b><br><br>".//"Mot de passe (à modifier au besoin)"
					 Txt::trad("USER_mailNotifContent3");//"Merci de conserver cet e-mail dans vos archives"
			return Tool::sendMail($mailTo, $subject, $message);
		}
	}

	/*******************************************************************************************
	 * RÉCUPÈRE LE SALT
	 *******************************************************************************************/
	public static function getSalt()
	{
		return (!defined("AGORA_SALT") || !AGORA_SALT)  ?  "Ag0rA-Pr0j3cT"  :  AGORA_SALT;
	}

	/*******************************************************************************************
	 * CONNEXION D'UN USER PAS PRESENT SUR L'AGORA : TENTE UNE CONNEXION LDAP POUR UNE CREATION A LA VOLEE
	 *******************************************************************************************/
	public static function ldapConnectCreateUser($login, $password)
	{
		$userInfos=[];
		// Creation d'user ldap autorisee ?
		if(Ctrl::$agora->ldap_crea_auto_users==1)
		{
			// Il faut au moins un espace affecte a tous les users & quota d'users pas depasse
			if(Db::getVal("SELECT count(*) FROM ap_joinSpaceUser WHERE allUsers=1")>0 && static::usersQuotaOk(false)==true)
			{
				// Mot de passe crypté.. ou pas (note : certains serveurs LDAP ne fournissent pas le password, tel ActiveDirectory)
				if(Ctrl::$agora->ldap_pass_cryptage=="sha")		{$ldapPassword="{sha}".base64_encode(mhash(MHASH_SHA1,$password));}//TESTER AVEC SHA256 && SHA512 && {SHA} && {sha}
				elseif(Ctrl::$agora->ldap_pass_cryptage=="md5")	{$ldapPassword="{md5}".base64_encode(mhash(MHASH_MD5,$password));}//IDEM
				else											{$ldapPassword=$password;}
				// récupère les valeurs de l'user sur le serveur LDAP
				$usersLDAP=self::ldapSearch(true, "userConnect", "(|(uid=".$login.")(samaccountname=".$login."))");
				if(count($usersLDAP["ldapPersons"])>0)
				{
					foreach($usersLDAP["ldapPersons"] as $tmpUser)
					{
						if((!empty($tmpUser["name"]) || !empty($tmpUser["firstName"])) && !empty($tmpUser["mail"]) && !empty($tmpUser["login"]))
						{
							// Teste la connexion de l'user sur le serveur LDAP
							$userLogin="uid=".$login.",".Ctrl::$agora->ldap_base_dn;
							$userLoginAD=$login.strrchr(Ctrl::$agora->ldap_admin_login,"@");//pour 2ème test sur ActiveDirectory (exple : "monLogin@monDomaineAD")
							$ldapConnection=self::ldapConnect(null, null, $userLogin, $password, false);
							if($ldapConnection==false)	{$ldapConnection=self::ldapConnect(null, null, $userLoginAD, $password, false);}
							//  Vérifie si l'id/password du serveur LDAP est identique à celui spécifié
							$idPassOk=($tmpUser["login"]==$login && $tmpUser["password"]==$ldapPassword);
							// Vérifie si l'user n'a pas déjà été importé :  la connexion peut être faite par erreur avec les login/password LDAP, différent de ceux de l'agora...
							$userAgoraExist=Db::getVal("SELECT count(*) FROM ap_user WHERE `login`=".Db::format($tmpUser["login"])." OR mail=".Db::format($tmpUser["mail"]));
							// Créé le compte sur l'agora
							if(($ldapConnection!=false || $idPassOk==true) && empty($userAgoraExist)){
								$newUser=new MdlUser();
								$sqlProperties=null;
								foreach($tmpUser as $attributeKey=>$attributeVal){
									if(!preg_match("/(password|login)/i",$attributeKey) && !empty($attributeVal))   {$sqlProperties.=$attributeKey."=".Db::format($attributeVal).", ";}
								}
								$sqlProperties=trim($sqlProperties,", ");
								$newUser=$newUser->createUpdate($sqlProperties, $login, $password);
								$userInfos=Db::getLine("SELECT * FROM ap_user WHERE _id=".$newUser->_id);
								break;
							}
						}
					}
				}
			}
		}
		return $userInfos;
	}
}