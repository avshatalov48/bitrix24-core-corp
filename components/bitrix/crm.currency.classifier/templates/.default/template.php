<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
]);

Loc::loadMessages(__FILE__);

if (isset($arResult['ERRORS']['CRM']) || isset($arResult['ERRORS']['CURRENCY']))
{
	ShowError($arResult['ERRORS']['CRM']);
	ShowError($arResult['ERRORS']['CURRENCY']);
	return;
}
if (isset($arResult['ERRORS']['NOT_FOUND']))
{
	ShowError($arResult['ERRORS']['NOT_FOUND']);
	return;
}
if (isset($arResult['ERRORS']['ADD']))
{
	ShowError($arResult['ERRORS']['ADD']);
}
elseif (isset($arResult['ERRORS']['UPDATE']))
{
	ShowError($arResult['ERRORS']['UPDATE']);
}

$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/entity-editor/css/style.css");
$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/slider.css");
?>

<? $padding = $arResult['IFRAME'] ? '0' : '0 15px 0 0';?>
<style>
	.workarea-content-paddings {
		padding: <?= HtmlFilter::encode($padding);?> !important;
	}
</style>

<form method="POST" id="currency_add" name="currency_add">
	<?= bitrix_sessid_post();?>
	<input id="after_adding" name="after_adding" type="hidden" value="<?= $arResult['AFTER_ADDING']?>">
	<input id="current_form_mode" name="current_form_mode" type="hidden" value="<?= HtmlFilter::encode($arResult['CURRENT_FORM_MODE']);?>">
	<input id="target_form_mode" name="target_form_mode" type="hidden" value="<?= HtmlFilter::encode($arResult['TARGET_FORM_MODE']);?>">

	<button class="crm-form-mode-switcher" id="form_mode_switcher" name="form_mode_switcher" type="button"
		<? if ($arResult['CURRENT_FORM_MODE'] == 'EDIT') echo "style=\"display: none\"";?>></button>
	<div id="form_wrapper">
		<table id="add_form" name="adding_form" class="crm-table">
			<tr>
				<td class="crm-table-left-column">
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<span class="crm-entity-card-widget-title-text"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_SECTION_SEARCH')?></span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SEARCH')?></div>
										<div class="crm-entity-widget-content-block-inner">
											<input class="crm-entity-widget-content-input" id="add_classifier_currency_needle" name="add_classifier_currency_needle"
												   placeholder="<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SEARCH_PLACEHOLDER')?>"
												   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['ADD']['GENERAL']['NEEDLE']);?>">
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-select">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SELECT')?></div>
										<div class="crm-entity-widget-content-block-inner">
											<div class="crm-entity-widget-content-block-select">
												<select class="crm-entity-widget-content-select crm-select-hidden-arrow" id="add_classifier_currency_id" name="add_classifier_currency_id" size="8">
													<? foreach ($arResult['CLASSIFIER'] as $key => $value)
														echo "<option value=".HtmlFilter::encode($key).">".HtmlFilter::encode($value[mb_strtoupper(LANGUAGE_ID)]['FULL_NAME'])."</option>";?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<span class="crm-entity-card-widget-title-text"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_SECTION_PROPERTIES')?></span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_NUM_CODE')?></div>
										<div class="crm-entity-widget-content-block-inner" id="add_num_code" name="add_num_code" type="text"></div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SYM_CODE')?></div>
										<input hidden id="add_hidden_sym_code" name="add_sym_code">
										<div class="crm-entity-widget-content-block-inner" id="add_sym_code" name="add_sym_code" type="text"></div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title">
											<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_EXCHANGE_RATE')?>
											<span style="color: #ff5752">*</span>
										</div>
										<div class="crm-entity-widget-content-block-inner">
											<table class="crm-block-inner-table">
												<tr>
													<td class="crm-block-inner-table-first-td">
														<input class="crm-entity-widget-content-input" id="add_nominal" name="add_nominal"
															   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['ADD']['GENERAL']['NOMINAL']);?>" />
													</td>
													<td width="43px">
														<label id="add_nominal_sym_code" name="add_nominal_sym_code"></label>
													</td>
													<td width="10px">
														<label>=</label>
													</td>
													<td class="crm-block-inner-table-middle-td">
														<input class="crm-entity-widget-content-input" id="add_exchange_rate" name="add_exchange_rate"
															   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['ADD']['GENERAL']['EXCHANGE_RATE']);?>" />
													</td>
													<td width="43px">
														<label><?= HtmlFilter::encode($arResult['BASE_CURRENCY_ID']);?></label>
													</td>
												</tr>
												<tr>
													<td class="crm-block-inner-table-first-td" colspan="2" id="add_nominal_container"></td>
													<td></td>
													<td class="crm-block-inner-table-middle-td" colspan="2" id="add_exchange_rate_container"></td>
												</tr>
											</table>
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SORT_INDEX')?></div>
										<div class="crm-entity-widget-content-block-inner" id="add_sort_index_container">
											<input class="crm-entity-widget-content-input" id="add_sort_index" name="add_sort_index"
												   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['ADD']['GENERAL']['SORT_INDEX']);?>">
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox">
										<div class="crm-entity-widget-content-block-inner">
											<label class="crm-entity-widget-content-block-checkbox-label">
												<input class="crm-entity-widget-content-checkbox" id="add_base_for_reports" name="add_base_for_reports" type="checkbox"
													<? if ($arResult['LAST_VALUES']['ADD']['GENERAL']['BASE_FOR_REPORTS']) echo "checked";?>>
												<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_BASE_FOR_REPORTS')?></span>
											</label>
										</div>
									</div>
									<?php //if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled()): ?>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox">
											<div class="crm-entity-widget-content-block-inner">
												<label class="crm-entity-widget-content-block-checkbox-label">
													<input class="crm-entity-widget-content-checkbox" id="add_base_for_count" name="add_base_for_count" type="checkbox"
														<? if ($arResult['LAST_VALUES']['ADD']['GENERAL']['BASE_FOR_COUNT']) echo "checked";?>>
													<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_BASE_FOR_COUNT')?></span>
												</label>
											</div>
										</div>
									<?php //endif;?>
								</div>
							</div>
						</div>
					</div>
				</td>
				<td class="crm-table-right-column">
					<div class="crm-entity-stream-container" style="width: 100%; vertical-align: top;">
						<? foreach ($arResult['LANGUAGES'] as $key => $value)
						{
							$upperKey = mb_strtoupper($key);
						?>
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<span class="crm-entity-card-widget-title-text"><?= HtmlFilter::encode($value);?></span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_FULL_NAME')?></div>
										<div class="crm-entity-widget-content-block-inner" id="add_full_name_<?= HtmlFilter::encode($key);?>"
											 name="add_full_name_<?= HtmlFilter::encode($key);?>" type="text"></div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_EXAMPLE')?></div>
										<div class="crm-entity-widget-content-block-inner" id="add_example_<?= HtmlFilter::encode($key);?>"
											 name="add_example_<?= HtmlFilter::encode($key);?>" type="text"></div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox">
										<div class="crm-entity-widget-content-block-inner">
											<label class="crm-entity-widget-content-block-checkbox-label">
												<input class="crm-entity-widget-content-checkbox" id="add_hide_zero_<?= HtmlFilter::encode($key);?>"
													   name="add_hide_zero_<?= HtmlFilter::encode($key);?>" type="checkbox"
													<? if ($arResult['LAST_VALUES']['ADD'][$upperKey]['HIDE_ZERO'] == 'Y') echo "checked";?>>
												<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_HIDE_ZERO')?></span>
											</label>
										</div>
									</div>
								</div>
							</div>
						<?}?>
					</div>
				</td>
			</tr>
		</table>
		<table id="edit_form" name="editing_form" class="crm-table">
			<tr>
				<td class="crm-table-left-column">
					<div class="crm-entity-card-container" style="width: 100%">
						<div class="crm-entity-card-container-content">
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<span class="crm-entity-card-widget-title-text"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_SECTION_PROPERTIES')?></span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title">
											<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SYM_CODE')?>
											<span style="color: #ff5752">*</span>
										</div>
										<div class="crm-entity-widget-content-block-inner" id="edit_sym_code_container">
											<input class="crm-entity-widget-content-input" id="edit_sym_code" name="edit_sym_code"
												   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT']['GENERAL']['SYM_CODE']);?>"
												<? if ($arResult['PRIMARY_FORM_MODE'] == 'EDIT') echo "readonly";?>>
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title">
											<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_EXCHANGE_RATE')?>
											<span style="color: #ff5752">*</span>
										</div>
										<div class="crm-entity-widget-content-block-inner">
											<table class="crm-block-inner-table">
												<tr>
													<td class="crm-block-inner-table-first-td">
														<input class="crm-entity-widget-content-input" id="edit_nominal" name="edit_nominal"
															   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT']['GENERAL']['NOMINAL']);?>" />
													</td>
													<td width="43px">
														<label id="edit_nominal_sym_code" name="edit_nominal_sym_code">
															<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT']['GENERAL']['SYM_CODE']);?>
														</label>
													</td>
													<td width="10px">
														<label>=</label>
													</td>
													<td class="crm-block-inner-table-middle-td">
														<input class="crm-entity-widget-content-input" id="edit_exchange_rate" name="edit_exchange_rate"
															   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT']['GENERAL']['EXCHANGE_RATE']);?>" />
													</td>
													<td width="43px">
														<label><?= HtmlFilter::encode($arResult['BASE_CURRENCY_ID']);?></label>
													</td>
												</tr>
												<tr>
													<td class="crm-block-inner-table-first-td" colspan="2" id="edit_nominal_container"></td>
													<td></td>
													<td class="crm-block-inner-table-middle-td" colspan="2" id="edit_exchange_rate_container"></td>
												</tr>
											</table>
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SORT_INDEX')?></div>
										<div class="crm-entity-widget-content-block-inner" id="edit_sort_index_container">
											<input class="crm-entity-widget-content-input" id="edit_sort_index" name="edit_sort_index"
												   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT']['GENERAL']['SORT_INDEX']);?>">
										</div>
									</div>
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox">
										<div class="crm-entity-widget-content-block-inner">
											<label class="crm-entity-widget-content-block-checkbox-label">
												<input class="crm-entity-widget-content-checkbox" id="edit_base_for_reports" name="edit_base_for_reports" type="checkbox"
												<? if ($arResult['LAST_VALUES']['EDIT']['GENERAL']['BASE_FOR_REPORTS']) echo "checked";?>>
												<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_BASE_FOR_REPORTS')?></span>
											</label>
										</div>
									</div>
									<?php //if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled()): ?>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox --last-block">
											<div class="crm-entity-widget-content-block-inner">
												<label class="crm-entity-widget-content-block-checkbox-label">
													<input class="crm-entity-widget-content-checkbox" id="edit_base_for_count" name="edit_base_for_count" type="checkbox"
														<? if ($arResult['LAST_VALUES']['EDIT']['GENERAL']['BASE_FOR_COUNT']) echo "checked";?>>
													<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_BASE_FOR_COUNT')?></span>
												</label>
											</div>
										</div>
									<?php //endif;?>
								</div>
							</div>
						</div>
					</div>
				</td>
				<td class="crm-table-right-column">
					<div class="crm-entity-stream-container" style="width: 100%; vertical-align: top;">
						<? foreach ($arResult['LANGUAGES'] as $key => $value)
						{
							$upperKey = mb_strtoupper($key);
						?>
							<div class="crm-entity-card-widget">
								<div class="crm-entity-card-widget-title">
									<span class="crm-entity-card-widget-title-text"><?= HtmlFilter::encode($value);?></span>
								</div>
								<div class="crm-entity-widget-content">
									<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
										<div class="crm-entity-widget-content-block-title">
											<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_FULL_NAME')?>
											<span style="color: #ff5752">*</span>
										</div>
										<div class="crm-entity-widget-content-block-inner" id="edit_full_name_container_<?= HtmlFilter::encode($key);?>">
											<input class="crm-entity-widget-content-input" id="edit_full_name_<?= HtmlFilter::encode($key);?>"
												   name="edit_full_name_<?= HtmlFilter::encode($key);?>"
												   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['FULL_NAME']);?>">
										</div>
									</div>
									<table class="crm-block-inner-table">
										<tr>
											<td class="crm-block-inner-table-first-td" width="33%">
												<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
													<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_FORMAT_TEMPLATE')?></div>
													<div class="crm-entity-widget-content-block-inner">
														<div class="crm-entity-widget-content-block-select">
															<select class="crm-entity-widget-content-custom-select" id="edit_format_template_<?= HtmlFilter::encode($key);?>"
																	name="edit_format_template_<?= HtmlFilter::encode($key);?>">
																<? foreach ($arResult['FORMAT_TEMPLATES'] as $k => $template)
																{
																	$selected = ($arResult['LAST_VALUES']['EDIT'][$upperKey]['FORMAT_TEMPLATE'] == $k) ? ' selected' : '';
																	echo "<option value=".HtmlFilter::encode($k.$selected).">".HtmlFilter::encode($template)."</option>";
																}?>
															</select>
														</div>
													</div>
												</div>
											</td>
											<td class="crm-block-inner-table-middle-td" width="33%">
												<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
													<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SIGN')?></div>
													<div class="crm-entity-widget-content-block-inner" id="edit_sign_container_<?= HtmlFilter::encode($key);?>">
														<input class="crm-entity-widget-content-input" id="edit_sign_<?= HtmlFilter::encode($key);?>"
															   name="edit_sign_<?= HtmlFilter::encode($key);?>"
															   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['SIGN']);?>">
													</div>
												</div>
											</td>
											<td class="crm-block-inner-table-last-td" width="33%">
												<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
													<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_SIGN_POSITION')?></div>
													<div class="crm-entity-widget-content-block-inner">
														<div class="crm-entity-widget-content-block-select">
															<select class="crm-entity-widget-content-custom-select" id="edit_sign_position_<?= HtmlFilter::encode($key);?>"
																	name="edit_sign_position_<?= HtmlFilter::encode($key);?>">
																<? foreach ($arResult['SIGN_POSITIONS'] as $k => $pos)
																{
																	$selected = ($arResult['LAST_VALUES']['EDIT'][$upperKey]['SIGN_POSITION'] == $k) ? ' selected' : '';
																	echo "<option value=".HtmlFilter::encode($k.$selected).">".HtmlFilter::encode($pos)."</option>";
																}?>
															</select>
														</div>
													</div>
												</div>
											</td>
										</tr>
										<? $contentExpanded = ($arResult['LAST_VALUES']['EDIT'][$upperKey]['CONTENT_EXPANDED'] == 'Y') ? true : false;?>
										<tr>
											<td colspan="3">
												<button class="crm-expand-button" style="display: block" id="expand_button_<?= HtmlFilter::encode($key);?>"
														name="expand_button_<?= HtmlFilter::encode($key);?>" type="button">
													<?= Loc::getMessage($contentExpanded ? 'CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_HIDE' : 'CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_SHOW')?>
												</button>
											</td>
										</tr>
									</table>
									<input type="hidden" id="expandable_content_hidden_input_<?= HtmlFilter::encode($key);?>" name="expandable_content_hidden_input_<?= HtmlFilter::encode($key);?>"
										   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['CONTENT_EXPANDED']);?>">
									<div class="crm-expandable-content<? if ($contentExpanded) echo " crm-expandable-content-active";?>"
										 id="expandable_content_<?= HtmlFilter::encode($key);?>">
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
											<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_THOUSANDS_VARIANT')?></div>
											<div class="crm-entity-widget-content-block-inner">
												<table class="crm-block-inner-table">
													<tr>
														<td class="crm-block-inner-table-first-td" width="65%">
															<div class="crm-entity-widget-content-block-select">
																<select class="crm-entity-widget-content-custom-select" id="edit_thousands_variant_<?= HtmlFilter::encode($key);?>"
																		name="edit_thousands_variant_<?= HtmlFilter::encode($key);?>" style="white-space: nowrap !important;">
																	<? foreach ($arResult['THOUSANDS_VARIANTS'] as $k => $var)
																	{
																		$selected = ($arResult['LAST_VALUES']['EDIT'][$upperKey]['THOUSANDS_VARIANT'] == $k) ? ' selected' : '';
																		echo "<option value=".HtmlFilter::encode($k.$selected).">".HtmlFilter::encode($var)."</option>";
																	}?>
																</select>
															</div>
														</td>
														<td class="crm-block-inner-table-last-td">
															<input class="crm-entity-widget-content-input" id="edit_thousands_sep_<?= HtmlFilter::encode($key);?>"
																   name="edit_thousands_sep_<?= HtmlFilter::encode($key);?>"
																   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['THOUSANDS_SEP']);?>"
																   <? if ($arResult['LAST_VALUES']['EDIT'][$upperKey]['THOUSANDS_VARIANT'] !== 'OWN') echo "readonly";?>>
														</td>
													</tr>
													<tr>
														<td class="crm-block-inner-table-first-td" id="edit_thousands_variant_container_<?= HtmlFilter::encode($key);?>"></td>
														<td class="crm-block-inner-table-last-td" id="edit_thousands_sep_container_<?= HtmlFilter::encode($key);?>"></td>
													</tr>
												</table>
											</div>
										</div>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
											<div class="crm-entity-widget-content-block-title">
												<?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_DEC_POINT')?>
												<span style="color: #ff5752">*</span>
											</div>
											<div class="crm-entity-widget-content-block-inner" id="edit_dec_point_container_<?= HtmlFilter::encode($key);?>">
												<input class="crm-entity-widget-content-input" id="edit_dec_point_<?= HtmlFilter::encode($key);?>"
													   name="edit_dec_point_<?= HtmlFilter::encode($key);?>"
													   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['DEC_POINT']);?>">
											</div>
										</div>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-text">
											<div class="crm-entity-widget-content-block-title"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_DECIMALS')?></div>
											<div class="crm-entity-widget-content-block-inner" id="edit_decimals_container_<?= HtmlFilter::encode($key);?>">
												<input class="crm-entity-widget-content-input" id="edit_decimals_<?= HtmlFilter::encode($key);?>"
													   name="edit_decimals_<?= HtmlFilter::encode($key);?>"
													   value="<?= HtmlFilter::encode($arResult['LAST_VALUES']['EDIT'][$upperKey]['DECIMALS']);?>">
											</div>
										</div>
										<div class="crm-entity-widget-content-block crm-entity-widget-content-block-field-checkbox">
											<div class="crm-entity-widget-content-block-inner">
												<label class="crm-entity-widget-content-block-checkbox-label">
													<input class="crm-entity-widget-content-checkbox" id="edit_hide_zero_<?= HtmlFilter::encode($key);?>"
														   name="edit_hide_zero_<?= HtmlFilter::encode($key);?>" type="checkbox"
														<? if ($arResult['LAST_VALUES']['EDIT'][$upperKey]['HIDE_ZERO'] == 'Y') echo "checked";?>>
													<span class="crm-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FIELD_HIDE_ZERO')?></span>
												</label>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?}?>
					</div>
				</td>
			</tr>
		</table>
	</div>
	<div class="crm-footer-container">
		<div class="crm-entity-section-control">
			<button class="webform-small-button webform-small-button-accept webform-button-active" id="save" name="save" type="button">
				<span class="webform-small-button-text"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FORM_BUTTONS_SAVE')?></span>
			</button>
			<button class="webform-small-button webform-button-active" id="apply" name="apply" type="button">
				<span class="webform-small-button-text"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FORM_BUTTONS_APPLY')?></span>
			</button>
			<a class="webform-button-link" id="cancel" name="cancel" type="button"><?= Loc::getMessage('CRM_CURRENCY_CLASSIFIER_FORM_BUTTONS_CANCEL')?></a>
		</div>
	</div>
</form>

<script>
	BX(function () {
		BX.message({
			CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_TITLE: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_TITLE')?>',
			CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_MESSAGE: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_MESSAGE')?>',
			CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_YES: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_YES')?>',
			CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_NO: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_POPUP_WINDOW_NO')?>',
			CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_MANUALLY: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_MANUALLY')?>',
			CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_CLASSIFIER: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FORM_SWITCHER_CLASSIFIER')?>',
			CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_SHOW: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_SHOW')?>',
			CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_HIDE: '<?= GetMessageJS('CRM_CURRENCY_CLASSIFIER_FIELD_ALL_PARAMETERS_HIDE')?>'
		});

		BX.CrmCurrencyClassifier = new BX.CurrencyClassifierClass(<?= Json::encode(array(
			'index' => $arResult['LAST_VALUES']['ADD']['GENERAL']['SELECTED_ID'],
			'lastIndex' => $arResult['LAST_VALUES']['ADD']['GENERAL']['SELECTED_ID'],
			'needle' => $arResult['LAST_VALUES']['ADD']['GENERAL']['NEEDLE'],
			'currencies' => $arResult['CLASSIFIER'],
			'baseLanguage' => LANGUAGE_ID,
			'lids' => $arResult['LANGUAGE_IDS'],
			'closeSlider' => $arResult['CLOSE_SLIDER'],
			'isFramePopup' => $arResult['IFRAME'],
			'pathToCurrencyList' => $arResult['PATH_TO_CURRENCY_LIST'],
			'errors' => $arResult['ERRORS'],
			'separators' => $arResult['THOUSANDS_SEP'],
			'formatTemplates' => $arResult['FORMAT_TEMPLATES'],
			'existingCurrencies' => $arResult['EXISTING_CURRENCIES'],
			'primaryFormMode' => $arResult['PRIMARY_FORM_MODE']
		));?>);
	});
</script>
