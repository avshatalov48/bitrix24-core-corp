<?php
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

$messages = Loc::loadLanguageFile(__FILE__);


CJSCore::Init([
	'ui.viewer',
	'disk',
	'disk_information_popups',
	'sidepanel',
	'ui.buttons',
	'disk.viewer.document-item',
]);
?>

<div>
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'AJAX_MODE' => 'Y',
			//Strongly required
			'AJAX_OPTION_JUMP'    => 'N',
			'AJAX_OPTION_STYLE'   => 'N',
			'AJAX_OPTION_HISTORY' => 'N',

			'GRID_ID' => $arResult['VERSION_GRID']['ID'],
			'CURRENT_URL' => $APPLICATION->GetCurPageParam("", array("action")),
			'HEADERS' => $arResult['VERSION_GRID']['HEADERS'],
			'SORT' => $arResult['VERSION_GRID']['SORT'],
			'SORT_VARS' => $arResult['VERSION_GRID']['SORT_VARS'],
			'ROWS' => $arResult['VERSION_GRID']['ROWS'],

			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_ROW_CHECKBOXES" => false,
			"SHOW_ROW_ACTIONS_MENU" => true,
			"SHOW_GRID_SETTINGS_MENU" => true,
			"SHOW_NAVIGATION_PANEL" => false,
			"SHOW_PAGINATION" => true,
			"SHOW_SELECTED_COUNTER" => false,
			"SHOW_TOTAL_COUNTER" => true,
			"SHOW_PAGESIZE" => false,
			"SHOW_ACTION_PANEL" => false,

			'ALLOW_CONTEXT_MENU' => true,
		),
		$component
	);
	?>

</div>

<script type="text/javascript">
	BX(function () {

		BX.Disk['FileHistoryComponent_<?= $component->getComponentId() ?>'] = new BX.Disk.FileHistoryComponent({
			gridId: '<?= $arResult['VERSION_GRID']['ID'] ?>',
			withoutEventBinding: true,
			object: {
				id: <?= $arResult['FILE']['ID'] ?>
			}
		});
	});

	BX.message(<?=\Bitrix\Main\Web\Json::encode($messages)?>);
</script>