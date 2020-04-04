<?php
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->IncludeLangFile("functions.php");
function DiskRenderCellDragDrop($addClass, $colspan)

{
	$selectAttach = GetMessage("WDUF_SELECT_ATTACHMENTS");
	$descrAttach = GetMessage("WDUF_DROP_ATTACHMENTS");
	return <<<HTML
	<td class="diskuf-selector wd-fa-add-file-light-cell wd-fa-add-file-from-main" colspan="{$colspan}" onmouseover="BX.addClass(this, 'wd-fa-add-file-light-hover')" onmouseout="BX.removeClass(this, 'wd-fa-add-file-light-hover')">
		<div class="diskuf-uploader">
			<span class="wd-fa-add-file-light">
				<span class="wd-fa-add-file-light-text">
					<span class="wd-fa-add-file-light-title">
						<span class="wd-fa-add-file-light-title-text">{$selectAttach}</span>
					</span>
					<span class="wd-fa-add-file-light-descript">{$descrAttach}</span>
				</span>
			</span>
			<input class="diskuf-fileUploader wd-test-file-light-inp {$addClass}" type="file" multiple='multiple' size='1' />
		</div>
	</td>
HTML;
}
function DiskRenderCellSelectFromDocs()
{
	$selectAttach = GetMessage("WD_SELECT_FILE_LINK");
	$descrAttach = GetMessage("WD_SELECT_FILE_LINK_ALT");
	return <<<HTML
	<td class="wd-fa-add-file-light-cell">
		<span class="wd-fa-add-file-light">
			<span class="wd-fa-add-file-light-text">
				<span class="wd-fa-add-file-light-title">
					<span class="wd-fa-add-file-light-title-text diskuf-selector-link">{$selectAttach}</span>
				</span>
				<span class="wd-fa-add-file-light-descript">{$descrAttach}</span>
			</span>
		</span>
	</td>
HTML;
}
function DiskRenderCellCreateDoc($documentServiceName)
{
	$docx = GetMessage('WDUF_CREATE_DOCX');
	$xlsx = GetMessage('WDUF_CREATE_XLSX');
	$pptx = GetMessage('WDUF_CREATE_PPTX');

	$dropdown = GetMessage('WDUF_CREATE_IN_SERVICE', array('#SERVICE#' => '<span class="wd-fa-add-file-editor">
		<span class="wd-fa-add-file-editor-text" onclick="DiskOpenMenuCreateService(this); return false;">' . $documentServiceName . '</span>
		<span class="wd-fa-add-file-editor-arrow"></span>
	</span>'));

	return <<<HTML
	<td class="wd-fa-add-file-light-cell">
		<span class="wd-fa-add-file-light wd-test-file-create">
			<span class="wd-fa-add-file-light-text">
				<span class="wd-fa-add-file-light-title">
					<span class="wd-fa-add-file-light-title-text">{$dropdown}</span>
				</span>
				<span class="wd-fa-add-file-editor-file">
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return DiskCreateDocument('docx', event);">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-doc"></span>
						<span class="wd-fa-add-file-editor-link">{$docx}</span>
					</a>
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return DiskCreateDocument('xlsx', event);">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-csv"></span>
						<span class="wd-fa-add-file-editor-link">{$xlsx}</span>
					</a>
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return DiskCreateDocument('pptx', event);">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-ppt"></span>
						<span class="wd-fa-add-file-editor-link">{$pptx}</span>
					</a>
				</span>
			</span>
		</span>
	</td>
HTML;
}
function DiskRenderCellImportDoc()
{
	$office365 = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_OFFICE365');
	$onedrive = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_ONEDRIVE');
	$gdrive = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_GDRIVE');
	$dropbox = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE_SERVICE_DROPBOX');

	$title = Loc::getMessage('DISK_UF_FILE_CLOUD_IMPORT_TITLE');

	return <<<HTML
	<td class="wd-fa-add-file-light-cell">
		<span class="wd-fa-add-file-light wd-test-file-create">
			<span class="wd-fa-add-file-light-text">
				<span class="wd-fa-add-file-light-title diskuf-selector-link-cloud" data-bx-doc-handler="gdrive">
					<span class="wd-fa-add-file-light-title-text">$title</span>
				</span>
				<span class="wd-fa-add-file-editor-file">
					<a class="wd-fa-add-file-editor-link-block diskuf-selector-link-cloud" data-bx-doc-handler="office365" href="javascript:void(0)">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-office365"></span>
						<span class="wd-fa-add-file-editor-link">{$office365}</span>
					</a>
					<a class="wd-fa-add-file-editor-link-block diskuf-selector-link-cloud" data-bx-doc-handler="gdrive" href="javascript:void(0)">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-gdr"></span>
						<span class="wd-fa-add-file-editor-link">{$gdrive}</span>
					</a>
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
function DiskRenderTable($allowExtDocServices, $addClass, $documentServiceName)
{
	$table = '<table class="diskuf-selector-table wd-fa-add-file-light-table">';
	if($allowExtDocServices)
	{
		$table .=
		'<tr>'
			. DiskRenderCellDragDrop($addClass, 0) .
		'
	    <td class="wd-fa-add-file-form-light-separate-cell">
            <div class="wd-fa-add-file-form-light-spacer"></div>
        </td>'
            . DiskRenderCellSelectFromDocs() .
        '</tr>
        <tr>
            <td colspan="3" class="wd-fa-add-file-form-light-separate">
                <div class="wd-fa-add-file-form-light-spacer"></div>
            </td>
        </tr>

		<tr>'
			. DiskRenderCellImportDoc() . '
			<td class="wd-fa-add-file-form-light-separate-cell">
				<div class="wd-fa-add-file-form-light-spacer"></div>
			</td>'
			. DiskRenderCellCreateDoc($documentServiceName) .
		'</tr>';
	}
	else
	{
		$table .=
		'<tr>'
			. DiskRenderCellSelectFromDocs() .
			'<td class="wd-fa-add-file-form-light-separate-cell">
				<div class="wd-fa-add-file-form-light-spacer"></div>
			</td>'
			. DiskRenderCellDragDrop($addClass, 0) .
		'</tr>';
	}
	$table .= '</table>';

	return $table;
}
?>
<script>
BX.message({
	DISK_FILE_LOADING : "<?=GetMessageJS('WD_FILE_LOADING')?>",
	DISK_FILE_EXISTS : "<?=GetMessage('WD_FILE_EXISTS')?>",
	DISK_ACCESS_DENIED : "<?=GetMessage('WD_ACCESS_DENIED')?>",
	DISK_CREATE_BLANK_URL : '<?= CUtil::JSUrlEscape($arResult['CREATE_BLANK_URL']) ?>',
	DISK_RENAME_FILE_URL : '<?= CUtil::JSUrlEscape($arResult['RENAME_FILE_URL']) ?>',
	DISK_TMPLT_THUMB : '<?=CUtil::JSEscape($thumb)?>',
	DISK_TMPLT_THUMB2 : '<?=CUtil::JSEscape($uploadedFile)?>',
	DISK_THUMB_WIDTH : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["width"]?>',
	DISK_THUMB_HEIGHT : '<?=\Bitrix\Disk\Uf\Controller::$previewParams["height"]?>',
	wd_service_edit_doc_default: "<?= CUtil::JSEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE']) ?>"
});
</script>
<?