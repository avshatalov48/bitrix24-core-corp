<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm;


class CrmWebFormEditTemplate
{
	public static function getUserBlockNav()
	{
		return '<span ' . 'data-bx-crm-webform-edit-option-nav="%ID%" ' . 'class="task-additional-alt-promo-text %DISPLAY_CLASS%">%CAPTION%</span>';
	}

	public static function getUserBlock()
	{
		ob_start();
		?>
		<div
			id="option_%ID_LOWER%"
			data-bx-crm-webform-edit-option="%ID%"
			class="crm-webform-edit-task-edit-block-place"
		>
			<div class="pinable-block crm-webform-edit-task-options-item crm-webform-edit-task-options-item-se-project">
				<span data-bx-crm-webform-edit-option-pin="" class="task-option-fixedbtn %FIXED_CLASS%"></span>
				<span class="crm-webform-edit-task-options-item-param">%CAPTION%:</span>
				<div class="crm-webform-edit-task-options-item-open-inner">
					%CONTENT%
				</div>
			</div><!--pinable-block crm-webform-edit-task-options-item crm-webform-edit-task-options-item-se-project-->
		</div><!--crm-webform-edit-task-edit-block-place-->
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
			if(!is_string($paramValue) && !is_integer($paramValue))
			{
				continue;
			}

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
		if(!$params['PLACEHOLDER'])
		{
			$params['PLACEHOLDER'] = '';
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


		if(!isset($params['SETTINGS_DATA']) || !is_array($params['SETTINGS_DATA']))
		{
			$params['SETTINGS_DATA'] = [];
		}
		if(!isset($params['SETTINGS_DATA']['BIG_PIC']))
		{
			$params['SETTINGS_DATA']['BIG_PIC'] = 'N';
		}
		if(!isset($params['SETTINGS_DATA']['QUANTITY_MIN']))
		{
			$params['SETTINGS_DATA']['QUANTITY_MIN'] = '';
		}
		if(!isset($params['SETTINGS_DATA']['QUANTITY_MAX']))
		{
			$params['SETTINGS_DATA']['QUANTITY_MAX'] = '';
		}
		if(!isset($params['SETTINGS_DATA']['QUANTITY_STEP']))
		{
			$params['SETTINGS_DATA']['QUANTITY_STEP'] = '';
		}
		foreach ($params['SETTINGS_DATA'] as $sdKey => $sdValue)
		{
			$params['SETTINGS_DATA_' . $sdKey] = $sdValue;
		}



		$params['URL_DISPLAY_STYLE'] = (mb_substr($params['ENTITY_FIELD_NAME'], 0, 3) == 'UF_' ? 'initial' : 'none');

		if(is_array($params['ITEMS']))
		{
			$itemsString = '';
			foreach($params['ITEMS'] as $item)
			{
				$itemsString .= self::replaceTemplate(
					self::callGetFieldItemByType($type, $item),
					array(
						'name' => $params['CODE'],
						'placeholder' => $params['PLACEHOLDER'],
						'item_id' => $item['ID'],
						'item_value' => $item['VALUE'],
						'item_discount' => $item['DISCOUNT'] ?: '',
						'item_custom_price' => $item['CUSTOM_PRICE'] === 'Y' ? 'checked' : '',
					)
				);
			}

			$settingsItemsString = '';
			foreach($params['ITEMS'] as $item)
			{
				$settingsItemsString .= self::replaceTemplate(
					self::callGetFieldSettingsItemByType($type, $item),
					array(
						'name' => $params['CODE'],
						'item_id' => $item['ID'],
						'item_value' => $item['VALUE'],
						'item_name' => $item['NAME'],
						'item_price' => $item['PRICE'],
						'item_discount' => $item['DISCOUNT'] ?: '',
						'item_custom_price' => $item['CUSTOM_PRICE'] === 'Y' ? 'checked' : '',
						'currency_short_name' => $params['CURRENCY_SHORT_NAME'],
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
		$typeList = \Bitrix\Crm\WebForm\Internals\FieldTable::getTypeList();
		$typeStringList = \Bitrix\Crm\WebForm\Helper::getFieldStringTypes();

		$result = '';
		foreach($typeList as $type => $data)
		{
			if(isset($typeStringList[$type]))
			{
				continue;
			}

			$result .= '<script type="text/html" id="tmpl_field_' . $type . '">';
			$result .= self::callGetFieldByType($type, array());
			$result .= '</script>' . "\n\n";

			$itemTemplate = self::callGetFieldItemByType($type, array());
			if($itemTemplate)
			{
				$result .= '<script type="text/html" id="tmpl_field_' . $type . '_item">';
				$result .= $itemTemplate;
				$result .= '</script>' . "\n\n";
			}

			$itemSettingsTemplate = self::callGetFieldSettingsItemByType($type, array());
			if($itemSettingsTemplate)
			{
				$result .= '<script type="text/html" id="tmpl_field_' . $type . '_settings_item">';
				$result .= $itemSettingsTemplate;
				$result .= '</script>' . "\n\n";
			}
		}
		return $result;
	}

	public static function getFieldHr()
	{
		$result = array();
		ob_start();
		?>
		<div class="crm-webform-element-separator">
			<span class="crm-webform-element-separator-item"></span>
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
		<div class="crm-webform-element-br">&lt;br&gt;</div>
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
			<input type="text" placeholder="%placeholder%" class="crm-webform-edit-left-inner-field-input">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName($showValueTypes);
		echo self::getFieldSettingsCommonFieldInCrm();
		echo self::getFieldSettingsCommonPlaceHolder();
		echo self::getFieldSettingsCommonDefaultValue();
		echo self::getFieldSettingsCommonStringType();
		echo self::getFieldSettingsCommonMultiple();
		echo self::getFieldSettingsCommonRequired();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldFile($params)
	{
		$result = array();
		ob_start();
		?>
			<div class="crm-webform-edit-file-upload">
				<button class="crm-webform-edit-file-upload-button"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_FILE_CHOOSE')?></button>
				<div class="crm-webform-edit-file-upload-text"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_FILE_NOT_SELECTED')?></div>
				<input type="file" class="crm-webform-edit-left-inner-field-input">
			</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldDate($params)
	{
		$result = array();
		ob_start();
		?>
			<input type="text" class="crm-webform-edit-left-inner-field-input">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldDatetime($params)
	{
		$result = array();
		ob_start();
		?>
			<input type="text" class="crm-webform-edit-left-inner-field-input">
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommon();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldSection($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-web-form-lbl-cont="">
			<span class="crm-webform-edit-field-option"></span>
			<span class="crm-webform-edit-left-inner-field-title-wrap">
				<span data-bx-web-form-lbl-caption="" class="crm-webform-edit-left-inner-field-title-item field-title-item">%caption%</span>
				<span data-bx-web-form-lbl-btn-edit="" class="inner-field-title-field-item-icon" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_INLINE_EDIT')?>"></span>
			</span>
			<span class="crm-webform-edit-left-inner-field-title-field">
				<input data-bx-web-form-btn-caption="" name="FIELD[%name%][CAPTION]" value="%caption%" type="text" class="crm-webform-edit-left-inner-field-title-field-item">
				<span data-bx-web-form-lbl-btn-apply="" class="crm-webform-edit-field-accept" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_INLINE_EDIT_APPLY')?>"></span>
			</span>
		</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();
		$result['SETTINGS'] = '';
		$result['HIDE_CAPTION'] = true;

		return $result;
	}

	public static function getFieldPage($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-web-form-lbl-cont="">
			<span class="crm-webform-edit-field-option"></span>
			<span class="crm-webform-edit-left-inner-field-title-wrap">
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_FORM_TREE_ADD_FIELD_PAGE')?>
				&nbsp;&nbsp;
				<span data-bx-web-form-lbl-caption="" class="crm-webform-edit-left-inner-field-title-item field-title-item">%caption%</span>
				<span data-bx-web-form-lbl-btn-edit="" class="inner-field-title-field-item-icon" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_INLINE_EDIT')?>"></span>
			</span>
			<span class="crm-webform-edit-left-inner-field-title-field">
				<input data-bx-web-form-btn-caption="" name="FIELD[%name%][CAPTION]" value="%caption%" type="text" class="crm-webform-edit-left-inner-field-title-field-item">
				<span data-bx-web-form-lbl-btn-apply="" class="crm-webform-edit-field-accept" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_INLINE_EDIT_APPLY')?>"></span>
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
			<select data-bx-web-form-field-display-cont="" class="crm-webform-edit-left-inner-field-select">%items%</select>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName();
		echo self::getFieldSettingsCommonFieldInCrm();
		?>
		<label for="" class="crm-webform-edit-popup-label">
			<div class="crm-webform-edit-popup-name"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-webform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		echo self::getFieldSettingsCommonMultiple();
		echo self::getFieldSettingsCommonRequired();
		?><?
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldRadio($params)
	{
		$result = array();
		ob_start();
		?>
		<div data-bx-web-form-field-display-cont="">%items%</div>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName();
		echo self::getFieldSettingsCommonFieldInCrm();
		?>
		<label for="" class="crm-webform-edit-popup-label">
			<div class="crm-webform-edit-popup-name"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-webform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		echo self::getFieldSettingsCommonRequired();
		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldCheckbox($params)
	{
		$hasItems = !empty($params['ITEMS']);

		$result = array();

		ob_start();
		if ($hasItems):
			?>
			<div data-bx-web-form-field-display-cont="">%items%</div>
			<?
		else:
			?>
			<span class="crm-webform-edit-left-inner-field-checkbox-container">
				<label class="crm-webform-edit-left-inner-field-checkbox">
					<input type="checkbox" class="crm-webform-edit-left-inner-field-checkbox-input">
					<span data-bx-web-form-lbl-caption="" class="crm-webform-edit-left-inner-field-text"
						title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_IN_CRM')?>: %entity_field_caption% (%entity_caption%)"
						>%caption%</span>
				</label>
			</span>
			<?
		endif;
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName();
		echo self::getFieldSettingsCommonFieldInCrm();
		echo self::getFieldSettingsCommonRequired();
		echo self::getFieldSettingsCommonMultiple();

		if ($hasItems):
		?>
		<label for="" class="crm-webform-edit-popup-label">
			<div class="crm-webform-edit-popup-name"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_LIST_ENUM')?></div>
			<div data-bx-crm-webform-field-settings-items="">%settings_items%</div>
		</label>
		<?
		else:
			$result['HIDE_CAPTION'] = true;
		endif;

		$result['SETTINGS'] = ob_get_clean();

		return $result;
	}

	public static function getFieldListItem()
	{
		return '<option value="%item_id%">%item_value%</option>';
	}

	public static function getFieldRadioItem()
	{
		return  '
		<span class="crm-webform-edit-left-inner-field-radio-container">
			<label for="%field_item_id%" class="crm-webform-edit-left-inner-field-radio">
				<input id="%field_item_id%" name="%field_item_name%" value="%item_id%" type="radio" class="crm-webform-edit-left-inner-field-radio-input">
				<span class="crm-webform-edit-left-inner-field-text">%item_value%</span>
			</label>
		</span>
		';
	}

	public static function getFieldCheckboxItem()
	{
		return  '
		<span class="crm-webform-edit-left-inner-field-checkbox-container">
			<label for="%field_item_id%" class="crm-webform-edit-left-inner-field-checkbox">
				<input id="%field_item_id%" name="%field_item_name%" value="%item_id%" type="checkbox" class="crm-webform-edit-left-inner-field-checkbox-input">
				<span class="crm-webform-edit-left-inner-field-text">%item_value%</span>
			</label>
		</span>
		';
	}

	public static function getFieldCommon($displayPart, $settings, $hideCaption = true)
	{
		ob_start();
		?>
		<div id="%name%" class="crm-webform-edit-left-inner-field">
			<div class="crm-webform-edit-left-inner-field-block">
				<div class="crm-webform-edit-left-inner-field-container">
					<?if($hideCaption !== true):?>
						<label data-bx-web-form-lbl-caption="" for="" class="crm-webform-edit-left-inner-field-label"
							title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_IN_CRM')?>: %entity_field_caption% (%entity_caption%)"
							>%caption%</label>
					<?endif;?>
					<div class="crm-webform-edit-left-inner-field-wrap">
						<span data-bx-web-form-btn-move="" class="crm-webform-edit-field-option"  title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_FIELD_MOVE')?>"></span>
						%FIELD_DISPLAY_PART%
						<?if($settings):?>
							<span data-bx-web-form-btn-edit="" class="crm-webform-edit-field-settings"  title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_FIELD_EDIT')?>"></span>
						<?endif;?>
						<span data-bx-web-form-btn-delete="" class="crm-webform-edit-field-close"  title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_FIELD_REMOVE')?>"></span>
					</div>
				</div>
				<span data-bx-web-form-btn-add="" class="crm-webform-edit-left-inner-field-add" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_COMMON_ADD_HINT')?>" style="display: none;"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_COMMON_ADD')?></span>
				<div data-bx-web-form-field-settings-cont="" style="display: none;">
					<div>%FIELD_SETTINGS%</div>
				</div>
				<input data-bx-web-form-field-sort="" type="hidden" name="FIELD[%name%][SORT]" value="%sort%">
				<input type="hidden" name="FIELD[%name%][ID]" value="%id%">
				<input data-bx-web-form-field-type="" type="hidden" name="FIELD[%name%][TYPE]" value="%type%">
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
		return
			self::getFieldSettingsCommonName() .
			self::getFieldSettingsCommonMultiple() .
			self::getFieldSettingsCommonRequired();
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
				<input data-bx-web-form-field-string-value-type="" type="hidden" name="FIELD[%name%][VALUE_TYPE]" value="%value_type%">
				<select data-bx-web-form-field-string-value-types=""  class="crm-webform-edit-popup-select">' . $valueTypesString . '</select>
			';
		}

		return '
			<label for="" class="crm-webform-edit-popup-label">
				<div class="crm-webform-edit-popup-name">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_NAME') . '</div>
				<div class="crm-webform-edit-popup-inner-container">
					<input data-bx-web-form-btn-caption="" name="FIELD[%name%][CAPTION]" type="text" value="%caption%" class="crm-webform-edit-popup-input">
					' . $valueTypesString . '
				</div>
			</label>
		';
	}

	public static function getFieldSettingsCommonMultiple()
	{
		return '
			<label data-bx-web-form-btn-multiple-cont="" for="FIELD_%name%_MULTIPLE" class="crm-webform-edit-popup-checkbox-container">
				<input data-bx-web-form-btn-multiple-value="" name="FIELD[%name%][MULTIPLE]" value="%multiple%" type="hidden">
				<input data-bx-web-form-btn-multiple="" id="FIELD_%name%_MULTIPLE" value="Y" type="checkbox" class="crm-webform-edit-popup-checkbox">
				<span class="crm-webform-edit-popup-checkbox-text">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_MULTIPLE') . '</span>
			</label>
		';
	}

	public static function getFieldSettingsCommonRequired()
	{
		return '
			<label for="FIELD_%name%_REQUIRED" class="crm-webform-edit-popup-checkbox-container">
				<input data-bx-web-form-btn-required-value="" name="FIELD[%name%][REQUIRED]" value="%required%" type="hidden">
				<input data-bx-web-form-btn-required="" id="FIELD_%name%_REQUIRED" value="Y" type="checkbox" class="crm-webform-edit-popup-checkbox">
				<span class="crm-webform-edit-popup-checkbox-text">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_REQUIRED') . '</span>
			</label>
		';
	}

	public static function getFieldSettingsCommonFieldInCrm()
	{
		return '
			<div class="crm-webform-edit-popup-tooltip">
				<span class="crm-webform-edit-popup-tooltip-element">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_IN_CRM') . ':</span>
				<span class="crm-webform-edit-popup-tooltip-element">%entity_field_caption% (%entity_caption%)</span>
				<span class="crm-webform-edit-popup-tooltip-edit" style="display: %url_display_style%;">
					<a target="_blank" href="/crm/configs/fields/CRM_%entity_name%/edit/%entity_field_name%/" title="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_IN_CRM_URL_HINT') . '">
						' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_IN_CRM_URL') . '
					</a>
				</span>
			</div>
		';
	}

	public static function getFieldSettingsCommonPlaceHolder()
	{
		return '
			<label for="" class="crm-webform-edit-popup-label">
				<div class="crm-webform-edit-popup-name">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_DESC') . '</div>
				<div class="crm-webform-edit-popup-inner-container">
					<input type="text" name="FIELD[%name%][PLACEHOLDER]" value="%placeholder%" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_DESC_HINT') . '"  class="crm-webform-edit-popup-input popup-input-placeholder">
				</div>
			</label>
		';
	}

	public static function getFieldSettingsCommonBigPic()
	{
		return '
			<label for="FIELD_%name%_SETTINGS_DATA_BIG_PIC" class="crm-webform-edit-popup-checkbox-container">
				<input data-bx-web-form-btn-big-pic-value="" name="FIELD[%name%][SETTINGS_DATA][BIG_PIC]" value="%settings_data_big_pic%" type="hidden">
				<input data-bx-web-form-btn-big-pic="" id="FIELD_%name%_SETTINGS_DATA_BIG_PIC" value="Y" type="checkbox" class="crm-webform-edit-popup-checkbox">
				<span class="crm-webform-edit-popup-checkbox-text">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_BIG_PIC') . '</span>
			</label>
		';
	}

	public static function getFieldSettingsCommonQuantity()
	{
		return '
			<label for="" class="crm-webform-edit-popup-label">
				<div class="crm-webform-edit-popup-name">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_QUANTITY') . '</div>
				<div class="crm-webform-edit-popup-inner-container">
					<input type="text" name="FIELD[%name%][SETTINGS_DATA][QUANTITY_MIN]" value="%settings_data_quantity_min%" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_QUANTITY_MIN') . '"  class="crm-webform-edit-popup-input popup-input-placeholder">
					<div style="line-height: 34px;">&nbsp - &nbsp</div>
					<input type="text" name="FIELD[%name%][SETTINGS_DATA][QUANTITY_MAX]" value="%settings_data_quantity_max%" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_QUANTITY_MAX') . '"  class="crm-webform-edit-popup-input popup-input-placeholder">
					<div style="line-height: 34px;">&nbsp ' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_QUANTITY_STEP') . ' &nbsp</div>
					<input type="text" name="FIELD[%name%][SETTINGS_DATA][QUANTITY_STEP]" value="%settings_data_quantity_step%" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_QUANTITY_STEP_HINT') . '"  class="crm-webform-edit-popup-input popup-input-placeholder">
				</div>
			</label>
		';
	}

	public static function getFieldSettingsCommonDefaultValue()
	{
		return '
			<label for="" class="crm-webform-edit-popup-label">
				<div class="crm-webform-edit-popup-name">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_DEF_VALUE') . '</div>
				<div class="crm-webform-edit-popup-inner-container">
					<input type="text" name="FIELD[%name%][VALUE]" value="%value%" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_DEF_VALUE_HINT') . '"  class="crm-webform-edit-popup-input popup-input-placeholder">
				</div>
			</label>
		';
	}

	public static function getFieldSettingsCommonStringType()
	{
		$typeList = \Bitrix\Crm\WebForm\Helper::getFieldStringTypes();
		$typesString = '';
		$typesString .= '<option value="string">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_STYPE_DEFAULT') . '</option>';
		foreach($typeList as $typeId => $typeCaption)
		{
			$typesString .= '<option value="' . htmlspecialcharsbx($typeId) . '">' . htmlspecialcharsbx($typeCaption) . '</option>';
		}
		$typesString = '
			<select data-bx-web-form-field-string-type="" class="crm-webform-edit-popup-select">' . $typesString . '</select>
		';

		return '
			<label for="" class="crm-webform-edit-popup-label">
				<div class="crm-webform-edit-popup-name">' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_SETTINGS_CHECK_AS') . '</div>
				<div class="crm-webform-edit-popup-inner-container">
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
			<select data-bx-web-form-field-display-cont="" class="crm-webform-edit-left-inner-field-select">
				<option value=""><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_ITEMS_DEFAULT')?></option>
				%items%
			</select>
		<?
		$result['DISPLAY_PART'] = ob_get_clean();

		ob_start();
		echo self::getFieldSettingsCommonName();
		echo self::getFieldSettingsCommonRequired();
		if (WebForm\Manager::isEmbeddingAvailable())
		{
			echo self::getFieldSettingsCommonMultiple();
			echo self::getFieldSettingsCommonBigPic();
			echo self::getFieldSettingsCommonQuantity();
		}
		?>
			<div class="crm-webform-edit-task-options-account-setup-info-description">
				<br>
				<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRODUCT_TITLE1')?>
			</div>
			<div data-bx-crm-webform-product="%id%">
				<div data-bx-crm-webform-product-items="">%settings_items%</div>
				<span data-bx-crm-webform-product-select="" class="crm-webform-edit-task-options-account-setup-new-goods-add">
					&#43;<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRODUCT_ADD')?>
				</span>
				<span class="crm-webform-edit-task-options-account-setup-new-goods-separator"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRODUCT_ADD_OR')?></span>
				<span data-bx-crm-webform-product-add-row="" class="crm-webform-edit-task-options-account-setup-new-goods-add">
					<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRODUCT_ADD_ROW')?>
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
			<label data-bx-crm-webform-product-item="%item_id%"  class="crm-webform-edit-task-options-account-setup-goods">
				<span data-bx-crm-webform-product-item-del="" class="crm-webform-edit-task-edit-deal-stage-close" title="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_REMOVE') . '"></span>
				<input name="FIELD[%name%][ITEMS][%item_id%][ID]" type="hidden" value="%item_id%">
				<input data-bx-crm-webform-product-item-input="" name="FIELD[%name%][ITEMS][%item_id%][VALUE]" value="%item_value%" class="crm-webform-edit-task-options-account-setup-goods-name" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_NAME1') . '">
				<input name="FIELD[%name%][ITEMS][%item_id%][PRICE]" value="%item_price%" class="crm-webform-edit-task-options-account-setup-goods-price" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_PRICE1') . ', %currency_short_name%">
				<input type="' . (WebForm\Manager::isEmbeddingAvailable() ? 'text' : 'hidden') . '" name="FIELD[%name%][ITEMS][%item_id%][DISCOUNT]" value="%item_discount%" class="crm-webform-edit-task-options-account-setup-goods-price" placeholder="' . Loc::getMessage('CRM_WEBFORM_EDIT_DOC_INVOICE_PRODUCT_CHOICE_DISCOUNT') . ', %currency_short_name%">
				<label style="' . (WebForm\Manager::isOrdersAvailable() ? '' : 'display: none;') . '" class="crm-webform-edit-task-options-account-setup-goods-price-custom">
				 	<input type="checkbox" name="FIELD[%name%][ITEMS][%item_id%][CUSTOM_PRICE]" value="Y" %item_custom_price%>
				 	Custom price
				</label>
			</label>
		';
	}

	public static function getFieldListSettingsItem($itemParams)
	{
		return '
			<div data-bx-crm-webform-field-settings-item="%item_id%" class="crm-webform-edit-popup-inner-container">
				<input name="FIELD[%name%][ITEMS][%item_id%][ID]" type="hidden" value="%item_id%">
				<input data-bx-crm-webform-field-settings-item-check="" style="display: none;" disabled name="FIELD[%name%][ITEMS][%item_id%][SELECTED]" value="Y" type="checkbox" class="crm-webform-edit-popup-input-element-checkbox">
				<input data-bx-crm-webform-field-settings-item-radio="" style="display: none;" disabled name="FIELD[%name%][ITEMS][%item_id%][SELECTED]" value="Y"  type="radio" class="crm-webform-edit-popup-input-element-checkbox">
				<input data-bx-crm-webform-field-settings-item-input="" name="FIELD[%name%][ITEMS][%item_id%][VALUE]" type="text" value="%item_value%" class="crm-webform-edit-popup-list-input">
				<span data-bx-crm-webform-field-settings-item-clear="" class="crm-webform-edit-popup-list-input-icon" title="' . Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_CLEAR') . '"></span>
			</div>
		';
	}

	public static function getFieldCheckboxSettingsItem($itemParams)
	{
		return self::getFieldListSettingsItem($itemParams);
	}

	public static function getFieldRadioSettingsItem($itemParams)
	{
		return self::getFieldListSettingsItem($itemParams);
	}

	public static function getFieldResourcebooking($itemParams)
	{
		$result = null;
		if (\Bitrix\Main\Loader::includeModule('calendar'))
		{
			\Bitrix\Crm\Integration\Calendar::loadResourcebookingUserfieldExtention();
			$result = [
				'DISPLAY_PART' => '<div class="crm-webform-resourcebooking-wrap"></div>',
				'SETTINGS' => '&nbsp;'
			];
		}
		return $result;
	}
}

function GetCrmWebFormFieldDependencyTemplate($params)
{
	$namePrefix = 'DEPENDENCIES[' . htmlspecialcharsbx($params['ID']) . ']';
	$idPrefix = 'DEPENDENCIES_' . htmlspecialcharsbx($params['ID']) . '';

	$actionList = array(
		array(
			'CAPTION' => Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_SHOW'),
			'VALUE' => \Bitrix\Crm\WebForm\Internals\FieldDependenceTable::ACTION_ENUM_SHOW
		),
		array(
			'CAPTION' => Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_HIDE'),
			'VALUE' => \Bitrix\Crm\WebForm\Internals\FieldDependenceTable::ACTION_ENUM_HIDE
		),
	);

	$isSelectedActionHide = false;
	$actionListCount = count($actionList);
	for($i = 0; $i < $actionListCount; $i++)
	{
		$actionList[$i]['SELECTED'] = (isset($params['DO_ACTION']) && $params['DO_ACTION'] == $actionList[$i]['VALUE']);
		if($actionList[$i]['SELECTED'] && $actionList[$i]['VALUE'] == \Bitrix\Crm\WebForm\Internals\FieldDependenceTable::ACTION_ENUM_HIDE)
		{
			$isSelectedActionHide = true;
		}
	}

	?>
	<div id="<?=$idPrefix?>" class="crm-webform-edit-task-options-rule-stage">
		<span id="<?=$idPrefix?>_BTN_REMOVE" class="crm-webform-edit-task-edit-deal-stage-close" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_REMOVE')?>"></span>
		<div class="crm-webform-edit-task-options-rule-select-container">
			<span class="crm-webform-edit-task-options-rule-select-item rule-select-item-if"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_IF')?>:</span>

			<input type="hidden" name="<?=$namePrefix?>[IF_FIELD_CODE]" id="<?=$idPrefix?>_IF_FIELD_CODE" value="<?=htmlspecialcharsbx($params['IF_FIELD_CODE'])?>">
			<input type="hidden" value="<?=htmlspecialcharsbx($params['IF_VALUE'])?>" name="<?=$namePrefix?>[IF_VALUE]" id="<?=$idPrefix?>_IF_VALUE">

			<select id="<?=$idPrefix?>_IF_FIELD_CODE_CTRL" class="crm-webform-edit-task-options-rule-select"></select>
			<span class="crm-webform-edit-task-options-rule-select-item rule-select-item-equally">&ndash; <?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_EQUAL')?> &ndash;</span>
			<select style="display: none;" id="<?=$idPrefix?>_IF_VALUE_CTRL_S" class="crm-webform-edit-task-options-rule-select"></select>
			<input style="display: none;" id="<?=$idPrefix?>_IF_VALUE_CTRL_I" class="crm-webform-edit-task-options-rule-input">
		</div>
		<div class="crm-webform-edit-task-options-rule-select-container">
			<span class="crm-webform-edit-task-options-rule-select-item rule-select-item-to"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_THEN')?>:</span>

			<select name="<?=$namePrefix?>[DO_ACTION]" id="<?=$idPrefix?>_DO_ACTION" class="crm-webform-edit-task-options-rule-select">
				<?foreach($actionList as $action):?>
					<option value="<?=htmlspecialcharsbx($action['VALUE'])?>" <?=($action['SELECTED'] ? 'selected' : '')?>><?=htmlspecialcharsbx($action['CAPTION'])?></option>
				<?endforeach;?>
			</select>

			<span class="crm-webform-edit-task-options-rule-select-item rule-select-item-equally-line">&ndash;</span>

			<input type="hidden" name="<?=$namePrefix?>[DO_FIELD_CODE]" id="<?=$idPrefix?>_DO_FIELD_CODE" value="<?=htmlspecialcharsbx($params['DO_FIELD_CODE'])?>">
			<select id="<?=$idPrefix?>_DO_FIELD_CODE_CTRL" class="crm-webform-edit-task-options-rule-select"></select>
		</div>
		<span id="<?=$idPrefix?>_ELSE_HIDE" style="display: <?=(!$isSelectedActionHide ? 'block' : 'none')?>;" class="crm-webform-edit-task-options-info">
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_ELSE_HIDE', array('%name%' => '<span></span>'))?>
		</span>
		<span id="<?=$idPrefix?>_ELSE_SHOW" style="display: <?=($isSelectedActionHide ? 'block' : 'none')?>;" class="crm-webform-edit-task-options-info">
			<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_DEP_ELSE_SHOW', array('%name%' => '<span></span>'))?>
		</span>
	</div><!--crm-webform-edit-task-edit-deal-stage-->
	<?
}

function GetCrmWebFormPresetFieldTemplate($params)
{
	$namePrefix = 'FIELD_PRESET[' . htmlspecialcharsbx($params['CODE']) . ']';
	$idPrefix = 'FIELD_PRESET_' . htmlspecialcharsbx($params['CODE']) . '';

	?>
	<div id="<?=$idPrefix?>" class="crm-webform-edit-task-edit-deal-stage">
		<span id="<?=$idPrefix?>_BTN_REMOVE" class="crm-webform-edit-task-edit-deal-stage-close" title="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_REMOVE')?>"></span>
		<span class="crm-webform-edit-task-edit-deal-stage-item">
			<?=htmlspecialcharsbx($params['ENTITY_FIELD_CAPTION'])?>
			(<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRESET_DOC')?>: <?=htmlspecialcharsbx($params['ENTITY_CAPTION'])?>)
		</span>
		<div class="crm-webform-edit-task-edit-deal-stage-input-container crm-webform-edit-macros-container">
			<input type="hidden" value="<?=htmlspecialcharsbx($params['VALUE'])?>" name="<?=$namePrefix?>[VALUE]" id="<?=$idPrefix?>_VALUE">
			<select style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_S" class="crm-webform-edit-task-edit-deal-stage-select"></select>
			<input style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_I" placeholder="<?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRESET_DOC_HINT')?>" class="crm-webform-edit-task-edit-deal-stage-input">
			<span class="crm-webform-edit-task-edit-deal-stage-macros" style="display: none;" id="<?=$idPrefix?>_VALUE_CTRL_I_M"><?=Loc::getMessage('CRM_WEBFORM_EDIT_TMPL_PRESET_MACROS')?></span>
			<span class="crm-webform-context-help" data-text="<?=htmlspecialcharsbx(nl2br(Loc::getMessage("CRM_WEBFORM_EDIT_TMPL_PRESET_MACROS_HINT")))?>">?</span>
		</div>
	</div><!--crm-webform-edit-task-edit-deal-stage-->
	<?
}