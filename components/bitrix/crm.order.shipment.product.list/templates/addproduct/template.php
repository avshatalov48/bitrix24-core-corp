<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/crm.entity.details/templates/.default/style.css');
$jsObjName = 'BX.Crm.Order.Shipment.ProductAdd.listObj_'.$arResult['GRID_ID'];
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$rows = [];

foreach($arResult['PRODUCTS'] as $product)
{
	$namePrefix = 'PRODUCT['.$product['BASKET_CODE'].']';

	$properties = '';
	if (!empty($product['PROPS']) && is_array($product['PROPS']))
	{
		$properties = '<div class="crm-order-product-control-additional">';
		foreach ($product['PROPS'] as $property)
		{
			if ($property['NAME'] === 'Catalog XML_ID' || $property['NAME'] === 'Product XML_ID')
				continue;

			$name = htmlspecialcharsbx($property['NAME']);
			$value = htmlspecialcharsbx($property['VALUE']);
			$properties .= "<div>{$name}: {$value}</div>";
		}
		$properties .= '</div>';
	}

	$columns = [
		'NAME' => '
			<div class="crm-order-product-container">
				<div class="crm-order-product-info-name">
					<a href="' . $product['EDIT_PAGE_URL'] . '" class="crm-order-product-info-name-text">'.htmlspecialcharsbx($product['NAME']).'</a>
					<input type="hidden" name="'.$namePrefix.'[BASKET_ID]" value="'.(float)$product['BASKET_ID'].'">
				</div>			
			</div>',
		'PICTURE' => '
			<div class="crm-order-product-container">
				<div class="crm-order-product-inner-container">
					<div class="crm-order-product-info-image-container">
						<img
							src="'.(!empty($product['PICTURE_URL']) ? $product['PICTURE_URL'] : $templateFolder.'/images/nofoto.svg').'"								
							alt="'.htmlspecialcharsbx($product['NAME']).'"
							class="crm-order-product-info-image">
					</div>
				</div>
			</div>',
		'PROPERTIES' => $properties,
		'QUANTITY' => '
			<div class="crm-order-product-control-amount">'.
				(float)$product['QUANTITY'].' '.$product['MEASURE_TEXT'].
			'</div>'
	];

	$actions = [];

	if($arResult['CAN_UPDATE_ORDER'])
	{
		$actions =
		[
			[
				'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SPLA_ADD_ITEM_TO_SHIPMENT'),
				'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SPLA_CHOOSE'),
				'ONCLICK' => $jsObjName.".onProductAdd('".$product['BASKET_ID']."')"
			]
		];
	}

	$rows[] = [
		'id' => $product['BASKET_ID'],
		'entity-type' => 'product',
		'data' => $product,
		'columns' => $columns,
		'actions' => $actions,
		'editable' => 'N'
	];
}

?><div class="crm-order-product-list-wrapper" id="crm-product-list-container"><?
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
//	'titleflex',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $rows,
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_MODE' => $arParams['AJAX_MODE'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => 'ORDER_PRODUCT',
				'gridId' => $arResult['GRID_ID'],
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			]
		]
	],
	$component
);
?>
<script>
		BX.ready(function ()
		{
			<?=$jsObjName?> = BX.Crm.Order.Shipment.ProductAdd.List.create(
				'',
				{
					gridId: '<?=$arResult['GRID_ID']?>'
				}
			);

			<?=$jsObjName?>.setFormId('<?=$arResult['FORM_ID']?>');
		});
</script>
</div><?