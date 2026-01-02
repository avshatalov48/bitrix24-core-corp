<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponentTemplate $this */
/** @var \CrmDocumentNumeratorsListComponent $component */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/main.ui.grid/templates/.default/style.css');

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');
$APPLICATION->SetTitle(Loc::getMessage('CRM_NUMERATOR_LIST_PAGE_TITLE'));

$component->addTopPanel($this);
$component->addToolbar($this);

$arResult['GRID_DATA'] = $arColumns = [];
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

foreach ($arResult['ITEMS'] as &$item)
{
	$gridActions = [];
	if ($arResult['CAN_EDIT'] && $item['CAN_EDIT'])
	{
		$gridActions[] = [
			'ICONCLASS' => 'menu-popup-item-edit',
			'TITLE'     => GetMessage('CRM_NUMERATOR_LIST_EDIT_TITLE'),
			'TEXT'      => GetMessage('CRM_NUMERATOR_LIST_EDIT'),
			'ONCLICK'   => "BX.Crm.Numerator.List.prototype.onEditNumeratorClick('" . CUtil::JSEscape($item['ID']) . "','" . CUtil::JSEscape($item['TYPE']) . "')",
			'DEFAULT'   => true,
		];
	}

	$arResult['GRID_DATA'][] = [
		'id'       => $item['ID'],
		'actions'  => $gridActions,
		'data'     => $item,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns'  => [
			'NAME' => $item['CAN_EDIT']
				? '<a href="' . htmlspecialcharsbx($item['PATH_TO_EDIT']) .
				  '" target="_self" ' .
				  '" data-id="' . intval($item['ID']) .
				  '" data-role="link-title-for-' . intval($item['ID']) . '">' .
				  htmlspecialcharsbx($item['NAME']) . '</a>'
				: $item['NAME'],
		],
	];
}
unset($item);

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID'             => $arResult['GRID_ID'],
		'MESSAGES'            => $arResult['MESSAGES'],
		'AJAX_MODE'           => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'HEADERS'             => $arResult['HEADERS'],
		'SORT'                => $arResult['SORT'],
		'SORT_VARS'           => $arResult['SORT_VARS'],
		'ROWS'                => $arResult['GRID_DATA'],
		'FOOTER'              =>
			[
				[
					'title' => GetMessage('CRM_ALL'),
					'value' => $arResult['ROWS_COUNT'],
				],
			],
		'EDITABLE'            => $arResult['CAN_EDIT'],
		'ACTIONS'             =>
			[
				'delete' => $arResult['CAN_DELETE'],
				'list'   => [],
			],
		'ACTION_ALL_ROWS'     => false,
		'NAV_OBJECT'          => $arResult['ITEMS'],
		'FORM_ID'             => $arResult['FORM_ID'],
		'TAB_ID'              => $arResult['TAB_ID'],
		'ACTION_PANEL'        => [
			'GROUPS' => [
				[
					'ITEMS' =>
						[
							$snippet->getEditButton(),
							$snippet->getRemoveButton(),
						],
				],
			],
		],
		'TOTAL_ROWS_COUNT'    => $arResult['ROWS_COUNT'],
	],
	$component
);
?>
<script>
	new BX.Crm.Numerator.List({
		filterId: '<?= CUtil::JSEscape($arResult['FILTER_ID'] ?? '')?>'
	});
</script>
