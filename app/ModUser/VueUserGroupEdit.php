<script>
////	Resize
lightboxSetWidth(600);

////	INIT
$(function(){
	////	Controle du formulaire
	$("form").submit(function(){
		//Vérif la présence du titre
		if($(this).find("[name='title']").isEmpty()){
			notify("<?= Txt::trad("requiredFields")." : ".Txt::trad("title") ?>");
			$(this).find("[name='title']").focusRed();
			return false;
		}
		// Au moins 2 utilisateurs sélectionnés
		if($(this).find("[name='userList[]']:checked").length<2){
			notify("<?= Txt::trad("selectUsers") ?>");
			return false;
		}
	});
});
</script>

<style>
/*Titre du lightbox*/
#labelSpaceName					{font-style:italic;}
/*formulaires*/
.miscContainer					{margin-top:40px; padding:10px; border:#999 1px solid;}
.miscContainer:last-of-type		{display:none; border:#999 2px solid;}/*masque le dernier formulaire : ajout d'element*/
input[name='title']				{width:50%;}
.vUserListMenu					{margin-top:20px; overflow:auto; max-height:150px;}
.userListUser					{display:inline-block; width:33%; padding:2px;}
.userListUser input				{display:none;}
.vAutorSubmit					{display:table; width:100%; margin-top:20px;}
.vAutorSubmit>div				{display:table-cell; vertical-align:middle;}
.vAutorSubmit>div:first-child	{font-style:italic; font-weight:normal;}
.vAutorSubmit>div:last-child	{text-align:right;}
.vAutorSubmit button			{width:120px; margin-right:10px;}
/*Ajout d'element*/
#addElem						{margin-top:50px; text-align:center;}
#addElem button					{width:300px; height:50px;}

/*RESPONSIVE FANCYBOX (440px)*/
@media screen and (max-width:440px){
	.vAutorSubmit, .vAutorSubmit>div  {display:block; margin-top:20px;}
	.userListUser	{width:48%; padding:5px;}
}
</style>

<div class="lightboxContent">
	<div class="lightboxTitle"><img src="app/img/user/userGroup.png"> <?= Txt::trad("USER_spaceGroups") ?> : <div id="labelSpaceName"><?= Ctrl::$curSpace->name ?></div></div>

	<div class="infos"><?= Txt::trad("USER_groupEditInfo") ?></div>

	<?php
	////	LISTE LES GROUPES D'UTILISATEURS
	foreach($groupList as $cptGroup=>$tmpGroup)
	{
		//Liste des utilisateurs (inputs)
		$userListInputs=null;
		foreach($usersList as $tmpUser){
			$tmpUserId="box_".$tmpGroup->tmpId."_".$tmpUser->_typeId;
			$tmpUserChecked=in_array($tmpUser->_id,$tmpGroup->userIds)  ?  "checked"  :  null;
			$userListInputs.="<div class='userListUser'>
								<input type='checkbox' name='userList[]' value='".$tmpUser->_id."' id='".$tmpUserId."' ".$tmpUserChecked.">
								<label for='".$tmpUserId."'>".$tmpUser->getLabel()."</label>
							  </div>";
		}
		//Affichage du formulaire
		$buttonsSubmitDelete=($tmpGroup->isNew())  ?  Txt::submitButton("add",false)  :  Txt::submitButton("record",false);
		if($tmpGroup->isNew()==false)  {$buttonsSubmitDelete.="<img src='app/img/delete.png' class='sLink' title=\"".Txt::trad("delete")."\" onclick=\"confirmDelete('".$tmpGroup->getUrl("delete")."')\">";}
		echo "<form action='index.php' method='post' class='miscContainer'>
				<input type='text' name='title' value=\"".$tmpGroup->title."\" placeholder=\"".Txt::trad("title")."\">
				<div class='vUserListMenu'>".$userListInputs."</div>
				<div class='vAutorSubmit'>
					<div>".$tmpGroup->createdBy."</div>
					<div>".$buttonsSubmitDelete."<input type='hidden' name='typeId' value='".$tmpGroup->tmpId."'></div>
				</div>
			  </form>";
	}
	?>

	<div id="addElem">
		<button onclick="$('form:last-of-type').slideToggle();$('form:last-of-type [name=title]').focus();"><img src="app/img/plus.png"> <?= Txt::trad("USER_addGroup") ?></button>	
	</div>
</div>