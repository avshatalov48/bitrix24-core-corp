<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */


use Bitrix\Iblock\UserField\Types\ElementType;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\Collection;

$userField = $arResult['userField'];

$value = $arResult['value'];
if (!is_array($value))
{
	$value = [$value];
}
Collection::normalizeArrayValuesByInt($value, false);
if (!empty($value))
{
	ElementType::getEnumList(
		$userField,
		[
			'CURRENT_VALUES' => $value,
		]
	);

	$result = $userField['USER_TYPE']['FIELDS'] ?? [];
	$arResult['value'] =
		!empty($result)
			? HtmlFilter::encode(implode(', ', $result))
			: ElementType::getEmptyCaption($userField)
	;
}
else
{
	$arResult['value'] = ElementType::getEmptyCaption($userField);
}
