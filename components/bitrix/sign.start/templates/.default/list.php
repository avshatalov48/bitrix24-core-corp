<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var \CMain $APPLICATION */
/** @var array $arParams */

Loc::loadLanguageFile(__DIR__ . '/template.php');
?>

<?php
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.item.list',
		'POPUP_COMPONENT_PARAMS' => [
			'entityTypeId' => $arParams['ENTITY_ID'],
			'categoryId' => '0'
		],
		'USE_UI_TOOLBAR' => 'Y'
	],
	$this->getComponent()
);

$APPLICATION->setTitle(Loc::getMessage('SIGN_CMP_START_TPL_DOCS_TITLE'));
