<?php
use Bitrix\Main\Context;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

$licenceText = '';
if($arResult['USER_CONSENT']['IS_USED'])
{
	$licenceText = nl2br(htmlspecialcharsbx($arResult['USER_CONSENT']['TEXT']));
}

?>
<div class="crm-webform-license-wrapper">
	<?=$licenceText?>
</div>