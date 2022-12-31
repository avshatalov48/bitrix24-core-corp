<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$categoryId = 0;
if ($arResult['VARIABLES']['company_id'] > 0)
{
	$categoryId = (int)Container::getInstance()
		->getFactory(CCrmOwnerType::Company)
		->getItemCategoryId($arResult['VARIABLES']['company_id'])
	;
}
elseif (isset($_REQUEST['category_id']))
{
	$categoryId = (int)$_REQUEST['category_id'];
}

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.details.frame',
		'',
		[
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $arResult['VARIABLES']['company_id'],
			'ENABLE_TITLE_EDIT' => true,
			'EXTRAS' => ['CATEGORY_ID' => $categoryId],
		]
	);
}
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');
	$entityId = isset($arResult['VARIABLES']['company_id']) ? (int)$arResult['VARIABLES']['company_id'] : 0;
	$entityCategoryId = $entityId <= 0 ? $categoryId : null;
	$viewCategoryId = $categoryId;

	$script = CCrmViewHelper::getDetailFrameWrapperScript(
		CCrmOwnerType::Company,
		$entityId,
		$entityCategoryId,
		$viewCategoryId
	);

	echo $script;
}