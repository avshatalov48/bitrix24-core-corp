<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllUser $USER */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load("ui.buttons");
?>
<div class="crm-report-vc-chart-config">
	<div class="crm-report-vc-chart-config-title"><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONFIG_TITLE')?></div>
	<a target="_top" href="/crm/tracking/" type="button" class="ui-btn ui-btn-primary"><?=Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONFIG_BTN')?></a>
</div>