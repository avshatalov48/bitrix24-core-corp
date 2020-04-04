<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true){die();}
use Bitrix\Main\Localization\Loc;

global $APPLICATION;
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/main.ui.grid/templates/.default/style.css');

$this->SetViewTarget('inside_pagetitle');

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');

$filterId = $arResult['FILTER_ID'];

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.filter',
	'',
	[
		'GRID_ID'            => $arResult['GRID_ID'],
		'FILTER_ID'          => $filterId,
		'FILTER'             => $arResult['FILTER'],
		'ENABLE_LIVE_SEARCH' => true,
		'ENABLE_LABEL'       => true,
		'CONFIG'             => [
			'AUTOFOCUS' => false,
		],
	],
	$this->getComponent()
);
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
?>
	<div class="pagetitle-container">
		<a href="#" class="ui-btn ui-btn-primary ui-btn-icon-add crm-btn-toolbar-add"
		   title="" data-role="numerator-create-btn"><?= Loc::getMessage('CRM_NUMERATOR_LIST_CREATE_NUMERATOR'); ?></a>
	</div>
<?
$this->EndViewTarget();
$APPLICATION->SetTitle(Loc::getMessage('CRM_NUMERATOR_LIST_PAGE_TITLE'));
// main menu
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	[
		'ID'                    => 'CRM_NUMERATOR_DOCUMENT_LIST',
		'ACTIVE_ITEM_ID'        => '',
		'PATH_TO_COMPANY_LIST'  => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT'  => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST'  => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT'  => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_WIDGET'   => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_LIST'     => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT'     => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_DEAL_CATEGORY' => isset($arResult['PATH_TO_DEAL_CATEGORY']) ? $arResult['PATH_TO_DEAL_CATEGORY'] : '',
		'PATH_TO_LEAD_LIST'     => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT'     => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST'    => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT'    => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST'  => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT'  => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST'   => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL'   => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST'    => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST'  => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : '',
	],
	$component
);

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
		filterId: '<?= CUtil::JSEscape($filterId)?>'
	});
</script>