<?php
use Bitrix\Main\Context;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

Loc::loadMessages(__DIR__ . '/template.php');
$text = $arResult['FORM']['RESULT_SUCCESS_TEXT'] ?: Loc::getMessage('CRM_WEBFORM_FILL_RESULT_SENT');
?>
<div class="crm-webform-success-block">
	<?=htmlspecialcharsbx($text)?>
</div>