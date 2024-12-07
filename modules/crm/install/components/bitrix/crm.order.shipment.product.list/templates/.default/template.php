<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.fonts.ruble',
	'sale.barcode',
]);

Loc::loadMessages(__FILE__);

$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_grid.js');

$jsObjName = 'BX.Crm.Order.Shipment.Product.listObj_'.$arResult['GRID_ID'];
$barcodeWidgetCssPath = $templateFolder.'/barcodewidget.css';
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$basketCodes = [];
$rows = [];
$storeBarcodeInfo = [];
$isSetItems = $arResult['LOADING_SET_ITEMS'];
$isReadOnly = !$arResult['CAN_UPDATE_ORDER'];
$storeBarcodeTmplB =
	'<div data-ps-basketcode="#BASKET_CODE#" data-ps-store-id="#STORE_ID#" style="padding-bottom: 10px;">
	<button 
		id="barcode_button_#BASKET_CODE#_#STORE_ID#"
		class="ui-btn ui-btn-primary" 
		name="barcode" 
		onclick="'.$jsObjName.'.onBarcodeClick(\''.$arResult['ORDER_ID'].'\', \'#BASKET_ID#\', \'#STORE_ID#\', \''.CUtil::JSEscape($barcodeWidgetCssPath).'\'); return false;"
	>'.\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_SPLT_BARCODES').'</button></div>';

$storeBarcodeTmplI=
	'<div data-ps-basketcode="#BASKET_CODE#" data-ps-store-id="#STORE_ID#" style="padding-bottom: 10px;">
	<input 
		type="text" 
		class="crm-entity-widget-content-input" 
		name="PRODUCT[#BASKET_CODE#][BARCODE_INFO][#STORE_ID#][BARCODE][1][VALUE]" 
		autocomplete="off" 
		style="width: 120px;" 
		value="#BARCODE#"
		onchange="'.$jsObjName.'.onBarcodeChange(this); return false;"					
	>
	<input type="hidden" name="PRODUCT[#BASKET_CODE#][BARCODE_INFO][#STORE_ID#][BARCODE][1][ID]" value="#BARCODE_ID#"></div>';

foreach($arResult['PRODUCTS'] as $product)
{
	$namePrefix = 'PRODUCT[' . $product['BASKET_CODE'] . ']';

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

	//region NAME
	$setButtonHtml = '';
	if ($product['IS_SET_PARENT'] === 'Y')
	{
		$setButtonHtml = '<span class="main-grid-plus-button"></span>';
	}
	$innerNameHtml = '';
	if ($arResult['CAN_UPDATE_ORDER'])
	{
		$innerNameHtml = '
			<input type="hidden" name="'.$namePrefix.'[BASKET_ID]" value="'.(float)$product['BASKET_ID'].'">					
			<input type="hidden" name="'.$namePrefix.'[BASKET_CODE]" value="'.$product['BASKET_CODE'].'">
			<input type="hidden" name="'.$namePrefix.'[ORDER_DELIVERY_BASKET_ID]" value="'.$product['ORDER_DELIVERY_BASKET_ID'].'">
			<input type="hidden" name="'.$namePrefix.'[IS_SUPPORTED_MARKING_CODE]" value="'.$product['IS_SUPPORTED_MARKING_CODE'].'">
		';
	}
	$nameClass = "crm-order-product-container";
	if ($isSetItems)
	{
		$nameClass .= " crm-order-product-set-item-container";
	}
	//eng region

	//region AMOUNT
	$innerAmountHtml = '';
	$product['AMOUNT'] = (float)$product['AMOUNT'];
	if (!$isReadOnly && !$isSetItems)
	{
		$innerAmountHtml = '
			<input 
				id="crm-product-amount-'.$product['BASKET_CODE'].'" 
				name="'.$namePrefix.'[AMOUNT]" 
				style="width: 60px; display: inline;" 
				type="number" 
				value="'.$product['AMOUNT'].'" 
				class="crm-entity-widget-content-input"
				onchange="'.$jsObjName.'.onDataChanged(); return false;"					
			>
		';
	}
	else
	{
		$innerAmountHtml = $product['AMOUNT'].'<input 
			id="crm-product-amount-'.$product['BASKET_CODE'].'"			 
			name="'.$namePrefix.'[AMOUNT]" 
			type="hidden" 
			value="'.$product['AMOUNT'].'">';
	}
	//eng region

	$nameHtml =
		$arResult['ALLOW_SELECT_PRODUCT']
			? '<a href="'.$product['EDIT_PAGE_URL'].'" class="crm-order-product-info-name-text">'.htmlspecialcharsbx($product['NAME'] ?? '').'</a>'
			: htmlspecialcharsbx($product['NAME'] ?? '')
	;


	$columns = [
		'NAME' => '
			<div class="crm-order-product-container">
				<div class="'.$nameClass.'">
					'.$setButtonHtml.'
					'.$nameHtml.'
					'.$innerNameHtml.'
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
			'</div>',
		'AMOUNT' => '
			<div class="crm-order-product-control-amount">'.$innerAmountHtml.
				'&nbsp;'.$product['MEASURE_TEXT'].'
			</div>'
	];

	if(!empty($product['STORE_BARCODE_INFO']))
	{
		$store = '';
		$storeBarcode = '';
		$storeQuantity = '';
		$barcodesMulti = '';
		$storeRemainingQuantity = '';
		$storeBarcodeTmpl = '';
		$storeBarcodeInfo[$product['BASKET_CODE']] = $product['STORE_BARCODE_INFO'];
		
		$storeTmpl =
			'<div class="crm-order-product-store-container" data-ps-basketcode="#BASKET_CODE#" data-ps-store-id="#STORE_ID#" style="min-height: 39px;">'.
				'<span class="crm-order-product-store-name">
					<a href="/shop/settings/cat_store_edit/?ID=#STORE_ID#&lang='.LANGUAGE_ID.'&publicSidePanel=Y">#STORE_NAME#</a>
				</span>'.
				'<span class="crm-order-product-store-del-container" id="product-store-del-#STORE_ID#" style="display: #DEL_DISPLAY_TYPE#">';
		if(count($product['STORES']) > 1 && !$isReadOnly)
		{
			$storeTmpl .= '<div class="crm-order-product-store-del" onclick="'.$jsObjName.'.onStoreDeleteClick(\'#BASKET_CODE#\', \'#STORE_ID#\'); return false;" title="'.Loc::getMessage('CRM_ORDER_SPLT_DELETE_STORE').'"></div>';
		}

		$storeInnerHtml = '';
		if (!$isReadOnly)
		{
			$storeInnerHtml = '<input type="hidden" name="PRODUCT[#BASKET_CODE#][BARCODE_INFO][#STORE_ID#][STORE_ID]" value="#STORE_ID#">';
		}
		$storeTmpl .=
				'</span>'.
				$storeInnerHtml.
			'</div>';

		$editableStoreTmpl = str_replace('#DEL_DISPLAY_TYPE#', 'inline-block', $storeTmpl);

		$storeQuantityInnerHtml = '';
		if (!$isReadOnly)
		{
			$storeQuantityInnerHtml = '
			<input 
				type="number"
				min="0"
				max="#AMOUNT#"
				class="crm-entity-widget-content-input" 
				style="width: 60px; display: inline;" 
				name="PRODUCT[#BASKET_CODE#][BARCODE_INFO][#STORE_ID#][QUANTITY]" 
				value="#QUANTITY#"
				onchange="'.$jsObjName.'.onProductStoreQuantityChange(\'#BASKET_CODE#\', \'#STORE_ID#\', parseFloat(this.value)); return false;"			
			>';
		}
		else
		{
			$storeQuantityInnerHtml = '#QUANTITY#'.'<input 				 
				name="PRODUCT[#BASKET_CODE#][BARCODE_INFO][#STORE_ID#][QUANTITY]" 
				type="hidden" 
				value="#QUANTITY#">';
		}
		$storeQuantityTmpl =
			'<div class="crm-order-product-control-amount"  data-ps-basketcode="#BASKET_CODE#" data-ps-store-id="#STORE_ID#" style="padding-bottom: 10px;">'.
				$storeQuantityInnerHtml.
				' #MEASURE_TEXT#'.
			'</div>';

		$storeRemainingQuantityTmpl =
			'<div data-ps-basketcode="#BASKET_CODE#" data-ps-store-id="#STORE_ID#" style="min-height: 39px;">'.
				'<span class="crm-entity-widget-content-block-value">#AMOUNT# #MEASURE_TEXT#</span>'.
			'</div>';

		if($product['IS_SET_PARENT'] === 'Y')
		{
			$storeBarcodeTmpl = '';

			if(is_array($product['SET_ITEMS']))
			{
				foreach ($product['SET_ITEMS'] as $item)
				{
					if($item['IS_SUPPORTED_MARKING_CODE'] === 'Y' || !empty($item['BARCODE_INFO']))
					{
						$storeBarcodeTmpl = Loc::getMessage('CRM_ORDER_SPLT_ITEMS_HAVE_BARCODES');
						break;
					}
				}
			}
		}
		else
		{
			if ($arResult['CAN_UPDATE_ORDER'])
			{
				if($product['BARCODE_MULTI'] == 'Y' || $product['IS_SUPPORTED_MARKING_CODE'] == 'Y')
				{
					$storeBarcodeTmpl .= $storeBarcodeTmplB;
				}
				else
				{
					$storeBarcodeTmpl .=  $storeBarcodeTmplI;
				}
			}
			else
			{
				$storeBarcodeTmpl = '#BARCODE_ID#';
			}
		}

		$usedStoresCount = 0;

		foreach($product['STORE_BARCODE_INFO'] as $storeId => $fields)
		{
			if($fields['IS_USED'] != 'Y')
			{
				continue;
			}

			$storeItem = $storeTmpl;
			$storeQuantityItem = $storeQuantityTmpl;
			$storeBarcodeItem = $storeBarcodeTmpl;
			$storeRemainingQuantityItem = $storeRemainingQuantityTmpl;

			foreach($fields as $key => $value)
			{
				if(is_array($value))
				{
					continue;
				}

				$item = '#'.$key.'#';
				$value = htmlspecialcharsbx($value);
				$storeItem = str_replace($item, $value, $storeItem);
				$storeQuantityItem = str_replace($item, $value, $storeQuantityItem);
				$storeBarcodeItem = str_replace($item, $value, $storeBarcodeItem);
				$storeRemainingQuantityItem = str_replace($item, $value, $storeRemainingQuantityItem);
			}

			$store .= $storeItem;
			$storeQuantity .= $storeQuantityItem;
			$storeBarcode .= $storeBarcodeItem;
			$storeRemainingQuantity .= $storeRemainingQuantityItem;
			$usedStoresCount++;
		}

		if ($usedStoresCount <= 1)
		{
			$store = str_replace('#DEL_DISPLAY_TYPE#', 'none', $store);
		}
		else
		{
			$store = str_replace('#DEL_DISPLAY_TYPE#', 'inline-block', $store);
		}

		if(count($product['STORES']) > 1)
		{
			$class = "crm-order-product-store-add-item";

			if($usedStoresCount >= count($product['STORES']))
			{
				$class .= ' crm-order-product-store-storeadder-hidden';
			}

			if (!$isReadOnly)
			{
				$store .= '<a id="crm-shipment-product-storeadd-'.$product['BASKET_CODE'].'" class="'.$class.'" href="#" onclick="'.$jsObjName.'.onAddStoreClick(\''.$product['BASKET_CODE'].'\', this); return false;">'.Loc::getMessage('CRM_ORDER_SPLT_ADD_STORE').'</a>';
			}
		}

		$columns['STORE_ID'] = '<div id="crm-shipment-product-store-'.$product['BASKET_CODE'].'">'.$store.'</div>';
		$columns['STORE_QUANTITY'] = '<div id="crm-shipment-product-quantity-'.$product['BASKET_CODE'].'">'.$storeQuantity.'</div>';
		$columns['STORE_REMAINING_QUANTITY'] = '<div id="crm-shipment-product-rquantity-'.$product['BASKET_CODE'].'">'.$storeRemainingQuantity.'</div>';
		$columns['STORE_BARCODE'] = '<div id="crm-shipment-product-barcode-'.$product['BASKET_CODE'].'">'.$storeBarcode.'</div>';
	}

	$actions = [];

	if($arResult['CAN_UPDATE_ORDER'])
	{
		$actions =
		[
			[
				'TITLE' => Loc::getMessage('CRM_ORDER_SPLT_DELETE_ITEM_FROM_SHIPMENT'),
				'TEXT' => Loc::getMessage('CRM_ORDER_SPLT_TO_REMOVE'),
				'ONCLICK' => $jsObjName.".onProductDelete('".$product['BASKET_CODE']."')"
			]
		];
	}

	$rows[] = [
		'id' => $product['BASKET_CODE'],
		'data' => $product,
		'columns' => $columns,
		'actions' => $actions,
		'parent_id' => (\Bitrix\Main\Grid\Context::isInternalRequest() && $product['IS_SET_ITEM'] === 'Y') ? "{$product['PARENT_BASKET_ID']}" : 0,
		'editable' => !$product['IS_SET_ITEM'] && !$isReadOnly,
		'has_child' => true
	];

	$basketCodes[] = $product['BASKET_CODE'];
}

$cols = [];

foreach($arResult['VISIBLE_COLUMNS'] as $column)
{
	$item = ["column" => $column];
	$cols[] = $item;
}

if (!$isReadOnly && $arResult['DEDUCTED'] != 'Y')
{
	$buttons = [
		[
			'TEXT' => Loc::getMessage('CRM_ORDER_SPLT_ADD_PRODUCT'),
			'TITLE' => Loc::getMessage('CRM_ORDER_SPLT_ADD_PRODUCT'),
			'ICON' => 'btn-new',
			'ONCLICK' => $jsObjName.".showAddProductDialog({lang: '".LANGUAGE_ID."', siteId: '".$arResult['ORDER_SITE_ID']."', orderId: '".$arResult['ORDER_ID']."'});"
		]

		/*
		[
			'TEXT' => Loc::getMessage('CRM_ORDER_SPLT_FIND_BY_BARCODE'),
			'TITLE' => Loc::getMessage('CRM_ORDER_SPLT_FIND_BY_BARCODE'),
			'ONCLICK' => "alert('Finding...')"
		]
		*/
	];

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		[
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => $buttons
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);
}
?>

<div class="crm-order-product-list-wrapper" id="crm-product-list-container"><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
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
		'AJAX_LOADER' => $arParams['AJAX_LOADER'] ?? null,
		'ENABLE_ROW_COUNT_LOADER' => true,
		"SHOW_CHECK_ALL_CHECKBOXES" => false,
		"SHOW_ROW_CHECKBOXES" => false,
		"SHOW_NAVIGATION_PANEL" => false,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'SHOW_ROW_ACTIONS_MENU' => !$isSetItems,
		'ENABLE_COLLAPSIBLE_ROWS' => true,
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => 'ORDER_PRODUCT',
				'gridId' => $arResult['GRID_ID'],
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.shipment.product.list/list.ajax.php?siteID=' . SITE_ID . '&' . bitrix_sessid_get(),
				'loaderData' => $arParams['AJAX_LOADER'] ?? null
			],
			'MESSAGES' => [
				'deletionDialogTitle' => Loc::getMessage('CRM_ORDER_SPLT_DELETE_ITEM'),
				'deletionDialogMessage' => Loc::getMessage('CRM_ORDER_SPLT_ARE_YOU_SURE_YOU_WANT'),
				'deletionDialogButtonTitle' => Loc::getMessage('CRM_ORDER_SPLT_TO_REMOVE')
			]
		]
	],
	$component
);

	unset($arParams['SHIPMENT'], $arParams['~SHIPMENT']);
//region PRODUCTS LIST SCRIPTS
?>

<script>

	BX.ready(function ()
		{
			BX.message({
				CRM_ORDER_SPLT_CHOOSE_STORE: '<?=Loc::getMessage("CRM_ORDER_SPLT_CHOOSE_STORE")?>',
				CRM_ORDER_SPLT_CHOOSE: '<?=Loc::getMessage("CRM_ORDER_SPLT_CHOOSE")?>',
				CRM_ORDER_SPLT_CLOSE: '<?=Loc::getMessage("CRM_ORDER_SPLT_CLOSE")?>',
				CRM_ORDER_SPLT_BARCODE: '<?=Loc::getMessage("CRM_ORDER_SPLT_BARCODE")?>',
				CRM_ORDER_SPLT_MARKING_CODE: '<?=Loc::getMessage("CRM_ORDER_SPLT_MARKING_CODE")?>'
			});
			<?=$jsObjName?> = BX.Crm.Order.Shipment.Product.List.create(
				"<?=$arResult['GRID_ID']?>",
				{
					serviceUrl: '<?=$arResult['SERVICE_URL']?>',
					barcodesDialogUrl: '/bitrix/components/bitrix/crm.order.shipment.product.barcodes/slider.ajax.php?<?=bitrix_sessid_get()?>',
					addProductUrl: '<?=$arResult['ADD_PRODUCT_URL']?>',
					siteId: '<?=$arResult['ORDER_SITE_ID']?>',
					languageId: '<?=LANGUAGE_ID?>',

					<?if(!empty($storeBarcodeInfo)):?>
						storeTmpl: '<?=CUtil::JSEscape($editableStoreTmpl)?>',
						storeQuantityTmpl: '<?=CUtil::JSEscape($storeQuantityTmpl)?>',
						storeBarcodeTmplI: '<?=CUtil::JSEscape($storeBarcodeTmplI)?>',
						storeBarcodeTmplB: '<?=CUtil::JSEscape($storeBarcodeTmplB)?>',
						storeRemainingQuantityTmpl: '<?=CUtil::JSEscape($storeRemainingQuantityTmpl)?>',
						storeBarcodeInfo: <?=CUtil::PhpToJSObject($storeBarcodeInfo)?>,
					<?endif;?>

					params: <?=CUtil::PhpToJSObject(
						array_filter(
							$arParams,
							function($k){
								return mb_strpos($k, '~') !== 0;
							},
							ARRAY_FILTER_USE_KEY
					))?>,
					componentName: '<?=$component->getName()?>'
				}
			);

//			BX.addCustomEvent(window, "BX.Crm.EntityEditor:onSave", function(args){console.log('BX.Crm.EntityEditor:onSave'); console.log(args);});

			<?=$jsObjName?>.setFormId('<?=$arResult['FORM_ID']?>');

			if(window['EntityEditorOrderShipmentController'])
			{
				<?=$jsObjName?>.setController(window['EntityEditorOrderShipmentController']);

				<?if($arResult['SHOW_TOOL_PANEL'] == 'Y'):?>
					<?=$jsObjName?>.markAsChanged();
				<?endif;?>
			}
			else
			{
				BX.addCustomEvent(window, "onEntityEditorOrderShipmentControllerInit", function(controller){
					<?=$jsObjName?>.setController(controller);

					<?if($arResult['SHOW_TOOL_PANEL'] == 'Y'):?>
						<?=$jsObjName?>.markAsChanged();
					<?endif;?>
				});
			}

			if (BX.Main.gridManager !== undefined)
			{
				var grid = BX.Main.gridManager.getInstanceById("<?=$arResult['GRID_ID']?>");
				grid.getUserOptions().setExpandedRows = function(){};
			}
		});

</script>
</div>

<?
//endregion
