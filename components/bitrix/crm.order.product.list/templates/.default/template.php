<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Grid\Panel\Actions;
use \Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load("ui.fonts.ruble");

$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_grid.js');

$jsObjName = 'BX.Crm.Order.Product.listObj_'.$arResult['GRID_ID'];
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$basketCodes = [];
$rows = [];
$productSkuValues = [];
$basketItemsParams = [];
$currencyList = \CCrmCurrencyHelper::PrepareListItems();
$isSetItems = $arResult['IS_SET_ITEMS'];
$isReadOnly = !$arResult['CAN_UPDATE_ORDER'] || $isSetItems;
$code2Id = [];

foreach($arResult['PRODUCTS'] as $product)
{
	$namePrefix = 'PRODUCT['.$product['BASKET_CODE'].']';
	$code2Id[$product['BASKET_CODE']] = $product['PRODUCT_ID'];

	//extract SKU props
	$skuHtml = '';

	if(is_array($product['SKU_PROPS_POSSIBLE_VALUES']))
	{
		foreach($product['SKU_PROPS_POSSIBLE_VALUES'] as $pSkuId => $possibleSku)
		{
			$skuItemType = [];
			$skuItemHtml = '';

			if(is_array($arResult['IBLOCKS_SKU_PARAMS_ORDER']) && !empty($arResult['IBLOCKS_SKU_PARAMS_ORDER']))
			{
				foreach($arResult['IBLOCKS_SKU_PARAMS_ORDER'][$product['OFFERS_IBLOCK_ID']] as $skuId)
				{
					$sku = $arResult['IBLOCKS_SKU_PARAMS'][$product['OFFERS_IBLOCK_ID']][$skuId];
					$currentSkuValueId = $product['SKU_PROPS'][$skuId]['VALUE']['ID'];
					$productSkuValues[$product['PRODUCT_ID']][$skuId] = $currentSkuValueId;
					$basketItemsParams[$product['BASKET_CODE']] = [
						'PRODUCT_ID' => $product['PRODUCT_ID'],
						'OFFERS_IBLOCK_ID' => $product['OFFERS_IBLOCK_ID']
					];

					foreach($sku['ORDER'] as $item)
					{
						$found = false;

						foreach($possibleSku as $possibleSkuItem)
						{
							if($possibleSkuItem == $sku['VALUES'][$item]['ID'])
							{
								$found = true;
								break;
							}
						}
						$selected = ($currentSkuValueId == $sku['VALUES'][$item]['ID'] ? ' selected' : '');
						if(!$found || ($isReadOnly && !$selected))
						{
							continue;
						}

						$onclickAction = "";
						$className = "crm-order-product-properties-scu-list-item";
						if (!$isReadOnly)
						{
							$className .= $selected;
							$onclickAction = 'onclick="event.stopPropagation(); '.$jsObjName.'.onSkuSelect(\''.$product['BASKET_CODE'].'\', \''.$skuId.'\', \''.$sku['VALUES'][$item]['ID'].'\');"';
						}

						$itemId = $sku['VALUES'][$item]['ID'];
						$skuItemHtml .= '<li class="'.$className.'" '.$onclickAction.'>';

						if($sku['VALUES'][$item]['PICT'])
						{
							if(!isset($skuItemType['picture']))
							{
								$skuItemType['picture'] = true;
							}

							$skuItemHtml .= '<span 
								class="crm-order-product-properties-scu-list-item-inner" 
								style="background-image: url('.$sku['VALUES'][$item]['PICT'].');">
								</span>';

						}
						else
						{
							if(!isset($skuItemType['text']))
							{
								$skuItemType['text'] = true;
							}

							$skuItemHtml .= '<span class="crm-order-product-properties-scu-list-item-inner">'.
								htmlspecialcharsbx($sku['VALUES'][$item]['NAME']).
								'</span>';
						}

						$skuItemHtml .= '</li>';
					}
				}
			}

			$skuItemHtml = '
				<div class="crm-order-product-properties-scu-title">
					<div class="crm-order-product-properties-scu-title-text">'.
						htmlspecialcharsbx($arResult['IBLOCKS_SKU_PARAMS'][$product['OFFERS_IBLOCK_ID']][$pSkuId]['NAME']).
					'</div>
				</div>
				<div class="crm-order-product-properties-scu-inner">
					<ul class="crm-order-product-properties-scu-list">'.
						$skuItemHtml.
					'</ul>				
				</div>';

			switch(key($skuItemType))
			{
				case 'picture':
					$skuItemHtml = '
						<div class="crm-order-product-properties-scu crm-order-product-properties-scu-image">'.
							$skuItemHtml.
						'</div>';
					break;

				case 'text':
					$skuItemHtml = '
						<div class="crm-order-product-properties-scu crm-order-product-properties-scu-text">'.
						$skuItemHtml.
						'</div>';
					break;
			}

			$skuHtml .= $skuItemHtml;
		}

		$skuHtml = '<div class="crm-order-product-info-properties-container">'.$skuHtml.'</div>';
	}

	//region DISCOUNTS
	$discountsHtml = '';

	if(is_array($product['DISCOUNTS']) && !empty($product['DISCOUNTS']))
	{
		foreach($product['DISCOUNTS'] as $discountId => $discount)
		{
			$discountsInnerHtml = '';
			if (!$isReadOnly)
			{
				$discountsInnerHtml = '
					<span class="crm-order-product-control-discounts-list-item-input">
						<input type="hidden" name="DISCOUNTS[BASKET]['.$product['BASKET_CODE'].']['.$discountId.']" value="N">
						<input data-discount-id="'.$discountId.'" type="checkbox" name="DISCOUNTS[BASKET]['.$product['BASKET_CODE'].']['.$discountId.']" value="Y"'.($discount['APPLY'] == 'Y' ? ' checked' : '').' onchange="'.$jsObjName.'.onProductDiscountCheck(this, \''.$discountId.'\');">			
					</span>
				';
			}

			$discountsHtml .= '
				<div class="crm-order-product-control-discounts-list-item">
					<span class="crm-order-product-control-discounts-list-item-label">
						'.$discountsInnerHtml.'
						<a href="'.$discount['EDIT_PAGE_URL'].'" class="crm-order-product-control-discounts-list-item-label-text">'.$discount['NAME'].'</a>
					</span>
					<div class="crm-order-product-control-discounts-list-item-desc">'.$discount['DESCR'].'</div>
				</div>';
		}
	}
	elseif (!$isReadOnly && $product['CUSTOM_PRICE'] != 'Y')
	{
		$discountsHtml = Loc::getMessage('CRM_ORDER_PL_DISCOUNTS_ABSENT');

		if(!empty($product['MODULE']))
		{
			$discountsHtml .= ' <a href="/shop/settings/sale_discount_edit/?lang='.LANGUAGE_ID.'&LID='.$arResult['ORDER_SITE_ID'].'">'.Loc::getMessage('CRM_ORDER_PL_TUNE').'</a>';
		}
	}
	//endregion

	if (!$isReadOnly)
	{
		$priceInnerHtml = '<input id="crm-product-price-'.$product['BASKET_CODE'].'" name="'.$namePrefix.'[PRICE]" value="'.$product['FORMATTED_PRICE'].'" class="crm-order-product-control-amount-field"> '.$product['CURRENCY_NAME_SHORT'];
	}
	else
	{
		$priceInnerHtml = $product['FORMATTED_PRICE_WITH_CURRENCY'];
	}
	$priceColumn = '
		<div class="crm-order-product-info-price">
			<div class="crm-order-product-info-price-current">'.$priceInnerHtml.'</div>';

	if(abs(floatval($product['PRICE']) - floatval($product['BASE_PRICE'])) > 0.0001)
	{
		$priceColumn .= '<div class="crm-order-product-info-price-old">'.CCrmCurrency::MoneyToString($product['BASE_PRICE'], $product['CURRENCY']).'</div>';
	}

	$priceColumn .= '
			<div class="crm-order-product-info-price-desc">'.$product['NOTES'].'</div>
		</div>';

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

	$name = htmlspecialcharsbx($product['NAME']);
	$actionEditScript = ''; //For custom products
	$productEditUrl = ''; //For catalog products

	if(!empty($product['EDIT_PAGE_URL']))
	{
		$productEditUrl = $product['EDIT_PAGE_URL'];
		//$actionEditScript = "BX.SidePanel.Instance.open('".$productEditUrl."'); return;";
		$name = '<a href="'.$productEditUrl.'" data-product-id="'.$product['PRODUCT_ID'].'" data-product-field="name" class="crm-order-product-info-name-text">'.$name.'</a>';
		if ((int)$product['TYPE'] === \Bitrix\Crm\Order\BasketItem::TYPE_SET)
		{
			$name = '<span class="main-grid-plus-button"></span>'.$name;
		}
	}
	elseif(empty($product['MODULE']))
	{
		$editPageUrl = CHTTP::urlAddParams('/bitrix/components/bitrix/crm.order.product.details/slider.ajax.php?'.bitrix_sessid_get(),
			array(
				'siteID' => $arResult['ORDER_SITE_ID'],
				'order_id' => $arResult['ORDER_ID'],
				'basket_id' => $product['BASKET_CODE'],
				'currency' => $arResult['CURRENCY']
			)
		);
		$actionEditScript = "BX.Crm.Page.openSlider('{$editPageUrl}', { width: 500 }); return;";
		$name = '<a href="#" onclick="'.$actionEditScript.'" class="crm-order-product-info-name-text">'.$name.'</a>';
	}

	//region Main Info
	$mainInfoClass = "crm-order-product-container";
	if ($isSetItems)
	{
		$mainInfoClass .= " crm-order-product-set-item-container";
	}
	$mainInfo = '
		<div class="'.$mainInfoClass.'">
			<div class="crm-order-product-info-name">					
				'.$name.'
			</div>
			<div class="crm-order-product-inner-container">
				<div class="crm-order-product-info-image-container">
					<img
						src="'.(!empty($product['PICTURE_URL']) ? $product['PICTURE_URL'] : $templateFolder.'/images/nofoto.svg').'"								
						alt="'.htmlspecialcharsbx($product['NAME']).'"
						class="crm-order-product-info-image">
				</div>'
				.$skuHtml.
			'</div>
		</div>
	';
	if (!$isReadOnly)
	{
		$mainInfo .= '<input type="hidden" name="'.$namePrefix.'[FIELDS_VALUES]" value="'.htmlspecialcharsbx($product['FIELDS_VALUES']).'">';
	}
	//region Main Info

	//region Quantity
	if ($isReadOnly)
	{
		$quantityInnerHtml = (float)$product['QUANTITY'].' '.$product['MEASURE_TEXT'];
	}
	else
	{
		$quantityInnerHtml = '<input id="crm-product-quantity-'.$product['BASKET_CODE'].'" name="'.$namePrefix.'[QUANTITY]" type="number" step="'.$product['MEASURE_RATIO'].'" value="'.(float)$product['QUANTITY'].'" class="crm-order-product-control-amount-field"> '.$product['MEASURE_TEXT'];
	}
	$quantityColumn = '
		<div class="crm-order-product-control-amount">'
			.$quantityInnerHtml.
			'<div class="crm-order-product-control-amount-desc">'.Loc::getMessage('CRM_ORDER_PL_AVAILABLE').': '.(float)$product['AVAILABLE'].' '.$product['MEASURE_TEXT'].'</div>
		</div>
	';
	//end region

	//region VAT
	if ($isReadOnly)
	{
		$vatIncludedInnerHtml = ($product['VAT_INCLUDED'] == 'Y') ? Loc::getMessage('CRM_ORDER_PL_YES') : Loc::getMessage('CRM_ORDER_PL_NO');
	}
	else
	{
		$vatIncludedInnerHtml = '
			<input type="checkbox" id="crm-product-vat-included-cb-'.$product['BASKET_CODE'].'"'.($product['VAT_INCLUDED'] == 'Y' ? ' checked' : '').'>
			<input type="hidden" id="crm-product-vat-included-'.$product['BASKET_CODE'].'" name="'.$namePrefix.'[VAT_INCLUDED]" value="'.($product['VAT_INCLUDED'] == 'Y' ? 'Y' : 'N').'">
		';
	}
	$vatIncludedColumn = '<div class="crm-order-product-control-additional">'.$vatIncludedInnerHtml.'</div>';
	//end region

	$columns = [
		'MAIN_INFO'=> $mainInfo,
		'PROPERTIES' => $properties,
		'PRICE' => $priceColumn,
		'QUANTITY' => $quantityColumn,
		'SUMM' => '
			<div class="crm-order-product-info-price">
				<div class="crm-order-product-info-price-current">'.CCrmCurrency::MoneyToString($product['SUMM'], $product['CURRENCY']).'</div>
				<div class="crm-order-product-info-price-desc">'.Loc::getMessage('CRM_ORDER_PL_VAT').' '.CCrmCurrency::MoneyToString($product['VAT'], $product['CURRENCY']).'</div>
			</div>',
		'VAT' => '
			<div class="crm-order-product-info-price">
				<div class="crm-order-product-info-price-current">'.(is_null($product['VAT_ID']) ? Loc::getMessage('CRM_ORDER_PL_NO') : Loc::getMessage('CRM_ORDER_PL_VAT').' '.(int)($product['VAT_RATE']*100).'%').'</div>
				<div class="crm-order-product-info-price-desc"></div>
			</div>',
		'VAT_INCLUDED' => $vatIncludedColumn,
		'DISCOUNTS' => '
			<div class="crm-order-product-control-discounts">
				<div class="crm-order-product-control-discounts-list">'.
					$discountsHtml.
				'</div>
			</div>'
	];

	$product['CURRENCY'] = $currencyList[$product['CURRENCY']];

	$actions = [];
	$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

	if($arResult['CAN_UPDATE_ORDER'])
	{
		$editAction = [
			'TITLE' => Loc::getMessage('CRM_ORDER_PL_CHANGE_ITEM_IN_SHOPPING_CART'),
			'TEXT' => Loc::getMessage('CRM_ORDER_PL_CHANGE')
		];

		if(!empty($productEditUrl))
		{
			$editAction['HREF'] = $productEditUrl;
		}
		else
		{
			$editAction['ONCLICK'] = $actionEditScript;
		}

		$jsProductDelete = $jsObjName.".onProductDelete('".$product['BASKET_CODE']."')";

		$actions =
		[
			$editAction,
			[
				'TITLE' => Loc::getMessage('CRM_ORDER_PL_REMOVE_ITEM_FROM_CART'),
				'TEXT' => Loc::getMessage('CRM_ORDER_PL_TO_REMOVE'),
				'ONCLICK' => $jsProductDelete
			]
		];

		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();

		$applyButton = $snippet->getApplyButton(
			array(
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
					)
				)
			)
		);

		$jsGroupProductDelete = $jsObjName.".onGroupAction('".$arResult['GRID_ID']."', 'delete')";
		$actionList = [['NAME' => Loc::getMessage('CRM_ORDER_PL_CHOOSE_ACTION'), 'VALUE' => 'none']];
		$removeButton = $snippet->getRemoveButton();
		$removeButton['ONCHANGE'][0]['DATA'][0]['JS'] = $jsGroupProductDelete;
		$controlPanel['GROUPS'][0]['ITEMS'][] = $removeButton;
		$actionList[] = array(
			"NAME" =>  Loc::getMessage('CRM_ORDER_PL_DELETE'),
			"VALUE" => "remove",
			"ONCHANGE" => array(
				array(
					"ACTION" => Actions::CALLBACK,
					"CONFIRM" => true,
					"CONFIRM_APPLY_BUTTON" => Loc::getMessage('CRM_ORDER_PL_DELETE'),
					"DATA" => array(
						array("JS" => $jsGroupProductDelete)
					)
				)
			)
		);

		$prefix = $arResult['GRID_ID'];
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
			"ID" => "action_button_{$prefix}",
			"NAME" => "action_button_{$prefix}",
			"ITEMS" => $actionList
		);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
	}

	$rows[] = [
		'id' => !empty($product['BASKET_CODE']) ? $product['BASKET_CODE'] : $product['PRODUCT_ID'],
		'data' => $product,
		'columns' => $columns,
		'actions' => $actions,
		'has_child' => true,
		'parent_id' => \Bitrix\Main\Grid\Context::isInternalRequest() && !empty($product['PARENT_ID']) ? $product['PARENT_ID'] : 0,
		'editable' => !$isSetItems && !$isReadOnly
	];

	$basketCodes[] = $product['BASKET_CODE'];
}

$cols = [];

foreach($arResult['VISIBLE_COLUMNS'] as $column)
{
	if($column == 'DISCOUNTS')
		continue;

	$item = ["column" => $column];

	if($column == 'MAIN_INFO' && !$isSetItems)
		$item['rowspan'] = 2;

	$cols[] = $item;
}

$rowLayout = [$cols];

if (!$isSetItems)
{
	$rowLayout = array_merge($rowLayout, [
		[
			["data" => "DISCOUNTS", "column" => "DISCOUNTS", "colspan" => count($arResult['VISIBLE_COLUMNS']) - 1]
		]
	]);
}

?><div><?

$productCreateLink = CHTTP::urlAddParams('/bitrix/components/bitrix/crm.order.product.details/slider.ajax.php?'.bitrix_sessid_get(),
	array(
		'siteID' => $arResult['ORDER_SITE_ID'],
		'order_id' => $arResult['ORDER_ID'],
		'product_id' => 0,
		'currency' => $arResult['CURRENCY']
	)
);
if (!$isReadOnly)
{
	$buttons = [
		[
			'TEXT' => Loc::getMessage('CRM_ORDER_PL_ADD_PRODUCT'),
			'TITLE' => Loc::getMessage('CRM_ORDER_PL_ADD_PRODUCT'),
			'ICON' => 'btn-new',
			'ONCLICK' => $jsObjName.".addProductSearch({lang: '".LANGUAGE_ID."', siteId: '".CUtil::JSEscape($arResult['ORDER_SITE_ID'])."', orderId: '".CUtil::JSEscape($arResult['ORDER_ID'])."'});"
	]];

	if($arResult['ALLOW_CREATE_NEW_PRODUCT'])
	{
		$buttons[] = [
			'TEXT' => Loc::getMessage('CRM_ORDER_PL_CREATE_PRODUCT'),
			'TITLE' => Loc::getMessage('CRM_ORDER_PL_CREATE_PRODUCT'),
			'ICON' => 'btn-new',
			'ONCLICK' => "BX.Crm.Page.openSlider('".CUtil::JSEscape($productCreateLink)."', { width: 500 }); return;"
		];
	}

	if($arResult['ORDER_ID'] > 0)
	{
		$buttons[] = [
			'TEXT' => Loc::getMessage('CRM_ORDER_PL_RECALCULATE_ORDER'),
			'TITLE' => Loc::getMessage('CRM_ORDER_PL_RECALCULATE_ORDER'),
			'ONCLICK' => "if(confirm('".Loc::getMessage('CRM_ORDER_PL_RECALCULATE_WARNING')."')) ".$jsObjName.".onRefreshOrderDataAndSave();"
		];
	}

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

<div class="crm-order-product-list-wrapper" id="crm-product-list-container">
<?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROW_LAYOUT' => $rowLayout,
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
		'SHOW_PAGINATION' => $arResult['SHOW_PAGINATION'],
		'SHOW_TOTAL_COUNTER' => $arResult['SHOW_TOTAL_COUNTER'],
		'SHOW_PAGESIZE' => $arResult['SHOW_PAGESIZE'],
		'SHOW_ROW_ACTIONS_MENU' => !$isSetItems,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => false,
		'HIDE_FILTER' => true,
		'ENABLE_COLLAPSIBLE_ROWS' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'ACTION_PANEL' => $controlPanel,
		'SHOW_ACTION_PANEL' => !empty($controlPanel),
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => 'ORDER_PRODUCT',
				'gridId' => $arResult['GRID_ID'],
				'serviceUrl' => '/bitrix/components/bitrix/crm.order.product.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			],
			'MESSAGES' => [
				'deletionDialogTitle' => Loc::getMessage('CRM_ORDER_PL_DELETE_PRODUCT'),
				'deletionDialogMessage' => Loc::getMessage('CRM_ORDER_PL_DELETE_WARNING'),
				'deletionDialogButtonTitle' => Loc::getMessage('CRM_ORDER_PL_DELETE'),
			]
		]
	],
	$component
);

unset($arParams['ORDER'], $arParams['~ORDER']);

/*
$customDiscountBlockTmpl = '
	<div class="crm-order-product-control-discounts-list-add-item-container">
		<div class="crm-order-product-control-discounts-list-add-item-title">'.Loc::getMessage('CRM_ORDER_PL_DISCOUNT').'</div>
		<span onclick="'.$jsObjName.'.onCloseCustomDiscount(this); return false;" class="crm-order-product-control-discounts-list-add-item-close"></span>
		<div class="crm-order-product-control-discounts-list-add-item-inner">
			<div>
				<input name="DISCOUNTS[CUSTOM][#TYPE#][#ID#]" type="number" value="15" class="crm-order-product-control-discounts-list-add-item-field" style="max-width: 50px;"/>
				<span class="crm-order-product-control-discounts-list-add-item-type">%</span>
			</div>
			<div>
				<span class="crm-order-product-control-discounts-list-add-item-text">'.Loc::getMessage('CRM_ORDER_PL_SUM').'</span>
				<input type="number" min="0" value="1343.32" class="crm-order-product-control-discounts-list-add-item-field" />
			</div>
			<div onclick="'.$jsObjName.'.onApplyCustomDiscount();" class="ui-btn ui-btn-link">'.Loc::getMessage('CRM_ORDER_PL_APPLY').'</div>
		</div>
	</div>';
*/

$couponHtml = '';
$couponsData = [];

if(is_array($arResult['COUPONS_LIST']))
{
	foreach($arResult['COUPONS_LIST'] as $coupon)
	{
		$couponCode = htmlspecialcharsbx($coupon['COUPON']);
		$discountSize = isset($coupon['DISCOUNT_SIZE']) ? $coupon['DISCOUNT_SIZE'] : '0 %';

		if($coupon['JS_STATUS'] == 'APPLYED')
		{
			$color = 'green';
		}
		elseif($coupon['JS_STATUS'] == 'BAD')
		{
			$color = 'red';
		}
		else
		{
			$color = 'gray';
		}

		$couponHtml .= '
		<div>								
			'.($arResult['ORDER_ID'] > 0 ? '<input type="checkbox" data-is-coupon="Y" data-discount-id="'.(int)$coupon['ORDER_DISCOUNT_ID'].'" onchange="'.$jsObjName.'.onCouponApply(this, \''.$couponCode.'\', \''.(int)$coupon['ORDER_DISCOUNT_ID'].'\');"'.($coupon['APPLY'] == 'Y' ? ' checked' : '').' title="'.Loc::getMessage('CRM_ORDER_PL_APPLY').'">' : '').
			'<span style="color: '.$color.';" title="'.htmlspecialcharsbx($coupon['JS_CHECK_CODE']).'">'.$coupon['DISCOUNT_SIZE'].' | ['.$couponCode.'] '.$coupon['DISCOUNT_NAME'].'
				'.(($arResult['ORDER_ID'] <= 0 || $coupon['JS_STATUS'] == 'BAD')? '<a href="javascript:void(0);" onclick="'.$jsObjName.'.onCouponDelete(\''.$couponCode.'\');">'.Loc::getMessage('CRM_ORDER_PL_DELETE').'</a>' : '').'
			</span>
		</div>';

		$couponsData[] = [
			'data-discount-id' => $coupon['ORDER_DISCOUNT_ID'],
			'value' => ($coupon['APPLY'] == 'Y' ? 'Y' : 'N'),
			'name' => 'DISCOUNTS[COUPON_LIST]['.$couponCode.']'
		];
	}
}

//region PRODUCTS LIST SCRIPTS
?>

<script type="text/javascript">
		BX.ready(function ()
		{
			<?=$jsObjName?> = BX.Crm.Order.Product.List.create(
				"<?=$arResult['GRID_ID']?>",
				{
					serviceUrl: '/bitrix/components/bitrix/crm.order.product.list/ajax.php',
					siteId: '<?=CUtil::JSEscape($arResult['ORDER_SITE_ID'])?>',
					languageId: '<?=LANGUAGE_ID?>',
					skuOrder: <?=CUtil::PhpToJSObject($arResult['IBLOCKS_SKU_PARAMS_ORDER'])?>,
					vatRates: <?=CUtil::PhpToJSObject($arResult['VAT_RATES'])?>,
					productSkuValues: <?=CUtil::PhpToJSObject($productSkuValues)?>,
					basketItemsParams: <?=CUtil::PhpToJSObject($basketItemsParams)?>,
					isChanged: '<?=$arResult['IS_CHANGED']?>',
					code2Id: <?=CUtil::PhpToJSObject($code2Id)?>,
					messages:<?=CUtil::PhpToJSObject([
						'CRM_ORDER_PL_PROD_EXIST_DLG_TITLE' => Loc::getMessage('CRM_ORDER_PL_PROD_EXIST_DLG_TITLE'),
						'CRM_ORDER_PL_PROD_EXIST_DLG_TEXT' => Loc::getMessage('CRM_ORDER_PL_PROD_EXIST_DLG_TEXT'),
						'CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_ADD' => Loc::getMessage('CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_ADD'),
						'CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_CANCEL' => Loc::getMessage('CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_CANCEL')
					])?>,
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

			<?=$jsObjName?>.setFormId('<?=CUtil::JSEscape($arResult['FORM_ID'])?>');

			var form,
				couponsData = <?=CUtil::PhpToJSObject($couponsData)?>;

			if(form = document.forms['<?=CUtil::JSEscape($arResult['FORM_ID'])?>'])
			{
				for(var i in couponsData)
				{
					if(!couponsData.hasOwnProperty(i))
					{
						continue;
					}

					form.appendChild(BX.create('input',{
						props: {
							type: 'hidden',
							name: couponsData[i]['name'],
							value: couponsData[i]['value']
						},
						attrs:{
							'data-discount-id': couponsData[i]['data-discount-id'],
							'data-is-coupon': 'Y'
						}
					}));
				}

				form.appendChild(BX.create('input',{
					props: {
						type: 'hidden',
						name: 'SITE_ID',
						value: '<?=CUtil::JSEscape($arResult['SITE_ID'])?>'
					}
				}));
			}

			var setBindings = function()
			{
				<?if(count($basketCodes) > 0):?>
					var basketCodes = <?=CUtil::PhpToJSObject($basketCodes)?>;

					if(basketCodes.length)
					{
						for(var i = 0, l = basketCodes.length; i < l; i++)
						{
							BX.bind(BX('crm-product-quantity-'+basketCodes[i]), 'change', BX.delegate(
								function(event){
									event = event || window.event;
									<?=$jsObjName?>.onDataChanged();
									event.stopPropagation();
								}
							));

							(function(itr)
							{
								BX.bind(BX('crm-product-price-'+basketCodes[itr]), 'change', BX.delegate(
									function(event){
										event = event || window.event;
										BX('crm-product-price-'+basketCodes[itr]).parentNode.appendChild(
											BX.create('input',{props:{
												type: 'hidden',
												value: 'Y',
												name: 'PRODUCT['+basketCodes[itr]+'][CUSTOM_PRICE]'
										}}));
										<?=$jsObjName?>.onDataChanged();
										event.stopPropagation();
									}
								));

								BX.bind(BX('crm-product-vat-included-cb-'+basketCodes[itr]), 'change', BX.delegate(
									function(event){
										event = event || window.event;
										event.stopPropagation();
										BX('crm-product-vat-included-'+basketCodes[itr]).value = event.target.checked ? 'Y' : 'N';
										<?=$jsObjName?>.onDataChanged();
									},
									<?=$jsObjName?>
								));

							})(i)
						}
					}
				<?endif;?>
			};

			if(window['ConnectedEntityController'])
			{
				<?=$jsObjName?>.setController(window['ConnectedEntityController']);
				setBindings();
			}
			else
			{
				BX.addCustomEvent(window, "EntityEditorOrderController", function(controller){ <?=$jsObjName?>.setController(controller); setBindings();});
			}
		});

</script>

<?
if (!$arParams["INTERNAL_RELOAD"])
{
	?>
	<script>
		BX.ready(function ()
		{
			BX.onCustomEvent(window, 'Grid::updated', []);
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function(event){
				if (
					event.getEventId() === 'CrmOrderBasketItem::Update'
					|| event.getEventId() === 'CrmOrderBasketItem::Create'
				)
				{
					var eventData = event.getData();
					if (
						BX.type.isPlainObject(eventData)
						&& parseInt(eventData.orderId) === parseInt(<?=(int)$arResult['ORDER_ID']?>)
					)
					{
						if (event.getEventId() === 'CrmOrderBasketItem::Create' )
						{
							<?=$jsObjName?>.onProductCreate(eventData.field);
						}
						else
						{
							<?=$jsObjName?>.onProductUpdate(eventData.basketId, eventData.field);
						}
					}
				}
			}));
			if (BX.Main.gridManager !== undefined)
			{
				var grid = BX.Main.gridManager.getInstanceById("<?=CUtil::JSEscape($arResult['GRID_ID'])?>");
				grid.getUserOptions().setExpandedRows = function(){};
				grid.getRows().getRows().map(function(current) {
					current.expand = function()
					{
						var self = this;
						this.stateExpand();

						if (this.isChildsLoaded())
						{
							this.showChildRows();
						}
						else
						{
							this.stateLoad();
							this.loadChildRows(function(rows) {
								var node = self.getNode();
								rows.reverse().forEach(function(current) {
									BX.insertAfter(current, node.nextElementSibling);
								});
								self.parent.getRows().reset();
								self.parent.bindOnRowEvents();

								if (self.parent.getParam('ALLOW_ROWS_SORT'))
								{
									self.parent.getRowsSortable().reinit();
								}

								if (self.parent.getParam('ALLOW_COLUMNS_SORT'))
								{
									self.parent.getColsSortable().reinit();
								}

								self.stateUnload();
								BX.data(self.getNode(), 'child-loaded', 'true');
								self.parent.updateCounterDisplayed();
								self.parent.updateCounterSelected();
								self.parent.adjustCheckAllCheckboxes();
							});
						}
					};
				});
			}
		});
	</script>
	<?
}
//endregion
//region SUMMARY

$discountsHtml = '';

if(is_array($arResult['DISCOUNTS_LIST']['DISCOUNT_LIST']))
{
	foreach($arResult['DISCOUNTS_LIST']['DISCOUNT_LIST'] as $discountId => $discount)
	{
		if(is_array($discount['ACTIONS_DESCR']))
		{
			$descr = implode(' ', $discount['ACTIONS_DESCR']);
		}
		else
		{
			$descr = '';
		}

		$discountsHtml .= '
			<div class="crm-order-product-control-discounts-list-item">
					<span class="crm-order-product-control-discounts-list-item-label">
						<span class="crm-order-product-control-discounts-list-item-input">							
							<input type="hidden" name="DISCOUNTS[DISCOUNT_LIST]['.$discountId.']" value="N">
							<input data-discount-id="'.$discountId.'" 
								type="checkbox" 
								name="DISCOUNTS[DISCOUNT_LIST]['.$discountId.']" 
								value="Y"'.($discount['APPLY'] == 'Y' ? ' checked' : '').' 
								onchange="'.$jsObjName.'.onDiscountCheck(this, '.$discountId.');">							
						</span>
						<a 
							href="'.$discount['EDIT_PAGE_URL'].'" 
							class="crm-order-product-control-discounts-list-item-label-text">'.$discount['NAME'].'
						</a>
					</span>
				<div class="crm-order-product-control-discounts-list-item-desc">'.$descr.'</div>
			</div>';
	}
}

?>
<div class="crm-order-total-wrapper">
	<div class="crm-order-total-discounts">
		<div class="crm-order-product-control-discounts-title"><?=Loc::getMessage('CRM_ORDER_PL_ORDER_DISCOUNT')?></div>
		<div class="crm-order-product-control-discounts-list">
			<?=$discountsHtml?>
		</div>
	</div>
	<div class="crm-order-total-container">
		<div class="crm-order-total-container-row">
			<div class="crm-order-total-container-column">
				<div class="crm-order-promotional-code-box">
					<div class="crm-order-product-control-discounts-title"><?=Loc::getMessage('CRM_ORDER_PL_PROMO_CODE')?></div>
					<div class="crm-order-product-control-discounts-wrap">
						<div class="crm-order-product-control-discounts-field-container">
							<input id="crm-order-product-new-coupon" name="NEW_COUPON" type="text" class="crm-order-product-control-discounts-field">
							<div>
								<?=$couponHtml?>
							</div>
							<div class="crm-order-product-control-discounts-field-description"><?=Loc::getMessage('CRM_ORDER_PL_COUPON_REFRESH')?></div>
						</div>
						<div class="crm-order-product-control-discounts-btn-container">
							<button class="ui-btn ui-btn-secondary" onclick="<?=$jsObjName?>.onCouponAdd();"><?=Loc::getMessage('CRM_ORDER_PL_ADD')?></button>
						</div>
					</div>
				</div>
			</div>
			<div class="crm-order-total-container-column">

				<div class="crm-order-total-box">
					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_TOTAL_PRICE')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['PRICE_BASKET'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_DISCOUNT_PRICE')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['PRICE_BASKET_DISCOUNTED'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_DELIVERY_PRICE')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['PRICE_DELIVERY'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_DELIVERY_DISCOUNT_PRICE')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['PRICE_DELIVERY_DISCOUNTED'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_TAX')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['TAX_VALUE'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_WEIGHT')?></div>
						<div class="crm-order-total-box-row-column"><?=$arResult['WEIGHT_FOR_HUMAN'].' '.$arResult['WEIGHT_UNIT']?></div>
					</div>

					<div class="crm-order-total-box-row">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_PAID')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['SUM_PAID'], $arResult['CURRENCY'])?></div>
					</div>

					<div class="crm-order-total-box-row crm-order-total-box-row-final">
						<div class="crm-order-total-box-row-column"><?=Loc::getMessage('CRM_ORDER_PL_TOTAL')?></div>
						<div class="crm-order-total-box-row-column"><?=CCrmCurrency::MoneyToString($arResult['SUM_UNPAID'], $arResult['CURRENCY'])?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>
<?//endregion?>