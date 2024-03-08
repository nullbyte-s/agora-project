<script>
$(function(){
	////	Sélectionne tous les objets : passe tout à "false" puis switch la sélection
	$("#objSelectAll").on("click",function(){
		$("[name='objectsTypeId[]']").prop("checked",false);
		$("[name='objectsTypeId[]']").each(function(){ objSelectSwitch(this.id); });
	});
	////	Switch la sélection de tous les objets
	$("#objSelectSwitch").on("click",function(){
		$("[name='objectsTypeId[]']").each(function(){ objSelectSwitch(this.id); });
	});
});

////	Switch la sélection d'un objet
function objSelectSwitch(menuId)
{
	//Récupère le "MenuId" de l'objet (sans préfixe)
	var menuId=menuId.replace(/(objCheckbox|objContainer)/i,"");
	//Swich la sélection de la checkbox
	$("#objCheckbox"+menuId).prop("checked", !$("#objCheckbox"+menuId).prop("checked"));
	//Swich la sélection/class du block de l'objet
	$("#objContainer"+menuId).toggleClass("objContainerSelect", $("#objCheckbox"+menuId).prop("checked"));
	//Affiche/Masque le menu des objets sélectionnés
	if($(":checked[name='objectsTypeId[]']").length==0)	{$("#objSelectMenu").slideUp(350);}
	else												{$("#objSelectMenu").slideDown(350);}
}

////	Action sur les objets sélectionnés
function objSelectAction(urlRedir, openPage)
{
	//Ajoute chaque objet sélectionné dans "urlRedir"
	var objCurType=null;
	var objListSelector=":checked[name='objectsTypeId[]']";
	$(objListSelector).each(function(){
		var typeId=this.value.split("-");																			//Transforme l'objet courant en tableau (ex: "file-22" -> ["file",22])
		if(objCurType!=typeId[0])	{urlRedir+="&objectsTypeId["+typeId[0]+"]="+typeId[1];  objCurType=typeId[0];}	//Ajoute à l'url un nouveau "objectsTypeId"  (ex: "&objectsTypeId[file]=22")
		else						{urlRedir+="-"+typeId[1];}														//Ajoute à l'url le "_id" de l'objet courant (ex: "-33")
	});
	//Nombre d'elements sélectionnés
	var confirmDeleteSelectNb="\n \n -> "+$(objListSelector).length+" <?= Txt::trad("confirmDeleteSelectNb") ?>";
	//Confirme une désaffectation d'un user à l'espace courant
	if(/deleteFromCurSpace/i.test(urlRedir)){
		if(!confirm("<?= Txt::trad("USER_deleteFromCurSpaceConfirm") ?> "+confirmDeleteSelectNb))  {return false;}
	}
	//Confirme la suppression d'un ou plusieurs elements (cf. "labelConfirmDelete" et "labelConfirmDeleteDbl" de " VueStructure.php")
	else if(/delete/i.test(urlRedir)){
		var firstConfirmDelete=($(objListSelector).length==1)  ?  "\n "+labelConfirmDelete  :  "\n <?= Txt::trad("confirmDeleteSelect") ?> "+confirmDeleteSelectNb;
		if(!confirm(firstConfirmDelete) || !confirm(labelConfirmDeleteDbl))  {return false;}
	}
	//Ouvre une page (download), ouvre une lightbox (folderMove) ou redirection simple (delete users)
	if(openPage=="newPage")			{window.open(urlRedir);}
	else if(openPage=="lightbox")	{lightboxOpen(urlRedir);}
	else							{redir(urlRedir);}
}
</script>


<style>
#objSelectMenu							{display:none;}										/*menu masqué par défaut*/
#objSelectMenu, .objContainerSelect		{box-shadow:2px 2px 5px rgb(80,80,80)!important;}	/*border des elements sélectionnés*/
</style>


<div id="objSelectMenu" class="miscContainer">
	<?php
	////	"TELECHARGER LES FICHIERS" (modFile)
	if(Req::$curCtrl=="file")  {echo "<div class='menuLine' onclick=\"objSelectAction('?ctrl=file&action=downloadArchive','newPage')\"><div class='menuIcon'><img src='app/img/download.png'></div><div>".Txt::trad("FILE_downloadSelection")."</div></div>";}

	////	"VOIR SUR UNE CARTE" (modUser / modContact)
	if(Req::$curCtrl=="contact" || Req::$curCtrl=="user")  {echo "<div class='menuLine' onclick=\"objSelectAction('?ctrl=misc&action=PersonsMap','lightbox')\" title=\"".Txt::trad("showOnMapTooltip")."\"><div class='menuIcon'><img src='app/img/map.png'></div><div>".Txt::trad("showOnMap")."</div></div>";}

	////	"DEPLACER DES OBJETS" (arborescence)
	if($folderMoveOption==true)  {echo "<div class='menuLine' onclick=\"objSelectAction('?ctrl=object&action=FolderMove&typeId=".Ctrl::$curContainer->_typeId."','lightbox')\"><div class='menuIcon'><img src='app/img/folder/folderMove.png'></div><div>".Txt::trad("changeFolder")."</div></div>";}

	////	"DESAFFECTER DE L'ESPACE" (modUser)
	if(Req::$curCtrl=="user" && Ctrl::$curUser->isSpaceAdmin() && self::$curSpace->allUsersAffected()==false)  {echo "<div class='menuLine' onclick=\"objSelectAction('?ctrl=user&action=DeleteFromCurSpace')\"><div class='menuIcon'><img src='app/img/delete.png'></div><div>".Txt::trad("USER_deleteFromCurSpace")."</div></div>";}

	////	"SUPPRIMER" (arborescence / subjet / modUser)
	if($curContainerEditContentRight==true  ||  (Req::$curCtrl=="forum" && Ctrl::$curUser->isUser())  ||  (Req::$curCtrl=="user" && Ctrl::$curUser->isGeneralAdmin())){
		$deleteLabel=(Req::$curCtrl=="user")  ?  Txt::trad("USER_deleteDefinitely")  :  Txt::trad("deleteElems");
		echo "<div class='menuLine' onclick=\"objSelectAction('?ctrl=object&action=Delete')\"><div class='menuIcon'><img src='app/img/delete.png'></div><div>".$deleteLabel."</div></div>";
	}
	?>
	<!--"SELECTIONNER TOUT" && "INVERSER LA SELECTION"-->
	<div class="menuLine sLink" id="objSelectAll"><div class='menuIcon'><img src="app/img/checkSmall.png"></div><div><?= Txt::trad("selectAll") ?></div></div>
	<div class="menuLine sLink" id="objSelectSwitch"><div class='menuIcon'><img src="app/img/checkSmall.png"></div><div><?= Txt::trad("selectSwitch") ?></div></div>
</div>