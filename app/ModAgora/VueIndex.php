<script>
$(function(){
	////	Logo du footer
	$("select[name='logo']").on("change",function(){
		$("#logoFile,#logoUrl").hide();
		if(this.value=="modify")	{$("#logoUrl,#logoFile").show();}
		else if(this.value!="")		{$("#logoUrl").show();}
	});

	////	Logo en page de connexion
	$("select[name='logoConnect']").on("change",function(){
		if(this.value=="modify")	{$("#logoConnectFile").show();}
		else						{$("#logoConnectFile").hide();}
	});
	//Affiche "logoConnectFile" si le <select> n'est pas affiché
	if($("select[name='logoConnect']").exist()==false)  {$("#logoConnectFile").show();}

	////	Vérif le type du fichier
	$("#wallpaperFile,#logoFile,#logoConnectFile").on("change",function(){
		if(/\.(jpg|jpeg|png)/i.test(this.value)==false)  {notify("<?= Txt::trad("AGORA_wallpaperLogoError") ?>");}
	});

	////	Affiche du "mapApiKeyDiv" si "mapTool"=="gmap"  &&   Affiche du "gIdentityClientId" si "gIdentity" est activé  ("trigger()" initie l'affichage)
	$("select[name='mapTool']").on("change",function(){  this.value=="gmap" ? $("#mapApiKeyDiv").fadeIn() : $("#mapApiKeyDiv").fadeOut();  }).trigger("change");
	$("select[name='gIdentity']").on("change",function(){  this.value=="1" ? $("#gIdentityClientIdDiv").fadeIn() : $("#gIdentityClientIdDiv").fadeOut();  }).trigger("change");

	////	Controle du formulaire
	$("#mainForm").submit(function(){
		//Contrôle le nom de l'espace
		if($("input[name='name']").isEmpty())   {notify("<?= Txt::trad("fillFieldsForm") ?>");  return false;}
		//Contrôle de l'espace disque, l'url de serveur de visio, le mapApiKey et gIdentityClientId
		<?php if(Req::isHost()==false){ ?>
		if(isNaN($("#limite_espace_disque").val()))   																		{notify("<?= Txt::trad("AGORA_diskSpaceInvalid") ?>");  return false;}	//doit être un nombre
		if($("input[name='visioHost']").isEmpty()==false && /^https/.test($("input[name='visioHost']").val())==false)		{notify("<?= Txt::trad("AGORA_visioHostInvalid") ?>");  return false;}	//doit commencer par "https"
		if($("input[name='visioHostAlt']").isEmpty()==false && /^https/.test($("input[name='visioHostAlt']").val())==false)	{notify("<?= Txt::trad("AGORA_visioHostInvalid") ?>");  return false;}	//doit commencer par "https"
		if($("select[name='mapTool']").val()=="gmap" && $("input[name='mapApiKey']").isEmpty())								{notify("<?= Txt::trad("AGORA_mapApiKeyInvalid") ?>");  return false;}	//Doit spécifier un "API Key"
		if($("select[name='gIdentity']").val()=="1" && $("input[name='gIdentityClientId']").isEmpty())						{notify("<?= Txt::trad("AGORA_gIdentityKeyInvalid") ?>");  return false;}	//Idem
		<?php } ?>
		return confirm("<?= Txt::trad("AGORA_confirmModif") ?>");
	});
});
</script>


<style>
/*Menu context de gauche*/
#pageModuleMenu .miscContainer	{text-align:center;}/*surcharge*/
#pageModuleMenu button			{width:90%;}
#pageModuleMenu button img		{max-height:25px; margin-left:10px;}
#agoraInfos div					{line-height:35px;}
#backupForm button				{margin:10px 0; width:100%; height:50px; text-align:left;}

/*Formulaire principal*/
.objField>div					{padding:4px 0px 4px 0px;}/*surcharge*/
#logoFile, #logoConnectFile		{display:none;}
#logoUrl						{margin-top:10px; <?= (empty(Ctrl::$agora->logo)) ? "display:none;":null ?>}
#imgLogo, #imgLogoConnect		{max-height:45px;}
#limite_espace_disque			{width:40px;}
#smtpConfig, #ldapConfig		{padding:10px; margin-bottom:20px;}
</style>


<div id="pageCenter">
	<div id="pageModuleMenu">
		<div class="miscContainer" id="agoraInfos">
			<!--VERSIONS D'AGORA-PROJECT (ET DATE D'UPDATE) / PHP / MYSQL-->
			<div>Agora-Project / Omnispace version <?= Req::appVersion() ?></div>
			<div><?= Txt::trad("AGORA_dateUpdate")." ".Txt::dateLabel(Ctrl::$agora->dateUpdateDb,"date") ?></div>
			<div><a onclick="lightboxOpen('docs/CHANGELOG.txt')"><button><?= Txt::trad("AGORA_Changelog") ?></button></a></div>
			<div>PHP <?= str_replace(strstr(phpversion(),"-"),"",phpversion()) ?> &nbsp;&nbsp; <?= Db::dbVersion() ?></div>
			<!--FONCTIONS PHP DÉSACTIVÉES-->
			<?php if(!function_exists("mail")){ ?><div ><img src="app/img/delete.png"> <?= Txt::trad("AGORA_funcMailDisabled") ?></div><?php } ?>
			<?php if(!function_exists("imagecreatetruecolor")){ ?><div><img src="app/img/delete.png"> <?= Txt::trad("AGORA_funcImgDisabled") ?></div><?php } ?>
			<?php if(!function_exists("ldap_connect")){ ?><div><img src="app/img/delete.png"> <?= Txt::trad("AGORA_ldapDisabled") ?></div><?php } ?>
		</div>

		<!--SAUVEGARDER LA BDD ET LE FICHIERS-->
		<?php if(Req::isMobile()==false){ ?>
		<form action="index.php" method="post" id="backupForm" class="miscContainer" onsubmit="return confirm('<?= Txt::trad('AGORA_backupConfirm',true) ?>')">
			<button type="submit" name="typeBackup" value="all" title="<?= Txt::trad("AGORA_backupFullTooltip") ?>"><img src="app/img/download.png"> <?= Txt::trad("AGORA_backupFull") ?></button>
			<button type="submit" name="typeBackup" value="db" title="<?= Txt::trad("AGORA_backupDbTooltip") ?>"><img src="app/img/download.png"> <?= Txt::trad("AGORA_backupDb") ?></button>
			<input type="hidden" name="ctrl" value="agora">
			<input type="hidden" name="action" value="getBackup">
		</form>
		<?php } ?>
	</div>

	<div id="pageCenterContent">
		<!--FORMULAIRE DU PRAMETRAGE GENERAL-->
		<form action="index.php" method="post" id="mainForm" class="miscContainer" enctype="multipart/form-data">
			<!--NAME-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_name") ?></div>
				<div><input type="text" name="name" value="<?= Ctrl::$agora->name ?>"></div>
			</div>
			<!--DESCRIPTION-->
			<div class="objField">
				<div><?= Txt::trad("description") ?></div>
				<div><input type="text" name="description" value="<?= Ctrl::$agora->description ?>"></div>
			</div>

			<hr><!--SEPARATEUR-->

			<!--FOOTER HTML-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_footerHtml") ?></div>
				<div><textarea name="footerHtml"><?= Ctrl::$agora->footerHtml ?></textarea></div>
			</div>
			<!--LOGO FOOTER-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_logo") ?></div>
				<div>
					<img src="<?= Ctrl::$agora->pathLogoFooter() ?>" id="imgLogo">
					<select name="logo">
						<?php
						echo "<option value=''>".Txt::trad("byDefault")."</option>";
						if(!empty(Ctrl::$agora->logo))	{echo "<option value=\"".Ctrl::$agora->logo."\" selected>".Txt::trad("keepImg")."</option>";}
						echo "<option value='modify'>".Txt::trad("changeImg")."</option>";
						?>
					</select>
					<input type="file" name="logoFile" id="logoFile">
					<input type="text" name="logoUrl" id="logoUrl" value="<?= Ctrl::$agora->logoUrl ?>" placeholder="<?= Txt::trad("AGORA_logoUrl") ?>">
				</div>
			</div>
			<!--WALLPAPER-->
			<div class="objField">
				<div><?= Txt::trad("wallpaper") ?></div>
				<div><?= CtrlMisc::menuWallpaper(Ctrl::$agora->wallpaper) ?></div>
			</div>
			<!--LOGO CONNECT-->
			<div class="objField" title="<?= Txt::trad("AGORA_logoConnectTooltip") ?>">
				<div><?= Txt::trad("AGORA_logoConnect") ?></div>
				<div>
					<img src="<?= Ctrl::$agora->pathLogoConnect() ?>" id="imgLogoConnect">
					<?php
					//Logo spécifié : selectionne "conserver" / "modifier" / "supprimer"
					if(!empty(Ctrl::$agora->logoConnect)){
						echo "<select name=\"logoConnect\">
								<option value=\"".Ctrl::$agora->logoConnect."\">".Txt::trad("keepImg")."</option>
								<option value='modify'>".Txt::trad("changeImg")."</option>
								<option value=''>".Txt::trad("delete")."</option>
							  </select>";
					}
					?>
					<input type="file" name="logoConnectFile" id="logoConnectFile">
				</div>
			</div>
			<!--SKIN COLOR-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_skin") ?></div>
				<div>
					<select name="skin">
						<option value="white"><?= Txt::trad("AGORA_white") ?></option>
						<option value="black" <?= Ctrl::$agora->skin=="black"?"selected":null ?>><?= Txt::trad("AGORA_black") ?></option>
					</select>
				</div>
			</div>
			<!--MODULE LABEL DISPLAY-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_moduleLabelDisplay") ?></div>
				<div>
					<select name="moduleLabelDisplay">
						<option value=""><?= Txt::trad("show") ?></option>
						<option value="hide" <?= Ctrl::$agora->moduleLabelDisplay=="hide"?"selected":null ?>><?= Txt::trad("hide") ?></option>
					</select>
				</div>
			</div>
			<!--MODULE LABEL DISPLAY-->
			<div class="objField">
				<div><?= Txt::trad("AGORA_folderDisplayMode") ?></div>
				<div>
					<select name="folderDisplayMode">
						<option value="block"><?= Txt::trad("displayMode_block") ?></option>
						<option value="line" <?= Ctrl::$agora->folderDisplayMode=="line"?"selected":null ?>><?= Txt::trad("displayMode_line") ?></option>
					</select>
				</div>
			</div>

			<hr><!--SEPARATEUR-->

			<!--LANG-->
			<div class="objField">
				<div><img src="app/img/earth.png"><?= Txt::trad("AGORA_lang") ?></div>
				<div><?= Txt::menuTrad("agora",Ctrl::$agora->lang) ?></div>
			</div>
			<!--TIMEZONE-->
			<div class="objField">
				<div><img src="app/img/earth.png"><?= Txt::trad("AGORA_timezone") ?></div>
				<div>
					<select name="timezone">
						<?php foreach(Tool::$tabTimezones as $tmpLabel=>$timezone)  {echo "<option value=\"".$timezone."\" ".($timezone==Tool::$tabTimezones[Ctrl::$curTimezone]?'selected':null).">[gmt ".($timezone>0?"+":"").$timezone."] ".$tmpLabel."</option>";}?>
					</select>
				</div>
			</div>
			<!--DISK SPACE (AUTO-HEBERGEMENT)-->
			<?php if(Req::isHost()==false){ ?>
			<div class="objField">
				<div><img src="app/img/diskSpace.png"><?= Txt::trad("AGORA_diskSpaceLimit") ?></div>
				<div><input type="text" name="limite_espace_disque" id="limite_espace_disque" value="<?= round((limite_espace_disque/File::sizeGo),2) ?>"> <?= Txt::trad("gigaOctet")?></div>
			</div>
			<?php } ?>
			<!--LOGS TIMEOUT-->
			<div class="objField" title="<?= Txt::trad("AGORA_logsTimeOutTooltip") ?>">
				<div><img src="app/img/log.png"><?= Txt::trad("AGORA_logsTimeOut") ?></div>
				<div>
					<select name="logsTimeOut">
						<?php foreach([0,30,120,360,720] as $tmpTimeOut)  {echo "<option value='".$tmpTimeOut."' ".($tmpTimeOut==Ctrl::$agora->logsTimeOut?"selected":null).">".$tmpTimeOut."</option>";} ?>
					</select>
					<?= Txt::trad("days") ?>
				</div>
			</div>

			<hr><!--SEPARATEUR-->

			<!--LIKES-->
			<div class="objField">
				<div><img src="app/img/usersLike_like.png"><?= Txt::trad("AGORA_usersLikeLabel") ?></div>
				<div>
					<select name="usersLike">
						<option value="likeSimple"><?= Txt::trad("AGORA_usersLike_likeSimple") ?> <img src="app/img/usersLike_like.png"></option>
						<option value="likeOrNot" <?= Ctrl::$agora->usersLike=="likeOrNot"?"selected":null ?>><?= Txt::trad("AGORA_usersLike_likeOrNot") ?></option>
						<option value="" <?= empty(Ctrl::$agora->usersLike)?"selected":null ?>><?= Txt::trad("no") ?></option>
					</select>
				</div>
			</div>
			<!--COMMENTS-->
			<div class="objField">
				<div><img src="app/img/usersComment.png"><?= Txt::trad("AGORA_usersCommentLabel") ?></div>
				<div>
					<select name="usersComment">
						<option value="1"><?= Txt::trad("yes") ?></option>
						<option value="0" <?= empty(Ctrl::$agora->usersComment)?"selected":null ?>><?= Txt::trad("no") ?></option>
					</select>
				</div>
			</div>
			<!--MESSENGER ENABLED/DISABLED-->
			<div class="objField">
				<div><img src="app/img/messenger.png"><?= Txt::trad("AGORA_messengerDisabled") ?></div>
				<div>
					<select name="messengerDisabled">
						<option value=""><?= Txt::trad("yes") ?></option>
						<option value="1" <?= !empty(Ctrl::$agora->messengerDisabled)?"selected":null ?>><?= Txt::trad("no") ?></option>
					</select>
				</div>
			</div>
			<!--PERSONS SORT-->
			<div class="objField">
				<div><img src="app/img/user/iconSmall.png"><?= Txt::trad("AGORA_personsSort") ?></div>
				<div>
					<select name="personsSort">
						<option value="firstName"><?= Txt::trad("firstName") ?></option>
						<option value="name" <?= Ctrl::$agora->personsSort=="name"?"selected":null ?>><?= Txt::trad("name") ?></option>
					</select>
				</div>
			</div>

			<hr><!--SEPARATEUR-->

			<!--SERVEURS JITSI (AUTO-HEBERGEMENT)-->
			<?php if(Req::isHost()==false){ ?>
			<div class="objField" title="<?= Txt::trad("AGORA_visioHostTooltip") ?>">
				<div><img src="app/img/visio.png"><?= Txt::trad("AGORA_visioHost") ?></div>
				<div><input type="text" name="visioHost" value="<?= Ctrl::$agora->visioHost ?>"></div>
			</div>
			<div class="objField" title="<?= Txt::trad("AGORA_visioHostAltTooltip") ?>">
				<div><img src="app/img/visio.png"><?= Txt::trad("AGORA_visioHostAlt") ?></div>
				<div><input type="text" name="visioHostAlt" value="<?= Ctrl::$agora->visioHostAlt ?>"></div>
			</div>
			<?php } ?>
			<!--MAP : OPENSTREETMAP / GOOGLE MAP-->
			<div class="objField" title="<?= Txt::trad("AGORA_mapToolTooltip") ?>">
				<div><img src="app/img/map.png"><?= Txt::trad("AGORA_mapTool") ?></div>
				<div>
					<select name="mapTool">
						<option value="leaflet">OpenStreetMap / Leaflet</option>
						<option value="gmap" <?= Ctrl::$agora->mapTool=="gmap"?"selected":null ?>>Google Map</option>
					</select>
				</div>
			</div>
			<!--GOOGLE MAP APIKEY (AUTO-HEBERGEMENT)-->
			<?php if(Req::isHost()==false){ ?>
			<div class="objField" id="mapApiKeyDiv" title="<?= Txt::trad("AGORA_mapApiKeyTooltip") ?>">
				<div><img src="app/img/map.png"><?= Txt::trad("AGORA_mapApiKey") ?></div>
				<div><input type="text" name="mapApiKey" value="<?= Ctrl::$agora->mapApiKey ?>"></div>
			</div>
			<?php } ?>
			<!--GOOGLE SIGNIN ENABLED/DISABLED-->
			<div class="objField" title="<?= Txt::trad("AGORA_gIdentityTooltip") ?>">
				<div><img src="app/img/google.png"><?= Txt::trad("AGORA_gIdentity") ?></div>
				<div>
					<select name="gIdentity">
						<option value="0"><?= Txt::trad("no") ?></option>
						<option value="1" <?= !empty(Ctrl::$agora->gIdentity)?"selected":null ?>><?= Txt::trad("yes") ?></option>
					</select>
				</div>
			</div>
			<!--GOOGLE SIGNIN "CLIENT ID" & PEOPLE "API KEY" (AUTO-HEBERGEMENT)-->
			<?php if(Req::isHost()==false){ ?>
			<div class="objField" id="gIdentityClientIdDiv" title="<?= Txt::trad("AGORA_gIdentityClientIdTooltip") ?>">
				<div><img src="app/img/google.png"><?= Txt::trad("AGORA_gIdentityClientId") ?></div>
				<div><input type="text" name="gIdentityClientId" value="<?= Ctrl::$agora->gIdentityClientId ?>"></div>
			</div>
			<div class="objField" title="<?= Txt::trad("AGORA_gPeopleApiKeyTooltip") ?>">
				<div><img src="app/img/google.png"><?= Txt::trad("AGORA_gPeopleApiKey") ?></div>
				<div><input type="text" name="gPeopleApiKey" value="<?= Ctrl::$agora->gPeopleApiKey ?>"></div>
			</div>
			<?php } ?>

			<!--PARAMETRAGE SMTP POUR L'ENVOI DE MAILS (AUTO-HEBERGEMENT)-->
			<?php if(Req::isHost()==false){ ?>
			<hr><!--SEPARATEUR-->
			<div class="objField" onclick="$('#smtpConfig').fadeToggle()">
				<div><img src="app/img/postMessage.png"> <?= Txt::trad("AGORA_smtpLabel") ?> <img src="app/img/arrowBottom.png"></div>
			</div>
			<fieldset id="smtpConfig" <?= empty(Ctrl::$agora->smtpHost)?"style='display:none'":null ?>>
				<div class="objField">
					<div><?= Txt::trad("AGORA_smtpHost") ?></div>
					<div><input type="text" name="smtpHost" value="<?= Ctrl::$agora->smtpHost ?>"></div>
				</div>
				<div class="objField" title="<?= Txt::trad("AGORA_smtpPortTooltip") ?>">
					<div><?= Txt::trad("AGORA_smtpPort") ?></div>
					<div><input type="text" name="smtpPort" value="<?= Ctrl::$agora->smtpPort ?>"></div>
				</div>
				<div class="objField" title="<?= Txt::trad("AGORA_smtpSecureTooltip") ?>">
					<div><?= Txt::trad("AGORA_smtpSecure") ?></div>
					<div><input type="text" name="smtpSecure" value="<?= Ctrl::$agora->smtpSecure ?>"></div>
				</div>
				<div class="objField">
					<div><?= Txt::trad("AGORA_smtpUsername") ?></div>
					<div><input type="text" name="smtpUsername" value="<?= Ctrl::$agora->smtpUsername ?>"></div>
				</div>
				<div class="objField">
					<div><?= Txt::trad("AGORA_smtpPass") ?></div>
					<div><input type="password" name="smtpPass" value="<?= Ctrl::$agora->smtpPass ?>"></div>
				</div>
				<div class="objField">
					<div><?= Txt::trad("AGORA_sendmailFrom") ?></div>
					<div><input type="text" name="sendmailFrom" value="<?= Ctrl::$agora->sendmailFrom ?>" placeholder="<?= Txt::trad("AGORA_sendmailFromPlaceholder") ?>"></div>
				</div>
			</fieldset>
			<?php } ?>

			<!--PARAMETRAGE LDAP-->
			<?php if(function_exists("ldap_connect")){ ?>
			<div class="objField" onclick="$('#ldapConfig').fadeToggle()" title="<?= Txt::trad("AGORA_ldapLabelTooltip") ?>">
				<div><img src="app/img/user/ldap.png"> <?= Txt::trad("AGORA_ldapLabel") ?> <img src="app/img/arrowBottom.png"></div>
			</div>
			<fieldset id="ldapConfig" <?= empty(Ctrl::$agora->ldap_server)?"style='display:none'":null ?>>
				<div class="objField" title="<?= Txt::trad("AGORA_ldapUriTooltip") ?>">
					<div><?= Txt::trad("AGORA_ldapUri") ?></div>
					<div><input type="text" name="ldap_server" value="<?= Ctrl::$agora->ldap_server ?>"></div>
				</div>
				<div class="objField" title="<?= Txt::trad("AGORA_ldapPortTooltip") ?>">
					<div><?= Txt::trad("AGORA_ldapPort") ?></div>
					<div><input type="text" name="ldap_server_port" value="<?= Ctrl::$agora->ldap_server_port ?>"></div>
				</div>
				<div class="objField" title="<?= Txt::trad("AGORA_ldapLoginTooltip") ?>">
					<div><?= Txt::trad("AGORA_ldapLogin") ?></div>
					<div><input type="text" name="ldap_admin_login" value="<?= Ctrl::$agora->ldap_admin_login ?>"></div>
				</div>
				<div class="objField">
					<div><?= Txt::trad("AGORA_ldapPass") ?></div>
					<div><input type="password" name="ldap_admin_pass" value="<?= Ctrl::$agora->ldap_admin_pass ?>"></div>
				</div>
				<div class="objField" title="<?= Txt::trad("AGORA_ldapDnTooltip") ?>">
					<div><?= Txt::trad("AGORA_ldapDn") ?></div>
					<div><input type="text" name="ldap_base_dn" value="<?= Ctrl::$agora->ldap_base_dn ?>"></div>
				</div>
			</fieldset>
			<?php } ?>

			<hr><!--SEPARATEUR-->

			<!--VALIDATION DU FORMULAIRE-->
			<?= Txt::submitButton() ?>
		</form>
	</div>
</div>