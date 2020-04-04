<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

function WDRenderCellDragDrop($addClass, $colspan)
{
	$selectAttach = GetMessage("WDUF_SELECT_ATTACHMENTS");
	$descrAttach = GetMessage("WDUF_DROP_ATTACHMENTS");
	return <<<HTML
	<td class="wduf-selector wd-fa-add-file-light-cell wd-fa-add-file-from-main" colspan="{$colspan}" onmouseover="BX.addClass(this, 'wd-fa-add-file-light-hover')" onmouseout="BX.removeClass(this, 'wd-fa-add-file-light-hover')">
		<div class="wduf-uploader">
			<span class="wd-fa-add-file-light">
				<span class="wd-fa-add-file-light-text">
					<span class="wd-fa-add-file-light-title">
						<span class="wd-fa-add-file-light-title-text">{$selectAttach}</span>
					</span>
					<span class="wd-fa-add-file-light-descript">{$descrAttach}</span>
				</span>
			</span>
			<input class="wduf-fileUploader wd-test-file-light-inp {$addClass}" type="file" multiple='multiple' size='1' />
		</div>
	</td>
HTML;
}
function WDRenderCellSelectFromDocs()
{
	$selectAttach = GetMessage("WD_SELECT_FILE_LINK");
	$descrAttach = GetMessage("WD_SELECT_FILE_LINK_ALT");
	return <<<HTML
	<td class="wd-fa-add-file-light-cell">
		<span class="wd-fa-add-file-light">
			<span class="wd-fa-add-file-light-text">
				<span class="wd-fa-add-file-light-title">
					<span class="wd-fa-add-file-light-title-text wduf-selector-link">{$selectAttach}</span>
				</span>
				<span class="wd-fa-add-file-light-descript">{$descrAttach}</span>
			</span>
		</span>
	</td>
HTML;
}
function WDRenderCellCreateDoc()
{
	$docx = GetMessage('WDUF_CREATE_DOCX');
	$xlsx = GetMessage('WDUF_CREATE_XLSX');
	$pptx = GetMessage('WDUF_CREATE_PPTX');

	$dropdown = GetMessage('WDUF_CREATE_IN_SERVICE', array('#SERVICE#' => '<span class="wd-fa-add-file-editor">
		<span class="wd-fa-add-file-editor-text" onclick="WDOpenMenuCreateService(this); return false;">' . CWebDavTools::getServiceEditNameForCurrentUser() . '</span>
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
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return WDCreateDocument('docx')">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-doc"></span>
						<span class="wd-fa-add-file-editor-link">{$docx}</span>
					</a>
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return WDCreateDocument('xlsx');">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-csv"></span>
						<span class="wd-fa-add-file-editor-link">{$xlsx}</span>
					</a>
					<a class="wd-fa-add-file-editor-link-block" href="javascript:void(0)" onclick="return WDCreateDocument('pptx');">
						<span class="wd-fa-add-file-editor-icon feed-file-icon-ppt"></span>
						<span class="wd-fa-add-file-editor-link">{$pptx}</span>
					</a>
				</span>
			</span>
		</span>
	</td>
HTML;
}
function WDRenderTable($allowExtDocServices, $addClass)
{
	$table = '<table class="wduf-selector-table wd-fa-add-file-light-table">';
	if($allowExtDocServices)
	{
		$table .=
		'<tr>'
			. WDRenderCellDragDrop($addClass, 3) .
		'</tr>
		<tr>
			<td class="wd-fa-add-file-form-light-separate" colspan="3">
				<div class="wd-fa-add-file-form-light-spacer"></div>
			</td>
		</tr>
		<tr>'
			. WDRenderCellSelectFromDocs() .
			'<td class="wd-fa-add-file-form-light-separate-cell">
				<div class="wd-fa-add-file-form-light-spacer"></div>
			</td>'
			. WDRenderCellCreateDoc() .
		'</tr>';
	}
	else
	{
		$table .=
		'<tr>'
			. WDRenderCellSelectFromDocs() .
			'<td class="wd-fa-add-file-form-light-separate-cell">
				<div class="wd-fa-add-file-form-light-spacer"></div>
			</td>'
			. WDRenderCellDragDrop($addClass, 0) .
		'</tr>';
	}
	$table .= '</table>';

	return $table;
}