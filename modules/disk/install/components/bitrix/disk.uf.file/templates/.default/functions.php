<?php
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->IncludeLangFile("functions.php");
function DiskRenderCellImportDoc()
{
	$office365 = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_OFFICE365');
	$onedrive = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_ONEDRIVE');
	$gdrive = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_GDRIVE');
	$dropbox = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_DROPBOX');

	$title = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE');

	$googleSection = <<<HTML
	<a class="wd-fa-add-file-editor-link-block diskuf-selector-link-cloud" data-bx-doc-handler="gdrive" href="javascript:void(0)">
		<span class="wd-fa-add-file-editor-icon feed-file-icon-gdr"></span>
		<span class="wd-fa-add-file-editor-link">{$gdrive}</span>
	</a>
HTML;

	$handlersManager = \Bitrix\Disk\Driver::getInstance()->getDocumentHandlersManager();
	$foundGoogle = array_filter($handlersManager->getHandlersForImport(), function($handler){
		return $handler instanceof \Bitrix\Disk\Document\GoogleHandler;
	});

	$defaultCloud = \Bitrix\Disk\Document\GoogleHandler::getCode();
	if (!$foundGoogle)
	{
		$googleSection = '';
		$defaultCloud = \Bitrix\Disk\Document\Office365Handler::getCode();
	}

	return <<<HTML
	<td class="wd-fa-add-file-light-cell">
		<span class="wd-fa-add-file-light wd-test-file-create">
			<span class="wd-fa-add-file-light-text">
				<span class="wd-fa-add-file-light-title diskuf-selector-link-cloud" data-bx-doc-handler="{$defaultCloud}">
					<span class="wd-fa-add-file-light-title-text">$title</span>
				</span>
				<span class="wd-fa-add-file-editor-file">
					<a class="wd-fa-add-file-editor-link-block diskuf-selector-link-cloud" data-bx-doc-handler="office365" href="javascript:void(0)">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-office365"></span>
						<span class="wd-fa-add-file-editor-link">{$office365}</span>
					</a>
					{$googleSection}
					<a class="wd-fa-add-file-editor-link-block diskuf-selector-link-cloud" data-bx-doc-handler="dropbox" href="javascript:void(0)">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-drb"></span>
						<span class="wd-fa-add-file-editor-link">{$dropbox}</span>
					</a>
				</span>
			</span>
		</span>
	</td>
HTML;
}

?>
<script>
BX.message({
	DISK_FILE_EXISTS : "<?=GetMessage('WD_FILE_EXISTS')?>",
	DISK_CREATE_BLANK_URL : '<?= CUtil::JSUrlEscape($arResult['CREATE_BLANK_URL']) ?>',
	DISK_RENAME_FILE_URL : '<?= CUtil::JSUrlEscape($arResult['RENAME_FILE_URL']) ?>',
	DISK_THUMB_WIDTH : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["width"]?>',
	DISK_THUMB_HEIGHT : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["height"]?>',
	wd_service_edit_doc_default: "<?= CUtil::JSEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>"
});
</script>
<?