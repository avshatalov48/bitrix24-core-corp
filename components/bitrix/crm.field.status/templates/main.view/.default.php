<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;

$result = [];
$statusList = CCrmStatus::GetStatusList($arResult['userField']['SETTINGS']['ENTITY_TYPE']);
foreach($arResult['value'] as $value)
{
	$str = (isset($statusList[$value]) ? HtmlFilter::encode($statusList[$value]) : '&nbsp;');
	$result[] = '<span class="field-item" data-id="' . HtmlFilter::encode($value) . '">' . $str . '</span>';
}
?>
<span class="fields field-wrap">
	<?= implode('', $result) ?>
</span>