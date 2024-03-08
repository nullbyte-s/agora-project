<script>
////	Resize
lightboxSetWidth(500);

////	Sélectionner au moins une demande d'inscription
$(function(){
	$("form").submit(function(){
		if($(this).find("[name='inscriptionValidate[]']:checked").length==0)   {notify("<?= Txt::trad("notifSelectUser") ?>");  return false;}
	});
});
</script>


<style>
.vInscription								{padding:10px;}
.vInscription label							{margin-right:10px;}
.vInscriptionMessage, .vInscriptionSpace	{margin-top:8px; margin-left:30px; font-weight:normal; }
.vInscriptionMessage:empty					{display:none;}
.submitButtonMain							{margin:30px 0px;}/*surcharge*/
.submitButtonMain button					{width:350px; height:50px;}/*surcharge*/
.submitButtonMain button img				{margin-right:10px;}
</style>


<form action="index.php" method="post">
	<div class="lightboxTitle"><?= Txt::trad("userInscriptionValidateTooltip") ?></div>
	<?php
	//// LISTE DES INSCRIPTIONS D'USERS
	foreach(CtrlUser::userInscriptionValidate() as $tmpInsc){
		echo "<div class='vInscription lineHover'>
				<input type='checkbox' name='inscriptionValidate[]' value=\"".$tmpInsc["_id"]."\" id='inputInscription".$tmpInsc["_id"]."'>
				<label for='inputInscription".$tmpInsc["_id"]."'>".Txt::dateLabel($tmpInsc["date"])." : ".$tmpInsc["name"]." ".$tmpInsc["firstName"]." - ".$tmpInsc["mail"]."</label>
				<div class='vInscriptionSpace'><img src='app/img/arrowRight.png'> ".ucfirst(Txt::trad("SPACE_space"))." <b>".Ctrl::getObj("space",$tmpInsc["_idSpace"])->getLabel()."</b></div>
				<div class='vInscriptionMessage'><img src='app/img/arrowRight.png'> ".Txt::trad("description")." : ".$tmpInsc["message"]."</div>
			 </div>";
	}

	//// BOUTON DE VALIDATION & BOUTON D'INVALIDATION
	echo Txt::submitButton("<img src='app/img/check.png'>".Txt::trad("userInscriptionSelectValidate"));
	echo "<div class='submitButtonMain' id='buttonInvalidate'><button type='submit' name='submitInvalidate' value='true'><img src='app/img/delete.png'>".Txt::trad("userInscriptionSelectInvalidate")."</button></div>";
	?>
</form>