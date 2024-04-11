<script>
/*******************************************************************************************
 *	CRÉATION D'UN USER À PARTIR D'UN CONTACT
 *******************************************************************************************/
function contactAddUser(typeId)
{
	if(confirm("<?= Txt::trad("CONTACT_createUserConfirm") ?>"))
		{redir("?ctrl=contact&action=contactAddUser&typeId="+typeId);}
}
</script>

<div id="pageFull">
	<div id="pageModuleMenu">
		<?= MdlContact::menuSelectObjects() ?>
		<div id="pageModMenu" class="miscContainer">
			<?php
			////	MENU D'AJOUT D'ELEMENTS
			if(Ctrl::$curContainer->addContentRight()){
				echo "<div class='menuLine' onclick=\"lightboxOpen('".MdlContact::getUrlNew()."');\"><div class='menuIcon'><img src='app/img/plus.png'></div><div>".Txt::trad("CONTACT_addContact")."</div></div>
					  <div class='menuLine' onclick=\"lightboxOpen('".MdlContactFolder::getUrlNew()."')\"><div class='menuIcon'><img src='app/img/folder/folderAdd.png'></div><div>".Txt::trad("addFolder")."</div></div>";
				if(Ctrl::$curUser->isSpaceAdmin())	{echo "<div class='menuLine' onclick=\"lightboxOpen('?ctrl=contact&action=EditPersonsImportExport&typeId=".Ctrl::$curContainer->_typeId."');\"><div class='menuIcon'><img src='app/img/dataImportExport.png'></div><div>".Txt::trad("importExport_contact")."</div></div>";}
				echo "<hr>";
			}
			////	ARBORESCENCE  &  MENU D'AFFICHAGE  &  MENU DE TRI  &  DESCRIPTION DU CONTENU
			echo CtrlObject::folderTreeMenu().MdlContact::menuDisplayMode().MdlContact::menuSort();
			echo "<div class='menuLine'><div class='menuIcon'><img src='app/img/info.png'></div><div>".Ctrl::$curContainer->folderContentDescription()."</div></div>";
			?>
		</div>
	</div>
	<div id="pageFullContent" class="<?= MdlContact::getDisplayMode()=="line"?"objLines":"objBlocks" ?>">
		<?php
		////	PATH DU DOSSIER COURANT & LISTE DES DOSSIERS
		echo CtrlObject::folderPathMenu(Txt::trad("CONTACT_addContact"),MdlContact::getUrlNew());
		echo CtrlObject::vueFolders();
		////	LISTE DES CONTACTS
		foreach($contactList as $tmpContact)
		{
			echo $tmpContact->objContainer("objPerson").$tmpContact->contextMenu().
				"<div class='objContentScroll'>
					<div class='objContent'>
						<div class='objIcon'>".$tmpContact->getImg(true,false,true)."</div>
						<div class='objLabel' onclick=\"lightboxOpen('".$tmpContact->getUrl("vue")."')\">
							".$tmpContact->getLabel("full")."
							<div class='objPersonDetails'>".$tmpContact->getFieldsValues(MdlContact::getDisplayMode())."</div>
						</div>
						<div class='objAutorDate'>".$tmpContact->autorDateLabel()."</div>
					</div>
				</div>
			</div>";
		}
		////	AUCUN CONTENU & AJOUTER
		if(empty(CtrlObject::vueFolders()) && empty($contactList)){
			$addElement=(Ctrl::$curContainer->addContentRight())  ?  "<div onclick=\"lightboxOpen('".MdlContact::getUrlNew()."')\"><img src='app/img/plus.png'> ".Txt::trad("CONTACT_addContact")."</div>"  :  null;
			echo "<div class='emptyContainer'>".Txt::trad("CONTACT_noContact").$addElement."</div>";
		}
		?>
	</div>
</div>