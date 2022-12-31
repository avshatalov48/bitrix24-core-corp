<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponentTemplate $this */

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.details.frame',
		'',
		[
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
			'ENTITY_ID' => $arResult['VARIABLES']['deal_id'],
			'ENABLE_TITLE_EDIT' => true,
			'EXTRAS' => [
				'DEAL_CATEGORY_ID' => $arResult['VARIABLES']['category_id'] ?? -1
			]
		]
	);
}
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');
	$entityId = isset($arResult['VARIABLES']['deal_id']) ? (int)$arResult['VARIABLES']['deal_id'] : 0;
	$entityCategoryId =
		isset($arResult['VARIABLES']['category_id'])
			? (int)$arResult['VARIABLES']['category_id']
			: null
	;

	$viewCategoryId = CUserOptions::GetOption('crm', 'current_deal_category', null);
	if ($viewCategoryId !== null)
	{
		$viewCategoryId = (int)$viewCategoryId;
	}

	$script = CCrmViewHelper::getDetailFrameWrapperScript(
		CCrmOwnerType::Deal,
		$entityId,
		$entityCategoryId,
		$viewCategoryId
	);

	echo $script;
}
