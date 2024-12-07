<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var SignStartComponent $component */
$component->setMenuIndex('sign_index');

/** @var CMain $APPLICATION */
/** @var array $arParams */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.kanban',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_ID' => $arParams['ENTITY_ID'],
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent(),
);

$APPLICATION->setTitle(Loc::getMessage('SIGN_CMP_START_TPL_DOCS_TITLE'));