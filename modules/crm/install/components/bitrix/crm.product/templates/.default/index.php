<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Page\Asset;

/** @var \CBitrixComponentTemplate $this  */

global $APPLICATION;

Asset::getInstance()->addCss($this->GetFolder().'/splitter.css');
Asset::getInstance()->addJs($this->GetFolder().'/splitter.js');
Asset::getInstance()->addJs($this->GetFolder().'/list_manager.js');

$viewOptionId = 'crm_product_template_list_default';
$splitterState = CUserOptions::GetOption(
	'crm',
	$viewOptionId,
	array(
		'rightSideWidth' => 250,
		'rightSideClosed' => 'N'
	)
);
if (!is_array($splitterState))
{
	$splitterState = array(
		'rightSideWidth' => 250,
		'rightSideClosed' => "N"
	);
}
$splitterState['rightSideWidth'] = intval($splitterState['rightSideWidth']);
if ($splitterState['rightSideWidth'] < 100)
{
	$splitterState['rightSideWidth'] = 250;
	$splitterState['rightSideClosed'] = "N";
}
if ($splitterState['rightSideClosed'] !== "Y"
	&& $splitterState['rightSideClosed'] !== "N")
{
	$splitterState['rightSideClosed'] = "N";
}

// Control panel
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'PRODUCT_LIST',
		'ACTIVE_ITEM_ID' => 'PRODUCT',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_INDEX']) ? $arResult['PATH_TO_INDEX'] : ''
	),
	$component
);

// Crumbs
$APPLICATION->ShowViewContent('crm_product_section_crumbs');

// Filter
$APPLICATION->ShowViewContent('crm-grid-filter');
?>
<div class="bx-crm-container">
	<table class="bx-crm-goods-table">
		<tr>
			<td class="bx-crm-goods-cont-cell">
				<div class="bx-crm-interface-toolbar-container">
					<?php
					// Toolbar
					$APPLICATION->ShowViewContent('crm_product_menu');
					?>
				</div>
			</td>
			<td class="bx-crm-goods-drug-btn"></td>
			<td class="bx-crm-table-sidebar-cell" id="bx-crm-table-sidebar-cell"<?= ($splitterState['rightSideClosed'] === 'Y' ? ' style="width: 0;"' : ' style="width: '.$splitterState['rightSideWidth'].'px;"') ?>></td>
		</tr>
		<tr>
			<td class="bx-crm-goods-cont-cell">
				<div class="bx-crm-interface-product-list">
				<?php
				// List
				$productListResult =
				$APPLICATION->IncludeComponent(
					'bitrix:crm.product.list',
					'',
					array(
						'CATALOG_ID' => $arResult['CATALOG_ID'],
						'SECTION_ID' => $arResult['SECTION_ID'],
						'PATH_TO_INDEX' => $arResult['PATH_TO_INDEX'],
						'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'],
						'PATH_TO_PRODUCT_SHOW' => $arResult['PATH_TO_PRODUCT_SHOW'],
						'PATH_TO_PRODUCT_EDIT' => $arResult['PATH_TO_PRODUCT_EDIT'],
						'PATH_TO_PRODUCT_FILE' => $arResult['PATH_TO_PRODUCT_FILE'],
						'PATH_TO_SECTION_LIST' => $arResult['PATH_TO_SECTION_LIST'],
						'PRODUCT_COUNT' => '20'
					),
					$component
				);
				?>
				</div>
			</td>
			<td class="bx-crm-goods-drug-btn bx-crm-goods-drug-btn-border" id="bx-crm-goods-drug-btn">
				<div id="bx-crm-goods-drug-btn-inner" class="bx-crm-goods-drug-btn-inner<?= ($splitterState['rightSideClosed'] === 'Y' ? ' bx-crm-goods-close' : '') ?>"></div>
			</td>
			<td class="bx-crm-table-sidebar-cell bx-crm-table-sidebar-border">
				<div id="crm_product_list_right" class="bx-crm-interface-product-list-right">
					<div id="crm_info_panel"></div>

					<div class="bx-crm-sidebar-section">
						<?php
						// Tree
						$APPLICATION->IncludeComponent(
							'bitrix:crm.product.section.tree',
							'',
							array(
								'CATALOG_ID' => $arResult['CATALOG_ID'],
								'SECTION_ID' =>
									(is_array($productListResult) && array_key_exists('SECTION_ID', $productListResult)) ?
										$productListResult['SECTION_ID'] : 0,
								'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST']
							),
							$component
						);?>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div>
<?php

// Crumbs
$this->SetViewTarget('crm_product_section_crumbs');
$APPLICATION->IncludeComponent(
	'bitrix:crm.product.section.crumbs',
	'',
	array(
		'CATALOG_ID' => $arResult['CATALOG_ID'],
		'SECTION_ID' =>
			(is_array($productListResult) && array_key_exists('SECTION_ID', $productListResult)) ?
				$productListResult['SECTION_ID'] : 0,
		'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST']
	),
	$component
);
$this->EndViewTarget();

// Toolbar
$this->SetViewTarget('crm_product_menu');
$APPLICATION->IncludeComponent(
	'bitrix:crm.product.menu',
	'',
	array(
		'CATALOG_ID' => $arResult['CATALOG_ID'],
		'SECTION_ID' => (is_array($productListResult) && array_key_exists('SECTION_ID', $productListResult)) ?
			$productListResult['SECTION_ID'] : 0,
		'PRODUCT_COUNT' => (
			is_array($productListResult)
			&& is_array($productListResult['PARAMS'])
			&& array_key_exists('~PRODUCT_COUNT', $productListResult['PARAMS'])
		) ? (int)$productListResult['PARAMS']['~PRODUCT_COUNT'] : 20,
		'PATH_TO_INDEX' => (
			is_array($productListResult)
			&& is_array($productListResult['PARAMS'])
			&& array_key_exists('~PATH_TO_INDEX', $productListResult['PARAMS'])
		) ? $productListResult['PARAMS']['~PATH_TO_INDEX'] : '',
		'PATH_TO_PRODUCT_LIST' => $arResult['PATH_TO_PRODUCT_LIST'],
		'PATH_TO_PRODUCT_SHOW' => $arResult['PATH_TO_PRODUCT_SHOW'],
		'PATH_TO_PRODUCT_EDIT' => $arResult['PATH_TO_PRODUCT_EDIT'],
		'PATH_TO_PRODUCT_IMPORT' => $arResult['PATH_TO_PRODUCT_IMPORT'],
		'PRODUCT_ID' => $arResult['VARIABLES']['product_id'],
		'PATH_TO_SECTION_LIST' => $arResult['PATH_TO_SECTION_LIST'],
		'PATH_TO_PRODUCT_FILE' => (
			is_array($productListResult)
			&& is_array($productListResult['PARAMS'])
			&& array_key_exists('~PATH_TO_PRODUCT_FILE', $productListResult['PARAMS'])
		) ? $productListResult['PARAMS']['~PATH_TO_PRODUCT_FILE'] : '',
		'TYPE' => 'list'
	),
	$component
);
$this->EndViewTarget();

?>
<script type="text/javascript">
	BX.namespace("BX.Crm");
	BX.Crm.productListManager = BX.Crm.ProductListManagerClass.create({
		splitterBtnId: "bx-crm-goods-drug-btn",
		splitterNodeId: "bx-crm-table-sidebar-cell",
		hideBtnId: "bx-crm-goods-drug-btn-inner",
		viewOptionId: "<?= CUtil::JSEscape($viewOptionId) ?>",
		splitterState: <?= CUtil::PhpToJSObject($splitterState) ?>
	});
</script>