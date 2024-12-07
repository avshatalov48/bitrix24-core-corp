<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Text\HtmlFilter;

Extension::load([
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.buttons",
	"ui.buttons.icons",
	"ui.alerts",
	"tasks.encoding-handler",
]);

Loc::loadMessages(__FILE__);

const columnsCount = 3;

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$APPLICATION->SetTitle(Loc::getMessage('TASKS_IMPORT_FORM_TITLE'));

if ($arResult['IFRAME'])
{
	$APPLICATION->RestartBuffer(); //сбрасываем весь вывод
	CJSCore::Init("sidepanel");
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<? $APPLICATION->ShowHead();?>
	</head>
	<body class="template-<?=SITE_TEMPLATE_ID?> <?$APPLICATION->ShowProperty("BodyClass");?>" onload="window.top.BX.onCustomEvent(window.top, 'tasksIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'tasksIframeUnload');">
	<div class="tasks-iframe-header">
		<div class="pagetitle-wrap">
			<div class="pagetitle-inner-container">
				<div class="pagetitle-menu" id="pagetitle-menu"><? $APPLICATION->ShowViewContent("pagetitle");?></div>
				<div class="pagetitle" <?if($arResult['IFRAME']){?>style="padding-left: 20px; padding-right:20px;"<?}?>>
					<span id="pagetitle" class="pagetitle-item"><?$APPLICATION->ShowTitle();?></span>
				</div>
			</div>
		</div>
	</div>
<?}?>
	<div class="task-iframe-workarea" <?if($arResult['IFRAME']){?> style="padding:0 20px;" <?} else {?> style="padding: 0 15px 0 0" <?}?>>
		<?$helper->displayFatals();?>
		<?if(!$helper->checkHasFatals()):?>

			<div id="<?=$helper->getScopeId()?>" class="tasks">
				<?$helper->displayWarnings();?>
				<?CJSCore::Init(array('tasks_encoding_handler'));?>

				<form class="js-id-import-tasks-import-form" id="<?= HtmlFilter::encode($arResult['FORM_ID']) ?>" method="POST" enctype="multipart/form-data">
					<?= bitrix_sessid_post();?>
					<input type="hidden" name="step" id="step" value="<?= HtmlFilter::encode($arResult['STEP']) ?>">
					<input type="hidden" name="hidden_found_file_encoding" id="hidden_found_file_encoding" value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['FOUND_FILE_ENCODING'] ?? null)?>">
					<input type="hidden" name="hidden_file_hash" id="hidden_file_hash" value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['FILE_HASH'] ?? null)?>">
					<input type="hidden" name="hidden_default_originator" id="hidden_default_originator" value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_ORIGINATOR'] ?? null)?>">
					<input type="hidden" name="hidden_default_responsible" id="hidden_default_responsible" value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['DEFAULT_RESPONSIBLE'] ?? null)?>">
					<input type="hidden" name="hidden_show_encoding_choice" id="hidden_show_encoding_choice" value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['SHOW_ENCODING_CHOICE'] ?? null)?>">
					<input type="hidden" name="hidden_from_tmp_dir" id="hidden_from_tmp_dir" value="<?= ($arResult['IMPORT_FILE_PARAMETERS']['FROM_TMP_DIR'] ?? null) ? 'Y' : 'N'?>">
					<?php
					if ($arResult['STEP'] == 2)
					{
						$headers = '';
						foreach ($arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] as $key => $value)
						{
							$headers .= $key.'[/]'.$value.'[//]';
						}
						echo '<input type="hidden" name="hidden_headers" id="hidden_headers" value="'.HtmlFilter::encode($headers).'">';

						$requiredFields = '';
						foreach ($arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'] as $key => $value)
						{
							$requiredFields .= $key.'[/]'.$value.'[//]';
						}
						echo '<input type="hidden" name="hidden_required_fields" id="hidden_required_fields" value="'.HtmlFilter::encode($requiredFields).'">';

						$fields = '';
						foreach ($arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] as $key => $value)
						{
							$fields .= $key.'[/]'.$value.'[//]';
						}
						echo '<input type="hidden" name="hidden_fields" id="hidden_fields" value="'.HtmlFilter::encode($fields).'">';

						$rows = [];
						foreach ($arResult['IMPORT_FILE_PARAMETERS']['ROWS'] as $rowIndex => $row)
						{
							foreach ($row as $key => $value)
							{
								if (!array_key_exists($rowIndex, $rows))
								{
									$rows[$rowIndex] = '';
								}
								$rows[$rowIndex] .= $key.'[/]'.$value.'[//]';
							}
						}
						$rowString = '';
						foreach ($rows as $row)
						{
							$rowString .= $row.'[///]';
						}
						echo '<input type="hidden" name="hidden_rows" id="hidden_rows" value="'.HtmlFilter::encode($rowString).'">';

						$skippedColumns = '';
						foreach ($arResult['IMPORT_FILE_PARAMETERS']['SKIPPED_COLUMNS'] as $key => $value)
						{
							$skippedColumns .= $key.'[/]'.$value.'[//]';
						}
						echo '<input type="hidden" name="hidden_skipped_columns" id="hidden_skipped_columns" value="'.HtmlFilter::encode($skippedColumns).'">';
					}
					?>
					<table class="tasks-main-table" id="main_table">
						<tr class="tasks-import-results-panel" id="third_step_show_container" style="display: <?= ($arResult['STEP'] == 3) ? "table-row" : "none"?>">
							<td colspan="2">
								<div class="tasks-entity-card-container tasks-import-results-inner-panel">
									<div id="third_step_inner_container" class="tasks-entity-card-container-content">
										<div class="tasks-entity-card-widget tasks-import-results-inner-panel-row">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_IMPORT_RESULTS')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text tasks-import-results-inner-panel-row-content">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_PROGRESS')?></div>
													<div class="tasks-entity-widget-content-block-inner">
														<progress id="progress_bar" class="tasks-progress" value="0"></progress>
													</div>
													<div>
														<table width="100%" cellpadding="0" cellspacing="0">
															<tr>
																<td>
																	<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text tasks-import-results-counts">
																		<div class="tasks-entity-widget-content-block-title tasks-import-results-counts-labels"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_TOTAL_TASKS_COUNT')?>:
																			<span class="tasks-import-results-counts-total" id="imports_total_count">
																				<svg class="tasks-circle-loader-circular" viewBox="25 25 50 50">
																					<circle class="tasks-circle-loader-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"></circle>
																				</svg>
																			</span>
																		</div>
																	</div>
																	<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text tasks-import-results-counts">
																		<div class="tasks-entity-widget-content-block-title tasks-import-results-counts-labels"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_PROCESSED_TASKS_COUNT')?>:
																			<span class="tasks-import-results-counts-processed" id="processed_count">0</span>
																		</div>
																	</div>
																	<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text tasks-import-results-counts">
																		<div class="tasks-entity-widget-content-block-title tasks-import-results-counts-labels"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_SUCCESSFUL_IMPORTS')?>:
																			<span class="tasks-import-results-counts-successful" id="successful_imports">0</span>
																		</div>
																	</div>
																	<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text tasks-import-results-counts">
																		<div class="tasks-entity-widget-content-block-title tasks-import-results-counts-labels"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_ERROR_IMPORTS')?>:
																			<span class="tasks-import-results-counts-error" id="error_imports">0</span>
																		</div>
																	</div>
																</td>
																<td class="tasks-import-results-button">
																	<input type="hidden" id="hidden_force_import_stop" value="N">
																	<input type="hidden" id="hidden_import_done" value="N">
																	<button class="ui-btn ui-btn-icon-stop tasks-force-import-stop" type="button" id="force_import_stop"><?= Loc::getMessage('TASKS_IMPORT_BUTTONS_STOP')?></button>
																</td>
															</tr>
														</table>
													</div>
													<div id="error_imports_messages_container" class="tasks-entity-widget-content tasks-import-results-error-messages"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
						<tr>
							<td class="tasks-first-step-column" id="first_step_show_container" style="display: <?= ($arResult['STEP'] == 1) ? 'block' : 'none'?>">
								<div class="tasks-entity-card-container tasks-first-step-column-inner">
									<div id="first_step_inner_container" class="tasks-entity-card-container-content tasks-steps-columns-background">
										<div class="tasks-entity-card-widget">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_IMPORT_PARAMETERS')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_FILE')?></div>
													<div id="file_label_container">
														<div class="tasks-file-label-container" id="file_label">
															<input type="file" hidden id="file" name="file">
															<table width="100%">
																<tr>
																	<td width="1%">
																		<label class="ui-btn ui-btn-light-border ui-btn-xs" for="file"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_IMPORT_FILE_CHOOSE')?></label>
																	</td>
																	<td>
																		<input class="tasks-file-name" id="file_name" name="file_name" readonly
																			   value="<?= HtmlFilter::encode($arResult['IMPORT_FILE_PARAMETERS']['FILE_NAME'])?>">
																	</td>
																</tr>
															</table>
														</div>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_ENCODING')?></div>
													<div class="tasks-entity-widget-content-block-inner">
														<div class="tasks-entity-widget-content-block-select">
															<select class="tasks-entity-widget-content-select" id="file_encoding" name="file_encoding">
																<?foreach ($arResult['ENCODINGS'] as $key => $name)
																{
																	$selected = (($arResult['IMPORT_FILE_PARAMETERS']['FILE_ENCODING'] ?? null) == $key) ? " selected" : "";
																	?>
																	<option value="<?= HtmlFilter::encode($key)?>"<?= HtmlFilter::encode($selected)?>><?= HtmlFilter::encode($name)?></option>
																<?}?>
															</select>
														</div>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_DEFAULT_ORIGINATOR')?></div>
													<div class="tasks-entity-widget-content-block-inner tasks-member-selector-container">
														<?
														$APPLICATION->IncludeComponent(
															'bitrix:tasks.widget.member.selector',
															'',
															array(
																'TEMPLATE_CONTROLLER_ID' => 'originator',
																'MAX' => 1,
																'MIN' => 1,
																'TYPES' => array('USER', 'USER.EXTRANET'),
																'INPUT_PREFIX' => 'default_originator',
																'SOLE_INPUT_IF_MAX_1' => 'Y',
																'DATA' => array(0 => $arResult['DEFAULT_ORIGINATOR']),
																'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER'],
																'READ_ONLY' => 'N',
															),
															false,
															array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
														);
														?>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_DEFAULT_ASSIGNEE')?></div>
													<div class="tasks-entity-widget-content-block-inner tasks-member-selector-container">
														<?
														$APPLICATION->IncludeComponent(
															'bitrix:tasks.widget.member.selector',
															'',
															array(
																'TEMPLATE_CONTROLLER_ID' => 'responsible',
																'MAX' => 1,
																'MIN' => 1,
																'TYPES' => array('USER', 'USER.EXTRANET'),
																'INPUT_PREFIX' => 'default_responsible',
																'SOLE_INPUT_IF_MAX_1' => 'Y',
																'DATA' => array(0 => $arResult['DEFAULT_RESPONSIBLE']),
																'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER'],
																'READ_ONLY' => 'N',
															),
															false,
															array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
														);
														?>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_NAME_FORMAT')?></div>
													<div class="tasks-entity-widget-content-block-inner">
														<div class="tasks-entity-widget-content-block-select">
															<select class="tasks-entity-widget-content-select" id="name_format" name="name_format">
																<? foreach ($arResult['NAME_FORMATS'] as $key => $name)
																{
																	$selected = ($arResult['IMPORT_FILE_PARAMETERS']['NAME_FORMAT'] == $key) ? " selected" : "";
																	?>
																	<option value="<?= HtmlFilter::encode($key)?>"<?= HtmlFilter::encode($selected)?>><?= HtmlFilter::encode($name)?></option>
																<?}?>
															</select>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="tasks-entity-card-widget">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_FILE_FORMAT')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_SEPARATOR')?></div>
													<div class="tasks-entity-widget-content-block-inner" id="separator_container">
														<div class="tasks-entity-widget-content-block-select">
															<select class="tasks-entity-widget-content-select" id="separator" name="separator">
																<? foreach ($arResult['SEPARATORS'] as $key => $name)
																{
																	$selected = (($arResult['IMPORT_FILE_PARAMETERS']['SEPARATOR_TEXT'] ?? null) == $key) ? " selected" : "";
																	?>
																	<option value="<?= HtmlFilter::encode($key)?>"<?= HtmlFilter::encode($selected)?>><?= HtmlFilter::encode($name)?></option>
																<?}?>
															</select>
														</div>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-checkbox">
													<div class="tasks-entity-widget-content-block-inner">
														<label class="tasks-entity-widget-content-block-checkbox-label">
															<input class="tasks-entity-widget-content-checkbox" type="checkbox" id="headers_in_first_row" name="headers_in_first_row"
																<?$checked = " checked";
																if (isset($arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW']) &&
																	!$arResult['IMPORT_FILE_PARAMETERS']['HEADERS_IN_FIRST_ROW']) $checked = ""?>
																<?= HtmlFilter::encode($checked)?> />
															<span class="tasks-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_HEADERS_IN_FIRST_ROW')?></span>
														</label>
													</div>
												</div>
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-checkbox">
													<div class="tasks-entity-widget-content-block-inner">
														<label class="tasks-entity-widget-content-block-checkbox-label">
															<input class="tasks-entity-widget-content-checkbox" type="checkbox" id="skip_empty_columns" name="skip_empty_columns"
																<?$checked = " checked";
																if (isset($arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS']) &&
																	!$arResult['IMPORT_FILE_PARAMETERS']['SKIP_EMPTY_COLUMNS']) $checked = ""?>
																<?= HtmlFilter::encode($checked)?> />
															<span class="tasks-entity-widget-content-block-checkbox-description"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SKIP_EMPTY_COLUMNS')?></span>
														</label>
													</div>
												</div>
											</div>
										</div>
										<div class="tasks-entity-card-widget">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_FILE_TEMPLATE')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-custom-link">
													<div class="tasks-entity-widget-content-block-title"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_TEMPLATE')?></div>
													<div class="tasks-entity-widget-content-block-inner">
														<a href="?download=csv"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_FILE_TEMPLATE_DOWNLOAD')?></a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</td>
							<td class="tasks-second-step-column" id="second_step_show_container" style="display: <?= ($arResult['STEP'] == 2) ? 'block' : 'none'?>">
								<div class="tasks-entity-card-container tasks-second-step-column-inner">
									<div id="second_step_inner_container" class="tasks-entity-card-container-content tasks-steps-columns-background">
										<div class="tasks-entity-card-widget">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_MATCHING_FIELDS')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div class="tasks-import-required-fields" id="required_fields_container">
													<?= Loc::getMessage('TASKS_IMPORT_FIELDS_REQUIRED_FIELDS')?>:
													<?= HtmlFilter::encode(implode(', ', $arResult['IMPORT_FILE_PARAMETERS']['REQUIRED_FIELDS'])) ?>.
												</div>
												<?
												$rows = array();
												$headers = $arResult['IMPORT_FILE_PARAMETERS']['HEADERS'];
												$i = 0;
												foreach ($headers as $key => $value)
												{
													$rows[intval(floor($i / columnsCount))][$i] = $value;
													$i++;
												}
												?>
												<table width="100%" cellspacing="0" cellpadding="0">
													<?foreach ($rows as $row)
													{?>
														<tr>
															<?foreach ($row as $key => $value)
															{
																if ($key % columnsCount == 0)
																{
																	$paddingLeft = "0";
																	$paddingRight = "5px";
																}
																elseif ($key % columnsCount == 1)
																{
																	$paddingLeft = "5px";
																	$paddingRight = "5px";
																}
																elseif ($key % columnsCount == 2)
																{
																	$paddingLeft = "5px";
																	$paddingRight = "0";
																}
																else
																{
																	$paddingLeft = "0";
																	$paddingRight = "0";
																}
																?>
																<td class="tasks-matching-fields-table-td" style="padding-left: <?= HtmlFilter::encode($paddingLeft)?>; padding-right: <?= HtmlFilter::encode($paddingRight)?>">
																	<div class="tasks-entity-widget-content-block tasks-entity-widget-content-block-field-text">
																		<div class="tasks-entity-widget-content-block-title"><?= HtmlFilter::encode($value)?></div>
																		<div class="tasks-entity-widget-content-block-inner">
																			<div class="tasks-entity-widget-content-block-select">
																				<select class="tasks-entity-widget-content-select" id="<?= HtmlFilter::encode("field_{$key}")?>" name="<?= HtmlFilter::encode("field_{$key}")?>">
																					<?
																					$selectedValue = isset($arResult['IMPORT_FILE_PARAMETERS']['FIELDS'][mb_strtoupper($value)]) ?
																						mb_strtoupper($value) : array_search(mb_strtoupper($value), $arResult['IMPORT_FILE_PARAMETERS']['UPPER_FIELDS']);

																					foreach ($arResult['IMPORT_FILE_PARAMETERS']['FIELDS'] as $id => $name)
																					{
																						if ($arResult['STEP'] == 2 && isset($arResult['ERRORS']['REQUIRED_FIELDS']))
																							$selected = ($arResult['IMPORT_FILE_PARAMETERS']['SELECTED_FIELDS'][$key] == $id) ? ' selected' : '';
																						elseif ($arResult['STEP'] == 2)
																							$selected = ($selectedValue == $id) ? ' selected' : '';
																						?>
																						<option value="<?= HtmlFilter::encode($id)?>"<?= HtmlFilter::encode($selected)?>><?= HtmlFilter::encode($name)?></option>
																					<?}?>
																				</select>
																			</div>
																		</div>
																	</div>
																</td>
															<?}?>
														</tr>
													<?}?>
												</table>
											</div>
										</div>
										<div class="tasks-entity-card-widget">
											<div class="tasks-entity-card-widget-title">
												<span class="tasks-entity-card-widget-title-text"><?= Loc::getMessage('TASKS_IMPORT_FIELDS_SECTION_IMPORT_DATA_EXAMPLE')?></span>
											</div>
											<div class="tasks-entity-widget-content">
												<div id="tasks_import_example_table_container" class="tasks-import-example-table-container">
													<table cellspacing="0" cellpadding="0" class="tasks-import-example-table">
														<tr>
															<?foreach ($arResult['IMPORT_FILE_PARAMETERS']['HEADERS'] as $key => $value):?>
																<th><?= HtmlFilter::encode($value)?></th>
															<?endforeach;?>
														</tr>
														<?
														if (!isset($arResult['IMPORT_FILE_PARAMETERS']['ROWS']))
															$arResult['IMPORT_FILE_PARAMETERS']['ROWS'] = array();
														foreach ($arResult['IMPORT_FILE_PARAMETERS']['ROWS'] as $row)
														{?>
															<tr>
																<?foreach ($row as $tdData):?>
																	<td><?= HtmlFilter::encode($tdData)?></td>
																<?endforeach;?>
															</tr>
														<?}?>
													</table>
												</div>
												<script>
													formWidth = BX('<?= CUtil::JSEscape($arResult['FORM_ID']) ?>').offsetWidth;
													rightColumnWidth = formWidth - 40;
													BX('tasks_import_example_table_container').style.width = rightColumnWidth + 'px';
												</script>
											</div>
										</div>
									</div>
								</div>
							</td>
						</tr>
					</table>
					<div class="tasks-footer-buttons-container">
						<div class="tasks-entity-section-control">
							<?
							$nextText = ($arResult['STEP'] == 3) ? Loc::getMessage('TASKS_IMPORT_BUTTONS_DONE') : Loc::getMessage('TASKS_IMPORT_BUTTONS_NEXT');
							$backText = ($arResult['STEP'] == 3) ? Loc::getMessage('TASKS_IMPORT_BUTTONS_NEW') : Loc::getMessage('TASKS_IMPORT_BUTTONS_BACK');
							$display = ($arResult['STEP'] == 3) ? 'none' : 'inline-block';
							?>
							<button class="ui-btn ui-btn-success" id="next" name="next" type="button" style="display: <?= HtmlFilter::encode($display)?>;"><?= HtmlFilter::encode($nextText)?></button>
							<?
							if ($arResult['STEP'] !== 1) {?>
								<button class="ui-btn ui-btn-light-border" id="back" name="back" type="button" style="display: <?= HtmlFilter::encode($display)?>;"><?= HtmlFilter::encode($backText)?></button>
							<?}
							if ($arResult['STEP'] !== 3) {?>
								<a class="ui-btn ui-btn-link" id="cancel" name="cancel" type="button"><?= Loc::getMessage('TASKS_IMPORT_BUTTONS_CANCEL')?></a>
							<?}
							if ($arResult['STEP'] == 3) {?>
								<button class="ui-btn ui-btn-icon-stop" id="stop" name="stop" type="button"><?= Loc::getMessage('TASKS_IMPORT_BUTTONS_STOP')?></button>
							<?}?>
						</div>
					</div>
				</form>
			</div>

			<script>
				BX.ready(
					function ()
					{
						var tasksImport = new BX.TasksImport(<?= Json::encode([
							'formId' => $arResult['FORM_ID'],
							'step' => $arResult['STEP'],
							'importFileParameters' => $arResult['IMPORT_FILE_PARAMETERS'],
							'errors' => $arResult['ERRORS'],
							'isFramePopup' => $arResult['IFRAME'],
						]) ?>);

						var encodingHandler = new BX.EncodingHandler({});
						var formId = '<?= CUtil::JSEscape($arResult['FORM_ID']) ?>';

						BX.bind(BX('next'), 'click', function() {
							if (
								BX('step').value === "1"
								&& BX('hidden_from_tmp_dir').value === 'N'
								&& !tasksImport.validateFile(BX('file').files[0])
							)
							{
								return;
							}

							if (BX('step').value === "1" && BX('file_encoding').value === 'auto')
							{
								if (BX('hidden_from_tmp_dir').value === 'Y')
								{
									if (BX('hidden_show_encoding_choice').value === 'Y')
									{
										encodingHandler.createPopup(
											<?= Json::encode($arResult['ENCODED_RESULTS'] ?? null) ?>,
											formId,
											'hidden_found_file_encoding'
										);
									}
									else
									{
										BX.submit(BX(formId), 'next');
									}
								}
								else
								{
									encodingHandler.handleEncodings({
										formId: formId,
										resultEncodingElementId: 'hidden_found_file_encoding',
										file: BX('file').files[0],
										charsets: <?= Json::encode($arResult['CHARSETS']) ?>
									});
								}
							}
							else
							{
								BX.submit(BX(formId), 'next');
							}
						});
					}
				);
			</script>

			<?$helper->initializeExtension();?>

		<?endif?>
	</div>