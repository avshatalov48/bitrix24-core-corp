<?php

use Bitrix\Crm\Integration\Analytics\Dictionary;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.details.frame',
		'',
		[
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'ENTITY_ID' => $arResult['VARIABLES']['lead_id'],
			'ENABLE_TITLE_EDIT' => true,
			'EXTRAS' => [
				'ANALYTICS' => [
					'c_section' => Dictionary::SECTION_LEAD,
					'c_sub_section' => Dictionary::SUB_SECTION_DETAILS,
				],
			],
		]
	);
}
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');
	$entityId = isset($arResult['VARIABLES']['lead_id']) ? (int)$arResult['VARIABLES']['lead_id'] : 0;

	$script = CCrmViewHelper::getDetailFrameWrapperScript(CCrmOwnerType::Lead, $entityId);

	echo $script;
}