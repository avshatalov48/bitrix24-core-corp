<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Helper
{
	const ENUM_TEMPLATE_LIGHT = 'light';
	const ENUM_TEMPLATE_TRANSPARENT = 'transp';
	const ENUM_TEMPLATE_COLORED = 'colored';

	/**
	 * Get template list.
	 *
	 * @return array
	 */
	public static function getTemplateList()
	{
		return array(
			static::ENUM_TEMPLATE_LIGHT => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_LIGHT'),
			static::ENUM_TEMPLATE_TRANSPARENT => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_TRANSPARENT'),
			static::ENUM_TEMPLATE_COLORED => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_COLORED'),
		);
	}

	/**
	 * Get field string types.
	 *
	 * @return array
	 */
	public static function getFieldStringTypes()
	{
		$types = [
			Internals\FieldTable::TYPE_ENUM_PHONE,
			Internals\FieldTable::TYPE_ENUM_EMAIL,
			Internals\FieldTable::TYPE_ENUM_INT,
			Internals\FieldTable::TYPE_ENUM_FLOAT,
		];

		$names = Internals\FieldTable::getTypeList();
		$result = [];
		foreach($types as $type)
		{
			$result[$type] = $names[$type];
		}

		return $result;
	}

	/**
	 * Get field listable types.
	 *
	 * @return array
	 */
	public static function getFieldListableTypes()
	{
		$types = [
			Internals\FieldTable::TYPE_ENUM_CHECKBOX,
			Internals\FieldTable::TYPE_ENUM_RADIO,
			Internals\FieldTable::TYPE_ENUM_LIST,
			Internals\FieldTable::TYPE_ENUM_PRODUCT,
		];

		$names = Internals\FieldTable::getTypeList();
		$result = [];
		foreach($types as $type)
		{
			$result[$type] = $names[$type];
		}

		return $result;
	}

	/**
	 * Get field non value types.
	 *
	 * @return array
	 */
	public static function getFieldNonValueTypes()
	{
		$types = [
			Internals\FieldTable::TYPE_ENUM_BR,
			Internals\FieldTable::TYPE_ENUM_HR,
			Internals\FieldTable::TYPE_ENUM_SECTION,
			Internals\FieldTable::TYPE_ENUM_PAGE,
		];

		$names = Internals\FieldTable::getTypeList();
		$result = [];
		foreach($types as $type)
		{
			$result[$type] = $names[$type];
		}

		return $result;
	}

	/**
	 * Get field string types.
	 *
	 * @param string|null $formName Form name.
	 * @param string|bool|null $formId Form ID.
	 * @return array
	 */
	public static function getExternalAnalyticsData($formName = null, $formId = null)
	{
		if (!$formName)
		{
			$formName = '%name%';
		}
		if ($formId === true)
		{
			$formId = '%form_id%';
		}

		return array(
			'category' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_CATEGORY')
				. ' "' . $formName . '"'
				. ($formId ? ", #$formId" : ''),
			'template' => array(
				'name' => '%name%',
				'code' => 'B24_' . ($formId ? $formId . "_" : '') . '%code%.html'
			),
			'eventTemplate' => array(
				'name' => '%name%',
				'code' => 'B24_FORM_%form_id%_%code%'
			),
			'field' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_FIELD')
					. ' "%name%"'
					. ($formId ? ", #$formId" : ''),
				'code' => '%code%'
			),
			'view' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_VIEW')
					. ($formId ? " #$formId" : ''),
				'code' => 'VIEW'
			),
			'start' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_START')
					. ($formId ? " #$formId" : ''),
				'code' => 'START'
			),
			'end' => array(
				'name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_END')
					. ($formId ? " #$formId" : ''),
				'code' => 'END'
			),
		);
	}

	/**
	 * Get web-forms entity selector field default settings.
	 *
	 * @param int $entityTypeId
	 * @param string $fieldId
	 * @param string $multiple
	 *
	 * @return array[]
	 */
	public static function getEntitySelectorParams(int $entityTypeId, string $fieldId = 'WEBFORM_ID', string $multiple = 'Y'): array
	{
		$tabId = sprintf('tab-%d-%s', $entityTypeId, mb_strtolower($fieldId));

		return [
			'params' => [
				'multiple' => $multiple,
				'dialogOptions' => [
					'items' => Manager::getListForEntitySelector($fieldId, $tabId),
					'height' => 200,
					'dropdownMode' => false,
					'showAvatars' => false,
					'tabs' => [
						[
							'id' => $tabId,
							'title' => Loc::getMessage('CRM_WEBFORM_HELPER_ENTITY_SELECTOR_TAB_NAME'),
						]
					],
					'recentTabOptions' => [
						'visible' => false,
					],
				],
			],
		];
	}
}
