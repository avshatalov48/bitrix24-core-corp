<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;

Loc::loadMessages(__FILE__);
?>

<?if(!empty($arResult['ERRORS']['FATAL'])):?>

	<?foreach($arResult['ERRORS']['FATAL'] as $error):?>
		<?=ShowError($error)?>
	<?endforeach?>

<?else:?>

	<?foreach($arResult['ERRORS']['NONFATAL'] as $error):?>
		<?=ShowError($error)?>
	<?endforeach?>

	<?
	global $APPLICATION;

	CJSCore::Init();
	$APPLICATION->AddHeadScript('/bitrix/js/sale/core_ui_widget.js');
	$APPLICATION->AddHeadScript('/bitrix/js/sale/core_ui_etc.js');
	$APPLICATION->AddHeadScript('/bitrix/js/sale/core_ui_dynamiclist.js');

	$arTabs = array(
		'tab_params' => array(
			'id' => 'tab_params',
			'name' => Loc::getMessage('CRM_CLE2_TAB_PARAMS'),
			'title' => Loc::getMessage('CRM_CLE2_TAB_PARAMS_TITLE'),
			'icon' => ''
		),
		'tab_external' => array(
			'id' => 'tab_external',
			'name' => Loc::getMessage('CRM_CLE2_TAB_EXTERNAL'),
			'title' => Loc::getMessage('CRM_CLE2_TAB_EXTERNAL_TITLE'),
			'icon' => ''
		)
	);

	foreach($arResult['FIELDS'] as $field)
	{
		if(!$geoHeadingShown && ($field['id'] == 'LATITUDE' || $field['id'] == 'LONGITUDE'))
		{
			$arTabs['tab_params']['fields'][] = array(
				'id' => 'geo_heading',
				'name' => Loc::getMessage('CRM_CLE2_HEADING_GEO'),
				'type' => 'section'
			);

			$geoHeadingShown = true;
		}

		if(!$nameHeadingShown && strpos($field['id'], 'NAME') !== false)
		{
			$arTabs['tab_params']['fields'][] = array(
				'id' => 'name_heading',
				'name' => Loc::getMessage('CRM_CLE2_HEADING_NAMES'),
				'type' => 'section'
			);

			$nameHeadingShown = true;
		}

		if($field['id'] == 'ID')
		{
			$field['type'] = 'label';
			$field['value'] = intval($field['value']) ? intval($field['value']) : Loc::getMessage('CRM_CLE2_NEW_ITEM');
		}
		elseif($field['id'] == 'PARENT_ID')
		{
			$field['type'] = 'custom';

			ob_start();
			$APPLICATION->IncludeComponent("bitrix:sale.location.selector.search", "", array(
				"ID" => intval($field['value']) ? intval($field['value']) : '',
				"INPUT_NAME" => "PARENT_ID",
				"PROVIDE_LINK_BY" => "id",
				"SHOW_ADMIN_CONTROLS" => 'N',
				"SELECT_WHEN_SINGLE" => 'N',
				"FILTER_BY_SITE" => 'N',
				"SHOW_DEFAULT_LOCATIONS" => 'N',
				"SEARCH_BY_PRIMARY" => 'N',

				"ADMIN_MODE" => 'N',
				//"JS_CONTROL_GLOBAL_ID" => 'crm_filter_parent_id'
				),
				false
			);
			$field['value'] = ob_get_contents();
			ob_end_clean();
		}
		elseif($field['id'] == 'TYPE_ID')
		{
			$field['type'] = 'list';

			$field['items'] = array();
			$field['items'][''] = Loc::getMessage('CRM_CLE2_NOT_SELECTED');
			foreach($arResult['TYPES'] as $id => $value)
				$field['items'][$id] = htmlspecialcharsbx($value);
		}
		elseif($field['id'] == 'EXTERNAL')
		{
			$field['type'] = 'custom';
			$field['name'] = Loc::getMessage('CRM_CLE2_TAB_EXTERNAL');

			ob_start();
			?>

			<?$randTag = rand(99, 999);?>
			<div id="ib_external_values_<?=$randTag?>" class="bx-ccle2-external-values">

				<table cellpadding="0" cellspacing="0">
					<tbody class="bx-ui-dynamiclist-container">

						<tr class="heading">
							<?foreach($arResult['EXTERNAL_TABLE_COLUMNS'] as $code => $column):?>
								<th><?=$column['title']?></td>
							<?endforeach?>
							<th><?=Loc::getMessage('CRM_CLE2_REMOVE')?></th>
						</tr>

						<?if(is_array($arResult['FORM_DATA']['EXTERNAL']) && !empty($arResult['FORM_DATA']['EXTERNAL'])):?>

							<?foreach($arResult['FORM_DATA']['EXTERNAL'] as $id => $ext):?>
								<tr>
									<?foreach($arResult['EXTERNAL_TABLE_COLUMNS'] as $code => $void):?>
										<?$value = Helper::makeSafeDisplay($ext[$code], $code);?>
										<td>
											<?if($code == 'SERVICE_ID'):?>
												<select name="EXTERNAL[<?=$ext['ID']?>][<?=$code?>]">
													<?foreach($arResult['EXTERNAL_SERVICES'] as $sId => $serv):?>
														<option value="<?=intval($serv['ID'])?>"<?=($serv['ID'] == $value ? ' selected' : '')?>><?=htmlspecialcharsbx($serv['CODE'])?></option>
													<?endforeach?>
												</select>
											<?elseif($code == 'ID'):?>
												<?if(intval($value)):?>
													<?=$value?>
												<?endif?>
											<?else:?>
												<input type="text" name="EXTERNAL[<?=$ext['ID']?>][<?=$code?>]" value="<?=$value?>" size="20" />
											<?endif?>
										</td>
									<?endforeach?>

									<td style="text-align: center">
										<?if($ext['ID']):?>
											<input type="checkbox" name="EXTERNAL[<?=$ext['ID']?>][REMOVE]" value="1" />
										<?endif?>
									</td>
								</tr>
							<?endforeach?>

						<?endif?>

						<script type="text/html" data-template-id="bx-ui-dynamiclist-row">
							<tr>
								<td></td>
								<td>
									<select name="EXTERNAL[n{{column_id}}][SERVICE_ID]">
										<?foreach($arResult['EXTERNAL_SERVICES'] as $sId => $serv):?>
											<option value="<?=intval($serv['ID'])?>"><?=htmlspecialcharsbx($serv['CODE'])?></option>
										<?endforeach?>
									</select>
								</td>
								<td>
									<input type="text" name="EXTERNAL[n{{column_id}}][XML_ID]" value="" size="20" />
								</td>
								<td></td>
							</tr>
						</script>

					</tbody>
				</table>

				<div class="bx-ccle2-addmore">
					<input class="bx-ui-dynamiclist-addmore" type="button" value="<?=Loc::getMessage('CRM_CLE2_TAB_EXTERNAL_MORE')?>" title="<?=Loc::getMessage('CRM_CLE2_TAB_EXTERNAL_MORE')?>">
				</div>

			</div>

			<script>
				new BX.ui.dynamicList({
					scope: 'ib_external_values_<?=$randTag?>',
					initiallyAdd: 3
				});
			</script>
				
			<?
			$field['value'] = ob_get_contents();
			ob_end_clean();
		}

		$arTabs[($field['id'] == 'EXTERNAL' ? 'tab_external' : 'tab_params')]['fields'][] = $field;
	}

	CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

	$formCustomHtml = '
		<input type="hidden" name="loc_id" value="'.intval($arResult['LOCATION_ID']).'"/>
	';

	if(strlen($arResult['SPECIFIED_BACK_URL']))
		$formCustomHtml .= '<input type="hidden" name="return_url" value="'.htmlspecialcharsbx($arResult['SPECIFIED_BACK_URL']).'"/>';

	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'',
		array(
			'FORM_ID' => $arResult['FORM_ID'],
			'TABS' => $arTabs,
			'BUTTONS' => array(
				'standard_buttons' => true,
				'back_url' => $arResult['CALCULATED_BACK_URL'],
				'custom_html' => $formCustomHtml
			),
			'DATA' => $arResult['LOC'],
			'SHOW_SETTINGS' => 'Y',
			'THEME_GRID_ID' => $arResult['GRID_ID'],
			'SHOW_FORM_TAG' => 'Y'
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	?>

<?endif?>