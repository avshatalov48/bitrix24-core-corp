<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;


class CrmOrderPropsFormEditTemplate
{
	public static function getUserBlockNav()
	{
		return '<span data-bx-crm-orderform-edit-option-nav="%ID%" class="task-additional-alt-promo-text %DISPLAY_CLASS%">%CAPTION%</span>';
	}

	public static function getUserBlock()
	{
		ob_start();
		?>
		<div
				id="option_%ID_LOWER%"
				data-bx-crm-orderform-edit-option="%ID%"
				class="crm-orderform-edit-task-edit-block-place"
		>
			<div class="pinable-block crm-orderform-edit-task-options-item crm-orderform-edit-task-options-item-se-project">
				<span data-bx-crm-orderform-edit-option-pin="" class="task-option-fixedbtn %FIXED_CLASS%"></span>
				<span class="crm-orderform-edit-task-options-item-param">%CAPTION%:</span>
				<div class="crm-orderform-edit-task-options-item-open-inner">
					%CONTENT%
				</div>
			</div><!--pinable-block crm-orderform-edit-task-options-item crm-orderform-edit-task-options-item-se-project-->
		</div><!--crm-orderform-edit-task-edit-block-place-->
		<?
		return ob_get_clean();
	}

	public static function callGetFieldByType($type, $params)
	{
		$type = mb_strtoupper(mb_substr($type, 0, 1)).mb_substr($type, 1);
		$callableField = array(__CLASS__, 'getField' . $type);

		if(is_callable($callableField))
		{
			$result = call_user_func_array($callableField, array($params));
		}
		else
		{
			$result = self::getFieldString($params);
		}

		$template =  self::getFieldCommon($result['DISPLAY_PART'], $result['SETTINGS'], $result['HIDE_CAPTION']);
		return self::replaceTemplate($template, $params);
	}

	public static function callGetFieldItemByType($type, $params)
	{
		$type = mb_strtoupper(mb_substr($type, 0, 1)).mb_substr($type, 1);
		$callableField = array(__CLASS__, 'getField' . $type . 'Item');

		$result = null;
		if(is_callable($callableField))
		{
			$result = call_user_func_array($callableField, array($params));
		}

		return $result;
	}

	public static function callGetFieldSettingsItemByType($type, $params)
	{
		$type = mb_strtoupper(mb_substr($type, 0, 1)).mb_substr($type, 1);
		$callableField = array(__CLASS__, 'getField' . $type . 'SettingsItem');

		$result = null;
		if(is_callable($callableField))
		{
			$result = call_user_func_array($callableField, array($params));
		}

		return $result;
	}

	protected static function replaceTemplate($template, $replaceList = array())
	{
		$replaceData = array('from' => array(), 'to' => array());
		foreach($replaceList as $paramKey => $paramValue)
		{
			if(!is_string($paramValue) && !is_numeric($paramValue))
			{
				continue;
			}

			$paramValue = (string)$paramValue;

			$isEscapedValue = false;
			if(mb_substr($paramKey, 0, 1) == '~')
			{
				$paramKey = mb_substr($paramKey, 1);
				$isEscapedValue = true;
			}

			$replaceData['from'][] = '%'.mb_strtolower($paramKey) . '%';
			$replaceData['to'][] = $isEscapedValue ? $paramValue : htmlspecialcharsbx($paramValue);
		}

		return str_replace($replaceData['from'], $replaceData['to'], $template);
	}

	public static function getField($params)
	{
		$type = $params['TYPE_ORIGINAL'] ? $params['TYPE_ORIGINAL'] : $params['TYPE'];
		if(!$params['CAPTION'])
		{
			$params['CAPTION'] = '';
		}
		if(!$params['VALUE'])
		{
			$params['VALUE'] = '';
		}
		if(!$params['ENTITY_FIELD_CAPTION'])
		{
			$params['ENTITY_FIELD_CAPTION'] = $params['CAPTION'];
		}
		if(!$params['ENTITY_CAPTION'])
		{
			$params['ENTITY_CAPTION'] = '-';
		}

		$params['URL_DISPLAY_STYLE'] = (mb_substr($params['ENTITY_FIELD_NAME'], 0, 3) == 'UF_' ? 'initial' : 'none');
		$params['SHOW_MULTIPLE'] = $params['MULTIPLE'] === 'Y' ? 'multiple' : '';
		$params['~SHOW_MATCH_ANCHOR'] = $params['ENTITY_NAME'] === 'ORDER' ? 'style="display: none;"' : '';

		if(is_array($params['ITEMS']))
		{
			$itemsString = '';

			foreach($params['ITEMS'] as $item)
			{
				$fileSrc = Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FILE_NOT_SELECTED');

				if (isset($item['SRC']) && !empty($item['FILE_NAME']))
				{
					$fileName = mb_strlen($item['FILE_NAME']) > 35 ? '...'.mb_substr($item['FILE_NAME'], -32) : $item['FILE_NAME'];
					$fileSrc = '<a href="'.$item['SRC'].'" title="'.Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FILE_DOWNLOAD')
						.'" target="_blank">'.$fileName.'</a>';
				}

				$itemsString .= self::replaceTemplate(
					self::callGetFieldItemByType($type, $item),
					[
						'name' => (string)$params['CODE'] ?: '',
						'item_id' => isset($item['ID']) ? (string)$item['ID'] : '',
						'item_code' => !empty($item['IS_ORDER_TYPE']) ? (string)$item['VALUE'] : (string)$item['ID'],
						'item_value' => !empty($item['IS_ORDER_TYPE']) ? (string)$item['NAME'] : (string)$item['VALUE'],
						'field_item_id' => $params['TYPE'].'_'.$params['ID'].'_'.$item['ID'],
						'checked' => isset($item['CHECKED']) ? $item['CHECKED'] : '',
						'selected' => isset($item['SELECTED']) ? $item['SELECTED'] : '',
						'~file_src' => $fileSrc,
						'list_order' => isset($item['LIST_ORDER']) ? $item['LIST_ORDER'] : '',
					]
				);
			}

			$settingsItemsString = '';

			foreach($params['ITEMS'] as $item)
			{
				$settingsItemsString .= self::replaceTemplate(
					self::callGetFieldSettingsItemByType($type, $item),
					array(
						'name' => $params['CODE'] ?: '',
						'item_id' => isset($item['ID']) ? (string)$item['ID'] : '',
						'item_code' => isset($item['NAME']) ? (string)$item['VALUE'] : (string)$item['ID'],
						'item_value' => isset($item['NAME']) ? (string)$item['VALUE'] : (string)$item['ID'],
						'item_name' => isset($item['NAME']) ? (string)$item['NAME'] : (string)$item['VALUE'],
						'checked' => isset($item['CHECKED']) ? (string)$item['CHECKED'] : '',
						'item_price' => isset($item['PRICE']) ? (string)$item['PRICE'] : '',
						'currency_short_name' => $params['CURRENCY_SHORT_NAME'] ?: '',
					)
				);
			}

			$params['~ITEMS'] = $itemsString;
			$params['~SETTINGS_ITEMS'] = $settingsItemsString;
		}

		return self::callGetFieldByType($type, $params);
	}

	public static function getFieldJsTemplateAll()
	{
		$typeList = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getTypeList();
		$typeStringList = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getFieldStringTypes();

		$result = '';
		foreach($typeList as $type => $data)
		{
			if(isset($typeStringList[$type]))
			{
				continue;
			}

			$result .= '<div type="text/html" id="tmpl_field_' . $type . '" style="display: none;">';
			$result .= self::callGetFieldByType($type, array());
			$result .= '</div>' . "\n\n";

			$itemTemplate = self::callGetFieldItemByType($type, array());
			if($itemTemplate)
			{
				$result .= '<div type="text/html" id="tmpl_field_' . $type . '_item" style="display: none;">';
				$result .= $itemTemplate;
				$result .= '</div>' . "\n\n";
			}

			$itemSettingsTemplate = self::callGetFieldSettingsItemByType($type, array());
			if($itemSettingsTemplate)
			{
				$result .= '<div type="text/html" id="tmpl_field_' . $type . '_settings_item" style="display: none;">';
				$result .= $itemSettingsTemplate;
				$result .= '</div>' . "\n\n";
			}
		}

		return $result;
	}

	public static function getFieldHr()
	{
		$result = array();
		ob_start();
		?>
		<div class="crm-orderform-element-separator">
			<span class="crm-orderform-element-separator-item"></span>
		</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();
		$result['SETTINGS'] = null;
		$result['HIDE_CAPTION'] = true;

		return $result;
	}

	public static function getFieldBr()
	{
		$result = array();
		ob_start();
		?>
		<div class="crm-orderform-element-br">&lt;br&gt;</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();
		$result['SETTINGS'] = null;
		$result['HIDE_CAPTION'] = true;

		return $result;
	}

	public static function getFieldTyped_string($params)
	{
		return self::getFieldString($params, true);
	}

	public static function getFieldString($params, $showValueTypes = false)
	{
		$result = array();
		ob_start();
		?>
		<input type="text" name="FIELD[%name%][VALUE]" value="%value%" placeholder="%placeholder%" class="crm-orderform-edit-left-inner-field-input">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName($showValueTypes);
		echo self::getFieldSettingsCommonStringType();
		echo self::getHiddenInputs();
		echo self::getFieldSettingsCommonRequisites();
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldLocation($params, $showValueTypes = false)
	{
		$result = array();

		ob_start();

		$inputNameCode = !empty($params['NAME']) ? $params['NAME'] : '%name%';
		$controlId = !empty($params['CODE']) ? '' : '%control_id%';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:sale.location.selector.'.\Bitrix\Sale\Location\Admin\Helper::getWidgetAppearance(),
			'',
			[
				'CODE' => !empty($params['DEFAULT_VALUE']) ? $params['DEFAULT_VALUE'] : '',
				'INPUT_NAME' => "FIELD[{$inputNameCode}][VALUE]",
				'PROVIDE_LINK_BY' => 'code',
				'SELECT_WHEN_SINGLE' => 'N',
				'FILTER_BY_SITE' => 'N',
				'SHOW_DEFAULT_LOCATIONS' => 'N',
				'SEARCH_BY_PRIMARY' => 'N',
				'JS_CONTROL_DEFERRED_INIT' => $controlId,
				'RANDOM_TAG' => $controlId,
				'JS_CONTROL_GLOBAL_ID' => $controlId
			],
			false
		);

		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName($showValueTypes);
		echo self::getHiddenInputs();
		echo self::getFieldSettingsCommonRequisites();

		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldFile($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-order-form-field-display-cont="">
			%items%
		</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldFileItem()
	{
		return '<div class="crm-orderform-edit-file-upload">
			%file_src%
			<input type="hidden" name="FIELD[%name%][VALUE]%list_order%[ID]" value="%item_id%">
			<input type="file" name="FIELD[%name%][VALUE]%list_order%" style="position:absolute; visibility:hidden"
					onchange="var anchor = this.previousElementSibling;
					var value = this.value.split(/(\\|\/)/g).pop();
					value = value.length > 35 ? (\'...\' + value.substr(-32)) : value;
					if (BX.type.isDomNode(anchor.previousElementSibling))
					{
						anchor.parentNode.removeChild(anchor.previousElementSibling);
					}
					anchor.previousSibling.textContent = value;
					">
			<button class="crm-orderform-edit-file-upload-button"
					onclick="this.previousElementSibling.click(); return false;">'.Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FILE_CHOOSE').'
			</button>
		</div>';
	}

	public static function getFieldDate($params)
	{
		$result = array();
		ob_start();
		?>
		<input id="%code%_id" name="FIELD[%name%][VALUE]" value="%value%" type="text" class="crm-orderform-edit-left-inner-field-input">
		<input type="button" value="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_CHOOSE_DATE')?>"
				onclick="BX.calendar({node:this, field:'%code%_id', form:'', bTime:'%show_time%', bHideTime:false});">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		echo self::getFieldSettingsCommonRequisites();
		?>
		<input name="FIELD[%name%][TIME]" type="hidden" value="%time%">
		<?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldDatetime($params)
	{
		$result = array();
		ob_start();
		?>
		<input type="text" class="crm-orderform-edit-left-inner-field-input">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		echo self::getFieldSettingsCommonRequisites();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldSection($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-order-form-lbl-cont="">
			<span class="crm-orderform-edit-field-option"></span>
			<span class="crm-orderform-edit-left-inner-field-title-wrap">
				<span data-bx-order-form-lbl-caption="" class="crm-orderform-edit-left-inner-field-title-item field-title-item">%caption%</span>
				<span data-bx-order-form-lbl-btn-edit="" class="inner-field-title-field-item-icon" title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_INLINE_EDIT')?>"></span>
			</span>
			<span class="crm-orderform-edit-left-inner-field-title-field">
				<input data-bx-order-form-btn-caption="" name="FIELD[%name%][CAPTION]" value="%caption%" type="text" class="crm-orderform-edit-left-inner-field-title-field-item">
				<span data-bx-order-form-lbl-btn-apply="" class="crm-orderform-edit-field-accept" title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_INLINE_EDIT_APPLY')?>"></span>
			</span>
		</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();
		$result['SETTINGS'] = '';
		$result['HIDE_CAPTION'] = true;

		return $result;
	}

	public static function getFieldList($params)
	{
		$result = array();
		ob_start();
		?>
		<select name="FIELD[%name%][VALUE][]" data-bx-order-form-field-display-cont="" class="crm-orderform-edit-left-inner-field-select" %show_multiple%>
			%items%
		</select>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?>
		<label for="" class="crm-orderform-edit-popup-label">
			<div class="crm-orderform-edit-popup-name"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-orderform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldListCheckbox($params)
	{
		$result = array();

		ob_start();
		?>
		<div data-bx-order-form-field-display-cont="">%items%</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		echo self::getFieldSettingsCommonRequisites();
		?>
		<label for="" class="crm-orderform-edit-popup-label">
			<div class="crm-orderform-edit-popup-name"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-orderform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldRadio($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-order-form-field-display-cont="">%items%</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?>
		<label for="" class="crm-orderform-edit-popup-label">
			<div class="crm-orderform-edit-popup-name"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-orderform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldCheckbox($params)
	{
		$hasItems = !empty($params['ITEMS']);

		$result = array();

		ob_start();
		if ($hasItems)
		{
			?>
			<div data-bx-order-form-field-display-cont="">%items%</div>
			<?
		}
		else
		{
			$result['HIDE_CAPTION'] = true;
			?>
			<span class="crm-orderform-edit-left-inner-field-checkbox-container">
				<label class="crm-orderform-edit-left-inner-field-checkbox">
					<input name="FIELD[%name%][VALUE]" type="hidden" value="N">
					<input name="FIELD[%name%][VALUE]" value="Y" type="checkbox"
							class="crm-orderform-edit-left-inner-field-checkbox-input" %checked%>
					<span data-bx-order-form-lbl-caption="" class="crm-orderform-edit-left-inner-field-text"
							title="<?= Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_SETTINGS_IN_CRM') ?>: %entity_field_caption% (%entity_caption%)"
					>%caption%<span class="crm-orderform-linked" data-crm-orderform-name-link="%name_id%" %show_match_anchor%></span>
					</span>
				</label>
			</span>
			<?
		}

		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		echo self::getFieldSettingsCommonRequisites();
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldListItem()
	{
		return '<option value="%item_code%" %selected%>%item_value%</option>';
	}

	public static function getFieldListCheckboxItem()
	{
		return '
			<span class="crm-orderform-edit-left-inner-field-checkbox-container">
				<label for="%field_item_id%" class="crm-orderform-edit-left-inner-field-checkbox">
					<input id="%field_item_id%" name="FIELD[%name%][VALUE][]" value="%item_code%" type="checkbox" class="crm-orderform-edit-left-inner-field-checkbox-input" %checked%>
					<span class="crm-orderform-edit-left-inner-field-text">%item_value%</span>
				</label>
			</span>
		';
	}

	public static function getFieldRadioItem()
	{
		return '
			<span class="crm-orderform-edit-left-inner-field-radio-container">
				<label for="%field_item_id%" class="crm-orderform-edit-left-inner-field-radio">
					<input id="%field_item_id%" type="radio" name="FIELD[%name%][VALUE]" value="%item_code%" class="crm-orderform-edit-left-inner-field-text" %checked%>%item_value%</input>
				</label>
			</span>
		';
	}

	public static function getFieldCheckboxItem()
	{
		return '
			<span class="crm-orderform-edit-left-inner-field-checkbox-container">
				<label for="%field_item_id%" class="crm-orderform-edit-left-inner-field-checkbox">				
					<input name="FIELD[%name%][VALUE][%item_id%]" type="hidden" value="N">
					<input id="%field_item_id%" name="FIELD[%name%][VALUE][%item_id%]" value="Y" type="checkbox" class="crm-orderform-edit-left-inner-field-checkbox-input" %checked%>
					<span class="crm-orderform-edit-left-inner-field-text">%item_value%</span>
				</label>
			</span>
		';
	}

	public static function getFieldCommon($displayPart, $settings, $hideCaption = true)
	{
		ob_start();
		?>
		<div id="%name_id%" class="crm-orderform-edit-left-inner-field" data-crm-orderform-edit-field-container>
			<div class="crm-orderform-edit-left-inner-field-block">
				<div class="crm-orderform-edit-left-inner-field-container">
					<?if ($hideCaption !== true): ?>
						<label data-bx-order-form-lbl-caption="%caption%" for="" class="crm-orderform-edit-left-inner-field-label"
								title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_SETTINGS_IN_CRM')?>: %entity_field_caption% (%entity_caption%)"
						>%caption%<span class="crm-orderform-linked" data-crm-orderform-name-link="%name_id%" %show_match_anchor%></span>
						</label>
					<? endif; ?>
					<div class="crm-orderform-edit-left-inner-field-wrap">
						<span data-bx-order-form-btn-move="" class="crm-orderform-edit-field-option"
								title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FIELD_MOVE')?>">
						</span>
						%FIELD_DISPLAY_PART%
						<span data-bx-order-form-btn-slider-edit="" class="crm-orderform-edit-field-settings"
								title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FIELD_EDIT')?>">
						</span>
						<span data-bx-order-form-btn-delete="" class="crm-orderform-edit-field-close"
								title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_FIELD_REMOVE')?>">
						</span>
					</div>
				</div>
				<span data-bx-order-form-btn-add="" class="crm-orderform-edit-left-inner-field-add"
						title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_COMMON_ADD_HINT')?>"
						style="display: none;">
					<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_COMMON_ADD')?>
				</span>
				<div data-bx-order-form-field-settings-cont="" style="display: none;">
					<div>%FIELD_SETTINGS%</div>
				</div>
				<input data-bx-order-form-field-sort="" type="hidden" name="FIELD[%name%][SORT]" value="%sort%">
				<input type="hidden" name="FIELD[%name%][ID]" value="%id%">
				<input data-bx-order-form-field-type="" type="hidden" name="FIELD[%name%][TYPE]" value="%type%">
			</div>
		</div>
		<?
		$template = ob_get_clean();

		return str_replace(
			array('%FIELD_DISPLAY_PART%', '%FIELD_SETTINGS%'),
			array($displayPart, $settings),
			$template
		);
	}

	public static function getFieldSettingsCommon()
	{
		return self::getFieldSettingsCommonName() . self::getHiddenInputs();
	}

	public static function getFieldSettingsCommonName($showValueTypes = false)
	{
		$valueTypeList = array();
		$valueTypesString = '';
		if($showValueTypes)
		{
			foreach($valueTypeList as $stringType)
			{
				$valueTypesString .= '<option value="' . htmlspecialcharsbx($stringType['ID']) . '">' . htmlspecialcharsbx($stringType['VALUE']) . '</option>';
			}
			$valueTypesString = '
				<input data-bx-order-form-field-string-value-type="" type="hidden" name="FIELD[%name%][VALUE_TYPE]" value="%value_type%">
				<select data-bx-order-form-field-string-value-types=""  class="crm-orderform-edit-popup-select">' . $valueTypesString . '</select>
			';
		}

		return '
			<label for="" class="crm-orderform-edit-popup-label">
				<div class="crm-orderform-edit-popup-name">' . Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_SETTINGS_NAME') . '</div>
				<div class="crm-orderform-edit-popup-inner-container">
					<input data-bx-order-form-btn-caption="" name="FIELD[%name%][CAPTION]" type="text" value="%caption%" class="crm-orderform-edit-popup-input">
					' . $valueTypesString . '
				</div>
			</label>
		';
	}

	public static function getHiddenInputs()
	{
		$str = '';
		$fields = [
			'PLACEHOLDER', 'PROPS_GROUP_ID', 'MULTIPLE', 'REQUIRED', 'USER_PROPS',
			'IS_PROFILE_NAME', 'IS_PAYER', 'IS_EMAIL', 'IS_PHONE', 'IS_ZIP', 'IS_ADDRESS'
		];

		foreach ($fields as $field)
		{
			$str .= '<input type="hidden" name="FIELD[%name%]['.$field.']" value="%'.mb_strtolower($field).'%">';
		}

		return $str;
	}

	public static function getFieldSettingsCommonRequisites()
	{
		return '
			<input type="hidden" name="FIELD[%name%][RQ_PRESET_ID]" value="%preset_id%">
			<input type="hidden" name="FIELD[%name%][RQ_BANK_DETAIL]" value="%bank_detail%">
			<input type="hidden" name="FIELD[%name%][RQ_ADDR]" value="%address%">
			<input type="hidden" name="FIELD[%name%][RQ_ADDR_TYPE]" value="%address_type%">
		';
	}

	public static function getFieldSettingsCommonStringType()
	{
		$typeList = \Bitrix\Crm\Order\Matcher\FieldSynchronizer::getFieldStringTypes();
		$typesString = '';
		$typesString .= '<option value="string">' . Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_SETTINGS_STYPE_DEFAULT') . '</option>';
		foreach($typeList as $typeId => $typeCaption)
		{
			$typesString .= '<option value="' . htmlspecialcharsbx($typeId) . '">' . htmlspecialcharsbx($typeCaption) . '</option>';
		}
		$typesString = '
			<select data-bx-order-form-field-string-type="" class="crm-orderform-edit-popup-select">' . $typesString . '</select>
		';

		return '
			<label for="" class="crm-orderform-edit-popup-label">
				<div class="crm-orderform-edit-popup-name">' . Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_SETTINGS_CHECK_AS') . '</div>
				<div class="crm-orderform-edit-popup-inner-container">
					'. $typesString .'
				</div>
			</label>
		';
	}

	public static function getFieldProduct($params)
	{
		//$result = self::getFieldList($params);
		$result = array();
		ob_start();
		?>
		<select data-bx-order-form-field-display-cont="" class="crm-orderform-edit-left-inner-field-select">
			<option value=""><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_ITEMS_DEFAULT')?></option>
			%items%
		</select>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?>
		<div class="crm-orderform-edit-task-options-account-setup-info-description">
			<br>
			<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRODUCT_TITLE')?>
		</div>
		<div data-bx-crm-orderform-product="%id%">
			<div data-bx-crm-orderform-product-items="">%settings_items%</div>
			<span data-bx-crm-orderform-product-select="" class="crm-orderform-edit-task-options-account-setup-new-goods-add">
				&#43;<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRODUCT_ADD')?>
			</span>
			<span class="crm-orderform-edit-task-options-account-setup-new-goods-separator"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRODUCT_ADD_OR')?></span>
			<span data-bx-crm-orderform-product-add-row="" class="crm-orderform-edit-task-options-account-setup-new-goods-add">
				<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRODUCT_ADD_ROW')?>
			</span>
		</div>
		<?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldProductItem()
	{
		return self::getFieldListItem();
	}

	public static function getFieldProductSettingsItem($itemParams)
	{
		return '
			<label data-bx-crm-orderform-product-item="%item_id%" class="crm-orderform-edit-task-options-account-setup-goods">
				<span data-bx-crm-orderform-product-item-del="" class="crm-orderform-edit-task-edit-deal-stage-close" title="' . Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_REMOVE') . '"></span>
				<input name="FIELD[%name%][ITEMS][%item_id%][ID]" type="hidden" value="%item_id%">
				<input data-bx-crm-orderform-product-item-input="" name="FIELD[%name%][ITEMS][%item_id%][VALUE]" value="%item_value%" class="crm-orderform-edit-task-options-account-setup-goods-name" placeholder="' . Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_NAME1') . '">
				<input name="FIELD[%name%][ITEMS][%item_id%][PRICE]" value="%item_price%" class="crm-orderform-edit-task-options-account-setup-goods-price" placeholder="' . Loc::getMessage('CRM_ORDERFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_PRICE1') . ', %currency_short_name%">
			</label>
		';
	}

	public static function getFieldListSettingsItem($itemParams)
	{
		return '
			<div data-bx-crm-orderform-field-settings-item="%item_code%" class="crm-orderform-edit-popup-inner-container">
				<input name="FIELD[%name%][ITEMS][%item_code%][ID]" type="hidden" value="%item_id%">
				<input name="FIELD[%name%][ITEMS][%item_code%][VALUE]" type="hidden" value="%item_value%">
				<input data-bx-crm-orderform-field-settings-item-input="" name="FIELD[%name%][ITEMS][%item_code%][NAME]" type="text" value="%item_name%" class="crm-orderform-edit-popup-list-input">
				<span data-bx-crm-orderform-field-settings-item-clear="" class="crm-orderform-edit-popup-list-input-icon" title="' . Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_CLEAR') . '"></span>
			</div>
		';
	}

	public static function getFieldListCheckboxSettingsItem($itemParams)
	{
		return static::getFieldListSettingsItem($itemParams);
	}

	public static function getFieldCheckboxSettingsItem($itemParams)
	{
		return '';
	}

	public static function getFieldRadioSettingsItem($itemParams)
	{
		return static::getFieldListSettingsItem($itemParams);
	}
}

function GetCrmOrderPropsFormFieldRelationTemplate($params)
{
	$namePrefix = 'DEPENDENCIES[' . htmlspecialcharsbx($params['ID']) . ']';
	$idPrefix = 'DEPENDENCIES_' . htmlspecialcharsbx($params['ID']) . '';

	$actionList = [
		[
			'CAPTION' => Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_SHOW'),
			'VALUE' => 'SHOW'
		]
	];

	$isSelectedActionHide = false;
	$actionListCount = count($actionList);

	for($i = 0; $i < $actionListCount; $i++)
	{
		$actionList[$i]['SELECTED'] = (isset($params['DO_ACTION']) && $params['DO_ACTION'] == $actionList[$i]['VALUE']);

		if($actionList[$i]['SELECTED'] && $actionList[$i]['VALUE'] === 'HIDE')
		{
			$isSelectedActionHide = true;
		}
	}

	$ifValue = is_array($params['IF_VALUE']) ? implode(',', $params['IF_VALUE']) : (string)$params['IF_VALUE'];
	?>
	<div id="<?=$idPrefix?>" class="crm-orderform-edit-task-options-rule-stage">
		<span id="<?=$idPrefix?>_BTN_REMOVE" class="crm-orderform-edit-task-edit-deal-stage-close" title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_REMOVE')?>"></span>
		<div class="crm-orderform-edit-task-options-rule-select-container">
			<span class="crm-orderform-edit-task-options-rule-select-item rule-select-item-if"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_IF')?>:</span>

			<input type="hidden" name="<?=$namePrefix?>[IF_FIELD_CODE]" id="<?=$idPrefix?>_IF_FIELD_CODE" value="<?=htmlspecialcharsbx($params['IF_FIELD_CODE'])?>">
			<input type="hidden" value="<?=htmlspecialcharsbx($ifValue)?>" name="<?=$namePrefix?>[IF_VALUE]" id="<?=$idPrefix?>_IF_VALUE">

			<select id="<?=$idPrefix?>_IF_FIELD_CODE_CTRL" class="crm-orderform-edit-task-options-rule-select"></select>
			<span class="crm-orderform-edit-task-options-rule-select-item rule-select-item-equally">&ndash; <?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_EQUAL')?> &ndash;</span>
			<select multiple style="display: none;" id="<?=$idPrefix?>_IF_VALUE_CTRL_S" class="crm-orderform-edit-task-options-rule-select"></select>
			<input style="display: none;" id="<?=$idPrefix?>_IF_VALUE_CTRL_I" class="crm-orderform-edit-task-options-rule-input">
		</div>
		<div class="crm-orderform-edit-task-options-rule-select-container">
			<span class="crm-orderform-edit-task-options-rule-select-item rule-select-item-to"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_THEN')?>:</span>

			<select name="<?=$namePrefix?>[DO_ACTION]" id="<?=$idPrefix?>_DO_ACTION" class="crm-orderform-edit-task-options-rule-select">
				<? foreach($actionList as $action): ?>
					<option value="<?=htmlspecialcharsbx($action['VALUE'])?>" <?=($action['SELECTED'] ? 'selected' : '')?>><?=htmlspecialcharsbx($action['CAPTION'])?></option>
				<? endforeach; ?>
			</select>

			<span class="crm-orderform-edit-task-options-rule-select-item rule-select-item-equally-line">&ndash;</span>

			<input type="hidden" name="<?=$namePrefix?>[DO_FIELD_CODE]" id="<?=$idPrefix?>_DO_FIELD_CODE" value="<?=htmlspecialcharsbx($params['DO_FIELD_CODE'])?>">
			<select id="<?=$idPrefix?>_DO_FIELD_CODE_CTRL" class="crm-orderform-edit-task-options-rule-select"></select>
		</div>
		<span id="<?=$idPrefix?>_ELSE_HIDE" style="display: <?=(!$isSelectedActionHide ? 'block' : 'none')?>;" class="crm-orderform-edit-task-options-info">
			<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_ELSE_HIDE', array('%name%' => '<span></span>'))?>
		</span>
		<span id="<?=$idPrefix?>_ELSE_SHOW" style="display: <?=($isSelectedActionHide ? 'block' : 'none')?>;" class="crm-orderform-edit-task-options-info">
			<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_DEP_ELSE_SHOW', array('%name%' => '<span></span>'))?>
		</span>
	</div><!--crm-orderform-edit-task-edit-deal-stage-->
	<?
}

function GetCrmOrderPropsFormPresetFieldTemplate($params)
{
	$namePrefix = 'FIELD_PRESET[' . htmlspecialcharsbx($params['CODE']) . ']';
	$idPrefix = 'FIELD_PRESET_' . htmlspecialcharsbx($params['CODE']) . '';

	?>
	<div id="<?=$idPrefix?>" class="crm-orderform-edit-task-edit-deal-stage">
		<span id="<?=$idPrefix?>_BTN_REMOVE" class="crm-orderform-edit-task-edit-deal-stage-close" title="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_REMOVE')?>"></span>
		<span class="crm-orderform-edit-task-edit-deal-stage-item">
			<?=htmlspecialcharsbx($params['ENTITY_FIELD_CAPTION'])?>
			(<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRESET_DOC')?>: <?=htmlspecialcharsbx($params['ENTITY_CAPTION'])?>)
		</span>
		<div class="crm-orderform-edit-task-edit-deal-stage-input-container crm-orderform-edit-macros-container">
			<input type="hidden" value="<?=htmlspecialcharsbx($params['VALUE'])?>" name="<?=$namePrefix?>[VALUE]" id="<?=$idPrefix?>_VALUE">
			<select style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_S" class="crm-orderform-edit-task-edit-deal-stage-select"></select>
			<input style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_I" placeholder="<?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRESET_DOC_HINT')?>" class="crm-orderform-edit-task-edit-deal-stage-input">
			<span class="crm-orderform-edit-task-edit-deal-stage-macros" style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_I_M"><?=Loc::getMessage('CRM_ORDERFORM_EDIT_TMPL_PRESET_MACROS')?></span>
			<span class="crm-orderform-context-help" data-text="<?=htmlspecialcharsbx(nl2br(Loc::getMessage("CRM_ORDERFORM_EDIT_TMPL_PRESET_MACROS_HINT")))?>">?</span>
		</div>
	</div><!--crm-orderform-edit-task-edit-deal-stage-->
	<?
}

function crmOrderPropsFormDrawFieldsTree($tree)
{
	foreach ($tree as $entityName => $entityFields)
	{
		?>
		<span data-bx-crm-wf-selector-field-group="<?=$entityName?>"
				class="crm-orderform-edit-right-list-item crm-orderform-edit-close">
			<div class="crm-orderform-edit-right-list-head">
				<span class="crm-orderform-edit-right-list-item-icon"></span>
				<span class="crm-orderform-edit-right-list-item-element">
					<?=htmlspecialcharsbx($entityFields['CAPTION'])?>
				</span>
			</div>

			<ul class="crm-orderform-edit-right-inner-list">
				<?
				foreach ($entityFields['FIELDS'] as $field)
				{
					if ($field['type'] === 'tree' && !empty($field['tree']))
					{
						crmOrderPropsFormDrawFieldsTree($field['tree']);
					}
					elseif ($field['type'] === 'empty-list-label')
					{
						?>
						<li class="crm-orderform-edit-right-inner-list-item-empty">
							<?=htmlspecialcharsbx($field['caption'])?>
						</li>
						<?
					}
					else
					{
						?>
						<li class="crm-orderform-edit-right-inner-list-item"
								data-bx-crm-wf-selector-field-name="<?=htmlspecialcharsbx($field['name'])?>"
								data-bx-crm-wf-selector-preset-field="<?=htmlspecialcharsbx($field['preset_id'])?>">
							<?=htmlspecialcharsbx($field['caption'])?>
						</li>
						<?
					}
				}
				?>
			</ul>
		</span>
		<?
	}
}