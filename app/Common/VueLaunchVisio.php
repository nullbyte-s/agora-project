<script>
////	Resize
lightboxSetWidth(650);

////	Lance la visio
$(function(){
	$("button#launchVisio").on("click",function(){
		////	Url de la visio : utilise le serveur alternatif ?
		visioURL="<?= $visioURL ?>";
		if($("#visioHostServer").exist() && $("#visioHostServer").val()=="alt")  {visioURL=visioURL.replace("<?= Ctrl::$agora->visioHost ?>","<?= Ctrl::$agora->visioHostAlt ?>");}
		////	Lance la visio
		window.open(visioURL);
	});
});
</script>

<style>
#visioDiv		{padding:30px; text-align:center; font-size:1.05em;}
#launchVisio	{width:300px; height:60px; border-radius:5px; font-size:1.1em;}
.visioInfos		{display:block; margin:30px;}
</style>

<div id="visioDiv">
	<?php
	////	Depuis le browser systeme d'Android : propose d'installer l'appli Jitsi 
	if(stristr($_SERVER['HTTP_USER_AGENT'],"Android"))
		{echo "<a onclick=\"window.open('android-app://org.jitsi.meet#omnispaceMobileApp_getFile')\" class='visioInfos'><img src='app/img/jitsi.png'> ".Txt::trad("VISIO_installJitsi")."</a>";}

	////	Bouton de lancement && Infos sur la visio
	echo "<button id='launchVisio'>".Txt::trad("VISIO_launchButton")." &nbsp; <img src='app/img/visioSmall.png'></button>
		  <a href='docs/VISIO.pdf' target='_blank' class='visioInfos' title=\"".Txt::trad("VISIO_launchTooltip2")."\"><img src='app/img/pdf.png'>&nbsp; ".Txt::trad("VISIO_launchTooltip")."</a>";

	////	Selection du serveur de visio
	if(!empty(Ctrl::$agora->visioHostAlt)){
		echo "<div title=\"".Txt::trad("VISIO_launchServerTooltip")."\">
				<img src='app/img/info.png'> &nbsp;
				<select id='visioHostServer'>
					<option value='main'>".Txt::trad("VISIO_launchServerMain")."</option>
					<option value='alt'>".Txt::trad("VISIO_launchServerAlt")."</option>
				</select>
			  </div>";
	}
	?>
</div>