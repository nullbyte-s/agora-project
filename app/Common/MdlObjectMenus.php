<?php
/**
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/*
 * Menus des Objects
 */
trait MdlObjectMenus
{
	public static $pageNbObjects=50;	//Nb d'éléments affichés par page : 50 par défaut
	public static $displayMode=null;	//Type d'affichage en préference (ligne/block)

	/*******************************************************************************************
	 * BALISE OUVRANTE DU BLOCK DE L'OBJET : CONTIENT L'URL D'ÉDITION (pour le DblClick)  ET L'ID DU MENU CONTEXTUEL (Click droit)
	 *******************************************************************************************/
	public function divContainer($specificClass=null, $specificAttributes=null)
	{
		$isSelectableclass=(static::isSelectable==true)  ?  "isSelectable"  :  null;
		$urlEdit=($this->editRight())  ?  "data-urlEdit=\"".$this->getUrl("edit")."\""  :  null;
		return  "<div class=\"objContainer ".$isSelectableclass." ".$specificClass."\" ".$specificAttributes." id=\"".$this->menuId("objBlock")."\" for=\"".$this->menuId("objMenu")."\" ".$urlEdit.">";
	}

	/*******************************************************************************************
	 * IDENTIFIANT DU MENU CONTEXTUEL : "objBlock"/"objBlock"/"objAttachment" (Cf. "initMenuContext()"!)
	 *******************************************************************************************/
	public function menuId($prefix)
	{
		if(empty($this->contextMenuId))  {$this->contextMenuId=Txt::uniqId();}//Un menu par instance de l'objet (Tester avec les evts récurrents ou les menus d'agendas)
		return $prefix."_".$this->contextMenuId;
	}

	/*******************************************************************************************
	 * VUE : MENU CONTEXTUEL (édition, droit d'accès, etc)
	 * $options["iconBurger"] (text)		: icone "burger" du launcher ("small", "big" ou "float" par défaut)
	 * $options["deleteLabel"] (Bool)		: label spécifique de suppression
	 * $options["specificOptions"] (Array)	: boutons à ajouter au menu : chaque bouton est un tableau avec par les propriétés suivante  ["actionJs"=>"?ctrl=file&action=monAction", "iconSrc"=>"app/img/plus.png", "label"=>"mon option", "tooltip"=>"mon tooltip"]
	 * $options["specificLabels"] (Array)	: Texte à afficher (Exple avec les "affectedCalendarsLabel()" pour afficher les agendas ou se trouve un evenement)
	 *******************************************************************************************/
	public function contextMenu($options=null)
	{
		////	PAS DE MENU CONTEXT POUR LES GUESTS
		if(Ctrl::$curUser->isUser()==false)  {return false;}

		////	INIT  &  DIVERSES OPTIONS
		$vDatas["curObj"]=$this;
		$vDatas["iconBurger"]=(!empty($options["iconBurger"]))  ?  $options["iconBurger"]  :  "float";//Icone "burger" du launcher : "small" inline / "big" inline / "float" en position absolute (par défaut)
		$vDatas["specificOptions"]=(!empty($options["specificOptions"]))  ?  $options["specificOptions"]  :  array();
		$vDatas["specificLabels"]=(!empty($options["specificLabels"]))  ?  $options["specificLabels"]  :  array();

		////	OBJET USER
		if(static::objectType=="user")
		{
			////	MODIFIER ELEMENT  &  MODIF MESSENGER
			if($this->editRight()){
				$vDatas["editLabel"]=Txt::trad("USER_profilEdit");
				$vDatas["editMessengerObjUrl"]="?ctrl=user&action=UserEditMessenger&targetObjId=".$this->_targetObjId;
			}
			////	SUPPRESSION DE L'ESPACE COURANT
			if($this->deleteFromCurSpaceRight()){
				$deleteFromCurSpaceUrl="?ctrl=user&action=deleteFromCurSpace&targetObjects[".static::objectType."]=".$this->_id;
				$vDatas["deleteFromCurSpaceConfirm"]="confirmDelete('".$deleteFromCurSpaceUrl."', '".Txt::trad("USER_deleteFromCurSpaceConfirm",true)."')";
			}
			////	SUPPRESSION DEFINITIVE
			if($this->deleteRight()){
				$vDatas["confirmDeleteJs"]="confirmDelete('".$this->getUrl("delete")."','".Txt::trad("confirmDeleteDbl",true)."')";
				$vDatas["deleteLabel"]=Txt::trad("USER_deleteDefinitely");
			}
			////	ESPACE DE L'UTILISATEUR
			if(Ctrl::$curUser->isAdminGeneral()){
				$vDatas["userSpaceList"]=Txt::trad("USER_spaceList")." : ";
				if(count($this->getSpaces())==0)	{$vDatas["userSpaceList"].=Txt::trad("USER_spaceNoAffectation");}
				else								{ foreach($this->getSpaces() as $tmpSpace)  {$vDatas["userSpaceList"].="<br>".$tmpSpace->name;} }
			}
			////	AUTEUR/DATE DE CREATION
			$vDatas["autorDateCrea"]="<a href=\"javascript:lightboxOpen('".Ctrl::getObj("user",$this->_idUser)->getUrl("vue")."');\">".$this->autorLabel()."</a> - ".$this->dateLabel(true,"full");
		}
		////	OBJET LAMBDA
		else
		{
			////	MODIFIER ELEMENT  &  LOGS/HISTORIQUE  &  CHANGER DE DOSSIER (SI Y EN A..)
			if($this->editRight())
			{
				$vDatas["editLabel"]=($this->hasAccessRight())  ?  Txt::trad("modifyAndAccesRight")  :  Txt::trad("modify");
				$vDatas["logUrl"]="?ctrl=object&action=logs&targetObjId=".$this->_targetObjId;
				if($this::isInArbo() && !empty(Ctrl::$curContainer)){
					$curRootFolder=Ctrl::getObj(get_class(Ctrl::$curContainer),1);//Récupère le dossier racine et compte le nb de sous-dossiers
					if(count($curRootFolder->folderTree())>1)  {$vDatas["moveObjectUrl"]="?ctrl=object&action=FolderMove&targetObjId=".$this->containerObj()->_targetObjId."&targetObjects[".static::objectType."]=".$this->_id;}
				}
			}
			////	SUPPRIMER
			if($this->deleteRight())
			{
				$labelConfirmDeleteDbl=$ajaxControlUrl=null;
				//Suppression d'espace ou de conteneur : Double confirmation
				if(static::objectType=="space")	{$labelConfirmDeleteDbl=",'".Txt::trad("SPACE_confirmDeleteDbl",true)."'";}	
				elseif(static::isContainer())	{$labelConfirmDeleteDbl=",'".Txt::trad("confirmDeleteDbl",true)."'";}
				//Suppression de dossier : controle Ajax (droit  d'accès and co)
				if(static::isFolder==true)  {$ajaxControlUrl=",'?ctrl=object&action=folderDeleteControl&targetObjId=".$this->_targetObjId."'";}
				//Ajoute l'option
				$vDatas["confirmDeleteJs"]="confirmDelete('".$this->getUrl("delete")."' ".$labelConfirmDeleteDbl.$ajaxControlUrl.")";
				$vDatas["deleteLabel"]=(!empty($options["deleteLabel"]))  ?  $options["deleteLabel"]  :  Txt::trad("delete");
			}
			////	LIBELLES DES DROITS D'ACCESS : AFFECTATION AUX ESPACES, USERS, ETC  (droit d'accès de l'objet OU du conteneur d'un objet)
			if($this->hasAccessRight() || $this->accessRightFromContainer())
			{
				//Récupère les affectations (de l'objet OU de son conteneur)  &&  Ajoute le label des affectations pour chaque type de droit d'accès (lecture/ecriture limité/ecriture)
				$objAffects=($this->hasAccessRight())  ?  $this->getAffectations()  :  $this->containerObj()->getAffectations();
				$vDatas["affectLabels"]=$vDatas["affectTooltips"]=["1"=>null,"1.5"=>null,"2"=>null];
				foreach($objAffects as $tmpAffect)  {$vDatas["affectLabels"][$tmpAffect["accessRight"]].=$tmpAffect["label"]."<br>";}
				//Affiche si l'objet est personnel ("isPersoAccess")
				$firstAffect=reset($objAffects);//Récup la première affectation du tableau
				$vDatas["isPersoAccess"]=(count($objAffects)==1 && $firstAffect["targetType"]=="user" && $firstAffect["target_id"]==Ctrl::$curUser->_id);
				//Tooltip spécifique
				if(static::isContainer())  					{$tooltipDetail=$this->tradObject("autorPrivilege")."<hr>";}						//"Seul l'auteur ou l'admin peuvent modifier/supprimer le -dossier-"
				elseif($this->accessRightFromContainer())	{$tooltipDetail=$this->containerObj()->tradObject("accessRightsInherited")."<hr>";}	//"Droits d'accès hérité du -dossier- parent"
				else										{$tooltipDetail=null;}
				//Tooltip : description de chaque droit d'accès
				if(!empty($vDatas["affectLabels"]["1"]))	{$vDatas["affectTooltips"]["1"]=$tooltipDetail.Txt::trad("readInfos");}
				if(!empty($vDatas["affectLabels"]["1.5"]))	{$vDatas["affectTooltips"]["1.5"]=$tooltipDetail.$this->tradObject("readLimitInfos");}
				if(!empty($vDatas["affectLabels"]["2"]))	{$vDatas["affectTooltips"]["2"]=(static::isContainer())  ?  $tooltipDetail.$this->tradObject("writeInfosContainer")  :  $tooltipDetail.Txt::trad("writeInfos");}
			}
			////	AUTEUR/DATE DE CREATION/MODIF
			//Init les labels  &&  vérif si c'est un nouvel objet (créé dans les 24 heures ou depuis la précédente connexion)
			$vDatas["autorDateCrea"]=$vDatas["autorDateModif"]=null;
			$vDatas["isNewObject"]=(strtotime($this->dateCrea) > (time()-86400) || strtotime($this->dateCrea) > Ctrl::$curUser->previousConnection);
			//Auteur de l'objet (Guest?)
			if($this->_idUser)		{$vDatas["autorDateCrea"]="<a href=\"javascript:lightboxOpen('".Ctrl::getObj("user",$this->_idUser)->getUrl("vue")."');\">".$this->autorLabel()."</a>";}
			elseif($this->guest)	{$vDatas["autorDateCrea"]=$this->autorLabel();}
			//Date de création de l'objet  &&  Précise si c'est un nouvel objet  &&  Précise l'auteur/date de modif
			if($this->dateCrea)					{$vDatas["autorDateCrea"].=" - ".$this->dateLabel(true,"full");}
			if($vDatas["isNewObject"]==true)	{$vDatas["autorDateCrea"].="<br><abbr title=\"".Txt::trad("objNewInfos")."\">".Txt::trad("objNew")."</abbr>&nbsp; <img src='app/img/menuNewSmall.png'>";}
			if(!empty($this->_idUserModif))  	{$vDatas["autorDateModif"]="<a href=\"javascript:lightboxOpen('".Ctrl::getObj("user",$this->_idUserModif)->getUrl("vue")."');\">".$this->autorLabel(false)."</a> - ".$this->dateLabel(false,"full");}
			////	USERS LIKES
			$vDatas["showMiscMenuClass"]=null;
			if($this->hasUsersLike())
			{
				$likeOptions=(Ctrl::$agora->usersLike=="likeOrNot")  ?  ["like","dontlike"]  :  ["like"];
				foreach($likeOptions as $likeOption){
					$likeMenuId="likeMenu_".$this->_targetObjId."_".$likeOption;//ID du menu. Exple: "likeMenu_news-55_dontlike". Cf. "usersLikeValidate()" dans le "common.js"
					$likeMenuNb=count($this->getUsersLike($likeOption));
					if(!empty($likeMenuNb))  {$vDatas["showMiscMenuClass"]="showMiscMenu";}
					$vDatas["likeMenu"][$likeOption]=["menuId"=>$likeMenuId, "likeDontLikeNb"=>$likeMenuNb];
				}
			}
			////	COMMENTAIRES
			if($this->hasUsersComment())
			{
				$commentNb=count($this->getUsersComment());
				$commentTooltip=$commentNb." ".Txt::trad($commentNb>1?"AGORA_usersComments":"AGORA_usersComment")." :<br>".Txt::trad("commentAdd");
				$commentsUrl="?ctrl=object&action=Comments&targetObjId=".$this->_targetObjId;
				if(!empty($commentNb))  {$vDatas["showMiscMenuClass"]="showMiscMenu";}
				$vDatas["commentMenu"]=["menuId"=>"commentMenu_".$this->_targetObjId, "commentNb"=>$commentNb, "commentTooltip"=>$commentTooltip, "commentsUrl"=>$commentsUrl];
			}
		}
		////	Affichage
		return Ctrl::getVue(Req::commonPath."VueObjMenuContext.php",$vDatas);
	}

	/*******************************************************************************************
	 * VUE DES OBJETS : AFFICHE LE MENU CONTEXTUEL  OU LE BOUTON D'EDITION
	 *******************************************************************************************/
	public function menuContextEdit()
	{
		if(Req::isMobile())			{return $this->contextMenu(["iconBurger"=>"big"]);}
		elseif($this->editRight())  {return "<img src='app/img/edit.png' onclick=\"lightboxOpen('".$this->getUrl("edit")."')\" class='sLink lightboxMenuEdit' title=\"".Txt::trad("modify")."\">";}
	}

	/*******************************************************************************************
	 * VUE DES OBJETS & RESPONSIVE : TITRE "NOUVEL OBJET" ("nouveau fichier", "nouveau dossier", etc)
	 *******************************************************************************************/
	public function editRespTitle($keyTrad)
	{
		if(Req::isMobile() && $this->isNew())  {echo "<div class='lightboxTitle'>".Txt::trad($keyTrad)."</div>";}
	}

	/*******************************************************************************************
	 * VUE : MENU D'ÉDITION (droits d'accès, fichiers joints, etc)
	 *******************************************************************************************/
	public function menuEdit()
	{
		////	Menu des droits d'accès
		if($this->hasAccessRight())
		{
			////	Init & Label
			$vDatas["accessRightMenu"]=true;
			$vDatas["accessRightMenuLabel"]=(static::isContainer())  ?  "<span title=\"".$this->tradObject("autorPrivilege")."<hr>".$this->tradObject("readLimitInfos")."\">".Txt::trad("EDIT_accessRightContent")." <img src='app/img/info.png'></span>"  :  Txt::trad("EDIT_accessRight");
			////	Droits d'accès pour chaque espace ("targets")
			$vDatas["spacesAccessRight"]=[];
			foreach(Ctrl::$curUser->getSpaces() as $tmpSpace)
			{
				//Verif si le module de l'objet est bien activé sur l'espace
				if(array_key_exists(static::moduleName,$tmpSpace->moduleList()))
				{
					//Init
					$tmpSpace->targetLines=[];
					////	Tous les utilisateurs de l'espace  (..."et les invités" : si l'espace est public et que l'objet n'est pas un agenda perso)
					if(!empty($tmpSpace->public) && $this->type!="user")	{$allUsersLabel=Txt::trad("EDIT_allUsersAndGuests");	$allUsersLabelInfo=Txt::trad("EDIT_allUsersAndGuestsInfo");}
					else													{$allUsersLabel=Txt::trad("EDIT_allUsers");				$allUsersLabelInfo=Txt::trad("EDIT_allUsersInfo");}
					$tmpSpace->targetLines[]=["targetId"=>$tmpSpace->_id."_spaceUsers", "label"=>$allUsersLabel, "icon"=>"user/icon.png", "tooltip"=>str_replace("--SPACENAME--",$tmpSpace->name,$allUsersLabelInfo)];
					////	Groupe d'utilisateurs de l'espace
					foreach(MdlUserGroup::getGroups($tmpSpace) as $tmpGroup){
						$tmpSpace->targetLines[]=["targetId"=>$tmpSpace->_id."_G".$tmpGroup->_id, "label"=>$tmpGroup->title, "icon"=>"user/userGroup.png", "tooltip"=>Txt::reduce($tmpGroup->usersLabel)];
					}
					////	Chaque user de l'espace
					foreach($tmpSpace->getUsers() as $tmpUser){
						if($tmpSpace->accessRightUser($tmpUser)==2)	{$tmpUserFullAccess=true;	$tmpUserTooltip=Txt::trad("EDIT_adminSpace");}//Admin d'espace
						else										{$tmpUserFullAccess=false;	$tmpUserTooltip=null;}//User lambda
						$tmpSpace->targetLines[]=["targetId"=>$tmpSpace->_id."_U".$tmpUser->_id, "label"=>$tmpUser->getLabel(), "icon"=>"user/user.png", "tooltip"=>$tmpUserTooltip, "onlyFullAccess"=>$tmpUserFullAccess, "isUser"=>true];
					}
					////	Ajoute l'espace
					$vDatas["spacesAccessRight"][]=$tmpSpace;
				}
			}
			////	Prépare les targets de chaque espace
			$objAffects=$this->getAffectations();
			foreach($vDatas["spacesAccessRight"] as $tmpSpaceKey=>$tmpSpace)
			{
				foreach($tmpSpace->targetLines as $targetKey=>$targetLine)
				{
					//Init les propriétés des checkboxes (pas de "class"!). Utilise des "id" pour une sélection rapide des checkboxes par jQuery
					$targetId=$targetLine["targetId"];//exple : "1_spaceUsers" ou "2_G4
					foreach(["1","1.5","2"] as $tmpRight)
						{$targetLine["boxProp"][$tmpRight]="value=\"".$targetId."_".$tmpRight."\"  id=\"objectRightBox_".$targetId."_".str_replace('.','',$tmpRight)."\"";}//Utiliser "_15" au lieu de "_1.5" à cause du selector jQuery
					//Check une des boxes ?
					if(isset($objAffects[$targetId])){
						$tmpRight=(string)$objAffects[$targetId]["accessRight"];//Typer en 'string', pas 'float'
						$targetLine["boxProp"][$tmpRight].=" checked";
						$targetLine["isChecked"]=true;
					}
					//Disable des boxes ?
					if(!empty($targetLine["onlyFullAccess"]))	{$targetLine["boxProp"]["1"].=" disabled";  $targetLine["boxProp"]["1.5"].=" disabled";}
					if(!empty($targetLine["onlyReadAccess"]))	{$targetLine["boxProp"]["2"].=" disabled";  $targetLine["boxProp"]["1.5"].=" disabled";}
					//Met à jour les propriétés de la target ($targetKey est la concaténation des champs "_idSpace" et "target")
					$vDatas["spacesAccessRight"][$tmpSpaceKey]->targetLines[$targetKey]=$targetLine;
				}
			}
		}
		////	OPTION "FICHIERS JOINTS"
		if(static::hasAttachedFiles==true){
			$vDatas["attachedFiles"]=true;
			$vDatas["attachedFilesList"]=$this->getAttachedFileList();
		}
		////	OPTIONS NOTIFICATION PAR MAIL
		if(static::hasNotifMail==true && function_exists("mail")){
			$vDatas["moreOptions"]=$vDatas["notifMail"]=true;
			$vDatas["notifMailUsers"]=Ctrl::$curUser->usersVisibles(true);
			$vDatas["curSpaceUsersIds"]=Ctrl::$curSpace->getUsers("idsTab");
			$vDatas["curSpaceUserGroups"]=MdlUserGroup::getGroups(Ctrl::$curSpace);
		}
		////	OPTION "SHORTCUT"
		if(static::hasShortcut==true){
			$vDatas["moreOptions"]=$vDatas["shortcut"]=true;
			$vDatas["shortcutChecked"]=(!empty($this->shortcut)) ? "checked" : null;
		}
		////	AFFICHE LA VUE
		$vDatas["curObj"]=$this;
		$vDatas["writeReadLimitInfos"]=$this->tradObject("readLimitInfos");
		$vDatas["extendToSubfolders"]=(static::isFolder==true && $this->isNew()==false && Db::getVal("SELECT count(*) FROM ".static::dbTable." WHERE _idContainer=".$this->_id)>0);//dossier avec des sous-dossiers
		return Ctrl::getVue(Req::commonPath."VueObjMenuEdit.php",$vDatas);
	}

	/*******************************************************************************************
	 * STATIC : CLÉ DE PRÉFÉRENCE EN BDD ($prefDbKey)
	 *******************************************************************************************/
	public static function prefDbKey($containerObj)
	{
		if(is_object($containerObj))											{return $containerObj->_targetObjId;}			//"_targetObjId" de l'objet en parametre
		elseif(!empty(Ctrl::$curContainer) && is_object(Ctrl::$curContainer))	{return Ctrl::$curContainer->_targetObjId;}		//"_targetObjId" du conteneur/dossier courant
		else																	{return static::moduleName;}					//"moduleName" courant
	}

	/*******************************************************************************************
	 * VUE : MENU DE SÉLECTION D'OBJETS (menu contextuel flottant)
	 *******************************************************************************************/
	public static function menuSelectObjects()
	{
		if(Req::isMobile()==false){
			$vDatas["curFolderIsWritable"]=(is_object(Ctrl::$curContainer) && Ctrl::$curContainer->editContentRight());
			$vDatas["rootFolderHasTree"]=($vDatas["curFolderIsWritable"]==true && count(Ctrl::getObj(get_class(Ctrl::$curContainer),1)->folderTree())>1);
			return Ctrl::getVue(Req::commonPath."VueObjMenuSelection.php",$vDatas);
		}
	}

	/*******************************************************************************************
	 * STATIC : TRI D'OBJETS : PREFERENCE EN BDD / PARAMÈTRE PASSÉ EN GET (exple: "firstName@@asc")
	 *******************************************************************************************/
	private static function getSort($containerObj=null)
	{
		//Récupère la préférence en Bdd ou params GET/POST
		$objectsSort=Ctrl::prefUser("sort_".static::prefDbKey($containerObj), "sort");
		//Tri par défaut si aucune préférence n'est précisé ou le tri sélectionné n'est pas dispo pour l'objet courant 
		if(empty($objectsSort) || !in_array($objectsSort,static::$sortFields))    {$objectsSort=static::$sortFields[0];}
		//renvoie le tri
		return $objectsSort;
	}

	/*******************************************************************************************
	 * STATIC SQL : TRI SQL DES OBJETS (avec premier tri si besoin : news, subject, etc. Exple: "ORDER BY firstName asc")
	 *******************************************************************************************/
	public static function sqlSort($firstSort=null)
	{
		//Init
		$firstSort=(!empty($firstSort))  ?  $firstSort.", "  :  null;							//Pré-tri ? Exple pour les News: "une desc"
		$sortTab=Txt::txt2tab(self::getSort(Ctrl::$curContainer));								//Récupère la préférence de tri du conteneur courant (dossier/sujet/etc). Exple: ["name","asc"]
		$fieldSort=($sortTab[0]=="extension") ? "SUBSTRING_INDEX(name,'.',-1)" : $sortTab[0];	//Tri par "extension" de fichier ?
		//Renvoie le tri Sql
		return "ORDER BY ".$firstSort." ".$fieldSort." ".$sortTab[1];
	}

	/*******************************************************************************************
	 * VUE : MENU DE TRI D'UN TYPE D'OBJET
	 *******************************************************************************************/
	public static function menuSort($containerObj=null, $addUrlParams=null)
	{
		$vDatas["sortFields"]=static::$sortFields;
		$vDatas["curSort"]=self::getSort($containerObj);
		$curSortTab=Txt::txt2tab($vDatas["curSort"]);
		$vDatas["curSortField"]=$curSortTab[0];
		$vDatas["curSortAscDesc"]=$curSortTab[1];
		$vDatas["addUrlParams"]=$addUrlParams;
		return Ctrl::getVue(Req::commonPath."VueObjMenuSort.php",$vDatas);
	}

	/*******************************************************************************************
	 * STATIC : RÉCUPÈRE LE TYPE D'AFFICHAGE DES OBJETS DE LA PAGE
	 *******************************************************************************************/
	public static function getDisplayMode($containerObj=null)
	{
		if(static::$displayMode===null)
		{
			//Affichage "block" sur mobile  OU  Récupère la préférence d'affichage
			if(static::mobileOnlyDisplayBlock())	{static::$displayMode="block";}
			else									{static::$displayMode=Ctrl::prefUser("displayMode_".static::prefDbKey($containerObj),"displayMode");}
			//Sinon on prend l'affichage par défaut : du paramétrage général ("folderDisplayMode") OU du premier $displayModes du module
			if(empty(static::$displayMode)){
				if(!empty(Ctrl::$agora->folderDisplayMode) && in_array(Ctrl::$agora->folderDisplayMode,static::$displayModes))	{static::$displayMode=Ctrl::$agora->folderDisplayMode;}
				else																											{static::$displayMode=static::$displayModes[0];}
			}
		}
		return static::$displayMode;
	}

	/*******************************************************************************************
	 * STATIC : SUR MOBILE, ON AFFICHE TOUJOURS EN MODE "BLOCK" (SI DISPO)
	 *******************************************************************************************/
	public static function mobileOnlyDisplayBlock()
	{
		return (Req::isMobile() && in_array("block",static::$displayModes));
	}

	/*******************************************************************************************
	 * VUE : MENU D'AFFICHAGE DES OBJETS DANS UNE PAGE : BLOCKS / LIGNES (cf. $displayModes)
	 *******************************************************************************************/
	public static function menuDisplayMode($containerObj=null)
	{
		if(static::mobileOnlyDisplayBlock()==false)
		{
			$vDatas["displayModes"]=static::$displayModes;
			$vDatas["curDisplayMode"]=static::getDisplayMode($containerObj);
			$vDatas["displayModeUrl"]=Tool::getParamsUrl("displayMode")."&displayMode=";
			return Ctrl::getVue(Req::commonPath."VueObjMenuDisplayMode.php",$vDatas);
		}
	}

	/*******************************************************************************************
	 * STATIC SQL : FILTRAGE DE PAGINATION
	 *******************************************************************************************/
	public static function sqlPagination()
	{
		$offset=(Req::isParam("pageNb"))  ?  ((Req::getParam("pageNb")-1)*static::$pageNbObjects)  :  "0";
		return "LIMIT ".static::$pageNbObjects." OFFSET ".$offset;
	}

	/*******************************************************************************************
	 * VUE : MENU DE FILTRE ALPHABÉTIQUE
	 *******************************************************************************************/
	public static function menuPagination($displayedObjNb, $getParamKey=null)
	{
		$pageNbTotal=ceil($displayedObjNb/static::$pageNbObjects);
		if($pageNbTotal>1)
		{
			//Nb de page et numéro de page courant
			$vDatas["pageNbTotal"]=$pageNbTotal;
			$vDatas["pageNb"]=$pageNb=Req::isParam("pageNb") ? Req::getParam("pageNb") : 1;
			//Url de redirection de base
			$vDatas["hrefBase"]="?ctrl=".Req::$curCtrl;
			if(!empty($getParamKey) && Req::isParam($getParamKey))  {$vDatas["hrefBase"].="&".$getParamKey."=".Req::getParam($getParamKey);}
			$vDatas["hrefBase"].="&pageNb=";
			//Page Précédente / Suivante (desactive si on est déjà en première ou dernière page)
			$vDatas["prevAttr"]=($pageNb>1)  ?  "href=\"".$vDatas["hrefBase"].((int)$pageNb-1)."\""  :  "class='vNavMenuDisabled'";
			$vDatas["nextAttr"]=($pageNb<$pageNbTotal)  ?  "href=\"".$vDatas["hrefBase"].((int)$pageNb+1)."\""  :  "class='vNavMenuDisabled'";
			//Récupère le menu
			return Ctrl::getVue(Req::commonPath."VueObjMenuPagination.php",$vDatas);
		}
	}

	/*******************************************************************************************
	 * MENU SPÉCIFIQUE D'AFFECTATION AUX ESPACES : THÈMES DE FORUM / CATEGORIES D'EVENEMENT
	 *******************************************************************************************/
	public function menuSpaceAffectation()
	{
		$vDatas["curObj"]=$this;
		////	Liste des espaces
		$vDatas["spaceList"]=Ctrl::$curUser->getSpaces();
		//Pour chaque espace : check espace affecté (déjà affecté à l'objet OU nouvel objet + espace courant)
		foreach($vDatas["spaceList"] as $tmpSpace){
			$tmpSpace->checked=(in_array($tmpSpace->_id,$this->spaceIds) || ($this->isNew() && $tmpSpace->isCurSpace()))  ?  "checked"  :  null;
		}
		////	pseudo Espace "Tous les espaces"
		if(Ctrl::$curUser->isAdminGeneral()){
			$spaceAllSpaces=new MdlSpace();
			$spaceAllSpaces->_id="all";
			$spaceAllSpaces->name=Txt::trad("visibleAllSpaces");
			$spaceAllSpaces->checked=(Ctrl::$curUser->isAdminGeneral() && $this->isNew()==false && empty($this->spaceIds))  ?  "checked"  :  null;//Check "tous les utilisateurs"?
			array_unshift($vDatas["spaceList"],$spaceAllSpaces);
		}
		//Affiche le menu
		$vDatas["displayMenu"]=(Ctrl::$curUser->isAdminGeneral() && count($vDatas["spaceList"])>1);
		return Ctrl::getVue(Req::commonPath."VueObjMenuSpaceAffectation.php",$vDatas);
	}
}