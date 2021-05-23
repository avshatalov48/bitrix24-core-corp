<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\UserField\Types\ElementType;

$isFirst = true;
$categoryTitles = [];
foreach($arResult['value'] as $value)
{
	if(!$isFirst)
	{
		print '<br>';
	}

	list($prefix, $id) = explode('_', $value);

	if(!in_array($prefix, $categoryTitles, true))
	{
		$categoryTitles[] = $prefix;
		if (!$isFirst){
			print '<br>';
		}
		?>
		<span class="mobile-grid-data-span mobile-grid-crm-element-category-title">
			<?= Loc::getMessage(
				'CRM_ENTITY_TYPE_' . ElementType::getLongEntityType($prefix)
			) . ':' ?>
		</span>
		<br>
		<?php
	}
	?>
	<span class="mobile-grid-data-span">
		<?= HtmlFilter::encode($arResult['ELEMENT'][$value]['title']) ?>
	</span>
	<?php
	$isFirst = false;
}
