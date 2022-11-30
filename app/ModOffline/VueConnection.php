<script>
////	INIT
$(function(){
	// apparition en "fade" du formulaire
	$(".miscContainer").fadeIn(500);
	// On met le focus sur l'input du login (ou le password)
    $("input[name='<?= empty($defaultLogin) ? "connectLogin" : "connectPassword" ?>']").focus();
	//Fait clignoter le "labelResetPassword" si une mauvaise authentification vient d'être faite
	<?php if(Req::isParam("notify") && in_array("NOTIF_identification",Req::param("notify"))){ ?>
		$("#labelResetPassword").addClass("sLinkSelect").pulsate(10);
	<?php } ?>
});

////	Accès guest à un espace  (accès direct ou avec mot de passe)
function publicSpaceAccess(_idSpace, password)
{
	// Accès direct sans password
	if(password===false)  {redir("?_idSpaceAccess="+_idSpace);}
	//Affiche le formulaire de saisie du mot de passe
	else if(password===true){
		$("#formSpacePassword_idSpace").val(_idSpace);
		$("#formSpacePasswordLabel").trigger("click");
	}
	//Controle du mot de passe (ajax)
	else if(password.length>2){
		var ajaxUrl="?action=publicSpaceAccess&password="+encodeURIComponent(password)+"&_idSpace="+encodeURIComponent(_idSpace);
		$.ajax(ajaxUrl).done(function(ajaxResult){
			if(/true/i.test(ajaxResult))	{redir("?_idSpaceAccess="+_idSpace+"&password="+password);}
			else							{notify("<?= Txt::trad("spacePassError") ?>");  return false;}
		});
	}
}

////	Controle l'email de reset du password
function resetPasswordControlSend()
{
	if($("[name='resetPasswordMail']").isMail()==false)  {notify("<?= Txt::trad("mailInvalid") ?>");  return false;}
}

////	Controle du formulaire de reset du password
function resetPasswordControlNew()
{
	if(!isValidPassword($("[name='newPassword']").val()))						{notify("<?= Txt::trad("passwordInvalid"); ?>");		return false;}//Password invalide
	if($("[name='newPassword']").val()!=$("[name='newPasswordVerif']").val())	{notify("<?= Txt::trad("passwordConfirmError") ?>");	return false;}//Passwords différents
}

////	Contrôle d'identification / connexion!
function controlConnect()
{
	var inputLogin=$("[name=connectLogin]");
	var inputPassword=$("[name=connectPassword]");
	if(inputLogin.isEmpty() || inputLogin.val()==inputLogin.attr("placeholder") || inputPassword.isEmpty() || inputPassword.val()==inputPassword.attr("placeholder")){
		notify(labelSpecifyLoginPassword);
		return false;
	}
}
</script>


<!--GSIGNIN-->
<?php if(Ctrl::$agora->gSigninEnabled()){ ?>
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet" type="text/css">
<script src="https://apis.google.com/js/api:client.js"></script>
<script>
////	INIT
$(function(){
	////	Init l'authentification "auth2"  (https://developers.google.com/api-client-library/javascript/  && https://developers.google.com/api-client-library/php/)
	gapi.load("auth2", function(){
		auth2=gapi.auth2.init({client_id:'<?= Ctrl::$agora->gSigninClientId ?>'});//Charge l'API avec le "ClientId"
		auth2.attachClickHandler(document.getElementById("gSignInButton"),{},onAuthSuccess,onAuthFailure);//Init le bouton "gSignInButton"
	});
	////	Authentification "auth2" OK : vérif si l'user est bien enregistré
	var onAuthSuccess=function(user){
		$.ajax("?action=GSigninAuth&id_token="+user.getAuthResponse().id_token).done(function(ajaxResult){
			if(/userConnected/i.test(ajaxResult))	{redir("index.php");}//User connecté : recharge la page courante
			else									{notify(user.getBasicProfile().getName()+" <?= Txt::trad("gSigninUserNotRegistered") ?> &nbsp; <i>"+user.getBasicProfile().getEmail()+"</i>");}//notif d'erreur
		});
	};
	////	Pas d'Authentification "auth2"
	var onAuthFailure=function(errorJson){
		console.log("gSignin : "+errorJson.error);
	};
});
</script>
<?php } ?>


<style>
#pageCenter				{margin-top:120px;}/*surcharge*/
.miscContainer			{display:none; max-width:500px;/*pour le responsive*/ padding:30px 10px; margin:0px auto 0px auto; border-radius:5px; text-align:center;}/*surcharge*/
#headerBar>div			{padding:10px;}/*surcharge*/
#customLogo				{margin-bottom:40px;}
#customLogo img			{max-width:100%; max-height:250px;}
#formConnect input[type=text], #formConnect input[type=password], #formConnect button, .vMiscOptions button	{width:280px!important; height:45px; margin-bottom:15px;}/*surcharge*/
.vConnectOptions		{display:inline-table;}
.vConnectOptions>div	{display:table-cell; padding:10px;}
.vPasswordForms			{display:none;}
.vPasswordForms input	{height:35px; margin:5px; width:230px;}
.vPasswordForms button	{height:35px; margin:5px; width:150px;}
#publicSpaceTitle, #publicSpaceList	{text-align:left; margin-left:25%;}
#publicSpaceTitle		{font-size:1.1em;}
#publicSpaceList li		{font-size:1.05em; margin-top:10px; list-style:circle;}
#formSpacePasswordLabel	{display:none;}

/*RESPONSIVE*/
@media screen and (max-width:1023px){
	#pageCenter				{margin-top:70px;}/*surcharge*/
	.miscContainer			{width:100%!important;}
	#headerBar>div			{padding:8px; font-weight:normal;}/*surcharge*/
	#publicSpaceTitle, #publicSpaceList	{margin-left:20px;}
}
</style>


<div id="headerBar">
	<div><?= Ctrl::$agora->name ?></div>
	<div><?= Ctrl::$agora->description ?></div>
</div>


<div id="pageCenter">
	<div class="miscContainer">
		<?php
		//Logo custom et Séparateur "-or-"
		if(Ctrl::$agora->pathLogoConnect())  {echo "<div id='customLogo'><img src=\"".Ctrl::$agora->pathLogoConnect()."\"></div>";}
		$separateHrOr="<div class='orLabel'><div><hr></div><div>".Txt::trad("or")."</div><div><hr></div></div>";
		?>

		<!--FORMULAIRE PRINCIPAL DE CONNEXION-->
		<form action="index.php" method="post" id="formConnect" class="noConfirmClose" OnSubmit="return controlConnect()">
			<input type="text" name="connectLogin" value="<?= $defaultLogin ?>" placeholder="<?= Txt::trad("loginPlaceholder") ?>" title="<?= Txt::trad("loginPlaceholder") ?>">
			<input type="password" name="connectPassword" value="<?= Req::param("newPassword") ?>" placeholder="<?= Txt::trad("password") ?>">
			<?php if(Req::isParam(["objUrl","_idSpaceAccess"])){ ?>
				<input type="hidden" name="objUrl" value="<?= Req::param("objUrl") ?>">
				<input type="hidden" name="_idSpaceAccess" value="<?= Req::param("_idSpaceAccess") ?>">
			<?php } ?>
			<button type="submit"><?= Txt::trad("connect") ?></button>
			<div class="vConnectOptions">
				<div><input type="checkbox" name="rememberMe" value="1" id="boxRememberMe" checked><label for="boxRememberMe" title="<?= Txt::trad("connectAutoInfo") ?>"><?= Txt::trad("connectAuto") ?></label></div>
				<div><a data-fancybox="inline" data-src="#formResetPassword" id="labelResetPassword"><?= Txt::trad("resetPassword") ?></a></div>
			</div>
		</form>

		<!--RESET DU PASSWORD : FORMULAIRE D'ENVOI DU MAIL-->
		<form id="formResetPassword" class="vPasswordForms" action="index.php" method="post" onsubmit="return resetPasswordControlSend();">
			<?= Txt::trad("resetPassword2") ?><hr>
			<input type="text" name="resetPasswordMail" placeholder="<?= Txt::trad("mail") ?>">
			<input type="hidden" name="resetPasswordSendMail" value="1">
			<?= Txt::submitButton("send",false) ?>
		</form>

		<!--RESET DU PASSWORD : FORMULAIRE DE MODIF DU PASSWORD => 2 ÈME ETAPE-->
		<?php if(!empty($resetPasswordIdOk) && Req::isParam("newPassword")==false){ ?>
			<div><a data-fancybox="inline" data-src="#formResetPasswordBis" id="formResetPasswordBisLabel"><?= Txt::trad("passwordModify") ?></a></div>
			<form id="formResetPasswordBis" class="vPasswordForms" action="index.php" method="post" onsubmit="return resetPasswordControlNew();">
				<?= Txt::trad("passwordModify") ?><hr>
				<input type="password" name="newPassword" placeholder="<?= Txt::trad("password") ?>"><br><!--nouveau password-->
				<input type="password" name="newPasswordVerif" placeholder="<?= Txt::trad("passwordVerif") ?>">
				<input type="hidden" name="resetPasswordMail" value="<?= Req::param("resetPasswordMail") ?>"><!--vérif du reset-->
				<input type="hidden" name="resetPasswordId" value="<?= Req::param("resetPasswordId") ?>"><!--idem-->
				<input type="hidden" name="login" value="<?= Req::param("resetPasswordMail") ?>"><!--pré-remplissage du champ après reset-->
				<br><?= Txt::submitButton("validate",false) ?>
			</form>
			<script>
			//Lance le fancybox dès l'affichage de la page
			setTimeout(function(){ $("#formResetPasswordBisLabel").trigger("click"); },300);
			</script>
		<?php } ?>

		<!--VALIDATION D'INVITATION : INIT DU PASSWORD-->
		<?php if(Req::isParam("_idInvitation") && Req::isParam("newPassword")==false){ ?>
			<div><a data-fancybox="inline" data-src="#formInvitPassword" id="formInvitPasswordLabel"><?= Txt::trad("USER_invitPassword") ?></a></div>
			<form id="formInvitPassword" class="vPasswordForms" action="index.php" method="post" onsubmit="return resetPasswordControlNew();">
				<?= Txt::trad("USER_invitPassword2") ?><hr>
				<input type="password" name="newPassword" placeholder="<?= Txt::trad("password") ?>"><br><!--nouveau password-->
				<input type="password" name="newPasswordVerif" placeholder="<?= Txt::trad("passwordVerif") ?>">
				<input type="hidden" name="_idInvitation" value="<?= Req::param("_idInvitation") ?>"><!--pour récupérer l'invit-->
				<input type="hidden" name="mail" value="<?= Req::param("mail") ?>">
				<br><?= Txt::submitButton("validate",false) ?>
			</form>
			<script>
			//Lance le fancybox dès l'affichage de la page
			setTimeout(function(){ $("#formInvitPasswordLabel").trigger("click"); },300);
			</script>
		<?php } ?>

		<?php
		////	CONNEXION AVEC GSIGNIN
		if(Ctrl::$agora->gSigninEnabled()){
			echo $separateHrOr.
				"<div class='vMiscOptions'>
					<button id='gSignInButton' title=\"".Txt::trad("gSigninButtonInfo")."\"><img src='app/img/gSignin.png'> ".Txt::trad("gSigninButton")."</button>
				</div>";
		}

		////	CONNEXION EN TANT QU'INVITE
		if(!empty($objPublicSpaces)){
			echo $separateHrOr.
				"<div id='publicSpaceTitle'><img src='app/img/user/accessGuest.png'> ".Txt::trad("guestAccess")." :</div>
				  <ul id='publicSpaceList'>";
				foreach($objPublicSpaces as $tmpSpace)  {echo "<li class='sLink' onclick=\"publicSpaceAccess(".$tmpSpace->_id.",".(!empty($tmpSpace->password)?"true":"false").");\">".$tmpSpace->name."</li>";}
			echo "</ul>";
		}

		////	INSCRIPTION D'USER
		if(!empty($userInscription)){
			echo $separateHrOr.
				"<div class='vMiscOptions'>
					<button onclick=\"lightboxOpen('?action=userInscription')\" title=\"".Txt::trad("userInscriptionInfo")."\"><img src='app/img/check.png'> ".Txt::trad("userInscription")."</button>
				</div>";
		}

		////	SWITCH D'ESPACE (APP MOBILE || HOST)
		if(Req::isMobileApp() || Req::isHost()){
			echo $separateHrOr.
			"<div class='vMiscOptions'>
				<button onclick=\"redir('".Req::connectSpaceSwitchUrl()."')\"><img src='app/img/switch.png'> ".Txt::trad("connectSpaceSwitch")."</button>
			</div>";
		}
		?>

		<!--ESPACES PUBLICS : ACCES VIA PASSWORD-->
		<a data-fancybox="inline" data-src="#formSpacePassword" id="formSpacePasswordLabel"><?= Txt::trad("password") ?></a>
		<div id="formSpacePassword" class="vPasswordForms">
			<?= Txt::trad("password") ?><hr>
			<input type="password" name="spacePassword" id="formSpacePasswordPassword" placeholder="<?= Txt::trad("password") ?>">
			<input type="hidden" name="_idSpace" id="formSpacePassword_idSpace"><br>
			<button onclick="publicSpaceAccess($('#formSpacePassword_idSpace').val(),$('#formSpacePasswordPassword').val());"><?= Txt::trad("validate") ?></button>
		</div>
	</div>
</div>