<?php

use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/configs/editor/index.php');
$APPLICATION->SetTitle(Loc::getMessage('TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'RELOAD_PAGE_AFTER_SAVE' => true,
		'PAGE_MODE' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'POPUP_COMPONENT_NAME' => 'bitrix:ui.form.config',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'ENTITY_TYPE_ID' => ($_GET['ENTITY_TYPE_ID'] ?? null),
			'MODULE_ID' => ($_GET['MODULE_ID'] ?? null),
		],
		//'POPUP_COMPONENT_PARENT' => $this->getComponent(),
		'CLOSE_AFTER_SAVE' => !empty($_SERVER['HTTP_REFERER']),
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');