<script>
lightboxSetWidth(550);//Resize
</script>

<form action="index.php" method="post" onsubmit="return mainFormControl()" enctype="multipart/form-data" class="lightboxContent">
	<!--TITRE RESPONSIVE-->
	<?= $curObj->editRespTitle("CONTACT_addContact") ?>
	
	<!--IMAGE-->
	<div class="objField">
		<div class="fieldLabel"><?= $curObj->hasImg()  ?  "<div class='personLabelImg'>".$curObj->getImg()."</div>"  :  "<img src='app/img/person/photo.png'> ".Txt::trad("picture") ?></div>
		<div><?= $curObj->displayImgMenu() ?></div>
	</div>
	<hr>
	<!--CHAMPS PRINCIPAUX & MENU COMMUN-->
	<?= $curObj->getFieldsValues("edit").$curObj->menuEdit() ?>
</form>