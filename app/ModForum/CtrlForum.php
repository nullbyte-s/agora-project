<?php
/**
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/*
 * CONTROLEUR DU MODULE "FORUM"
 */
class CtrlForum extends Ctrl
{
	const moduleName="forum";
	public static $moduleOptions=["adminAddSubject","adminAddTheme"];
	public static $MdlObjects=["MdlForumSubject","MdlForumMessage"];

	/*******************************************************************************************
	 * VUE : PAGE PRINCIPALE
	 *******************************************************************************************/
	public static function actionDefault()
	{
		//Init
		$vDatas["themeList"]=MdlForumTheme::getList();
		$vDatas["editThemeMenu"]=false;
		////	AFFICHE D'UN SUJET ET SES MESSAGES
		$curSubject=Ctrl::getObjTarget();
		if(is_object($curSubject) && $curSubject::objectType=="forumSubject")
		{
			$vDatas["displayForum"]="messages";
			$curSubject->curUserConsultLastMessageMaj();//Met à jour si besoin la consultation du dernier message
			$vDatas["curSubject"]=$curSubject;
			$vDatas["subjectMessages"]=$curSubject->getMessages();
		}
		////	AFFICHE LES THÈMES DE SUJET
		elseif(!empty($vDatas["themeList"]) && Req::isParam("_idThemeFilter")==false)
		{
			//Init
			$vDatas["displayForum"]="themes";
			if(MdlForumTheme::addRight())  {$vDatas["editThemeMenu"]=true;}
			$vDatas["themeList"][]=new MdlForumTheme(["_idThemeFilter"=>"noTheme"]);//Pseudo theme "sans theme"
			//Liste des themes
			foreach($vDatas["themeList"] as $tmpKey=>$tmpTheme)
			{
				//Nombre de sujets & Objet du dernier sujet
				$sqlThemeFilter=(!empty($tmpTheme->_id)) ? "_idTheme=".$tmpTheme->_id : "_idTheme is NULL";//Theme spécifique OU "sans theme"
				$tmpTheme->subjectList=Db::getObjTab("forumSubject", "SELECT * FROM ap_forumSubject WHERE ".MdlForumSubject::sqlDisplay()." AND ".$sqlThemeFilter." ORDER BY dateCrea desc");
				$tmpTheme->subjectsNb=count($tmpTheme->subjectList);
				if(empty($tmpTheme->subjectsNb) && $tmpTheme->noTheme==true)	{unset($vDatas["themeList"][$tmpKey]);}					//Enleve le theme "sans theme" si ya aucun sujet correspondant
				elseif($tmpTheme->subjectsNb>0)									{$tmpTheme->subjectLast=reset($tmpTheme->subjectList);}	//Pointe le premier sujet (le + récent)
				//Nombre de messages & Date du dernier message : tous sujets confondus!
				foreach($tmpTheme->subjectList as $tmpSubject)
				{
					$tmpSubject->getMessages(true);
					if($tmpSubject->messagesNb>0){
						$tmpTheme->messagesNb+=$tmpSubject->messagesNb;
						if(empty($tmpTheme->timeLastPost) || $tmpSubject->timeLastPost>$tmpTheme->timeLastPost)  {$tmpTheme->messageLast=$tmpSubject->messageLast;  $tmpTheme->timeLastPost=$tmpSubject->timeLastPost;}
					}
				}
			}
		}
		////	AFFICHE LES SUJETS (D'UN THEME SPECIFIQUE?)
		else
		{
			//Init
			$vDatas["displayForum"]="subjects";
			if(MdlForumTheme::addRight() && empty($vDatas["themeList"]))  {$vDatas["editThemeMenu"]=true;}
			//Liste les sujets
			if(Req::param("_idThemeFilter")=="noTheme")		{$sqlThemeFilter="AND (_idTheme IS NULL OR _idTheme=0)";}		//sujets "sans theme"
			elseif(Req::isParam("_idThemeFilter"))			{$sqlThemeFilter="AND _idTheme=".Db::param("_idThemeFilter");}	//sujets d'un theme spécifique
			else											{$sqlThemeFilter=null;}											//tout les sujets
			$sqlDisplayedSubjects="SELECT * FROM ".MdlForumSubject::dbTable." WHERE ".MdlForumSubject::sqlDisplay()." ".$sqlThemeFilter." ".MdlForumSubject::sqlSort();
			$vDatas["subjectsDisplayed"]=Db::getObjTab("forumSubject", $sqlDisplayedSubjects." ".MdlForumSubject::sqlPagination());
			$vDatas["subjectsTotalNb"]=count(Db::getTab($sqlDisplayedSubjects));
			//Pour chaque sujet : Nombre de messages & Dernier message
			foreach($vDatas["subjectsDisplayed"] as $tmpSubject)  {$tmpSubject->getMessages(true);}
		}
		////	THEME COURANT POUR LE MENU PATH
		if($vDatas["displayForum"]!="themes" && !empty($vDatas["themeList"])){
			if(is_object($curSubject) && !empty($curSubject->_idTheme))		{$vDatas["curTheme"]=self::getObj("forumTheme",$curSubject->_idTheme);}			//Theme du sujet courant
			elseif(Req::param("_idThemeFilter")=="noTheme")					{$vDatas["curTheme"]=new MdlForumTheme(["_idThemeFilter"=>"noTheme"]);}			//Pseudo theme "sans theme"
			elseif(Req::param("_idThemeFilter"))							{$vDatas["curTheme"]=self::getObj("forumTheme",Req::param("_idThemeFilter"));}	//Theme spécifique affiché
		}
		////	AFFICHAGE
		static::displayPage("VueIndex.php",$vDatas);
	}

	/*******************************************************************************************
	 * PLUGINS DU MODULE
	 *******************************************************************************************/
	public static function getPlugins($params)
	{
		$pluginsList=[];
		//Sujets
		foreach(MdlForumSubject::getPluginObjects($params) as $objSubject)
		{
			$objSubject->pluginIcon=self::moduleName."/icon.png";
			$objSubject->pluginLabel=(!empty($objSubject->title))  ?  $objSubject->title  :  Txt::reduce($objSubject->description);
			$objSubject->pluginTooltip=$objSubject->pluginLabel;
			$objSubject->pluginJsIcon="windowParent.redir('".$objSubject->getUrl()."');";//Redir vers le sujet
			$objSubject->pluginJsLabel=$objSubject->pluginJsIcon;
			$pluginsList[]=$objSubject;
		}
		//messages
		if($params["type"]!="shortcut")
		{
			foreach(MdlForumMessage::getPluginObjects($params) as $objMessage)
			{
				$objMessage->pluginIcon=self::moduleName."/icon.png";
				$objMessage->pluginLabel=(!empty($objMessage->title))  ?  $objMessage->title  :  Txt::reduce($objMessage->description);
				$objMessage->pluginTooltip=$objMessage->pluginLabel;
				$objMessage->pluginJsIcon="windowParent.redir('".$objMessage->getUrl()."');";//Affiche le message dans son sujet conteneur
				$objMessage->pluginJsLabel=$objMessage->pluginJsIcon;
				$pluginsList[]=$objMessage;
			}
		}
		return $pluginsList;
	}

	/*******************************************************************************************
	 * AJAX : ACTIVE/DÉSACTIVE LES NOTIFICATIONS DES MESSAGES PAR MAIL
	 *******************************************************************************************/
	public static function actionNotifyLastMessage()
	{
		$curSubject=Ctrl::getObjTarget();
		if($curSubject->readRight()){
			$usersNotifyLastMessage=Txt::txt2tab($curSubject->usersNotifyLastMessage);
			if($curSubject->curUserNotifyLastMessage())		{$usersNotifyLastMessage=array_diff($usersNotifyLastMessage,[Ctrl::$curUser->_id]);		echo "removeUser";}
			else											{$usersNotifyLastMessage[]=Ctrl::$curUser->_id;											echo "addUser";}
			Db::query("UPDATE ap_forumSubject SET usersNotifyLastMessage=".Db::formatTab2txt($usersNotifyLastMessage)." WHERE _id=".$curSubject->_id);
		}
	}

	/*******************************************************************************************
	 * VUE : EDITION D'UN SUJET
	 *******************************************************************************************/
	public static function actionForumSubjectEdit()
	{
		//Init
		$curObj=Ctrl::getObjTarget();
		if($curObj->isNew() && MdlForumSubject::addRight()==false)	{self::noAccessExit();}
		else														{$curObj->editControl();}
		////	Valide le formulaire
		if(Req::isParam("formValidate"))
		{
			//Enregistre & recharge l'objet
			$dateLastMessage=($curObj->isNew())  ?  ", dateLastMessage=".Db::dateNow()  :  null;//Init "dateLastMessage" pour un nouveau sujet (classement des sujets)
			$curObj=$curObj->createUpdate("title=".Db::param("title").", description=".Db::param("description").", _idTheme=".Db::param("_idTheme").", usersConsultLastMessage=".Db::formatTab2txt([Ctrl::$curUser->_id])." ".$dateLastMessage);
			//Notifie par mail & Ferme la page
			$curObj->sendMailNotif();
			static::lightboxClose();
		}
		////	Affiche la vue
		$vDatas["curObj"]=$curObj;
		if(Req::isParam("_idTheme"))  {$curObj->_idTheme=Req::param("_idTheme");}
		$vDatas["themeList"]=MdlForumTheme::getList();
		static::displayPage("VueForumSubjectEdit.php",$vDatas);
	}

	/*******************************************************************************************
	 * VUE : EDITION D'UN MESSAGE
	 *******************************************************************************************/
	public static function actionForumMessageEdit()
	{
		//Init
		$curObj=Ctrl::getObjTarget();
		$curObj->editControl();
		////	Valide le formulaire
		if(Req::isParam("formValidate")){
			//Enregistre & recharge l'objet
			$idMessageParent=Req::isParam("_idMessageParent")  ?  ", _idMessageParent=".Db::param("_idMessageParent")  :  null;//Rattaché à un message parent?
			$curObj=$curObj->createUpdate("title=".Db::param("title").", description=".Db::param("description").$idMessageParent);
			//MAJ "dateLastMessage" & "usersConsultLastMessage" du sujet conteneur
			Db::query("UPDATE ap_forumSubject SET dateLastMessage=".Db::dateNow().", usersConsultLastMessage=".Db::formatTab2txt([Ctrl::$curUser->_id])." WHERE _id=".$curObj->_idContainer);
			//Notif "auto" si c'est un nouveau message (cf. "Me notifier par mail")
			if($curObj->isNewlyCreated()==false)	{$notifUserIds=null;}
			else{
				$notifUserIds=array_diff(Txt::txt2tab($curObj->containerObj()->usersNotifyLastMessage), [Ctrl::$curUser->_id]);	//Users qui on demandé une notif (enlève l'auteur courant)
				$notifUserIds=array_intersect($notifUserIds, $curObj->containerObj()->affectedUserIds());						//Enlève les users qui ne sont plus affectés au sujet
			}
			//Notifie par mail aux users spécifiés & Ferme la page
			$curObj->sendMailNotif(null, null, $notifUserIds);
			static::lightboxClose();
		}
		////	Affiche la vue
		$vDatas["curObj"]=$curObj;
		$vDatas["messageParent"]=Req::isParam("_idMessageParent")  ?  self::getObj("forumMessage",Req::param("_idMessageParent"))  :  null;
		static::displayPage("VueForumMessageEdit.php",$vDatas);
	}
}