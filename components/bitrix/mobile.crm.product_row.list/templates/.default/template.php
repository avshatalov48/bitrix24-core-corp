<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
CJSCore::Init(array('mobile_crm'));

$UID = $arResult['UID'];
$taxMode = $arResult['TAX_MODE'];
$stubID = $UID.'_stub';
$qty = count($arResult['ITEMS']);
?>
<?/*
<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
	<div class="crm_head_title tal m0" style="padding: 10px 5px 0;">
		<?=htmlspecialcharsbx($arResult['TITLE'])?>
		<span style="font-size: 13px;color: #87949b;"> <?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_LEGEND'))?></span>
	</div>
	<hr style="border-top: 1px solid #a2acb0;" />
	<div id="<?=htmlspecialcharsbx($stubID)?>" class="crm_contact_info tac"<?=$qty > 0 ? ' style="display:none;"' : ''?>>
		<strong style="color: #9ca9b6;font-size: 15px;display: inline-block;margin: 30px 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_NOTHING_FOUND'))?></strong>
	</div>
	<ul class="crm_company_list"<?=$qty === 0 ? ' style="display:none;"' : ''?>><?
		$isVATEnabled = $taxMode === 'VAT';
		foreach($arResult['ITEMS'] as &$item):
			$productName = $item['PRODUCT_NAME'];
			if($productName === 'OrderDelivery')
				$productName = GetMessage('M_CRM_PRODUCT_ROW_DELIVERY');
			elseif($productName === 'OrderDiscount')
				$productName = GetMessage('M_CRM_PRODUCT_ROW_DISCOUNT');
			?><li class="crm_company_list_item" style="padding: 7px 7px 13px 13px;">
				<a class="crm_company_title"><?=htmlspecialcharsbx($productName)?></a>
				<div class="crm_company_company">
					<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_PRICE'))?>:&nbsp;
					<span class="fwb"><?=$item['FORMATTED_PRICE']?></span>
					<?if($isVATEnabled):?>
					<span class="fwb"> <?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_VAT', array('#VAT_RATE#' => $item['VAT_RATE'])))?></span>
					<?endif;?>
					<br/>
					<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_QTY'))?>:&nbsp;
					<span class="fwb"><?=$item['QUANTITY']?></span>
				</div>
				<div class="clb"></div>
			</li>
		<?endforeach;?>
		<?unset($item);?>
	</ul>
</div>*/?>

<div id="mobile-crm-invoice-edit-product" data-role="mobile-crm-invoice-edit-products"> <!--Products' html is generated on javascript, object BX.Mobile.Crm.ProductEditor-->
</div>
<?if (!$arParams["RESTRICTED_MODE"]):?>
<a class="mobile-grid-button select-user" href="javascript:void(0)" onclick="BX.Mobile.Crm.loadPageBlank('<?=CUtil::JSEscape($arParams['PRODUCT_SELECTOR_URL_TEMPLATE'])?>')"><?=GetMessage("CRM_BUTTON_SELECT")?></a>
<?endif?>

<?if(count($arResult['ITEMS']) > 0):?>
	<div class="crm_block_container">
		<?if($taxMode === 'VAT'):?>
			<div class="crm_meeting_info" style="padding-bottom: 10px;">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_SUM_TOTAL_2'))?>:&nbsp;
				<strong><?=$arResult['FORMATTED_SUM_BRUTTO']?></strong>
			</div>
			<div class="crm_meeting_info" style="padding-bottom: 10px;">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_VAT_INCLUDED_SUM'))?>:&nbsp;
				<strong><?=$arResult['FORMATTED_VAT_SUM']?></strong>
			</div>
		<?elseif($taxMode === 'EXT'):?>
			<div class="crm_meeting_info" style="padding-bottom: 10px;">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_SUM_TOTAL_2'))?>:&nbsp;
				<strong><?=$arResult['FORMATTED_SUM_NETTO']?></strong>
			</div>
			<?foreach($arResult['TAX_LIST'] as &$taxInfo):?>
				<div class="crm_meeting_info" style="padding-bottom: 10px;">
					<?if($taxInfo['IS_IN_PRICE'] === 'Y'):?>
						<?if($taxInfo['IS_PERCENT'] === 'Y'):?>
							<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_VAT_INCLUDED_PERCENTS', array('#TAX_NAME#'=>$taxInfo['TAX_NAME'], '#TAX_RATE#'=>roundEx($taxInfo['VALUE'], $arResult['TAX_LIST_PERCENT_PRECISION']))))?>:&nbsp;
						<?else:?>
							<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_VAT_INCLUDED', array('#TAX_NAME#'=>$taxInfo['TAX_NAME'])))?>:&nbsp;
						<?endif;?>
					<?else:?>
					<?endif;?>
					<strong><?=$taxInfo['FORMATTED_SUM_BRUTTO']?></strong>
				</div>
			<?endforeach;?>
			<?unset($taxInfo);?>
			<div class="crm_meeting_info" style="padding-bottom: 10px;">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_SUM_TOTAL'))?>:&nbsp;
				<strong><?=$arResult['FORMATTED_SUM']?></strong>
			</div>
		<?else:?>
			<div class="crm_meeting_info" style="padding-bottom: 10px;">
				<?=htmlspecialcharsbx(GetMessage('M_CRM_PRODUCT_ROW_LIST_SUM_TOTAL'))?>:&nbsp;
				<strong><?=$arResult['FORMATTED_SUM']?></strong>
			</div>
		<?endif;?>
	</div>
<?endif;?>

<script type="text/javascript">
	var productParams = {
		isEditMode: '<?=($arParams["RESTRICTED_MODE"] ? "N" : "Y")?>',
		products: <?=CUtil::PhpToJSObject($arResult['ITEMS'])?>,
		productsContainerNode: document.querySelector("[data-role='mobile-crm-invoice-edit-products']") || "",
		eventName: "<?=CUtil::JSEscape($arParams["PRODUCT_CHANGE_EVENT_NAME"])?>"
	};
	BX.Mobile.Crm.ProductEditor.init(productParams); // products' editor
</script>
