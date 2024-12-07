<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Sign\Document\Entity\Smart;

/** @var array $arParams */
/** @var \CMain $APPLICATION */
/** @var SignStartComponent $component */

$urlAfterClose = ($component->getRequest('inlineCrm') === 'Y' || $component->getRequest('noRedirect') === 'Y')
	? ''
	: Smart::getEntityDetailUrlId()
;

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.master',
		'POPUP_COMPONENT_PARAMS' => [
			'CATEGORY_ID' => $component->getRequest('categoryId'),
			'PAGE_URL_EDIT' => $arParams['PAGE_URL_EDIT'],
			'VAR_DOC_ID' => 'docId',
			'VAR_STEP_ID' => 'stepId',
			'CRM_ENTITY_TYPE_ID' => $arParams['ENTITY_ID'],
			'ENTITY_TYPE_ID' => 'SMART',
			'OPEN_URL_AFTER_CLOSE' => $urlAfterClose
		],
		'PLAIN_VIEW' => true,
		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => false
	],
	$this->getComponent()
);
