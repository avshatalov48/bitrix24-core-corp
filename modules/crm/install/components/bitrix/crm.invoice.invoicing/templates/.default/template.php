<?php

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'INVOICING',
		'ACTIVE_ITEM_ID' => 'INVOICING'
	),
	$component
);
?>
<div class="bx-invoicing-wrap">
	<table class="bx-invoicing-main-table">
		<tbody>
			<tr>
				<td class="bx-invoicing-main-table-cell">
					<div class="bx-invoicing-tabs-block" id="filter-tabs">
						<span class="bx-invoicing-tab bx-invoicing-tab-active"><?=Loc::getMessage('CRM_FILTER_TITLE');?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="bx-invoicing-main-table-cell">
					<div class="bx-invoicing-content">
						<div class="bx-invoicing-content-inner">
							<div class="bx-invoicing-content-table-wrap">
								<table class="bx-invoicing-content-table">
									<tbody>
									<? if (count($arResult['PAY_SYSTEM_LIST']) > 1):?>
										<tr class="bx-invoicing-item-row" >
											<td class="bx-invoicing-item-left"><?=Loc::getMessage('CRM_FILTER_PAY_SYSTEM')?>:</td>
											<td class="bx-invoicing-item-center">
												<div class="bx-invoicing-alignment">
													<div class=" bx-invoicing-box-sizing">
														<span class="bx-select-wrap">
															<select name="PAY_SYSTEM" id="PAY_SYSTEM" class="bx-select">
																<?foreach ($arResult['PAY_SYSTEM_LIST'] as $id => $name):?>
																	<option value="<?=$id;?>"><?=$name;?></option>
																<?endforeach;?>
															</select>
														</span>
													</div>
												</div>
											</td>
										</tr>
									<?endif;?>
										<tr class="bx-invoicing-item-row" >
											<td class="bx-invoicing-item-left"><?=Loc::getMessage('CRM_FILTER_DATE_PERIOD')?>:</td>
											<td class="bx-invoicing-item-center">
												<div class="bx-invoicing-alignment">
													<div class=" bx-invoicing-box-sizing">
														<div class="bx-input-wrap">
															</span><input type="text" name="DATE_START" id="DATE_START" class="bx-input bx-input-period" size="15" value="" onclick="BX.calendar({node:this, field:'DATE_START', form: '', bTime: false, bHideTime: false});"> - <input type="text" name="DATE_END" id="DATE_END" size="15" class="bx-input bx-input-period" value="" onclick="BX.calendar({node:this, field:'DATE_END', form: '', bTime: false, bHideTime: false});">
														</div>
													</div>
												</div>
											</td>
										</tr>
										<tr class="bx-invoicing-item-row" >
											<td class="bx-invoicing-item-left"><?=Loc::getMessage('CRM_FILTER_INVOICING_TYPE')?>:</td>
											<td class="bx-invoicing-item-center">
												<div class="bx-invoicing-alignment">
													<div class=" bx-invoicing-box-sizing">
														<span class="bx-select-wrap">
															<select name="INVOICING_TYPE" id="INVOICING_TYPE" class="bx-select">
																<?foreach ($arResult['INVOICING_TYPE'] as $id => $name):?>
																	<option value="<?=$id;?>"><?=$name;?></option>
																<?endforeach;?>
															</select>
														</span>
													</div>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="bx-invoicing-bottom-separate"></div>
							<div class="bx-invoicing-bottom">
								<input value="<?=Loc::getMessage('CRM_FILTER_SEND');?>" name="set_filter" type="button" onclick="BX.Invoicing.getMovementList();">
							</div>
						</div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<br>

<script type="text/javascript">
	BX.Invoicing.init(<?=$arResult['AJAX_OPTIONS'];?>);
	BX.message({
		CRM_RESULT_NO_FOUND: '<?=Loc::getMessage("CRM_RESULT_NO_FOUND")?>'
	});
</script>

<div class="bx-crm-edit-form-wrapper" id="bx-crm-edit-form-wrapper">
	<?php
		$APPLICATION->IncludeComponent(
			'bitrix:crm.interface.grid',
			'',
			array(
				'GRID_ID' => 'invoicing_grid',
				'HEADERS' => $arResult['HEADERS'],
				'ROWS' => $arResult['ROWS'],
				'FORM_ID' => 'invoicing_list'
			),
			$component
		);
	?>
</div>
