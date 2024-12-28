<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CModule::includeModule('biconnector');

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
			'DESCRIPTION' => $arResult['ERROR_DESCRIPTIONS'][0] ?? null,
		]
	);

	return;
}

CJSCore::init(['translit']);

Extension::load([
	'biconnector.dataset-import',
]);

Toolbar::deleteFavoriteStar();
$articleCode = (int)($arResult['helpdeskCode'] ?? 0);
if ($articleCode > 0)
{
	Toolbar::addButton(
		new Buttons\Button(
			[
				'color' => Buttons\Color::LIGHT_BORDER,
				'size'  => Buttons\Size::MEDIUM,
				'click' => new Buttons\JsCode(
					"top.BX.Helper.show('redirect=detail&code={$articleCode}');"
				),
				'dataset' => [
					'toolbar-collapsed-icon' => Buttons\Icon::INFO,
				],
				'text' => Loc::getMessage('DATASET_IMPORT_HELP'),
			]
		)
	);
}
if ($arParams['datasetId'])
{
	$APPLICATION->SetTitle(Loc::getMessage('DATASET_IMPORT_EDIT_TITLE'));
}
else
{
	$APPLICATION->SetTitle(Loc::getMessage('DATASET_IMPORT_TITLE'));
}
?>
<div id="app-root"></div>
<script>
	const initialData = <?= Json::encode($arResult['initialData']) ?>;
	const appParams = <?= Json::encode($arResult['appParams']) ?>;

	const app = BX.BIConnector.DatasetImport.AppFactory.getApp('<?= CUtil::JSescape($arParams['sourceId']) ?>', initialData, appParams);
	app.mount('#app-root');
</script>
