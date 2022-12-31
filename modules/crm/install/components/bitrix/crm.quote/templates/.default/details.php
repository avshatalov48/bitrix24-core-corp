<?php
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
			'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
			'ENTITY_ID' => $arResult['VARIABLES']['quote_id'],
			'ENABLE_TITLE_EDIT' => true
		]
	);
}
else
{
	Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/workareainvisible.css');
	$entityId = isset($arResult['VARIABLES']['quote_id']) ? (int)$arResult['VARIABLES']['quote_id'] : 0;

	$script = CCrmViewHelper::getDetailFrameWrapperScript(
		CCrmOwnerType::Quote,
		$entityId
	);

	echo $script;
}