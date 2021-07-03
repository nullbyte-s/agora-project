<?php
/**
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/*
 * CONTROLEUR DU MODULE "CALENDAR"
 */
class CtrlCalendar extends Ctrl
{
	const moduleName="calendar";
	public static $moduleOptions=["createSpaceCalendar","adminAddRessourceCalendar","adminAddCategory"];
	public static $MdlObjects=["MdlCalendar","MdlCalendarEvent"];

	/********************************************************************************************
	 * VUE : PAGE PRINCIPALE
	 ********************************************************************************************/
	public static function actionDefault()
	{
		////	MENU DE PROPOSITION D'EVENEMENT A CONFIRMER
		$vDatas["eventProposition"]=self::eventProposition();
		////	AGENDAS VISIBLE POUR L'USER (OU TOUS LES AGENDAS : "affectationCalendars()" SI ADMIN GENERAL)  &&  AGENDAS AFFICHES  &&  EVT PROPOSÉS
		if(empty($_SESSION["displayAllCals"]) || Req::isParam("displayAllCals"))   {$_SESSION["displayAllCals"]=(Req::getParam("displayAllCals")==1 && Ctrl::$curUser->isAdminGeneral());}
		$vDatas["visibleCalendars"]=($_SESSION["displayAllCals"]==true)  ?  MdlCalendar::affectationCalendars()  :  MdlCalendar::visibleCalendars();
		$vDatas["displayedCalendars"]=MdlCalendar::displayedCalendars($vDatas["visibleCalendars"]);
		////	MODE D'AFFICHAGE (cf. MdlCalendar::$displayModes : month, week, workWeek, 4Days, day)
		$displayMode=self::prefUser("calendarDisplayMode","displayMode");
		if(empty($displayMode))  {$displayMode=(Req::isMobile()) ? "4Days" : "month";}//Affichage par défaut
		$vDatas["displayMode"]=$displayMode;
		////	TEMPS DE RÉFÉRENCE  &  JOURS FÉRIÉS
		$vDatas["curTime"]=$curTime=(Req::isParam("curTime")) ? Req::getParam("curTime") : time();
		$vDatas["celebrationDays"]=Trad::celebrationDays(date("Y",$curTime));

		////	AFFICHAGE : PREPARE LES TIMES/DATES
		//AFFICHAGE MOIS
		if($displayMode=="month"){
			$vDatas["timeBegin"]	=strtotime(date("Y-m",$curTime)."-01 00:00");
			$vDatas["timeEnd"]		=strtotime(date("Y-m",$curTime)."-".date("t",$curTime)." 23:59");
			$vDatas["urlTimePrev"]	=strtotime("-1 month",$curTime);
			$vDatas["urlTimeNext"]	=strtotime("+1 month",$curTime);
			$vDatas["timeDisplayedBegin"]=strtotime("-".(date("N",$vDatas["timeBegin"])-1)." days", $vDatas["timeBegin"]);//On commence l'affichage par un lundi : si le mois commence un mercredi, on affiche aussi le lundi/mardi du mois précédent
			$vDatas["timeDisplayedEnd"]=strtotime("+".(7-date("N",$vDatas["timeEnd"]))." days", $vDatas["timeEnd"]);	  //On termine l'affichage par un dimanche (Idem)
		}
		//AFFICHAGE SEMAINE / SEMAINE DE TRAVAIL
		elseif(preg_match("/week/i",$displayMode)){
			$weekTimeBegin=strtotime("-".(date("N",$curTime)-1)." days",$curTime);//lundi=0 => dimanche=6
			$weekTimeEnd=($displayMode=="week") ? strtotime("+6 days",$weekTimeBegin) : strtotime("+4 days",$weekTimeBegin);
			$vDatas["timeBegin"]	=strtotime(date("Y-m-d",$weekTimeBegin)." 00:00");
			$vDatas["timeEnd"]		=strtotime(date("Y-m-d",$weekTimeEnd)." 23:59");
			$vDatas["urlTimePrev"]	=strtotime("-1 week",$curTime);
			$vDatas["urlTimeNext"]	=strtotime("+1 week",$curTime);
		}
		//AFFICHAGE 4 PROCHAINS JOURS
		elseif($displayMode=="4Days"){
			$vDatas["timeBegin"]	=strtotime(date("Y-m-d",$curTime)." 00:00");
			$vDatas["timeEnd"]		=$vDatas["timeBegin"]+345599;//345599 = 4 jours moins une seconde
			$vDatas["urlTimePrev"]	=strtotime("-4 day",$curTime);
			$vDatas["urlTimeNext"]	=strtotime("+4 day",$curTime);
		}
		//AFFICHAGE JOUR
		elseif($displayMode=="day"){
			$vDatas["timeBegin"]	=strtotime(date("Y-m-d",$curTime)." 00:00");
			$vDatas["timeEnd"]		=strtotime(date("Y-m-d",$curTime)." 23:59");
			$vDatas["urlTimePrev"]	=strtotime("-1 day",$curTime);
			$vDatas["urlTimeNext"]	=strtotime("+1 day",$curTime);
		}

		////	FILTRE DES CATEGORIES DANS LES LIENS "urlTimePrev" et "urlTimeNext" ?
		$vDatas["urlCatFilter"]=(Req::isParam("_idCatFilter"))  ?  "&_idCatFilter=".Req::getParam("_idCatFilter")  :  null;
		////	LABEL DU MOIS AFFICHÉ : AFFICHAGE MOBILE OU  NORMAL
		$monthTime=$vDatas["timeBegin"];//Début de période comme référence
		if(Req::isMobile())	{$vDatas["labelMonth"]=(date("Y")==date("Y",$monthTime))  ?  Txt::formatime("%B",$monthTime)  :  Txt::formatime("%b %Y",$monthTime);}//"Janvier" OU "Janvier 2018" (si affiche une autre année)
		else				{$vDatas["labelMonth"]=(date("Ym",$vDatas["timeBegin"])==date("Ym",$vDatas["timeEnd"]))  ?  Txt::formatime("%B %Y",$monthTime)  :  Txt::formatime("%b %Y",$vDatas["timeBegin"])." / ".Txt::formatime("%b %Y",$vDatas["timeEnd"]);}//"Janvier 2020" OU "Janv. 2020 / fev. 2020" (si on affiche une semaine sur 2 mois)
		////	MENU DES ANNÉES & MOIS
		$vDatas["calMonthPeriodMenu"]=null;
		for($tmpMonth=1; $tmpMonth<=12; $tmpMonth++){
			$tmpMonthTime=strtotime(date("Y",$curTime)."-".($tmpMonth>9?$tmpMonth:"0".$tmpMonth)."-01");
			$vDatas["calMonthPeriodMenu"].="<a onClick=\"redir('?ctrl=calendar&curTime=".$tmpMonthTime."')\" ".(date("Y-m",$curTime)==date("Y-m",$tmpMonthTime)?"class='sLinkSelect'":null).">".Txt::formatime("%B",$tmpMonthTime)."</a>";
		}
		$vDatas["calMonthPeriodMenu"].="<hr>";
		for($tmpYear=date("Y")-3; $tmpYear<=date("Y")+5; $tmpYear++){
			$tmpYearTime=strtotime($tmpYear."-".date("m",$curTime)."-01");
			$vDatas["calMonthPeriodMenu"].="<a onClick=\"redir('?ctrl=calendar&curTime=".$tmpYearTime."')\" ".(date("Y",$curTime)==$tmpYear?"class='sLinkSelect'":null).">".$tmpYear."</a>";
		}
		////	LISTE DES JOURS À AFFICHER
		$vDatas["periodDays"]=[];
		if(empty($vDatas["timeDisplayedBegin"]))	{$vDatas["timeDisplayedBegin"]=$vDatas["timeBegin"];  $vDatas["timeDisplayedEnd"]=$vDatas["timeEnd"];}
		for($timeDay=$vDatas["timeDisplayedBegin"]+43200; $timeDay<=$vDatas["timeDisplayedEnd"]; $timeDay+=86400)//43200sec de décalage (cf. heures d'été/hiver)
		{
			//Date et Timestamp de début/fin du jour
			$tmpDay["date"]=date("Y-m-d",$timeDay);
			$tmpDay["timeBegin"]=strtotime(date("Y-m-d",$timeDay)." 00:00");
			$tmpDay["timeEnd"]=strtotime(date("Y-m-d",$timeDay)." 23:59");
			//Libelle d'un jour ferie ?
			$tmpDay["celebrationDay"]=(array_key_exists(date("Y-m-d",$timeDay),$vDatas["celebrationDays"]))  ?  $vDatas["celebrationDays"][date("Y-m-d",$timeDay)]  :  null;
			//Ajoute le jour à la liste
			$vDatas["periodDays"][]=$tmpDay;
		}

		////	RECUPERE LA VUE "MONTH"/"WEEK" DE CHAQUE AGENDA  &&  LA LISTE DE LEURS EVENEMENTS
		foreach($vDatas["displayedCalendars"] as $cptCal=>$tmpCal)
		{
			//Label d'ajout d'événement OU de proposition d'événement
			if($tmpCal->addContentRight())		{$tmpCal->addEventLabel=Txt::trad("CALENDAR_addEvtTooltip");}
			elseif($tmpCal->addOrProposeEvt())	{$tmpCal->addEventLabel=Txt::trad("CALENDAR_proposeEvtTooltip");}
			else								{$tmpCal->addEventLabel=null;}
			//EVENEMENTS POUR CHAQUE JOUR
			$tmpCal->eventList=[];
			$tmpCal->displayedPeriodEvtList=$tmpCal->evtList($vDatas["timeDisplayedBegin"],$vDatas["timeDisplayedEnd"]);//Récupère les evts de toute la période affichée
			foreach($vDatas["periodDays"] as $dayCpt=>$tmpDay)
			{
				//EVENEMENTS DU JOUR COURANT
				$tmpCal->eventList[$tmpDay["date"]]=[];
				$tmpCalDayEvtList=MdlCalendar::periodEvts($tmpCal->displayedPeriodEvtList,$tmpDay["timeBegin"],$tmpDay["timeEnd"]);//Récupère uniquement les evts de la journée
				foreach($tmpCalDayEvtList as $tmpEvt)
				{
					//Evt hors catégorie?
					if(Req::isParam("_idCatFilter") && $tmpEvt->_idCat!=Req::getParam("_idCatFilter"))  {continue;}
					//Element pour l'affichage "semaine"/"jour"
					if($displayMode!="month")
					{
						//Duree / Hauteur à afficher pour l'evt
						$tmpEvt->dayCpt=$dayCpt;
						$evtTimeBegin=strtotime($tmpEvt->dateBegin);
						$evtTimeEnd=strtotime($tmpEvt->dateEnd);
						$evtBeforeTmpDay=($evtTimeBegin < $tmpDay["timeBegin"]);//Evt commence avant le jour courant ?
						$evtAfterTmpDay=($evtTimeEnd > $tmpDay["timeEnd"]);		//Evt termine après le jour courant?
						if($evtBeforeTmpDay==true && $evtAfterTmpDay==true)	{$tmpEvt->durationMinutes=24*60;}									//Affiche toute la journée
						elseif($evtBeforeTmpDay==true)						{$tmpEvt->durationMinutes=($evtTimeEnd-$tmpDay["timeBegin"])/60;}	//Affiche l'evt depuis 0h00 du jour courant
						elseif($evtAfterTmpDay==true)						{$tmpEvt->durationMinutes=($tmpDay["timeEnd"]-$evtTimeBegin)/60;}	//Affiche l'evt jusqu'à 23h59 du jour courant
						else												{$tmpEvt->durationMinutes=($evtTimeEnd-$evtTimeBegin)/60;}			//Affichage normal (au cour de la journée)
						//Heure/Minutes de début d'affichage ("evtBeforeDayBegin" si l'evt a commencé avant le jour affiché)
						$tmpEvt->minutesFromDayBegin=($evtTimeBegin>$tmpDay["timeBegin"])  ?  (($evtTimeBegin-$tmpDay["timeBegin"])/60)  :  "evtBeforeDayBegin";
					}
					//Ajoute l'evt à la liste
					$tmpCal->eventList[$tmpDay["date"]][]=$tmpEvt;
				}
			}
			//Récupère la vue
			$tmpCal->isFirstCal=($cptCal==0);
			$vCalDatas=$vDatas;
			$vCalDatas["tmpCal"]=$tmpCal;
			$calendarVue=($displayMode=="month")?"VueCalendarMonth.php":"VueCalendarWeek.php";
			$tmpCal->calendarVue=self::getVue(Req::curModPath().$calendarVue, $vCalDatas);
		}

		////	SYNTHESE DES AGENDAS (SI + D'UN AGENDA)
		if(count($vDatas["displayedCalendars"])>1 && !Req::isMobile())
		{
			//Jours à afficher pour la synthese
			$vDatas["periodDaysSynthese"]=[];
			foreach($vDatas["periodDays"] as $tmpDay)
			{
				//affichage "month" & jour d'un autre mois : passe le jour
				if($displayMode=="month" && date("m",$tmpDay["timeBegin"])!=date("m",$curTime))	{continue;}
				//Evénements de chaque agenda pour le $tmpDay 
				$nbCalsOccuppied=0;
				$tmpDay["calsEvts"]=[];
				foreach($vDatas["displayedCalendars"] as $tmpCal){
					$tmpDay["calsEvts"][$tmpCal->_id]=MdlCalendar::periodEvts($tmpCal->displayedPeriodEvtList,$tmpDay["timeBegin"],$tmpDay["timeEnd"]);//Récupère uniquement les evts de la journée
					if(!empty($tmpDay["calsEvts"][$tmpCal->_id]))	{$nbCalsOccuppied++;}
				}
				//Tooltip de synthese si au moins un agenda possède un événement à cette date
				$tmpDay["nbCalsOccuppied"]=(!empty($nbCalsOccuppied))  ?  Txt::dateLabel($tmpDay["timeBegin"],"full")." :<br>".Txt::trad("CALENDAR_calendarsPercentBusy")." : ".$nbCalsOccuppied." / ".count($tmpDay["calsEvts"])  :  null;
				//Ajoute le jour
				$vDatas["periodDaysSynthese"][]=$tmpDay;
			}
		}
		////	LANCE L'AFFICHAGE
		static::displayPage("VueIndex.php",$vDatas);
	}

	/********************************************************************************************
	 * AGENDA COURANT EST AFFICHÉ ?
	 ********************************************************************************************/
	public static function isDisplayedCal($displayedCalendars, $curCal)
	{
		foreach($displayedCalendars as $tmpCal){
			if($tmpCal->_id==$curCal->_id)  {return true;}
		}
	}

	/********************************************************************************************
	 * PLUGINS
	 ********************************************************************************************/
	public static function getModPlugins($params)
	{
		$pluginsList=$evtList=[];
		//// Plugins uniquement pour "search" et "dashboard"  ..et s'il y a des agendas disponibles
		if(preg_match("/search|dashboard/i",$params["type"]) && count(MdlCalendar::visibleCalendars())>0)
		{
			//// Affichage "dashboard"
			if($params["type"]=="dashboard")
			{
				//Ajoute si besoin les propositions d'evts (créé un objet standard)
				$eventProposition=self::eventProposition();
				if(!empty($eventProposition))  {$pluginsList["eventProposition"]=(object)["pluginModule"=>self::moduleName, "pluginSpecificMenu"=>$eventProposition];}
				//Ajoute les evts courants de la période affichée
				foreach(MdlCalendar::visibleCalendars() as $tmpCal)
				{
					$timeBegin=strtotime($params["dateTimeBegin"]);
					$timeEnd=strtotime($params["dateTimeEnd"]);
					$evtListFull=$tmpCal->evtList($timeBegin,$timeEnd,1,false);//Evts de la période + tous les evts périodiques (accessRight>=1 et tri par date)
					//Pour chaque jour de la période : garde les evts du jour et les evts periodiques sur le jour
					for($timeTmp=$timeBegin; $timeTmp<=$timeEnd; $timeTmp+=86400)  {$evtList=array_merge($evtList, MdlCalendar::periodEvts($evtListFull,$timeTmp,($timeTmp+86399)));}
					//Ajoute à la liste des evt avec la propriété "pluginIsCurrent"
					foreach($evtList as $tmpEvt)  {$tmpEvt->pluginIsCurrent=true;  $eventList[]=$tmpEvt;}
				}
			}
			//// Ajoute la sélection normale du plugin (accessRight>=1 et tri par date et filtre avec $params)
			foreach(MdlCalendar::visibleCalendars() as $tmpCal)  {$evtList=array_merge($evtList, $tmpCal->evtList(null,null,1,false,$params));}
			//// Ajoute chaque plugin "evt"
			foreach($evtList as $tmpEvt)
			{
				//Vérif que l'evt n'est pas déjà dans la liste && Qu'il soit accessible en lecture && Qu'il ne s'agit pas des "eventProposition()"
				if(array_key_exists($tmpEvt->_targetObjId,$evtList)==false && $tmpEvt->readRight() && empty($tmpEvt->pluginSpecificMenu))
				{
					$tmpEvt->pluginModule=self::moduleName;
					$tmpEvt->pluginIcon=self::moduleName."/icon.png";
					$tmpEvt->pluginLabel=Txt::dateLabel($tmpEvt->dateBegin,"normal",$tmpEvt->dateEnd)." : ".$tmpEvt->title;
					$tmpEvt->pluginTooltip=Txt::dateLabel($tmpEvt->dateBegin,"full",$tmpEvt->dateEnd)."<hr>".$tmpEvt->affectedCalendarsLabel();
					$tmpEvt->pluginJsIcon="windowParent.redir('".$tmpEvt->getUrl()."');";//Affiche l'événement dans son agenda principal, avec le bon "datetime" (fonction "getUrl()" surchargée)
					$tmpEvt->pluginJsLabel="lightboxOpen('".$tmpEvt->getUrl("vue")."');";
					$pluginsList[$tmpEvt->_targetObjId]=$tmpEvt;//"_targetObjId" pour éviter les doublons d'evt (sur plusieurs agendas)
				}
			}
		}
		return $pluginsList;
	}

	/********************************************************************************************
	 * VUE : EDITION D'UN AGENDA
	 ********************************************************************************************/
	public static function actionCalendarEdit()
	{
		//Init
		$curObj=Ctrl::getTargetObj();
		if($curObj->isNew() && MdlCalendar::addRight()==false)	{self::noAccessExit();}
		else													{$curObj->editControl();}
		////	Valide le formulaire
		if(Req::isParam("formValidate")){
			//Enregistre & recharge l'objet
			$timeSlot=Req::getParam("timeSlotBegin")."-".Req::getParam("timeSlotEnd");
			$typeCalendar=$curObj->isNew() ? ", type='ressource'" : null;
			$curObj=$curObj->createUpdate("title=".Db::formatParam("title").", description=".Db::formatParam("description").", timeSlot=".Db::format($timeSlot).", propositionNotify=".Db::formatParam("propositionNotify").", propositionGuest=".Db::formatParam("propositionGuest").$typeCalendar);
			static::lightboxClose();
		}
		////	Affiche la vue
		$vDatas["curObj"]=$curObj;
		$vDatas["hidePropositionGuest"]=($curObj->type=="user" || Db::getVal("SELECT count(*) FROM ap_space WHERE public=1")==0);//Masque l'option pour les agendas d'users OU si l'espace n'est pas public
		static::displayPage("VueCalendarEdit.php",$vDatas);
	}

	/********************************************************************************************
	 * VUE : EDITION D'UN EVENEMENT D'AGENDA
	 ********************************************************************************************/
	public static function actionCalendarEventEdit()
	{
		////	INIT
		$curObj=Ctrl::getTargetObj();
		$curObj->editControl();
		////	VALIDE LE FORMULAIRE
		if(Req::isParam("formValidate"))
		{
			////	EDITE LES PRINCIPAUX CHAMPS DE L'ÉVÉNEMENT (titre, timeBegin..)
			if($curObj->fullRight())
			{
				//Prépare les dates
				$dateBegin=Txt::formatDate(Req::getParam("dateBegin")." ".Req::getParam("timeBegin"), "inputDatetime", "dbDatetime");
				$dateEnd=Txt::formatDate(Req::getParam("dateEnd")." ".Req::getParam("timeEnd"), "inputDatetime", "dbDatetime");
				//Périodicité
				$periodDateEnd=$periodValues=$periodDateExceptions=null;
				if(Req::isParam("periodType")){
					$periodDateEnd=Txt::formatDate(Req::getParam("periodDateEnd"), "inputDate", "dbDate");
					$periodValues=Txt::tab2txt(Req::getParam("periodValues_".Req::getParam("periodType")));
					if(Req::isParam("periodDateExceptions")){
						$periodDateExceptions=[];
						foreach(Req::getParam("periodDateExceptions") as $tmpDate)  {$periodDateExceptions[]=Txt::formatDate($tmpDate,"inputDate","dbDate");}
					}
				}
				//Invité : affiche une notif "Votre proposition sera examiné..."
				if(Ctrl::$curUser->isUser()==false && Req::isParam("guest"))  {Ctrl::notify("EDIT_guestElementRegistered");}
				//Enregistre & recharge l'objet
				$curObj=$curObj->createUpdate("title=".Db::formatParam("title").", description=".Db::formatParam("description","editor").", dateBegin=".Db::format($dateBegin).", dateEnd=".Db::format($dateEnd).", _idCat=".Db::formatParam("_idCat").", important=".Db::formatParam("important").", contentVisible=".Db::formatParam("contentVisible").", visioUrl=".Db::formatParam("visioUrl").", periodType=".Db::formatParam("periodType").", periodValues=".Db::format($periodValues).", periodDateEnd=".Db::format($periodDateEnd).", periodDateExceptions=".Db::formatTab2txt($periodDateExceptions).", guest=".Db::formatParam("guest").", guestMail=".Db::formatParam("guestMail"));
			}
			////	RÉINITIALISE SI BESOIN LES AFFECTATIONS/PROPOSITIONS AUX AGENDAS
			if(Req::isParam("reinitCalendars")){
				foreach(Req::getParam("reinitCalendars") as $idCal)  {$curObj->deleteAffectation($idCal,true);}
			}
			////	ATTRIBUE LES NOUVELLES AFFECTATIONS/PROPOSITIONS AUX AGENDAS
			$affectationCals=(array)Req::getParam("affectationCalendars");
			$propositionCals=(array)Req::getParam("propositionCalendars");
			$propositionIdUsers=[];
			foreach(array_merge($affectationCals,$propositionCals) as $idCal)
			{
				//Récupère l'agenda  &&  Verif si l'evt est déjà affecté à cet agenda
				$tmpCal=Ctrl::getObj("calendar",$idCal);
				if(in_array($tmpCal,MdlCalendar::affectationCalendars())){
					$isConfirmed=in_array($idCal,$affectationCals);
					Db::query("INSERT INTO ap_calendarEventAffectation SET _idEvt=".$curObj->_id.", _idCal=".$tmpCal->_id.", confirmed=".Db::format($isConfirmed));		//Ajoute l'affectation à l'agenda
					if($isConfirmed==false && $tmpCal->propositionNotify==true)  {$propositionIdUsers=array_merge($propositionIdUsers,$tmpCal->calendarOwnerIdUsers());}//Proposition : ajoute les proprios de l'agenda pour la notif mail
				}
			}
			////	NOTIFIE PAR MAIL LA PROPOSITION D'EVT (AUX GESTIONNAIRES/AUTEUR DES AGENDAS CONCERNES)
			if(!empty($propositionIdUsers)){
				$notifySubject=Txt::trad("CALENDAR_propositionNotifTitle")." ".$curObj->autorLabel();
				$evtTitleDate=$curObj->title." : ".Txt::dateLabel($curObj->dateBegin,"full",$curObj->dateEnd);
				$notifyMessage=str_replace(["--AUTOR_LABEL--","--EVT_TITLE_DATE--","--EVT_DESCRIPTION--"], [$curObj->autorLabel(),$evtTitleDate,$curObj->description], Txt::trad("CALENDAR_propositionNotifMessage"));
				Tool::sendMail($propositionIdUsers, $notifySubject, $notifyMessage, "noNotify");
			}
			////	NOTIFIE PAR MAIL LA CREATION D'EVT (AUX PERSONNES AFFECTEES AUX AGENDAS DE L'EVT)
			if(Req::isParam("notifMail") && $curObj->fullRight())
			{
				$objLabel=Txt::dateLabel($curObj->dateBegin,"full",$curObj->dateEnd)." : <b>".$curObj->title."</b>";
				$icalPath=self::getIcal($curObj, true);
				$icsFile=[["path"=>$icalPath, "name"=>Txt::clean($curObj->title).".ics"]];
				$curObj->sendMailNotif($objLabel, null, $icsFile);
				File::rm($icalPath);
			}
			//Ferme la page
			static::lightboxClose();
		}
		////	AFFICHE LA VUE
		////	Liste des agendas pour les affectations
		$vDatas["affectationCalendars"]=MdlCalendar::affectationCalendars();
		//Evt créé par un autre user : ajoute si besoin les agendas inaccessibles pour l'user courant mais quand même affectés à l'événement
		if($curObj->isNew()==false && $curObj->isAutor()==false){
			$vDatas["affectationCalendars"]=array_merge($vDatas["affectationCalendars"], $curObj->affectedCalendars("all"));
			$vDatas["affectationCalendars"]=MdlCalendar::sortCalendars(array_unique($vDatas["affectationCalendars"],SORT_REGULAR));//"SORT_REGULAR" pour les objets
		}
		////	Prépare l'affichage de chaque agenda
		foreach($vDatas["affectationCalendars"] as $tmpCal){
			//Ajoute quelques propriétés à l'agenda
			$tmpCal->inputType=($tmpCal->addContentRight() || in_array($tmpCal,$curObj->affectedCalendars()))  ?  "affectation"  :  "proposition";		//Input principal : affectation OU proposition pour l'agenda
			$tmpCal->isChecked=($tmpCal->_id==Req::getParam("_idCal") || in_array($tmpCal,$curObj->affectedCalendars("all")))  ?  "checked"  :  null;	//Input principal : check l'agenda s'il est présélectionné ou déjà affecté
			$tmpCal->isDisabled=($tmpCal->addContentRight()==false && $curObj->fullRight()==false)  ?  "disabled"  :  null;								//Input principal : désactive l'agenda s'il n'est pas accessible en écriture && user courant pas auteur de l'evt
			$tmpCal->reinitCalendarInput=($curObj->isNew()==false && $tmpCal->isDisabled==null);														//Ajoute l'input "hidden" de réinitialisation de l'affectation : modif d'evt et input pas "disabled"
			//Tooltip du label principal
			if($tmpCal->isDisabled!=null)				{$tmpCal->tooltip=Txt::trad("CALENDAR_noModifInfo");}			//"Modification non autorisé..." (tjs mettre en premier)
			elseif($tmpCal->inputType=="affectation")	{$tmpCal->tooltip=Txt::trad("CALENDAR_addEvtTooltipBis");}		//"Ajouter l'événement.."
			else										{$tmpCal->tooltip=Txt::trad("CALENDAR_proposeEvtTooltipBis2");}	//"Proposer l'événement .."
			if(!empty($tmpCal->description))  {$tmpCal->tooltip.=" (".$tmpCal->description.")";}//Ajoute la description de l'agenda
		}
		////	Nouvel evt : dates par défaut
		if($curObj->isNew()){
			$curObj->dateBegin =(Req::isParam("newEvtTimeBegin"))	?  date("Y-m-d H:i",Req::getParam("newEvtTimeBegin"))	:  date("Y-m-d H:00",time()+3600);							//date du jour, avec la prochaine heure courante
			$curObj->dateEnd   =(Req::isParam("newEvtTimeEnd"))		?  date("Y-m-d H:i",Req::getParam("newEvtTimeEnd"))		:  date("Y-m-d H:00",strtotime($curObj->dateBegin)+3600);	//une heure après l'heure de début
		}
		////	AFFICHE LA PAGE
		$vDatas["curObj"]=$curObj;
		$vDatas["tabPeriodValues"]=Txt::txt2tab($curObj->periodValues);
		foreach(Txt::txt2tab($curObj->periodDateExceptions) as $keyTmp=>$tmpException)	{$vDatas["periodDateExceptions"][$keyTmp+1]=Txt::formatDate($tmpException,"dbDate","inputDate");}
		$vDatas["curSpaceUserGroups"]=MdlUserGroup::getGroups(Ctrl::$curSpace);
		static::displayPage("VueCalendarEventEdit.php",$vDatas);
	}

	/********************************************************************************************
	 * AJAX : CONTROL DES CRÉNEAUX HORAIRES DES AGENDAS SÉLECTIONNÉS
	 ********************************************************************************************/
	public static function actionTimeSlotBusy()
	{
		if(Req::isParam(["dateTimeBegin","dateTimeEnd","targetObjects"]))
		{
			//Init
			$textTimeSlotBusy=null;
			$timeBegin=Txt::formatDate(Req::getParam("dateTimeBegin"),"inputDatetime","time")+1;//Décale d'une sec. pour eviter les faux positifs. Exple: créneaux 11h-12h dispo, même si 12h-13h est occupé
			$timeEnd=Txt::formatDate(Req::getParam("dateTimeEnd"),"inputDatetime","time")-1;//idem. Exple: créneaux 11h-12h dispo, même si 12h-13h est occupé
			//Vérifie le créneau horaire sur chaque agenda sélectionné
			foreach(self::getTargetObjects() as $tmpCal)
			{
				$calendarBusy=$calendarBusyTimeSlots=null;
				//Evts de l'agenda sur la période sélectionné ($accessRightMini=0.5)
				foreach(MdlCalendar::periodEvts($tmpCal->evtList($timeBegin,$timeEnd),$timeBegin,$timeEnd) as $tmpEvt){
					if($tmpEvt->_id!=Req::getParam("_evtId")){//Sauf l'evt en cours d'édition (si modif)
						$calendarBusyTimeSlots.=" &nbsp; &nbsp; <img src='app/img/arrowRight.png'> ".Txt::dateLabel($tmpEvt->dateBegin,"normal",$tmpEvt->dateEnd)." ";
						$calendarBusy=true;
					}
				}
				//L'agenda est occupé?
				if($calendarBusy==true)  {$textTimeSlotBusy.="<div class='vTimeSlotBusyRow'><div class='vTimeSlotBusyCell'>".$tmpCal->title."</div><div class='vTimeSlotBusyCell'>".$calendarBusyTimeSlots."</div></div>";}
			}
			//Retourne le message
			echo $textTimeSlotBusy;
		}
	}

	/********************************************************************************************
	 * VUE : EVENEMENTS QUE L'USER COURANT A CRÉÉ
	 ********************************************************************************************/
	public static function actionMyEvents()
	{
		$vDatas["myEvents"]=Db::getObjTab("calendarEvent","SELECT * FROM ap_calendarEvent WHERE _idUser=".Ctrl::$curUser->_id." ORDER BY dateBegin");
		static::displayPage("VueMyEvents.php",$vDatas);
	}

	/********************************************************************************************
	 * VUE : AFFICHE LES PROPOSITIONS D'EVENEMENT DES AGENDAS DE L'USER
	 ********************************************************************************************/
	public static function eventProposition()
	{
		//// Récupère les propositions d'événement de tous les agendas de l'user courant
		$vDatas["eventPropositions"]=[];
		foreach(MdlCalendar::curUserCalendars() as $tmpCal)
		{
			//// Liste les propositions d'événement sur l'agenda :  Supprime la proposition si obsolète (+ de 60 jours)  ||  Ajoute la proposition d'evt à l'agenda concerné
			foreach(Db::getObjTab("calendarEvent","SELECT T1.* FROM ap_calendarEvent T1, ap_calendarEventAffectation T2 WHERE T1._id=T2._idEvt AND T2._idCal=".$tmpCal->_id." AND T2.confirmed is null") as $tmpEvt){
				if($tmpEvt->isOldEvt(time()-5184000))	{$tmpEvt->deleteAffectation($tmpCal->_id);}
				else									{$vDatas["eventPropositions"][]=["evt"=>$tmpEvt,"cal"=>$tmpCal];}
			}
		}
		//Renvoie la vue s'il y a des propositions
		if(!empty($vDatas["eventPropositions"]))  {return self::getVue("app/ModCalendar/VueCalendarEventProposition.php", $vDatas);}
	}

	/********************************************************************************************
	 * AJAX : VALIDE / DECLINE UNE PROPOSITION D'ÉVÉNEMENT
	 ********************************************************************************************/
	public static function actionEventPropositionConfirm()
	{
		//Récupère l'agenda concerné et vérif le droit d'accès (cf. "targetObjId")
		$curCal=Ctrl::getTargetObj();
		if($curCal->curUserProperty())
		{
			//Récup L'evt et l'email pour la notif
			$curEvt=Ctrl::getObj("calendarEvent",Req::getParam("_idEvt"));
			$notifMail=(!empty($curEvt->guestMail))  ?  $curEvt->guestMail  :  Ctrl::getObj("user",$curEvt->_idUser)->mail;
			//Valide/Invalide la proposition
			if(Req::isParam("isConfirmed"))	{Db::query("UPDATE ap_calendarEventAffectation SET confirmed=1 WHERE _idEvt=".(int)$curEvt->_id." AND _idCal=".$curCal->_id);}
			else							{$curEvt->deleteAffectation($curCal->_id);}
			//Envoi une notification par email
			if(!empty($notifMail)){
				$notifySubject=(Req::isParam("isConfirmed"))  ?  Txt::trad("CALENDAR_evtProposedConfirmMail")  :  Txt::trad("CALENDAR_evtProposedDeclineMail");
				$notifyMessage=$notifySubject." : <br><br>".
								$curEvt->title." : ".Txt::dateLabel($curEvt->dateBegin,"full",$curEvt->dateEnd)."<br><br>".
								ucfirst(Txt::trad("OBJECTcalendar"))." : ".$curCal->title;
				Tool::sendMail($notifMail, $notifySubject, $notifyMessage, "noNotify");
			}
		}
	}

	/********************************************************************************************
	 * VUE : DÉTAILS D'UN ÉVÉNEMENT
	 ********************************************************************************************/
	public static function actionVueCalendarEvent()
	{
		$curObj=Ctrl::getTargetObj();
		$curObj->readControl();
		// visibilite / Catégorie
		if($curObj->contentVisible=="public_cache")	{$vDatas["contentVisibility"]=Txt::trad("CALENDAR_visibilityPublicHide");}
		elseif($curObj->contentVisible=="prive")	{$vDatas["contentVisibility"]=Txt::trad("CALENDAR_visibilityPrivate");}
		else										{$vDatas["contentVisibility"]=null;}
		$vDatas["labelCategory"]=(!empty($curObj->objCategory))  ?  $curObj->objCategory->display()  :  null;
		//Périodicité
		$vDatas["labelPeriod"]=$periodValues=null;
		if(!empty($curObj->periodType))
		{
			//Périodicité
			$vDatas["labelPeriod"]=Txt::trad("CALENDAR_period_".$curObj->periodType);
			foreach(Txt::txt2tab($curObj->periodValues) as $tmpVal){
				if($curObj->periodType=="weekDay")		{$periodValues.=Txt::trad("day_".$tmpVal).", ";}
				elseif($curObj->periodType=="month")	{$periodValues.=Txt::trad("month_".$tmpVal).", ";}
			}
			if(!empty($periodValues))	{$vDatas["labelPeriod"].=" : ".trim($periodValues, ", ");}
			//Périodicité : fin
			if(!empty($curObj->periodDateEnd))	{$vDatas["labelPeriod"].="<br>".Txt::trad("CALENDAR_periodDateEnd")." : ".Txt::dateLabel($curObj->periodDateEnd,"full");}
			//Périodicité : exceptions
			if(!empty($curObj->periodDateExceptions)){
				$vDatas["labelPeriod"].="<br>".Txt::trad("CALENDAR_periodException")." : ";
				$periodDateExceptions=array_filter(Txt::txt2tab($curObj->periodDateExceptions));//"array_filter" pour enlever les valeurs vides
				foreach($periodDateExceptions as $tmpVal)	{$vDatas["labelPeriod"].=Txt::dateLabel($tmpVal,"dateMini").", ";}
				$vDatas["labelPeriod"]=trim($vDatas["labelPeriod"], ", ");
			}
		}
		//Détails de l'événement
		$vDatas["curObj"]=$curObj;
		static::displayPage("VueCalendarEvent.php",$vDatas);
	}

	/********************************************************************************************
	 * VUE : EDITION DES CATEGORIES D'EVENEMENTS
	 ********************************************************************************************/
	public static function actionCalendarEventCategoryEdit()
	{
		////	Droit d'ajouter une categorie?
		if(MdlCalendarEventCategory::addRight()==false)  {static::lightboxClose();}
		////	Validation de formulaire
		if(Req::isParam("formValidate")){
			$curObj=Ctrl::getTargetObj();
			$curObj->editControl();
			//Modif d'une categorie
			$_idSpaces=(!in_array("all",Req::getParam("spaceList")))  ?  Txt::tab2txt(Req::getParam("spaceList"))  :  null;
			$curObj->createUpdate("title=".Db::formatParam("title").", description=".Db::formatParam("description").", color=".Db::formatParam("color").", _idSpaces=".Db::format($_idSpaces));
			//Ferme la page
			static::lightboxClose();
		}
		////	Liste des categories
		$vDatas["categoriesList"]=MdlCalendarEventCategory::getCategories(true);
		$vDatas["categoriesList"][]=new MdlCalendarEventCategory();//nouvelle categorie vide
		foreach($vDatas["categoriesList"] as $tmpKey=>$tmpCategory){
			if($tmpCategory->editRight()==false)	{unset($vDatas["categoriesList"][$tmpKey]);}
			else{
				$tmpCategory->tmpId=$tmpCategory->_targetObjId;
				$tmpCategory->createdBy=($tmpCategory->isNew()==false)  ?  Txt::trad("creation")." : ".$tmpCategory->autorLabel()  :  null;
			}
		}
		////	Affiche le form
		static::displayPage("VueCalendarEventCategoryEdit.php",$vDatas);
	}

	/********************************************************************************************
	 * EXPORTE/TÉLÉCHARGE DES ÉVÉNEMENTS D'UN AGENDA AU FORMAT .ICAL
	 ********************************************************************************************/
	public static function actionExportEvents()
	{
		$objCalendar=Ctrl::getTargetObj();
		if($objCalendar->editRight())  {self::getIcal($objCalendar);}
	}

	/********************************************************************************************
	 * CRÉATION DU FICHIER .ICAL
	 ********************************************************************************************/
	public static function getIcal($curObj, $tmpFile=false)
	{
		////	Evenement spécifié : récupère l'agenda principal  ||  Agenda spécifié : récupère ses événements
		if($curObj::objectType=="calendarEvent"){
			$eventList=[$curObj];
			$objCalendar=$curObj->containerObj();
			$icalCalname=null;
		}elseif($curObj::objectType=="calendar"){
			$periodBegin=time()-(86400*365);//Time - 1 an
			$periodEnd=time()+(86400*365*5);//Time + 5 ans
			$eventList=$curObj->evtList($periodBegin,$periodEnd);
			$objCalendar=$curObj;
			$icalCalname="X-WR-CALNAME:".$objCalendar->title."\n";
		}else{return false;}

		////	Prépare le fichier Ical
		$ical=	"BEGIN:VCALENDAR\n".
				"METHOD:PUBLISH\n".
				"VERSION:2.0\n".
				$icalCalname.
				"PRODID:-//Agora-Project//".self::$agora->name."//EN\n".
				"X-WR-TIMEZONE:".self::$curTimezone."\n".
				"CALSCALE:GREGORIAN\n".
				"BEGIN:VTIMEZONE\n".
				"TZID:".self::$curTimezone."\n".
				"X-LIC-LOCATION:".self::$curTimezone."\n".
				"BEGIN:DAYLIGHT\n".
				"TZOFFSETFROM:".self::icalHour()."\n".
				"TZOFFSETTO:".self::icalHour(1)."\n".
				"TZNAME:CEST\n".
				"DTSTART:19700329T020000\n".
				"RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3\n".
				"END:DAYLIGHT\n".
				"BEGIN:STANDARD\n".
				"TZOFFSETFROM:".self::icalHour(1)."\n".
				"TZOFFSETTO:".self::icalHour()."\n".
				"TZNAME:CET\n".
				"DTSTART:19701020T030000\n".
				"RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10\n".
				"END:STANDARD\n".
				"END:VTIMEZONE\n";
		//Ajoute chaque evenement (plusieurs fois si l'evt est périodique)
		foreach($eventList as $tmpEvt)
		{
			//Init
			$icalPeriod=$periodDateEnd=$icalCategories=null;
			if($tmpEvt->periodDateEnd)  {$periodDateEnd=";UNTIL=".self::icalDate($tmpEvt->periodDateEnd);}
			//Périodicité (année/jour/mois)
			if($tmpEvt->periodType=="year"){
				$icalPeriod="RRULE:FREQ=YEARLY;INTERVAL=1".$periodDateEnd."\n";
			}elseif($tmpEvt->periodType=="weekDay"){
				$tmpEvtPeriodValues=str_replace([1,2,3,4,5,6,7], ['MO','TU','WE','TH','FR','SA','SU'], Txt::txt2tab($tmpEvt->periodValues));
				$icalPeriod="RRULE:FREQ=WEEKLY;INTERVAL=1;BYDAY=".implode(",",$tmpEvtPeriodValues).$periodDateEnd."\n";
			}elseif($tmpEvt->periodType=="month"){
				$icalPeriod="RRULE:FREQ=MONTHLY;INTERVAL=1".$periodDateEnd."\n";
			}
			//Description & Périodicité & Categorie
			$icalDescription=$tmpEvt->description;
			if(!empty($tmpEvt->periodValues)){
				$icalDescription.=" - ".Txt::trad("CALENDAR_period_".$tmpEvt->periodType)." : ";
				foreach(Txt::txt2tab($tmpEvt->periodValues) as $tmpVal){
					if($tmpEvt->periodType=="weekDay")		{$icalDescription.=Txt::trad("day_".$tmpVal).", ";}
					elseif($tmpEvt->periodType=="month")	{$icalDescription.=Txt::trad("month_".$tmpVal).", ";}
				}
			}
			if(count($tmpEvt->affectedCalendars())>0)  {$icalDescription.=" - ".$tmpEvt->affectedCalendarsLabel();}//agendas où l'evt est affecté
			$icalDescription=str_replace(["\r","\n"], null, html_entity_decode(strip_tags($icalDescription)));//idem
			if(!empty($icalDescription))	{$icalDescription="DESCRIPTION:".$icalDescription."\n";}
			if(!empty($tmpEvt->_idCat))		{$icalCategories="CATEGORIES:".Ctrl::getObj("calendarEventCategory",$tmpEvt->_idCat)->title."\n";}
			//Ajoute l'evenement
			$ical.= "BEGIN:VEVENT\n".
					"CREATED:".self::icalDate($tmpEvt->dateCrea)."\n".
					"UID:".$tmpEvt->md5Id()."\n".
					"DTEND;TZID=".self::icalDate($tmpEvt->dateEnd,true)."\n".
					"SUMMARY:".$tmpEvt->title."\n".
					"LAST-MODIFIED:".self::icalDate($tmpEvt->dateModif)."\n".
					"DTSTAMP:".self::icalDate(date("Y-m-d H:i"))."\n".
					"DTSTART;TZID=".self::icalDate($tmpEvt->dateBegin,true)."\n".
					"DTEND;TZID=".self::icalDate($tmpEvt->dateEnd,true)."\n".
					$icalPeriod.$icalDescription.$icalCategories.
					"SEQUENCE:0\n".
					"END:VEVENT\n";
		}
		//Fin du ical
		$ical.="END:VCALENDAR";

		////	Enregistre un fichier Ical temporaire et on renvoie son "Path"
		if($tmpFile==true){
			$tmpFilePath=tempnam(File::getTempDir(),"exportIcal".uniqid());
			$fp=fopen($tmpFilePath, "w");
			fwrite($fp,$ical);
			fclose($fp);
			return $tmpFilePath;
		}
		////	Affiche directement le fichier .Ical
		else{
			header("Content-type: text/calendar; charset=utf-8");
			header("Content-Disposition: inline; filename=".Txt::clean($objCalendar->title)."_".date("d-m-Y").".ics");
			echo $ical;
		}
	}

	/********************************************************************************************
	 * EXPORT .ICAL : FORMATE L'HEURE
	 ********************************************************************************************/
	public static function icalHour($timeLag=0)
	{
		// Exemple avec "-5:30"
		$hourTimezone=Tool::$tabTimezones[self::$curTimezone];
		$valueSign=(substr($hourTimezone,0,1)=="-") ? '-' : '+';				//"-"
		$hourAbsoluteVal=str_replace(['-','+'],null,substr($hourTimezone,0,-3));//"5"
		$hourAbsoluteVal+=$timeLag;												//Si $timeLag=2 -> "7"
		if($hourAbsoluteVal<10)	{$hourAbsoluteVal="0".$hourAbsoluteVal;}		//"05"
		$minutes=substr($hourTimezone,-2);										//"30"
		return $valueSign.$hourAbsoluteVal.$minutes;//Retourne "-0530"
	}

	/********************************************************************************************
	 * EXPORT .ICAL : FORMATE LA DATE
	 ********************************************************************************************/
	public static function icalDate($dateTime, $timezone=false)
	{
		$dateTime=date("Ymd",strtotime($dateTime))."T".date("Hi",strtotime($dateTime))."00";//exple: "20151231T235900Z"
		return ($timezone==true) ? self::$curTimezone.":".$dateTime : str_replace("T000000Z","T235900Z",$dateTime."Z");
	}

	/********************************************************************************************
	 * IMPORT D'ÉVÉNEMENT (FORMAT .ICAL) DANS UN AGENDA
	 ********************************************************************************************/
	public static function actionImportEvents()
	{
		//Charge et controle
		$objCalendar=Ctrl::getTargetObj();
		$objCalendar->editControl();
		$vDatas=[];
		////	Validation de formulaire : sélection du fichier / des evt à importer
		if(Req::isParam("formValidate"))
		{
			////	PRÉPARE LE TABLEAU D'IMPORT
			if(isset($_FILES["importFile"]) && is_file($_FILES["importFile"]["tmp_name"]))
			{
				//Récupère les événements
				require 'class.iCalReader.php';
				$ical=new ical($_FILES["importFile"]["tmp_name"]);
				$vDatas["eventList"]=$ical->events();
				//Formate les evenements à importer
				if(empty($vDatas["eventList"]))  {Ctrl::notify("Import .Ical file : formating error");}
				else
				{
					foreach($vDatas["eventList"] as $cptEvt=>$tmpEvt)
					{
						//init
						$tmpEvt["dbDateEnd"]=$tmpEvt["dbDescription"]=$tmpEvt["dbPeriodType"]=$tmpEvt["dbPeriodValues"]=$tmpEvt["dbPeriodDateEnd"]="";
						//Prépare l'evt (attention au décalage des timezones dans le fihier .ics : mais corrigé via le "strtotime()")
						$tmpEvt["dbDateBegin"]=date("Y-m-d H:i",strtotime($tmpEvt["DTSTART"]));
						if(!empty($tmpEvt["DTEND"])){
							$tmpEvt["dbDateEnd"]=date("Y-m-d H:i",strtotime($tmpEvt["DTEND"]));
							if(strlen($tmpEvt["DTEND"])==8)  {$tmpEvt["dbDateEnd"]=date("Y-m-d H:i",(strtotime($tmpEvt["DTEND"])-86400));}//Les événements "jour" sont importés avec un jour de trop (cf. exports depuis G-Calendar)
						}
						$tmpEvt["dbTitle"]=strip_tags(nl2br($tmpEvt["SUMMARY"]));
						if(!empty($tmpEvt["DESCRIPTION"]))  {$tmpEvt["dbDescription"]=strip_tags(nl2br($tmpEvt["DESCRIPTION"]));}
						//Evenement périodique
						if(!empty($tmpEvt["RRULE"]))
						{
							//init
							$rruleTab=explode(";",$tmpEvt["RRULE"]);
							//Périodique : semaine
							if(stristr($tmpEvt["RRULE"],"FREQ=WEEKLY") && stristr($tmpEvt["RRULE"],"BYDAY=")){
								$tmpEvt["dbPeriodType"]="weekDay";
								foreach($rruleTab as $rruleTmp){//Jours de la période
									if(stristr($rruleTmp,"BYDAY="))  {$tmpEvt["dbPeriodValues"]=str_replace(['BYDAY=',',','MO','TU','WE','TH','FR','SA','SU'], [null,'@@',1,2,3,4,5,6,7], $rruleTmp);}
								}
							}
							//Périodique : mois
							if(stristr($tmpEvt["RRULE"],"FREQ=MONTHLY")){
								$tmpEvt["dbPeriodType"]="month";
								$tmpEvt["dbPeriodValues"]="@@1@@2@@3@@4@@5@@6@@7@@8@@9@@10@@11@@12@@";//sélectionne tous les mois
							}
							//Périodique : année
							if(stristr($tmpEvt["RRULE"],"FREQ=YEARLY")){
								$tmpEvt["dbPeriodType"]="year";
								$tmpEvt["dbPeriodValues"]=null;
							}
							//Périodicité : Fin de périodicité
							if(stristr($tmpEvt["RRULE"],"UNTIL=")){
								foreach($rruleTab as $rruleTmp){//Fin de période
									if(stristr($rruleTmp,"UNTIL=")){
										$tmpEvt["dbPeriodDateEnd"]=substr(intval(str_replace('UNTIL=','',$rruleTmp)), 0, 8);
										$tmpEvt["dbPeriodDateEnd"]=date("Y-m-d", strtotime($tmpEvt["dbPeriodDateEnd"]));}
								}
							}
						}
						//Etat de l'événement : à importer OU dejà present (donc ne pas importer)
						$tmpEvt["isPresent"]=(Db::getVal("SELECT count(*) FROM ap_calendarEvent T1, ap_calendarEventAffectation T2 WHERE T1._id=T2._idEvt AND T2._idCal=".$objCalendar->_id." AND T1.title=".Db::format($tmpEvt["dbTitle"])." AND T1.dateBegin=".Db::format($tmpEvt["dbDateBegin"])." AND T1.dateEnd=".Db::format($tmpEvt["dbDateEnd"])) > 0);
						//Ajoute l'evt
						$vDatas["eventList"][$cptEvt]=$tmpEvt;
					}
				}
			}
			////	IMPORTE LES ÉVÉNEMENTS
			elseif(Req::isParam("eventList"))
			{
				//Import de chaque événement
				foreach(Req::getParam("eventList") as $tmpEvt)
				{
					//Import sélectionné?
					if(!empty($tmpEvt["checked"])){
						//Créé et enregistre l'événement
						$curObj=new MdlCalendarEvent();
						$curObj=$curObj->createUpdate("title=".Db::format($tmpEvt["dbTitle"]).", description=".Db::format($tmpEvt["dbDescription"]).", dateBegin=".Db::format($tmpEvt["dbDateBegin"]).", dateEnd=".Db::format($tmpEvt["dbDateEnd"]).", periodType=".Db::format($tmpEvt["dbPeriodType"]).", periodValues=".Db::format($tmpEvt["dbPeriodValues"]).", periodDateEnd=".Db::format($tmpEvt["dbPeriodDateEnd"]));
						//Affecte à l'agenda courant
						Db::query("INSERT INTO ap_calendarEventAffectation SET _idEvt=".$curObj->_id.", _idCal=".$objCalendar->_id.", confirmed=1");
					}
				}
				//Ferme la page
				static::lightboxClose();
			}
		}
		////	Affiche le menu d'Import/Export
		static::displayPage("VueCalendarImportEvt.php",$vDatas);
	}
}