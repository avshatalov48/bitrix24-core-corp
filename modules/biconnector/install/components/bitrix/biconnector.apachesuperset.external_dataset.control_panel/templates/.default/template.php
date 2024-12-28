<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('ui');

/** @var \CMain $APPLICATION */
/** @var array $arResult */
?>

<div class="biconnector-external-dataset-top">
	<div class="biconnector-external-dataset-title-box">
		<span class="biconnector-external-dataset-title"><?= \Bitrix\Main\Localization\Loc::getMessage('BICONNECTOR_CONTROL_PANEL_TITLE') ?></span>
	</div>
</div>

<?php

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.buttons',
	'',
	[
		'ID' => 'biconnector_dataset_menu',
		'ITEMS' => $arResult['MENU_ITEMS'],
	],
);
