<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
\Bitrix\Main\UI\Extension::load(['sidepanel']);
?>
<div class="rpa-automation-base">
<?php
$APPLICATION->IncludeComponent('bitrix:bizproc.automation', '', [
	'DOCUMENT_TYPE' => $arResult['DOCUMENT_TYPE'],
	'DOCUMENT_ID'                   => null,
	'DOCUMENT_CATEGORY_ID'          => null,
	'WORKFLOW_EDIT_URL'             => null,//'/bizproc/script/template/?id=#ID#',
	//'CONSTANTS_EDIT_URL'            => '/bizproc/script/template/constants/?id=#ID#',
	//'PARAMETERS_EDIT_URL'            => '/bizproc/script/template/parameters/?id=#ID#',
	'MARKETPLACE_ROBOT_CATEGORY' => $arResult['DOCUMENT_TYPE'][0].'_bots',
	'MARKETPLACE_TRIGGER_PLACEMENT' => mb_strtoupper($arResult['DOCUMENT_TYPE'][0]).'_ROBOT_TRIGGERS',
	'IS_TEMPLATES_SCHEME_SUPPORTED' => true,
], $this);
?>
</div>