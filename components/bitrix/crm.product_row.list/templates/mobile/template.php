<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var \CBitrixComponent $component */
if (!\Bitrix\Main\Loader::includeModule("mobile"))
	return;

CJSCore::Init(array('mobile_crm'));

if ($arParams["RESTRICTED_MODE"] && (!is_array($arResult['PRODUCT_ROWS']) || empty($arResult['PRODUCT_ROWS'])))
	return;

/*
$bCanAddProduct = $arResult['CAN_ADD_PRODUCT'];

$readOnly = !isset($arResult['READ_ONLY']) || $arResult['READ_ONLY']; //Only READ_ONLY access by defaul
$bInitEditable = ((isset($arResult['INIT_EDITABLE']) ? $arResult['INIT_EDITABLE'] : false) && !$readOnly);
$bHideModeButton = ((isset($arResult['HIDE_MODE_BUTTON']) ? $arResult['HIDE_MODE_BUTTON'] : false) || $readOnly);
$enableCustomProducts = $arResult['ENABLE_CUSTOM_PRODUCTS'];
$currencyText = CCrmViewHelper::getCurrencyText($arResult['CURRENCY_ID']);*/

$bShowDiscount = $arResult['ENABLE_DISCOUNT'];
$bShowTax = (!$arResult['HIDE_ALL_TAXES'] && ($arResult['ALLOW_LD_TAX'] || ($arResult['ALLOW_TAX'] && $arResult['ENABLE_TAX'])));
$bDiscountExists = false;
$bTaxExists = false;

$productTotalContainerID = $arResult['PREFIX'].'_product_sum_total_container';

$productEditorCfg = array(
	'sessid' => bitrix_sessid(),
	'productSearchUrl'=> '/bitrix/components/bitrix/crm.product.list/list.ajax.php?'.bitrix_sessid_get(),
	'pathToProductShow' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_SHOW']),
	'pathToProductEdit' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_EDIT']),
	'ownerType' => $arResult['OWNER_TYPE'],
	'invoiceMode' => $arResult['INVOICE_MODE'],
	'ownerID' => $arResult['OWNER_ID'],
	'currencyID' => $arResult['CURRENCY_ID'],
	'locationID' => $arResult['LOCATION_ID'],
	'currencyFormat' => $arResult['CURRENCY_FORMAT'],
	'siteId' => $arResult['SITE_ID'],
	'clientTypeName' => $arResult['CLIENT_TYPE_NAME'],
	'taxValueID' => $arResult['PREFIX'].'_tax_value',
	'productTotalContainerID' => $productTotalContainerID
);

$productEditorCfg['hideTaxIncludedColumn'] = $arResult['HIDE_TAX_INCLUDED_COLUMN'];
$productEditorCfg['hideAllTaxes'] = $arResult['HIDE_ALL_TAXES'];
$productEditorCfg['allowTax'] = $arResult['ALLOW_TAX'];
$productEditorCfg['taxUniform'] = $arResult['PRODUCT_ROW_TAX_UNIFORM'];
$productEditorCfg['defaultTax'] = $defaultTax;
$productEditorCfg['allowLDTax'] = $arResult['ALLOW_LD_TAX'];

$taxes = array();
if($arResult['ALLOW_TAX'])
{
	$productEditorCfg['taxes'] = $taxes = CCrmTax::GetVatRateInfos();
}
$taxRatesOrig = array();
foreach ($taxes as $tax)
	$taxRatesOrig[] = $tax['VALUE'];

$productEditorCfg['enableTax'] = $arResult['ENABLE_TAX'];
$productEditorCfg['enableDiscount'] = $arResult['ENABLE_DISCOUNT'];

?>
<div id="<?=$productTotalContainerID?>" style="<?= count($arResult['PRODUCT_ROWS']) === 0 ? 'display: none;' : '' ?>">
	<table class="crm-view-table-total">
		<tbody>
		<tr class="crm-view-table-total-value"<?= $bShowDiscount ? '' : ' style="display: none;"' ?>>
			<td class="crm-view-table-total-value-title">
				<span><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_BEFORE_DISCOUNT')) ?>:</span>
			</td>
			<td class="crm-view-table-total-value-value">
				<? $productEditorCfg['TOTAL_BEFORE_DISCOUNT_ID'] = $arResult['PREFIX'].'_total_before_discount'; ?>
				<span id="<?= htmlspecialcharsbx($productEditorCfg['TOTAL_BEFORE_DISCOUNT_ID']) ?>"
					  class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_DISCOUNT'], $arResult['CURRENCY_ID']) ?></span>
			</td>
		</tr>
		<tr class="crm-view-table-total-value"<?= $bShowDiscount ? '' : ' style="display: none;"' ?>>
			<td class="crm-view-table-total-value-title">
				<span><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_DISCOUNT')) ?>:</span>
			</td>
			<td class="crm-view-table-total-value-value"><?
				$productEditorCfg['TOTAL_DISCOUNT_ID'] = $arResult['PREFIX'].'_total_discount';
				if (round(doubleval($arResult['TOTAL_DISCOUNT']), 2) !== 0.0)
					$bDiscountExists = true;
				?>
				<span id="<?= htmlspecialcharsbx($productEditorCfg['TOTAL_DISCOUNT_ID']) ?>"
					  class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['TOTAL_DISCOUNT'], $arResult['CURRENCY_ID']) ?></span>
			</td>
		</tr><?
		$productEditorTaxList = array();
		if ($arResult['ALLOW_TAX'] || $arResult['ALLOW_LD_TAX']):
			?>
			<tr class="crm-view-table-total-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
				<td class="crm-view-table-total-value-title">
					<span><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX')) ?>:</span>
				</td>
				<td class="crm-view-table-total-value-value"><? $productEditorCfg['TOTAL_BEFORE_TAX_ID'] = $arResult['PREFIX'].'_total_before_tax'; ?>
					<span id="<?= htmlspecialcharsbx($productEditorCfg['TOTAL_BEFORE_TAX_ID']) ?>"
						  class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_TAX'], $arResult['CURRENCY_ID']) ?></span>
				</td>
			</tr>
			<?
		endif;
		if ($arResult['ALLOW_TAX']):
			$productEditorTaxList[] = array(
				'TAX_NAME'  => GetMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX'),
				'TAX_VALUE' => CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_TAX'], $arResult['CURRENCY_ID'])
			);
			if (round(doubleval($arResult['TOTAL_TAX']), 2) !== 0.0)
				$bTaxExists = true;
			?>
		<tr class="crm-view-table-total-value crm-tax-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
			<td class="crm-view-table-total-value-title">
				<span><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_TAX')) ?>:</span>
			</td>
			<td class="crm-view-table-total-value-value">
				<span id="<?= htmlspecialcharsbx($productEditorCfg['taxValueID']) ?>"
					  class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['TOTAL_TAX'], $arResult['CURRENCY_ID']) ?></span>
			</td>
			</tr><?
		elseif ($arResult['ALLOW_LD_TAX']):
			$taxList = isset($arResult['TAX_LIST']) ? $arResult['TAX_LIST'] : array();
			if (!is_array($arResult['TAX_LIST']) || count($arResult['TAX_LIST']) === 0)
			{
				$taxList = array(
					array(
						'NAME'      => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
						'TAX_VALUE' => CCrmCurrency::MoneyToString(0.0, $arResult['CURRENCY_ID'])
					)
				);
			}
			$i = 0;
			foreach ($taxList as $taxInfo):
				$productEditorTaxList[] = array(
					'TAX_NAME'  => sprintf(
						"%s%s%s",
						($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING')." " : "",
						$taxInfo["NAME"],
						($taxInfo["IS_PERCENT"] == "Y")
							? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], $arResult['TAX_LIST_PERCENT_PRECISION']))
							: ""
					),
					'TAX_VALUE' => CCrmCurrency::MoneyToString(
						$taxInfo['VALUE_MONEY'], $arResult['CURRENCY_ID']
					)
				);
				if (round(doubleval($taxInfo['VALUE_MONEY']), 2) !== 0.0)
					$bTaxExists = true;

				?>
			<tr class="crm-view-table-total-value crm-tax-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
				<td class="crm-view-table-total-value-title">
					<span><?= htmlspecialcharsbx($productEditorTaxList[$i]['TAX_NAME']) ?>:</span>
				</td>
				<td class="crm-view-table-total-value-value">
				<span
					<?php echo ($i === 0) ? 'id="'.htmlspecialcharsbx($productEditorCfg['taxValueID']).'" ' : ''; ?>class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($taxInfo['VALUE_MONEY'], $arResult['CURRENCY_ID']) ?></span>
				</td>
				</tr><?
				$i++;
			endforeach;
			$productEditorCfg['LDTaxes'] = $productEditorTaxList;
			if (isset($arResult['TAX_LIST_PERCENT_PRECISION']))
				$productEditorCfg['taxListPercentPrecision'] = $arResult['TAX_LIST_PERCENT_PRECISION'];
		endif; ?>

		<tr class="crm-view-table-total-value">
			<td class="crm-view-table-total-value-title">
				<span><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_SUM_TOTAL')) ?>:</span>
			</td>
			<td class="crm-view-table-total-value-value">
				<? $productEditorCfg['SUM_TOTAL_ID'] = $arResult['PREFIX'].'_sum_total'; ?>
				<span id="<?= htmlspecialcharsbx($productEditorCfg['SUM_TOTAL_ID']) ?>"
					  class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($arResult['TOTAL_SUM'], $arResult['CURRENCY_ID']) ?></span>
			</td>
		</tr>

		<?
		$productEditorCfg['_discountExistsInit'] = $bDiscountExists;
		$productEditorCfg['_taxExistsInit'] = $bTaxExists;
		?>
		</tbody>
	</table>
</div>

<div id="mobile-crm-invoice-edit-product" data-role="mobile-crm-invoice-edit-products" class="crm-mobile-product-view-container" <?if (empty($arResult['PRODUCT_ROWS'])):?>style="display: none" <?endif?>>
	<!--Products' html is generated on javascript, object BX.Mobile.Crm.ProductEditor-->
</div>

<?if (!$arParams["RESTRICTED_MODE"]):?>
	<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageBlank('<?=CUtil::JSEscape($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'])?>')"><?=GetMessage("CRM_BUTTON_SELECT")?></a>
<?endif?>

<?if ($arParams["RESTRICTED_MODE"] && $arParams['SEND_PRODUCTS_IN_RESTRICTED_MODE']):?>
	<?foreach($arResult['PRODUCT_ROWS'] as $key => $row):?>
		<?foreach($row as $id => $val):?>
		<input type="hidden" name="<?=$arParams["PRODUCT_DATA_FIELD_NAME"]?>[<?=$key?>][<?=$id?>]" value="<?=$val?>">
		<?endforeach?>
	<?endforeach?>
<?endif?>

<script type="text/javascript">
	var productEditorParams = {
		isEditMode: '<?=($arParams["RESTRICTED_MODE"] ? "N" : "Y")?>',
		onProductSelectEventName: "<?=$arParams["ON_PRODUCT_SELECT_EVENT_NAME"]?>",
		products: <?=CUtil::PhpToJSObject($arResult['PRODUCT_ROWS'])?>,
		productsContainerNode: document.querySelector("[data-role='mobile-crm-invoice-edit-products']") || "",
		eventName: "<?=CUtil::JSEscape($arParams["PRODUCT_CHANGE_EVENT_NAME"])?>",
		ajaxUrl : "<?=$component->GetPath().'/ajax.php?'.bitrix_sessid_get()?>",
		settings: <?=CUtil::PhpToJSObject($productEditorCfg)?>
	};
	BX.Mobile.Crm.ProductEditor.init(productEditorParams); // products' editor

	BX.message({
		"PERMISSION_DENIED": "<?= GetMessageJS('CRM_PERMISSION_DENIED_ERROR')?>",
		"INVALID_REQUEST_ERROR": "<?= GetMessageJS('CRM_INVALID_REQUEST_ERROR')?>",
		"CUSTOM_PRODUCT_NAME_NOT_ASSIGNED": "<?= GetMessageJS('CRM_CUSTOM_PRODUCT_NAME_NOT_ASSIGNED_ERROR')?>"
	});
</script>

