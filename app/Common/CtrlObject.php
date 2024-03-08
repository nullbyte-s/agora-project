<?php
/**
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/*
 * CONTROLEUR POUR LES "ACTION" ET "VUE" DES OBJETS : MENU, CONTROLE AJAX, VUE, ETC.
 */
class CtrlObject extends Ctrl
{
	//Vue des Folders en cache
	public static $vueFolders=null;

	/*******************************************************************************************
	 * ACTION : AFFICHE LES LOGS D'UN OBJET
	 *******************************************************************************************/
	public static function actionLogs()
	{
		if(Req::isParam("typeId")){
			$curObj=self::getObjTarget();
			if($curObj->editRight()){
				$vDatas["logsList"]=Db::getTab("SELECT *, UNIX_TIMESTAMP(date) as dateUnix FROM ap_log WHERE objectType='".$curObj::objectType."' AND _idObject=".$curObj->_id." ORDER BY date");
				static::displayPage(Req::commonPath."VueObjLogs.php",$vDatas);
			}
		}
	}

	/*******************************************************************************************
	 * ACTION : SUPPRIME LE OU LES OBJETS SÉLECTIONNÉS : CF. $object->getUrl("delete")
	 *******************************************************************************************/
	public static function actionDelete()
	{
		// Init
		$redirUrl=$updateDatasFolderSize=null;
		$notDeletedObjects=[];
		////	Supprime le/les objets
		foreach(self::getObjectsTypeId() as $tmpObj)
		{
			//Enregistre l'Url de redirection après le delete
			if(empty($redirUrl)){
				if($tmpObj::isFolder==true)						{$redirUrl=$tmpObj->containerObj()->getUrl();}	//Suppr un dossier : affiche le dossier parent 
				elseif($tmpObj::isContainerContent())			{$redirUrl=$tmpObj->getUrl();}					//Suppr un contenu (content) : affiche le "container"
				elseif($tmpObj::objectType=="forumSubject")		{$redirUrl=$tmpObj->getUrl("theme");}			//Suppr un sujet du forum : "getUrl()" surchargé
				else											{$redirUrl="?ctrl=".$tmpObj::moduleName;}		//Sinon redir en page principale du module
			}
			//Enregistre si on doit mettre à jour le "datasFolderSize()"
			if($tmpObj::moduleName=="file")  {$updateDatasFolderSize=true;}
			//Delete si on a les droits ..ou prepare un message d'erreur
			if($tmpObj->deleteRight())	{$tmpObj->delete();}																		
			else						{$notDeletedObjects[]=$tmpObj->getLabel();}	
		}
		////	Update le "datasFolderSize()" en session
		if($updateDatasFolderSize==true)  {File::datasFolderSize(true);}
		////	Objets non supprimés : affiche les labels des objets concernés (10 objets maxi)
		if(!empty($notDeletedObjects)){
			if(count($notDeletedObjects)>10)  {$notDeletedObjects=array_slice($notDeletedObjects,0,10);  $notDeletedObjects[]="..."; }
			Ctrl::notify(Txt::trad("notDeletedElements")." :<br><br>".implode(", ",$notDeletedObjects));
		}
		////	Redirection
		self::redir($redirUrl);
	}

	/*******************************************************************************************
	 * VUE : MENU POUR DÉPLACER DES ÉLÉMENTS DANS UN AUTRE DOSSIER
	 *******************************************************************************************/
	public static function actionFolderMove()
	{
		//Validation du formulaire
		if(Req::isParam("formValidate") && Req::isParam("newFolderId")){
			foreach(self::getObjectsTypeId() as $tmpObj)  {$tmpObj->folderMove(Req::param("newFolderId"));}
			static::lightboxClose();
		}
		//Affiche le menu de déplacement de dossier
		self::folderTreeMenu("move");
	}

	/*******************************************************************************************
	 * VUE : MENU D'ARBORESCENCE DE DOSSIERS ($context: "nav" / "move")
	 *******************************************************************************************/
	public static function folderTreeMenu($context="nav")
	{
		//Affiche l'arborescence (si ya pas que le dossier racine)
		if(count(Ctrl::$curRootFolder->folderTree())>1){
			$vDatas["context"]=$context;
			$vueFolderTree=Req::commonPath."VueObjFolderTree.php";
			if($context=="nav")	{return Ctrl::getVue($vueFolderTree,$vDatas);}//"nav"	: renvoie le menu de navigation de l'arborescence de dossiers
			else				{static::displayPage($vueFolderTree,$vDatas);}//"move"	: affiche uniquement le menu de selection d'un dossier pour y déplacer un element
		}
	}

	/*******************************************************************************************
	 * VUE : MENU DU CHEMIN DU DOSSIER COURANT
	 *******************************************************************************************/
	public static function folderPathMenu($addElemLabel=null, $addElemUrl=null)
	{
		//Affiche le chemin d'un dossier  ET/OU  L'option d'ajout d'élement
		if(Ctrl::$curContainer->isRootFolder()==false || !empty($addElemLabel)){
			$vDatas["addElemLabel"]=$addElemLabel;
			$vDatas["addElemUrl"]=$addElemUrl;
			return Ctrl::getVue(Req::commonPath."VueObjFolderPath.php", $vDatas);
		}
	}

	/*******************************************************************************************
	 * VUE : LISTE DES DOSSIERS DU DOSSIER COURANT
	 *******************************************************************************************/
	public static function vueFolders()
	{
		if(self::$vueFolders===null)
		{
			//Récupère le dossier courant et les dossiers qu'il contient
			$curFolder=Ctrl::$curContainer;
			$vDatas["foldersList"]=Db::getObjTab($curFolder::objectType, "SELECT * FROM ".$curFolder::dbTable." WHERE ".$curFolder::sqlDisplay($curFolder)." ".$curFolder::sqlSort());
			//Aucun dossier / Liste des dossiers
			if(empty($vDatas["foldersList"]))  {self::$vueFolders="";}
			else{
				$vDatas["objContainerClass"]=$curFolder::moduleName=="contact" ? "objPerson" : null;
				self::$vueFolders=Ctrl::getVue(Req::commonPath."VueObjFolders.php",$vDatas);
			}
		}
		return self::$vueFolders;
	}

	/*******************************************************************************************
	 * VUE : EDITION D'UN DOSSIER
	 *******************************************************************************************/
	public static function actionEditFolder()
	{
		////	Charge le dossier et Controle d'accès: dossier existant / nouveau dossier
		$curObj=Ctrl::getObjTarget();
		$curObj->editControl();
		////	Valide le formulaire
		if(Req::isParam("formValidate"))
		{
			//Enregistre et recharge l'objet
			$curObj=$curObj->createUpdate("name=".Db::param("name").", description=".Db::param("description").", icon=".Db::param("icon"));
			//Etend les droits aux sous dossiers?
			if(Req::isParam("extendToSubfolders")){
				foreach($curObj->folderTree("all") as $tmpObj)	{$tmpObj->setAffectations();}
			}
			//Notifie par mail & Ferme la page
			$curObj->sendMailNotif();
			static::lightboxClose();
		}
		////	Affiche la vue
		else
		{
			$vDatas["curObj"]=$curObj;
			static::displayPage(Req::commonPath."VueObjEditFolder.php",$vDatas);
		}
	}

	/**************************************************************************************************************
	* VUE : EDITION DES OBJETS DE TYPE "CATEGORY" : CATEGORIES D'EVT / THEMES DE SUJET / COLONNES KANBAN
	**************************************************************************************************************/
	public static function actionEditCategories()
	{
		////	Modèle des objets (ex: "MdlTaskStatus")  &&  Droit d'ajouter un objet ?
		$MdlObject="Mdl".ucfirst(Req::param("objectType"));
		if($MdlObject::addRight()==false)  {static::lightboxClose();}
		////	Valide le formulaire : edite une des categories
		if(Req::isParam("formValidate")){
			$curObj=Ctrl::getObjTarget();
			$curObj->editControl();
			$_idSpaces=(in_array("allSpaces",Req::param("spaceList")))  ?  null : Txt::tab2txt(Req::param("spaceList"));
			$curObj->createUpdate("title=".Db::param("title").", description=".Db::param("description").", color=".Db::param("color").", _idSpaces=".Db::format($_idSpaces));
			static::lightboxClose();
		}
		////	Liste des objets à afficher (+ nouvel objet)  &&  Liste des espaces de l'user courant  &&  Préfixe des traduction
		$vDatas["objectList"]=array_merge($MdlObject::getList(true), [new $MdlObject()]);
		$vDatas["spaceList"]=Ctrl::$curUser->getSpaces();
		$vDatas["tradModulePrefix"]=strtoupper($MdlObject::moduleName);
		////	Affiche la vue
		static::displayPage(Req::commonPath."VueObjEditCategories.php",$vDatas);
	}

	/*******************************************************************************************
	 * AJAX : CHANGE L'ORDRE DES CATEGORIES
	 *******************************************************************************************/
	public static function actionCategoryChangeOrder()
	{
		foreach(self::getObjectsTypeId() as $cpt=>$tmpObj){
			Db::query("UPDATE ".$tmpObj::dbTable." SET `rank`=".Db::format($cpt+1)." WHERE _id=".(int)$tmpObj->_id);
		}
		echo "true";
	}

	/*******************************************************************************************
	 * AJAX : CONTROLE SI UN AUTRE OBJET PORTE LE MÊME NOM (FOLDER/FILE) OU TITRE (CALENDAR)
	 *******************************************************************************************/
	public static function actionControlDuplicateName()
	{
		if(Req::isParam(["typeId","controledName"])){
			//Récupère l'objet courant +  Recherche sur le nom ou le titre  +  Sélectionne si besoin le conteur de l'objet (cf. dossier/fichier)
			$curObj=Ctrl::getObjTarget();
			$sqlMainSelectors=($curObj::objectType=="calendar")  ?  "`title`=".Db::param("controledName")  :  "`name`=".Db::param("controledName");
			if(Req::isParam("typeIdContainer"))  {$sqlMainSelectors.="AND _idContainer=".Ctrl::getObjTarget(Req::param("typeIdContainer"))->_id;}
			//Recherche les doublons dans les objets affectés à l'espace courant
			$nbDuplicate=Db::getVal("SELECT count(*) FROM ".$curObj::dbTable." WHERE  _id!=".$curObj->_id." AND ".$sqlMainSelectors." AND _id IN  (select _idObject as _id from ap_objectTarget where objectType='".$curObj::objectType."' and _idSpace=".Ctrl::$curSpace->_id.")");
			if($nbDuplicate>0)  {echo "duplicate";}
		}
	}

	/*******************************************************************************************
	 * AJAX : CONTROLE D'ACCÈS AVANT SUPPRESSION D'UN DOSSIER
	 *******************************************************************************************/
	public static function actionFolderDeleteControl()
	{
		//// Init les notifications
		$result=[];
		//// Controle si tous les sous-dossiers sont bien accessibles en écriture ("Certains sous-dossiers ne vous sont pas accessibles... confirmer?")
		$curFolder=Ctrl::getObjTarget();
		$folderTreeAll=$curFolder->folderTree("all");//Liste tous les dossiers (pas forcément en lecture)
		$folderTreeWrite=$curFolder->folderTree(2);//Liste les dossiers accessibles en écriture (pas forcément en accès total)
		if(count($folderTreeAll)!=count($folderTreeWrite))  {$result["confirmDeleteFolderAccess"]=Txt::trad("confirmDeleteFolderAccess");}
		//// Arborescence de plus de 100 dossiers : notif "merci de patienter quelques instants avant la fin du processus"
		if(count($folderTreeAll)>100 )  {$result["notifyBigFolderDelete"]=str_replace("--NB_FOLDERS--",count($folderTreeAll),Txt::trad("notifyBigFolderDelete"));}
		//// Retourne le résultat au format JSON
		echo json_encode($result);
	}

	/*******************************************************************************************
	 * AJAX : EDITION D'UN DOSSIER => CONTROL LE DROIT D'ACCÈS AU DOSSIER PARENT
	 *******************************************************************************************/
	public static function actionAccessRightParentFolder()
	{
		$objFolder=Ctrl::getObjTarget();						//Récupère le dossier parent
		$objectRight=explode("_",Req::param("objectRight"));	//Droit d'accès sélectionné pour le dossier édité (Ex: "1_U2_2" ou "1_spaceUsers_1.5")
		$_idSpace=(int)$objectRight[0];							//Espace du droit d'accès
		$target=(string)$objectRight[1];						//Target du droit d'accès
		if($objFolder::isFolder==true && $objFolder->isRootFolder()==false && count($objectRight)==3){
			$nbAffectations=Db::getVal("SELECT count(*) FROM ap_objectTarget WHERE objectType='".$objFolder::objectType."' AND _idObject=".$objFolder->_id." AND _idSpace=".$_idSpace." AND (target='spaceUsers' OR target='".$target."')");
			if(empty($nbAffectations)){																								//Erreur si la "target" n'a pas été affecté au dossier parent !
				$spaceLabel=Ctrl::getObj("space",$_idSpace)->getLabel();															//Label de l'espace du droit d'accès
				if(preg_match("/^G/",$target))		{$targetLabel=self::getObj("userGroup",str_ireplace("G","",$target))->title;}	//Label du Groupe
				elseif(preg_match("/^U/",$target))	{$targetLabel=self::getObj("user",str_ireplace("U","",$target))->getLabel();}	//Label de l'user spécifique
				else								{$targetLabel=Txt::trad("EDIT_allUsers");}										//"Tous les utilisateurs"
				$result["errorMessage"]=str_replace(["--SPACE_LABEL--","--TARGET_LABEL--","--FOLDER_NAME--"], [$spaceLabel,$targetLabel,$objFolder->name], Txt::trad("EDIT_parentFolderAccessError"));
				echo json_encode($result);
			}
		}
	}

	/*******************************************************************************************
	 * VUE : AFFICHE LES OPTIONS DE BASE POUR L'ENVOI D'EMAIL (cf. "Tool::sendMail()") 
	 *******************************************************************************************/
	public static function sendMailBasicOptions()
	{
		return Ctrl::getVue(Req::commonPath."VueSendMailOptions.php");
	}

	/*******************************************************************************************************************************************
	 * VUE : AFFICHE LES FICHIERS JOINTS DE L'OBJET (cf. "editDescription()")
	 *******************************************************************************************************************************************/
	public static function attachedFile($curObj=null)
	{
		$vDatas["curObj"]=$curObj;
		return self::getVue(Req::commonPath."VueObjAttachedFile.php",$vDatas);
	}

	/*******************************************************************************************
	 * ACTION : TELECHARGE UN FICHIER JOINT
	 *******************************************************************************************/
	public static function actionAttachedFileDownload()
	{
		$curFile=MdlObject::attachedFileInfos(Req::param("_id"));
		if(is_file($curFile["path"])  &&  ($curFile["containerObj"]->readRight() || md5($curFile["name"])==Req::param("nameMd5")))   {File::download($curFile["name"],$curFile["path"]);}
	}

	/*******************************************************************************************
	 * ACTION : AFFICHE UN FICHIER JOINT (IMAGE/PDF/ETC) DANS LE BROWSER
	 *******************************************************************************************/
	 public static function actionAttachedFileDisplay()
	{
		$curFile=MdlObject::attachedFileInfos(Req::param("_id"));
		if(is_file($curFile["path"])  &&  ($curFile["containerObj"]->readRight() || md5($curFile["name"])==Req::param("nameMd5")))   {File::display($curFile["path"]);}
	}

	/*******************************************************************************************
	 * FICHIER JOINT : URL D'AFFICHAGE DU FICHIER VIA "actionAttachedFileDisplay()"
	 *******************************************************************************************/
	public static function attachedFileDisplayUrl($fileId, $fileName)
	{
		//Ajoute "&amp;" pour Tinymce et ajoute l'extension en toute fin (cf. fancybox des images et controle du type de fichier)
		return "?ctrl=object&amp;action=AttachedFileDisplay&amp;_id=".$fileId."&amp;extension=.".File::extension($fileName);
	}

	/*******************************************************************************************
	 * AJAX : SUPPRIME UN FICHIER JOINT
	 *******************************************************************************************/
	public static function actionAttachedFileDelete()
	{
		$curFile=MdlObject::attachedFileInfos(Req::param("_id"));
		if(is_file($curFile["path"]) && $curFile["containerObj"]->editRight()){
			$deleteResult=$curFile["containerObj"]->attachedFileDelete($curFile);
			if($deleteResult==true)  {echo "true";}
		}
	}

	/*******************************************************************************************
	 * AJAX : VALIDE/INVALIDE UN LIKE
	 *******************************************************************************************/
	public static function actionUsersLikeValidate()
	{
		//Vérifs de base
		if(Ctrl::$curUser->isUser() && Req::isParam("typeId"))
		{
			//Init
			$curObj=self::getObjTarget();
			//Applique la nouvelle valeur / le changement de valeur
			$newValue=(Req::param("likeValue")=="like")  ?  1  :  0;
			$sqlValueUser="WHERE objectType='".$curObj::objectType."' AND _idObject=".$curObj->_id." AND _idUser=".Ctrl::$curUser->_id;
			$oldValue=Db::getVal("SELECT value FROM ap_objectLike ".$sqlValueUser);			//recup l'ancienne valeur
			if($oldValue!=null)  {Db::query("DELETE FROM ap_objectLike ".$sqlValueUser);}	//reinit la valeur?
			if($oldValue==null || $newValue!=$oldValue)  {Db::query("INSERT INTO ap_objectLike SET objectType='".$curObj::objectType."', _idObject=".$curObj->_id.", _idUser=".Ctrl::$curUser->_id.", value=".$newValue);}//Ajoute la nouvelle valeur si elle change
			//Nb et liste des personnes qui likes / dontlike
			$ajaxResult["nbLikes"]=count($curObj->getUsersLike("like"));
			$ajaxResult["nbDontlikes"]=count($curObj->getUsersLike("dontlike"));
			$ajaxResult["usersLikeList"]=$curObj->getUsersLikeTooltip("like");
			$ajaxResult["usersDontlikeList"]=$curObj->getUsersLikeTooltip("dontlike");
			echo json_encode($ajaxResult);
		}
	}

	/*******************************************************************************************
	 * ACTION : AFFICHE LES COMMENTAIRES D'UN OBJET
	 *******************************************************************************************/
	public static function actionComments()
	{
		////	Charge l'element
		$curObj=Ctrl::getObjTarget();
		$curObj->readControl();
		////	Ajoute / Modif / Supprime un commentaire
		if(Req::isParam(["formValidate","comment"]) && Req::param("actionComment")=="add")
			{Db::query("INSERT INTO ap_objectComment SET objectType='".$curObj::objectType."', _idObject=".$curObj->_id.", _idUser=".self::$curUser->_id.", dateCrea=".Db::dateNow().", `comment`=".Db::param("comment"));}
		elseif(Req::isParam("idComment") && MdlObject::userCommentEditRight(Req::param("idComment"))){
			$sqlSelectComment="_id=".Db::param("idComment")." AND objectType='".$curObj::objectType."' AND _idObject=".$curObj->_id;
			if(Req::param("actionComment")=="delete")	{Db::query("DELETE FROM ap_objectComment WHERE ".$sqlSelectComment);}
			elseif(Req::param("actionComment")=="modif")	{Db::query("UPDATE ap_objectComment SET `comment`=".Db::param("comment")." WHERE ".$sqlSelectComment);}
		}
		////	Affiche la vue
		$vDatas["curObj"]=$curObj;
		$vDatas["updateCircleNb"]=Req::isParam("actionComment");
		$vDatas["commentList"]=Db::getTab("SELECT * FROM ap_objectComment WHERE objectType='".$curObj::objectType."' AND _idObject=".$curObj->_id." ORDER BY dateCrea DESC");
		$vDatas["commentsTitle"]=count($vDatas["commentList"])." ".Txt::trad(count($vDatas["commentList"])>1?"AGORA_usersComments":"AGORA_usersComment");
		static::displayPage(Req::commonPath."VueObjComments.php",$vDatas);
	}
}