<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Service\Container;

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$categoryId = 0;
$entityId = isset($arResult['VARIABLES']['contact_id']) ? (int)$arResult['VARIABLES']['contact_id'] : 0;
if ($entityId > 0)
{
	$categoryId = (int)Container::getInstance()
		->getFactory(CCrmOwnerType::Contact)
		->getItemCategoryId($entityId)
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
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_ID' => $entityId,
			'ENABLE_TITLE_EDIT' => false,
			'EXTRAS' => [
				'CATEGORY_ID' => $categoryId,
				'ANALYTICS' => [
					'c_section' => $categoryId === 0 ? Dictionary::SECTION_CONTACT : Dictionary::SECTION_CATALOG_CONTRACTOR_CONTACT,
					'c_sub_section' => Dictionary::SUB_SECTION_DETAILS,
				],
			],
		]
	);
}
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');
	$entityCategoryId = $entityId <= 0 ? $categoryId : null;
	$viewCategoryId = $categoryId;

	$script = CCrmViewHelper::getDetailFrameWrapperScript(
		CCrmOwnerType::Contact,
		$entityId,
		$entityCategoryId,
		$viewCategoryId
	);

	echo $script;
}
