<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * UI rendering for a generic user field type
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\UserField;

use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\UserField;

abstract class UI
{
	public static function getClass($dataType)
	{
		$dataType = trim((string)$dataType);
		if ($dataType == '')
		{
			throw new \Bitrix\Main\ArgumentException('$dataType could not be empty');
		}
		$dataType = str_replace('_', '', $dataType);

		$className = __NAMESPACE__ . '\\ui\\' . $dataType;
		if (!class_exists($className))
		{
			return __CLASS__;
		}

		return $className;
	}

	public static function showEdit(array $field, array $parameters = array(), $component = null)
	{
		if(isset($field['EDIT_IN_LIST']) && $field['EDIT_IN_LIST'] === 'Y')
		{
			static::showUI('bitrix:system.field.edit', $field, $parameters, $component);
		}
		else
		{
			static::showView($field, $parameters);
		}
	}

	public static function showView($field, array $parameters = array())
	{
		static::showUI('bitrix:system.field.view', $field, $parameters);
	}

	/**
	 * Check whether userfield is recommended to have user interface
	 *
	 * @param array $field
	 * @return bool
	 */
	public static function isSuitable(array $field)
	{
		// the following fields are obsolete and\or have no purpose of using with tasks module
		if(in_array($field['USER_TYPE_ID'], array('file', 'vote', 'video', 'disk_version', 'string_formatted', 'url_preview')))
		{
			return false;
		}

		// the following combinations of type\multiple works too far unstable to be presented to a user
		if($field['MULTIPLE'] == 'Y')
		{
			if(in_array($field['USER_TYPE_ID'], array('employee', 'hlblock', 'boolean', 'iblock_section')))
			{
				return false;
			}
		}

		return true;
	}

	private static function showUI($componentName, array $field, array $parameters = array(), $parentComponentInstance = null)
	{
		if (!(int)($field['ENTITY_VALUE_ID'] ?? null))
		{
			$useDefault = false;
			$valueEmpty = isset($field['VALUE']) ? UserField::isValueEmpty($field['VALUE']) : true;

			if ((($parameters['PREFER_DEFAULT'] ?? null) || (isset($field['MANDATORY']) && $field['MANDATORY'] == 'Y')) && $valueEmpty)
			{
				$useDefault = true;
			}

			// just to make uf logic work
			$field['ENTITY_VALUE_ID'] = !$useDefault;
		}

		if (isset($field['VALUE']) && Collection::isA($field['VALUE']))
		{
			$field['VALUE'] = $field['VALUE']->toArray();
		}

		$parameters = array_merge(
			$parameters,
			[
				'bVarsFromForm' => false,
				'arUserField' => $field,
				'DISABLE_LOCAL_EDIT' => ($parameters['PUBLIC_MODE'] ?? null)
			]
		);

		$GLOBALS['APPLICATION']->IncludeComponent(
			$componentName,
			$field["USER_TYPE"]["USER_TYPE_ID"] ?? '',
			$parameters,
			$parentComponentInstance,
			array("HIDE_ICONS" => "Y")
		);
	}
}