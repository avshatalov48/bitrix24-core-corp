<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || $this->__component->__parent->__name != "bitrix:webdav"):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/webdav/templates/.default/style.css');
endif;
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/webdav/imgshw.js');
?>
<?= GetMessage('WD_HELP_FULL_TEXT', array(
	'#TEMPLATEFOLDER#' => $templateFolder,
    '#BPHELP#' => !empty($arResult['BPHELP'])?
		GetMessage('WD_HELP_BPHELP_TEXT', array('#LINK#' => $arResult['BPHELP'])) : '',
	));
?>