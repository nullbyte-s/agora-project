<?php
////	AFFICHE CHAQUE DOSSIERS DE LA VUE
foreach($foldersList as $tmpFolder)
{
	echo $tmpFolder->divContainer($objContainerClass).$tmpFolder->contextMenu();
	echo "<div class='objContent objFolders'>
				<div class='objIcon'><a href=\"".$tmpFolder->getUrl()."\"><img src=\"".$tmpFolder->iconPath()."\" title=\"".Txt::formatTooltip($tmpFolder->description)."\"></a></div>
				<div class='objLabel'><a href=\"".$tmpFolder->getUrl()."\" class='objLabelLink'>".Txt::reduce($tmpFolder->name,70,false)."</a></div>
				<div class='objDetails'>".$tmpFolder->folderOtherDetails()." ".$tmpFolder->folderContentDescription()."</div>
				<div class='objAutorDate'>".$tmpFolder->autorDateLabel()."</div>
			</div>
		</div>";
}
?>