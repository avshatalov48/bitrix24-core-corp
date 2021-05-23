<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @global CMain $APPLICATION
 */

if ($arResult['INCLUDE_LANG'])
{
	\Bitrix\Main\Localization\Loc::loadLanguageFile(dirname(__FILE__)."/template.php");
}

CModule::IncludeModule("fileman");
$LHE = new CLightHTMLEditor();
?>
<div class="meeting-detail-title-wrap">
	<span class="meeting-ques-inp-label"><?=GetMessage('MI_EDIT_TITLE')?></span>
	<input type="text" name="ITEM_TITLE" value="<?=htmlspecialcharsbx($arResult['ITEM']['TITLE'])?>" class="meeting-ques-edit-inp" maxlength="255" />
</div>
<div id="meeting-detail-description" class="meeting-detail-description">
	<span class="meeting-ques-form-label"><?=GetMessage('MI_EDIT_DESCRIPTION')?></span>
	<div class="meeting-detail-description-form">
<?
$LHE->Show(array(
	'id' => "",
	'content' => $arResult['ITEM']['DESCRIPTION'],
	'inputName' => "ITEM_DESCRIPTION",
	'inputId' => "",
	'width' => "100%",
	'height' => "200px",
	'bUseFileDialogs' => false,
	'jsObjName' => "oMeetingLHE",
	'toolbarConfig' => Array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'ForeColor','FontList', 'FontSizeList',
		'RemoveFormat',
		'Quote', 'Code',
		'Image',
		'Table',
		'InsertOrderedList',
		'InsertUnorderedList',
		'SmileList',
		'Source'
	),
	//'smileCountInToolbar' => 4,
	'bResizable' => true,
	'bAutoResize' => true
));
?>
	</div>
	<div id="meeting-detail-files" class="meeting-detail-files meeting-detail-files-edit">
<?
$APPLICATION->IncludeComponent('bitrix:main.file.input', '', array(
		'INPUT_NAME' => 'ITEM_FILES',
		'INPUT_NAME_UNSAVED' => 'ITEM_FILES_TMP',
		'INPUT_VALUE' => array_keys($arResult['ITEM']['FILES']),
		'CONTROL_ID' => 'MEETING_ITEM_FILES_'.$arParams['ITEM_ID'],
		'MODULE_ID' => 'meeting'
	), null, array('HIDE_ICONS' => 'Y')
)
?>
	</div>
</div>
