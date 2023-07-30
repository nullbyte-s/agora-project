/**
*------------------------V3---------------------------
* This file is part of the Agora-Project Software package.
*
* @copyright (c) Agora-Project Limited <https://www.agora-project.net>
* @license GNU General Public License, version 2 (GPL-2.0)
*/


/**************************************************************************************************
 * SURCHARGE JQUERY POUR AJOUTER DE NOUVELLES FONCTIONS
 **************************************************************************************************/
////	Vitesse par défaut des effets "fadeIn()", "toggle()", etc
$.fx.speeds._default=100;
////	Verifie l'existance d'un element
$.fn.exist=function(){
	return (this.length>0);
};
////	Verifie si l'element ou l'input est vide
$.fn.isEmpty=function(){
	return (this.length==0 || this.val().trim().length==0);
};
////	Verifie si l'element est visible
$.fn.isVisible=function(){
	return this.is(":visible");
};
////	Vérifie si l'element est un email (cf. "isMail()")
$.fn.isMail=function(){
	return isMail(this.val());
};
////	Clignotement / "Blink" d'un element (toute les secondes et 5 fois par défaut : cf. "times")
$.fn.pulsate=function(times){
	if(typeof times=="undefined")  {var times=5;}
	this.effect("pulsate",{times:parseInt(times-1)},parseInt(times*1000));
};
////	Focus sur un champ surligné en rouge
$.fn.focusRed=function(){
	this.addClass("focusRed").focus();
};
////	Renvoie la hauteur totale des élements sélectionnées (marge comprise)
$.fn.totalHeight=function(){
	var tmpHeight=0;
	this.each(function(){ tmpHeight+=$(this).outerHeight(true); });
	return Math.floor(tmpHeight);
};
////	Scroll vers un element de la page
$.fn.scrollTo=function(){
	var scrollTopPos=$(this).offset().top - $("#headerBar,#headerBarCenter").height() - 10;//Soustrait la barre de menu principale fixe (#headerBar ou #headerBarCenter en fonction de la page)
	$("html,body").animate({scrollTop:scrollTopPos},300);
};

/**************************************************************************************************
 * DOCUMENT READY : ENREGISTRE LE "windowWidth" DE LA PAGE (RECUPERE COTE SERVEUR)
 **************************************************************************************************/
$(function(){
	if(isMainPage==true){
		var forceReload=(isTouchDevice() && /windowWidth/i.test(document.cookie)==false);	//Cookie "windowWidth" absent sur un appareil tactile : force un premier reload (cf. affichage des "respMenu")
		windowWidthCookie(forceReload);														//Init le cookie "windowWidth"
		window.onresize=function(){	windowWidthCookie(false); };							//Redimensionne la page (width/Height)
		window.onorientationchange=function(){ windowWidthCookie(false); };					//Change l'orientation de la page
	}
});
////	Enregistre le "windowWidth" dans un cookie. Timeout le temps d'avoir le width final (cf. "onresize"/"onorientationchange")
function windowWidthCookie(forceReload){
	if(typeof onresizeTimeout!="undefined")  {clearTimeout(onresizeTimeout);}//Pas de cumul de Timeout (cf. "onresize"/"onorientationchange")
	onresizeTimeout=setTimeout(function(){
		var pageReload=(forceReload==true || (typeof pageWidthLast!="undefined" && Math.abs($(window).width()-pageWidthLast)>30));	//Reload uniquement si le width a été modifé d'au moins 30px (pas de reload avec l'apparition/disparition de l'ascenseur)
		pageWidthLast=$(window).width();																							//Enregistre/Update le width courant pour le controle ci-dessus
		document.cookie="windowWidth="+$(window).width()+";expires=01 Jan 2050 00:00:00 GMT;samesite=Lax";							//Enregistre/Update le width dans un cookie permanent ("samesite" obligatoire pour les browsers)
		if(pageReload==true && windowParent.confirmCloseForm==false)  {location.reload();}											//Reload la page (sauf si on affiche un formulaire principal)
	},500);
}

/**************************************************************************************************
 * DOCUMENT READY : LANCE LES FONCTIONS DE BASE
 **************************************************************************************************/
$(function(){
	mainPageDisplay(true);	//Title via Tooltipster / Gallerie d'image via LightBox / largeur des blocks d'objet / etc.
	initMenuContext();		//Initialise les menus contextuels
});

/**************************************************************************************************
 * DOCUMENT READY : SURCHARGE CERTAINES FONCTION JQUERY AVEC "lightboxResize()"
**************************************************************************************************/
$(function(){
	if(isMainPage!==true)
	{
		var fadeInBASIC=$.fn.fadeIn;
		var showBASIC=$.fn.show;
		var toggleBASIC=$.fn.toggle;
		var slideToggleBASIC=$.fn.slideToggle;
		var slideDownBASIC=$.fn.slideDown;
		var fadeToggleBASIC=$.fn.fadeToggle;
		$.fn.fadeIn=function(){			lightboxResize();	return fadeInBASIC.apply(this,arguments); };
		$.fn.show=function(){			lightboxResize();	return showBASIC.apply(this,arguments); };
		$.fn.toggle=function(){			lightboxResize();	return toggleBASIC.apply(this,arguments); };
		$.fn.slideToggle=function(){	lightboxResize();	return slideToggleBASIC.apply(this,arguments); };
		$.fn.slideDown=function(){		lightboxResize();	return slideDownBASIC.apply(this,arguments); };
		$.fn.fadeToggle=function(){		lightboxResize();	return fadeToggleBASIC.apply(this,arguments); };
	}
});

/**************************************************************************************************
 * DOCUMENT READY : INITIALISE LES PRINCIPAUX TRIGGERS
 * => Click/DblClick sur ".objContainer", Menu flottant, etc.
 **************************************************************************************************/
$(function(){
	////	Triggers sur Mobile
	if(isMobile()){
		//Récupère la position du "touch" pour les swipes de menu ou autre (cf. "responsiveSwipe()")
		document.addEventListener("touchstart",function(event){  swipeBeginX=event.touches[0].clientX;  swipeBeginY=event.touches[0].clientY; });
		document.addEventListener("touchend",function(){  swipeBeginX=swipeBeginY=0;  });
	}
	////	Triggers en mode Desktop
	else
	{
		////	Click/DblClick sur les blocks conteneurs des objets : Sélectionne ou Edite un objet
		if($(".objContainer").exist())
		{
			//Trigger si ya click sur le block et que les actions par défaut sont autorisées
			$(".objContainer").click(function(){
				//Init
				var blockId="#"+this.id;
				timeDblClick=300;//intervalle entre 2 clics (pas plus de 300ms: cela doit rester un raccourcis du menu contextuel)
				if(typeof timeLastClick=="undefined")	{timeLastClick=Date.now();  containerIdLastClick=this.id;}
				//Double click?
				diffNowAndLastClick=(Date.now()-timeLastClick);
				var isDblClick=(diffNowAndLastClick>10 && diffNowAndLastClick<timeDblClick && containerIdLastClick==this.id);
				//Double click sur l'objet
				if(isDblClick==true && $(blockId).attr("data-urlEdit"))				{lightboxOpen($(blockId).attr("data-urlEdit"));}//Edition d'objet
				else if(isDblClick==false && typeof objSelectSwitch=="function")	{objSelectSwitch(this.id);}						//Switch la sélection (cf. "VueObjMenuSelection.php")
				//Update "lastClickTime" & "containerIdLastClick"
				timeLastClick=Date.now();
				containerIdLastClick=this.id;
			});
		}
		////	Menu flottant du module (à gauche)
		if($("#pageModuleMenu").exist())
		{
			var pageMenuPos=$("#pageModuleMenu").position();
			$(window).scroll(function(){
				var pageMenuHeight=pageMenuPos.top;//Init la position top du menu
				$("#pageModuleMenu").children().each(function(){ pageMenuHeight+=$(this).outerHeight(true); });//hauteur de chaque element
				if(pageMenuHeight < $(window).height())  {$("#pageModuleMenu").css("padding-top",$(window).scrollTop()+"px");}
			});
		}
	}
});

/**************************************************************************************************
 * DOCUMENT READY : INITIALISE LES CONTROLES DES CHAMPS
 * => Datepickers, Timepicker, FileSize controls, Integer, etc.
 **************************************************************************************************/
$(function(){
	////	Formulaire modifié : passe "confirmCloseForm" à "true" pour la confirmation de fermeture (".noConfirmClose" : sauf les forms de connexion and co. Ajoute "parent" pour cibler les "form" de lightbox)
	setTimeout(function(){
		$("form:not(.noConfirmClose)").find("input,select,textarea").on("change keyup",function(){ windowParent.confirmCloseForm=true; });
	},500);//500ms après l'init et préremplissage du formulaire

	////	Init le Datepicker jquery-UI
	$(".dateInput, .dateBegin, .dateEnd").datepicker({
		dateFormat:"dd/mm/yy",
		firstDay:1,
		showOtherMonths: true,
		selectOtherMonths: true,
		onSelect:function(date){
			//Select .dateBegin -> bloque la date minimum de .dateEnd (mais pas inversement!)
			if($(this).hasClass("dateBegin"))	{$(".dateEnd").datepicker("option","minDate",date);}
			//Trigger sur le champ concerné pour continuer l'action
			$(this).trigger("change");
		}
	});

	////	Init le plugin Timepicker (jquery-UI)
	if(jQuery().timepicker){
		var timepickerMinutesStep=(isMobile())  ?  5  :  15;//Palier de 5mn en "responsive", car champs en "readonly"
		$(".timeBegin, .timeEnd").timepicker({timeFormat:"H:i", step:timepickerMinutesStep});
	}

	////	Readonly sur les datepickers et timepickers
	if(isMobile())	{$(".dateInput,.dateBegin,.dateEnd,.timeBegin,.timeEnd").prop("readonly",true).css("background-color","white");}

	////	Controle les dates de début/fin
	$(".dateBegin, .dateEnd, .timeBegin, .timeEnd").change(function(){
		//Masque le champ H:M?
		if($(this).hasClass("dateBegin") || $(this).hasClass("dateEnd")){
			var timeClass=$(this).hasClass("dateBegin") ? ".timeBegin" : ".timeEnd";
			if($(this).isEmpty()==false)	{$(timeClass).show();}
			else							{$(timeClass).hide();  $(timeClass).val(null);}
		}
		//Controle des date/time
		if($(".dateBegin").isEmpty()==false || $(".dateEnd").isEmpty()==false)
		{
			//Controle des "H:M"
			if($(this).hasClass("timeBegin") || $(this).hasClass("timeEnd"))
			{
				//Champ à controler
				var timeClass=$(this).hasClass("timeBegin") ? ".timeBegin" : ".timeEnd";
				//Controle Regex des H:M
				var timeRegex=/^[0-2][0-9][:][0-5][0-9]$/;
				if($(timeClass).isEmpty()==false && timeRegex.test($(timeClass).val())==false){
					notify("H:m error");
					$(timeClass).val(null);
					return false;
				}
				//précise H:M de fin si vide et début précisé
				if($(".timeEnd").isEmpty())  {$(".timeEnd").val($(".timeBegin").val());}
			}
			//Début après Fin : message d'erreur
			if($(".dateBegin").isEmpty()==false && $(".dateEnd").isEmpty()==false)
			{
				var timestampBegin=$(".dateBegin").datepicker("getDate").getTime()/1000;//getTime() renvoie des millisecondes..
				var timestampEnd=$(".dateEnd").datepicker("getDate").getTime()/1000;//idem
				if($(".timeBegin").isEmpty()==false)	{var hourMinute=$(".timeBegin").val().split(":");	timestampBegin=timestampBegin + (hourMinute[0]*3600) + (hourMinute[1]*60);}
				if($(".timeEnd").isEmpty()==false)		{var hourMinute=$(".timeEnd").val().split(":");		timestampEnd=timestampEnd + (hourMinute[0]*3600) + (hourMinute[1]*60);}
				if(timestampBegin > timestampEnd)
				{
					//Date/heure de fin reculé : message d'erreur
					if($(this).hasClass("dateEnd") || $(this).hasClass("timeEnd"))  {notify(labelDateBeginEndControl);}
					//Modif la date/heure de fin ("setTimeout" pour éviter une re-modif du timePicker)
					setTimeout(function(){
						$(".dateEnd").val($(".dateBegin").val());
						$(".timeEnd").val($(".timeBegin").val());
					},500);
				}
			}
		}
	});

	////	Controle la taille des fichiers des inputs "file"
	$("input[type='file']").change(function(){
		if($(this).isEmpty()==false && this.files[0].size > valueUploadMaxFilesize){
			$(this).val("");
			notify(labelUploadMaxFilesize);
		}
	});

	////	Affecte une couleur à un input "select" (chaque option doit avoir un attribut "data-color")
	$("select option").each(function(){
		if(this.getAttribute("data-color"))  {$(this).css("background-color",this.getAttribute("data-color")).css("color","#fff");}
	});
	$("select").change(function(){
		var optionColor=$(this).find("option:selected").attr("data-color");
		if(isValue(optionColor))	{$(this).css("background-color",optionColor).css("color","#fff");}
		else						{$(this).css("background-color","#fff").css("color","#000");}
	});
	
	////	Pas d'autocomplétion sur TOUS les inputs des formulaires (password, dateBegin, etc) !
	$("form input:not([name*='connectLogin'])").attr("autocomplete","off");
});

/**************************************************************************************************
 * DOCUMENT READY : INITIALISE L'AFFICHAGE DES PAGES PRINCIPALES
 * => Menu flottant / Largeur des blocks d'objet / Clic sur les blocks d'objet / Etc.
 **************************************************************************************************/
function mainPageDisplay(firstLoad)
{
	////	Affiche les "Title" avec Tooltipster
	tooltipsterOptions={theme:"tooltipster-shadow",delay:450,contentAsHTML:true};				//Variable globale : delais pas trop long  (pas d'"animation" pour pas afficher de scroller dans les lightbox)
	$("[title]").not(".noTooltip,[title=''],[title*='http']").tooltipster(tooltipsterOptions);	//Applique le tooltipster (sauf si "noTooltip" est spécifié, ou le tooltip contient une URL, ou le title est vide)
	$("[title*='http']").tooltipster($.extend(tooltipsterOptions,{interactive:true}));			//Ajoute "interactive" pour les "title" contenant des liens "http" (cf. description & co). On créé une autre instance car "interactive" peut interférer avec les "menuContext"

	////	Fancybox des images (dans les news, etc) & inline (contenu html)
	var fancyboxImagesButtons=(isMobile()) ? ['close'] : ['fullScreen','thumbs','close'];
	$("[data-fancybox='images']").fancybox({buttons:fancyboxImagesButtons});
	$("[data-fancybox='inline']").fancybox({touch:false,arrows:false,infobar:false,smallBtn:false,buttons:['close']});//Pas de navigation entre les elements "inline" ("touch","arrow","infobar"). Pas de "smallBtn" close, mais plutôt celui en haut à droite.

	////	Initialise toute la page : largeur des blocks d'objet, Footer, etc.
	if(firstLoad===true)
	{
		////	Calcule la largeur des objets ".objContainer" (Affichage "block" uniquement. Calculé en fonction de la largeur de la page : après loading ou resize de la page)
		if($(".objBlocks .objContainer").length>0)
		{
			//Marge & Largeur min/max des objets
			var objMargin=parseInt($(".objContainer").css("margin-right"))+1;
			var objMinWidth=parseInt($(".objContainer").css("min-width"));
			var objMaxWidth=parseInt($(".objContainer").css("max-width")) + objMargin;//ajoute la marge pour l'application du "width()"
			//Largeur disponible
			var containerWidth=$("#pageFullContent").width();//pas de "innerWidth()" car cela ajoute le "padding"
			if(isMobile()==false && $(document).height()==$(window).height())	{containerWidth=containerWidth-18;}//pas encore d'ascenseur : anticipe son apparition (sauf en responsive ou l'ascenseur est masqué)
			//Calcul la largeur des objets
			var objWidth=null;
			var lineNbObjects=Math.ceil(containerWidth / objMaxWidth);//Nb maxi d'objets par ligne
			if(containerWidth < (objMinWidth*2))				{objWidth=containerWidth;  $(".objContainer").css("max-width",containerWidth);}	//On peut afficher qu'un objet par ligne : il prendra la largeur du conteneur
			else if($(".objContainer").length<lineNbObjects)	{objWidth=objMaxWidth;}															//Nb d'objets insuffisant pour remplir la 1ère ligne : il prendra sa largeur maxi
			else												{objWidth=Math.floor(containerWidth/lineNbObjects);}							//Sinon on calcul : fonction du conteneur et du nb d'objets par ligne
			//Applique la largeur des blocks (enlève le margin: pas pris en compte par le "outerWidth")  &&  Rend visible si ce n'est pas le cas !!
			$(".objContainer").outerWidth(Math.round(objWidth-objMargin)+"px");
		}
		//...affiche à nouveau après l'éventuel calcul ci-dessus : ".objBlocks" masqués par défaut via "common.css"!
		$(".objBlocks").css("visibility","visible");

		////	Affichage du footer
		$("#pageFooterHtml").css("max-width", parseInt($(window).width()-$("#pageFooterIcon").width()-20));//Pour que "pageFooterHtml" ne se superpose pas à "pageFooterIcon"
		setTimeout(function(){ $("#pageFull,#pageCenter").css("padding-bottom",footerHeight()); },300);//Padding (pas margin) sous le contenu principal, pour pas être masqué par le Footer. Timeout car "footerHeight()" est fonction du "#livecounterMain" chargé en Ajax..
	}
}

/**************************************************************************************************
 * DOCUMENT READY : INITIALISE LES MENUS CONTEXTUELS
 * chaque launcher (icone/texte/block d'objet) doit avoir la propriété "for" correspondant à l'ID du menu  &&  une class "menuLaunch" (sauf pour les launcher de block d'objet) 
 **************************************************************************************************/
function initMenuContext()
{
	////	MENU RESPONSIVE (width<=1024)
	if(isMobile()){
		//// Click d'un "launcher" (icone/texte) : affiche le menu responsive
		$(".menuLaunch").click(function(){
			if($("#respMenuContent").isVisible()==false)	{showRespMenu(this.getAttribute("for"),this.getAttribute("forBis"));}			//Menu masqué : on l'affiche
			else											{$("#"+this.getAttribute("for")).addClass("menuContextSubMenu").slideToggle();}	//Menu déjà affiché : on affiche le sous-menu
		});
		//// Swipe sur la page pour afficher/masquer le menu
		document.addEventListener("touchmove",function(event){
			if(typeof swipeBeginX!="undefined"  &&  Math.abs(swipeBeginY - event.touches[0].clientY) < 40){					//Swipe horizontal de 40px maxi d'amplitude verticale
				if((event.touches[0].clientX - swipeBeginX) > 10)  {respMenuClose(event.touches[0].clientX);}				//Swipe right : masque progressivement le menu  (swipe d'au moins 10px)
				else if((swipeBeginX - event.touches[0].clientX) > 50  &&  parseInt($(window).width()-swipeBeginX)<100){	//Swipe left : affiche le menu  (swipe d'au moins 50px, à moins de 100px du bord droit de la page)
					if($("#headerModuleTab").exist())		{showRespMenu("headerModuleTab","pageModMenu");}				//Affiche la liste des modules ("headerModuleTab")
					else if($("#headerMainMenu").exist())	{showRespMenu("headerMainMenu","pageModMenu");}					//Affiche sinon le menu principal de la page ("headerMainMenu")
				}
			}
		});
		//// Swipe terminé ("touchend") && menu en partie masqué => on le raffiche totalement (cf. position "right" du "respMenuClose()")
		document.addEventListener("touchend",function(){
			if($("#respMenuMain").isVisible() && parseInt($("#respMenuMain").css("right"))<0)  {$("#respMenuMain").css("right","0px");}
		});
		//// Click sur l'icone "close" ou le background du menu responsive : masque le menu
		$("#respMenuClose,#respMenuBg").click(function(){ respMenuClose(); });
	}
	////	MENU NORMAL (tester sur tablette)
	else{
		//// Mouseover/Click d'un "launcher" (icone/texte) : affiche le menu classique
		$(".menuLaunch").on("mouseover click",function(){ showMenuContext(this); });
		//// Click Droit d'un block d'objet : affiche le menu context ("Return false" pour pas afficher le menu du browser)
		$(".objContainer[for^='objMenu']").on("contextmenu",function(event){ showMenuContext(this,event);  return false; });
		//// Masque le menu dès qu'on le quitte
		$(".menuContext").on("mouseleave",function(){ $(".menuContext").hide(); });
	}
	////	Click/Survol le corps de la page : masque le menu contextuel
	$("#pageFull,#pageCenter").on("click mouseenter", function(){ $(".menuContext").hide(); });
	////	Pas de sélection d'un objet via "objSelectSwitch()" : si on click sur son menu context (ou son launcher) ou un lien spécifique (ex: sujet du forum ou download de fichier)
	$(".menuLaunch, .menuContext, .stopPropagation, .objContainer a, .objLabelLink").click(function(event){  event.stopPropagation();  });
}

/*MENU NORMAL : AFFICHE LE MENU CONTEXT*/
function showMenuContext(thisLauncher, event)
{
	////	Récup l'Id du menu  &&  Hauteur max du menu en fonction de la hauteur de page (cf. "overflow:scroll")
	var menuId="#"+$(thisLauncher).attr("for");
	$(menuId).css("max-height", Math.round($(window).height()-30)+"px");
	////	Vérif si un des parents est en position "relative|absolute|fixed"
	var parentRelativeAbsolute=false;
	$(menuId).parents().each(function(){  if(/(relative|absolute|fixed)/i.test($(this).css("position"))) {parentRelativeAbsolute=true; return false;}  });
	////	Position du menu
	if(event && event.type=="contextmenu")	{var menuPosX=event.pageX-$(thisLauncher).offset().left;	var menuPosY=event.pageY - $(thisLauncher).offset().top;}//En fonction click droit sur ".objContainer". Ajuste la position en fonction de ".objContainer" (toujours en position relative/absolute)
	else if(parentRelativeAbsolute==true)	{var menuPosX=$(thisLauncher).position().left;				var menuPosY=$(thisLauncher).position().top;}			 //En fonction de sa position absolute/relative
	else									{var menuPosX=$(thisLauncher).offset().left;				var menuPosY=$(thisLauncher).offset().top;}				 //En fonction de sa position sur la page
	////	Repositionne le menu s'il est au bord droit/bas de la page
	//Positions du menu + largeur/hauteur : bordure droite et bas du menu
	var menuRightPos =menuPosX + $(menuId).outerWidth(true);
	var menuBottomPos=menuPosY + $(menuId).outerHeight(true);
	//"Parent" en position relative/absolute : ajoute sa position sur la page
	if(/(relative|absolute|fixed)/i.test($(menuId).parent().css("position")))  {menuRightPos+=$(menuId).parent().offset().left;  menuBottomPos+=$(menuId).parent().offset().top;}
	//Ajuste si besoin la position si on est en bordure de page
	var pageBottomPosition=$(window).height()+$(window).scrollTop();
	if($(window).width() < menuRightPos)	{menuPosX=menuPosX-(menuRightPos-$(window).width());}
	if(pageBottomPosition < menuBottomPos)	{menuPosY=menuPosY-(menuBottomPos-pageBottomPosition);}
	////	Positionne et affiche le menu
	if(menuPosY>15)  {menuPosX-=15;  menuPosY-=15;}//recentre le menu de 20px
	$(menuId).css("left",menuPosX+"px").css("top",menuPosY+"px").slideDown(20);//Positionne le menu. Affiche avec un rapide slide (pas de "show()"!)
	$(".menuContext").not(menuId).hide();//Masque les autres menus
}

/*MENU RESPONSIVE : AFFICHE LE MENU CONTEXT (cf. VueStructure.php)*/
function showRespMenu(menuOneSourceId, menuTwoSourceId)
{
	//// Id des menus d'origine qui seront intégrés au menu responsive
	respMenuOneSourceId="#"+menuOneSourceId;
	respMenuTwoSourceId=(typeof menuTwoSourceId=="string") ? "#"+menuTwoSourceId : null;
	//// Vérifie que le menu demandé existe bien
	if($(respMenuOneSourceId).exist())
	{
		//Déplace les menus demandés dans "#respMenuOne" et "#respMenuTwo"  ("appendTo()" conserve les listeners, contrairement à "html()")
		$(respMenuOneSourceId+">*").appendTo("#respMenuOne");
		if($(respMenuTwoSourceId).exist())  {$(respMenuTwoSourceId+">*").appendTo("#respMenuTwo");  $("#respMenuTwo").show();}
		//Affiche le menu et son contenu
		$("#respMenuOne,#respMenuBg").fadeIn(50);
		$("#respMenuMain").css("right","0px").show("slide",{direction:"right"});//Réinit si besoin la position "right" (cf. "respMenuClose()"), Puis affiche le menu
		//Désactive le scroll de page en arriere plan
		$("body").css("overflow","hidden");
	}
}

/*MENU RESPONSIVE : MASQUE LE MENU RESPONSIVE*/
function respMenuClose(swipeCurrentX)
{
	if($("#respMenuMain").isVisible())
	{
		//// Masque progressivement le menu sur les 80 premiers pixels de "swipe" (déplace le menu vers la droite en même temps que le swipe)
		if(typeof swipeCurrentX!="undefined" && parseInt($("#respMenuMain").css("right")) > -80)  {$("#respMenuMain").css("right", "-"+(swipeCurrentX-swipeBeginX)+"px");}
		//// Sinon on masque totalement le menu
		else{
			//Masque les menus
			$("#respMenuOne,#respMenuTwo,#respMenuBg").fadeOut(50);
			$("#respMenuMain").hide("slide",{direction: "right"});
			//Remet le contenu de "#respMenuOne" et "#respMenuTwo" dans leur div d'origine ("respMenuOneSourceId" et "respMenuTwoSourceId")
			$("#respMenuOne>*").appendTo(respMenuOneSourceId);
			if(respMenuTwoSourceId!=null)  {$("#respMenuTwo>*").appendTo(respMenuTwoSourceId);}
			//Réactive le scroll de page en arriere plan
			$("body").css("overflow","visible");
		}
	}
}

/**************************************************************************************************
 * NAVIGATION EN MODE "MOBILE" SI LE WIDTH EST INFÉRIEUR À 1024PX  (IDEM Req.php && && Common.css)
 **************************************************************************************************/
function isMobile()
{
	return (windowParent.document.body.clientWidth<1024);
}

/**************************************************************************************************
 * VÉRIFIE SI ON EST SUR UN APPAREIL TACTILE (Android/Ipad/Iphone ..ou Ipad OS : cf. "Macintosh")
 **************************************************************************************************/
function isTouchDevice()
{
	return (navigator.maxTouchPoints>2 && /android|iphone|ipad|Macintosh/i.test(navigator.userAgent));
}

/**************************************************************************************************
 * VÉRIFIE SI UNE VALEURE N'EST PAS VIDE
 **************************************************************************************************/
function isValue(value)
{
	return (typeof value!="undefined" && value!=null && value!="" && value!=0);
}

/**************************************************************************************************
 * CONTROLE S'IL S'AGIT D'UN MAIL
 **************************************************************************************************/
function isMail(mail)
{
	var mailRegex=/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return mailRegex.test(mail);
}

/**************************************************************************************************
 * CONTROLE LA VALIDITÉ D'UN PASSWORD (au moins 6 caractères, un chiffre et une lettre)
 **************************************************************************************************/
function isValidPassword(password)
{
	return (password.length>=6 && /[0-9]/.test(password) && /[a-z]/i.test(password));
}

/**************************************************************************************************
 * EXTENSION D'UN FICHIER (SANS LE POINT)
 **************************************************************************************************/
function extension(fileName)
{
	if(isValue(fileName))  {return fileName.split(".").pop().toLowerCase();}
}

/**************************************************************************************************
 * AFFICHE UNE NOTIFICATION (via le Jquery "toastmessage")
 **************************************************************************************************/
function notify(curMessage, typeNotif)
{
	if(typeof curMessage!="undefined")
	{
		//Type de notification :  "success" vert  /  "warning" jaune  /  "notice" bleu (par défaut)
		var type=(typeof typeNotif!="undefined")  ?  typeNotif  :  "notice";
		//Temps d'affichage de la notification  (1 seconde pour 5 caracteres & 10 secondes minimum)
		var stayTime=parseInt(curMessage.length/5);
		if(stayTime<5)  {stayTime=10;}
		//Affiche la notification
		windowParent.$().toastmessage("showToast",{
			text		: curMessage,
			type		: type,
			position	: "top-center",
			stayTime	: (stayTime*1000)//stayTime en microsecondes
		});
	}
}

/*******************************************************************************************************
 * REDIRECTION DEPUIS UNE PAGE PRINCIPALE OU LIGHTBOX : CONFIRME SI BESOIN LA FERMETURE D'UN FORMULAIRE
 *******************************************************************************************************/
function redir(adress)
{
	if(closeFormConfirmed()==false)  {return false;}
	location.href=adress;
}
/*IDEM : REDIRECTION VIA BALISE "<A HREF>"*/
$(function(){
	$("a:not(.noConfirmClose)").click(function(event){
		if(closeFormConfirmed()==false)  {event.preventDefault(); return false;}
	});
});

/**************************************************************************************************
 * CONFIRME SI BESOIN LA FERMETURE D'UN FORMULAIRE EN COURS D'EDITION  (page principale ou lightbox)
 **************************************************************************************************/
function closeFormConfirmed()
{
	if(windowParent.confirmCloseForm==false || confirm(windowParent.labelConfirmCloseForm))	{windowParent.confirmCloseForm=false;  return true;}	//Réinit "confirmCloseForm" pour éviter un autre "confirm()" via un "redir()"
	else																					{return false;}											//Annule la fermeture du formulaire
}

/**************************************************************************************************
 * AFFICHE L'ICONE DE "LOADING" DANS LE BUTTON DE SUBMIT && "DISABLE" LE BUTTON
 **************************************************************************************************/
function submitButtonLoading()
{
	$(".submitButtonLoading").css("visibility","visible");
	$(".submitButtonMain button, .submitButtonInline button").css("background","#f5f5f5").prop("disabled",true);
}

/**************************************************************************************************
 * OUVRE UNE LIGHTBOX
 * Ne pas lancer via via "href" : plus souple et n'interfère pas avec "stopPropagation()" des "menuContext"
 * tester via : edit object, open pdf/mp3/mp4, userMap, inline html
 **************************************************************************************************/
function lightboxOpen(urlSrc)
{
	////	PDF + RESPONSIVE : OUVRE UNE NOUVELLE PAGE
	if(/\.pdf$/i.test(urlSrc) && isMobile())	{window.open(urlSrc);}
	////	ON EST DANS UNE LIGHTBOX : RELANCE DEPUIS LA PAGE "PARENT"
	else if(isMainPage!==true)					{parent.lightboxOpen(urlSrc);}
	////	LIGHTBOX "INLINE" : LECTEUR MP3/VIDEO
	else if(/\.(mp3|mp4|webm)$/i.test(urlSrc)){
		var mediaUrlSrc=(/\.mp3$/i.test(urlSrc))  ?  '<div><audio controls><source src="'+urlSrc+'" type="audio/mpeg">HTML5 required</audio></div>'  :  '<video controls><source src="'+urlSrc+'" type="video/'+extension(urlSrc)+'">HTML5 required</video>';
		$.fancybox.open({type:"inline", opts:{buttons:[]}, src:mediaUrlSrc});//Masque les "buttons" par défaut
	}
	////	LIGHTBOX "IFRAME" : AFFICHAGE PAR DEFAUT
	else{
		$.fancybox.open({
			type:"iframe",
			src:urlSrc,
			opts:{
				buttons:['close'],										//Affiche uniquement le bouton "close"
				autoFocus:false,										//Pas de focus automatique sur le 1er element du formulaire!
				beforeClose:function(){ return closeFormConfirmed(); }	//Affiche un formulaire : confirme la fermeture du lightbox
			}
		});
	}
}

/**************************************************************************************************
 * RESIZE LE WIDTH D'UNE LIGHTBOX  : APPELÉ DEPUIS UNE LIGHTBOX
 **************************************************************************************************/
function lightboxSetWidth(iframeBodyWidth)
{
	//Page entièrement chargé (pas de "$(function(){})" sinon peut poser problème sur Firefox & co)
	window.onload=function(){
		//Width définie en pixel/pourcentage : convertie en entier pixel
		if(/px/i.test(iframeBodyWidth))		{iframeBodyWidth=iframeBodyWidth.replace("px","");}									
		else if(/%/.test(iframeBodyWidth))	{iframeBodyWidth=($(windowParent).width()/100) * iframeBodyWidth.replace("%","");}
		//Width du contenu > Width de la page : le width devient celui de la page "parent"
		if(iframeBodyWidth>$(windowParent).width())  {iframeBodyWidth=$(windowParent).width();}
		//Définie le "max-width" de l'iframe (pas de "width", car cela peut afficher un scroll horizontal à l'agrandissement de la lightbox : cf. "lightboxResize()")
		if(isValue(iframeBodyWidth))  {$("body").css("max-width",parseInt(iframeBodyWidth));}
	};
}

/**************************************************************************************************
 * REDUIT/AGRANDIT LA HAUTEUR DE L'IFRAME "LIGHTBOX"  (après fadeIn(), FadeOut(), modif du TinyMce)
 **************************************************************************************************/
function lightboxResize()
{
	//Verif si le lightbox est affiché
	if(isMainPage!=true && windowParent.$(".fancybox-iframe").isVisible())
	{
		//Lance avec un timeout, pour pas cumuler les updates (quand les "show()", "fadeIn()", etc. s'enchainent)
		if(typeof lightboxResizeTimeout!="undefined")  {clearTimeout(lightboxResizeTimeout);}
		lightboxResizeTimeout=setTimeout(function(){
			//Resize si besoin la hauteur du lightbox : réduit au 1er affichage du lightbox || agrandit si on lance un "fadeIn()" ou modif du contenu de l'éditeur tinymce
			if(typeof lightboxHeightOld=="undefined" || lightboxHeightOld < windowParent.$(".fancybox-iframe").contents().height()){
				windowParent.$.fancybox.getInstance().update();								//Lance l'update du lightbox!
				lightboxHeightOld=windowParent.$(".fancybox-iframe").contents().height();	//Retient la taille du contenu du lightbox : celle après update!
			}
		},300);//pas moins de 300ms (cf. "$.fx.speeds._default=100" des "fadeIn()" & co)
	}
}

/**************************************************************************************************
 * RELOAD LA PAGE PRINCIPALE DEPUIS UNE LIGHTBOX (ex: après edit d'objet)
 **************************************************************************************************/
function lightboxClose(urlRedir, urlParms)
{
	if(isValue(urlRedir)==false)  {urlRedir=parent.location.href;}								//Récupère l'url de la page principale "parent"
	if(/notify/i.test(urlRedir))  {urlRedir=urlRedir.substring(0,urlRedir.indexOf('&notify'));}	//Enlève les anciens "notify()"
	if(isValue(urlParms))  {urlRedir+=urlParms;}												//Ajoute de nouveaux parametres notify() & co
	parent.location.replace(urlRedir);															//Redir la page principale "parent"
}

/**************************************************************************************************
 * CONFIRME UNE SUPPRESSION PUIS REDIRIGE POUR EFFECTUER LA SUPPRESION
 **************************************************************************************************/
function confirmDelete(redirUrl, labelConfirmDbl, ajaxControlUrl, objLabelId)
{
	////	"labelConfirmDelete" de "VueStructure.php" ("Voulez-vous supprimer...")  &  Ajoute si besoin le label d'un objet (cf "menuContextLabel" et "objLabelId")
	var labelConfirm=(isValue(labelConfirmDelete))  ?  "\n "+labelConfirmDelete  :  null;
	if(isValue(objLabelId) && $("#"+objLabelId).exist())  {labelConfirm+=" \n \n -> "+$("#"+objLabelId).text();}
	////	Confirmation principale
	if(isValue(labelConfirm) && confirm(labelConfirm)==false)  {return false;}
	////	Double confirmation (Ex: "labelConfirmDeleteDbl" de "VueStructure.php" ou "SPACE_confirmDeleteDbl")
	if(isValue(labelConfirmDbl) && confirm(labelConfirmDbl)==false)  {return false;}
	////	Controle Ajax pour une suppression de dossier : "Certains sous-dossiers ne vous sont pas accessibles [...] confirmer ?"
	if(isValue(ajaxControlUrl)){
		$.ajax({url:ajaxControlUrl,dataType:"json"}).done(function(result){
			if(result.confirmDeleteFolderAccess && confirm(result.confirmDeleteFolderAccess)==false)  {return false;}
			if(result.notifyBigFolderDelete)  {notify(result.notifyBigFolderDelete,"warning");}//Notify "merci de patienter un instant avant la fin du processus"
		});
	}
	////	Redirection pour executer la suppression
	redir(redirUrl);
}


/******************************************************************************************************************************************/
/*******************************************	SPECIFIC FUNCTIONS	********************************************************/
/******************************************************************************************************************************************/


/**************************************************************************************************
 * CALCUL LA HAUTEUR DISPONIBLE POUR LE CONTENU PRINCIPAL DE LA PAGE
 **************************************************************************************************/
function availableContentHeight()
{
	//Height de la fenêtre (mais pas la page), moins la Position "top" du conteneur, moins le paddingTop du conteneur, moins le paddingBottom du conteneur, moins le Height du footer
	var containerSelectors="#pageCenterContent,#pageFullContent,.emptyContainer";
	return Math.round($(window).height() - $(containerSelectors).offset().top - parseInt($(containerSelectors).css("padding-top")) - parseInt($(containerSelectors).css("padding-bottom")) - footerHeight());
}

/**************************************************************************************************
 * CALCUL LA HAUTEUR DU FOOTER
 **************************************************************************************************/
function footerHeight()
{
	//Icone du footer / Text html du footer  /  LivecounterMain (recup la hauteur préétablie via CSS, le contenu du livecounter est chargé après via Ajax)
	var footerHeightTmp=0;
	$("#pageFooterHtml:visible,#pageFooterIcon:visible,#livecounterMain:visible").each(function(){
		if($(this).html().length>0 && footerHeightTmp<$(this).outerHeight(true))  {footerHeightTmp=$(this).outerHeight(true);}//Controle du ".length" car le contenu peut être vide mais quant même affiché ("&nbsp;" & co)
	});
	return footerHeightTmp+3;//+ 3px (cf. "blox-shadow" des blocs)
}

/**************************************************************************************************
 * AFFECTATIONS DES SPACES<->USERS : "VueSpaceEdit.php" & "VueUserEdit.php"
 **************************************************************************************************/
function spaceAffectations()
{
	//// Click le Label d'une affectation (sauf "allUsers")
	$(".spaceAffectLabel").click(function(){
		//init
		var _idTarget=$(this).parent().attr("id").replace("targetLine","");	//Id de l'user ou espace dans le div parent contenant "targetLine" (ex: "targetLine55" -> "55")
		var box1=".spaceAffectInput[value='"+_idTarget+"_1']";				//Checkbox "user"
		var box2=".spaceAffectInput[value='"+_idTarget+"_2']";				//Checkbox "admin"
		//Switch de checkbox
		var boxToCheck=null;
		if($(box1).prop("checked")==false && $(box2).prop("checked")==false)	{boxToCheck=box1;}	//Check la box "user"
		else if($(box1).prop("checked") && $(box2).prop("checked")==false)		{boxToCheck=box2;}	//Check la box "admin"
		//Uncheck toutes les boxes (sauf celles "disabled")  &&  Check la box sélectionnée  &&  Stylise les labels
		$(".spaceAffectInput[value^='"+_idTarget+"_']:not(:disabled)").prop("checked",false);
		if(boxToCheck!=null)  {$(boxToCheck).prop("checked",true);}
		spaceAffectationsLabel();
	});

	//// Click la checkbox d'une affectation
	$(".spaceAffectInput").change(function(){
		var targetId=this.value.split("_")[0];																//Id de l'user ou espace (ex: "55_2" -> "55")
		$("[name='spaceAffect[]'][value^='"+targetId+"_']:not(:disabled)").not(this).prop("checked",false);	//Uncheck les autres box de l'user ou espace (sauf celles disabled)
		spaceAffectationsLabel();																			//Stylise les labels
	});

	//// Init le style des labels
	spaceAffectationsLabel();
};

/**************************************************************************************************
 * APPLIQUE UN STYLE À CHAQUE LABEL, EN FONCTION DE LA CHECKBOX COCHÉE
 **************************************************************************************************/
function spaceAffectationsLabel()
{
	//Réinit le style des affectations
	$(".spaceAffectLine").removeClass("lineSelect sAccessRead sAccessWrite");
	//Stylise les labels && la ligne sélectionnées
	$(".spaceAffectInput:checked").each(function(){
		var targetId   =this.value.split("_")[0];	//Id de l'user ou espace (ex: "55_2" -> "55")
		var targetRight=this.value.split("_")[1];	//Droit "user" ou "admin" (ex: "55_2" -> "2")
		if(targetRight=="1")		{$("#targetLine"+targetId).addClass("lineSelect sAccessRead");}	//Sélectionne la box "user"
		else if(targetRight=="2")	{$("#targetLine"+targetId).addClass("lineSelect sAccessWrite");}	//Sélectionne la box "admin"
	});
}

/**************************************************************************************************
 * RÉCUPÈRE LA VALEUR D'UN PARAMETRE DANS UNE URL
 **************************************************************************************************/
function urlParam(paramName, url)
{
	if(/(msie|trident)/i.test(window.navigator.userAgent)==false){			//Verif si le browser prend en charge "URL.SearchParams" (Safari aussi?)
		if(typeof url=="undefined")  {url=window.location.href;}			//Pas d'Url en paramètre : récupère l'Url de la page courante
		const urlParams=new URLSearchParams(url);							//Créé un objet 'URLSearchParams'
		if(urlParams.has(paramName))  {return urlParams.get(paramName);}	//Renvoi le paramètre s'il existe	
	}
}

/**************************************************************************************************
 * VALIDE LE "LIKE"/"DONTLIKE" D'UN OBJET
 **************************************************************************************************/
function usersLikeValidate(typeId, likeValue)
{
	if(isValue(typeId) && isValue(likeValue))
	{
		//Requête Ajax
		$.ajax({url:"?ctrl=object&action=UsersLikeValidate&typeId="+typeId+"&likeValue="+likeValue, dataType:"json"}).done(function(result){
			//Init les id
			var menuIdLike		="#likeMenu_"+typeId+"_like";
			var menuIdDontLike	="#likeMenu_"+typeId+"_dontlike";
			var idCircleLike    =menuIdLike+" .menuCircle";
			var idCircleDontlike=menuIdDontLike+" .menuCircle";
			//Nb de likes/dontlikes dans les cercles
			$(idCircleLike+", "+idCircleDontlike).addClass("menuCircleHide");//Réinit (Masque par défaut)
			if(parseInt(result.nbLikes)>0)		{$(idCircleLike).removeClass("menuCircleHide").html(result.nbLikes);}
			if(parseInt(result.nbDontlikes)>0)	{$(idCircleDontlike).removeClass("menuCircleHide").html(result.nbDontlikes);}
			//Liste des users dans les tooltip/title
			$(menuIdLike).attr("title",result.usersLikeList).tooltipster("destroy").tooltipster(tooltipsterOptions);
			$(menuIdDontLike).attr("title",result.usersDontlikeList).tooltipster("destroy").tooltipster(tooltipsterOptions);
			//Fait clignoter le like/dontlike  &&  affiche le tooltip (trigger mouseover)
			var menuId=(likeValue=="like") ? menuIdLike : menuIdDontLike;
			$(menuId).effect("pulsate",{times:1},300).trigger("mouseover");//pulsate rapide : pas de "pulsate()"
			//Affichage permanent du "objMiscMenus"
			if(result.nbLikes>0 || result.nbDontlikes>0)  {$(menuId).parent(".objMiscMenus").find(".objMenuLikeComment").addClass("showMiscMenu");}
		});
	}
}

/**************************************************************************************************
 * CHECK/UNCHECK UN GROUPE D'USERS
 * Tester : edition d'evt avec les groupes pour affectation aux agendas ET les groupes pour notification par email
 * Note : les inputs des groupes doivent avoir un "name" spécifique ET les inputs d'user doivent avoir une propriété "data-idUser"
 * On passe en paramètre le "this" de l'input du groupe ET l'id du conteneur des inputs d'users ("idContainerUsers") pour définir le périmère des inputs d'users
 **************************************************************************************************/
function userGroupSelect(thisGroup, idContainerUsers)
{
	//Check/uncheck chaque users du groupe
	var idUsers=$(thisGroup).val().split(",");
	for(var tmpKey in idUsers)
	{
		//Groupe "checked" : check l'user du groupe  ||  Sinon on vérifie si l'user est aussi sélectionné dans un autre groupe
		if($(thisGroup).prop("checked"))  {var userChecked=true;}
		else{
			var userChecked=false;
			$("[name='"+thisGroup.name+"']:checked").not(thisGroup).each(function(){
				var otherGroupUserIds=this.value.split(",");
				if($.inArray(idUsers[tmpKey],otherGroupUserIds)!==-1)  {userChecked=true;}
			});
		}
		//Check l'user courant
		$(idContainerUsers+" input[data-idUser="+idUsers[tmpKey]+"]:enabled").prop("checked",userChecked).trigger("change");//"trigger" pour le style du label
	}
}

/**************************************************************************************************
 * LANCE UNE VISIO (SI BESOIN AVEC LE NOM DES USERS CONCERNES DANS L'URL)
 **************************************************************************************************/
function launchVisio(visioURL)
{
	lightboxOpen("?ctrl=misc&action=LaunchVisio&visioURL="+encodeURIComponent(visioURL));
}