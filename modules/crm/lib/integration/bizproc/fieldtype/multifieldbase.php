<?php

namespace Bitrix\Crm\Integration\BizProc\FieldType;

use Bitrix\Bizproc\BaseType;
use Bitrix\Bizproc\FieldType;

class MultiFieldBase extends BaseType\Base
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::STRING;
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		$result = [];

		if (is_array($value) && !\CBPHelper::IsAssociativeArray($value))
		{
			$value = $value[0];
		}

		if (is_array($value) && is_array($value[mb_strtoupper($fieldType->getType())]))
		{
			foreach ($value[mb_strtoupper($fieldType->getType())] as $val)
			{
				if (!empty($val))
				{
					$result[] = \CCrmFieldMulti::GetEntityNameByComplex(
							mb_strtoupper($fieldType->getType()).'_'.$val['VALUE_TYPE'], false
						)
						.': '.$val['VALUE'];
				}
			}
		}

		return implode(static::getFormatSeparator('printable'), $result);
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class manager name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		if (is_array($value) && $fieldType->getTypeClass() === $toTypeClass)
		{
			$newValue = [];

			$type = mb_strtoupper($fieldType->getType());
			if (is_array($value[$type]))
			{
				$value = $value[$type];
			}

			foreach ($value as $key => $v)
			{
				if (is_array($v) && isset($v['VALUE'], $v['VALUE_TYPE']))
				{
					$newValue[$key] = [
						'VALUE' => $v['VALUE'],
						'VALUE_TYPE' => $v['VALUE_TYPE'],
					];
				}
			}

			return [$type => $newValue];
		}

		if (is_array($value))
		{
			if (isset($value['VALUE']))
			{
				return BaseType\StringType::convertTo($fieldType, $value['VALUE'], $toTypeClass);
			}
			else
			{
				$converted = [];
				foreach ($value as $k => $v)
				{
					if (is_array($v))
					{
						$v = isset($v['VALUE']) ? $v['VALUE'] : implode(', ', \CBPHelper::MakeArrayFlat($v));
					}
					$converted[] = BaseType\StringType::convertTo($fieldType, $v, $toTypeClass);
				}
				return $converted;
			}
		}

		return BaseType\StringType::convertTo($fieldType, $value, $toTypeClass);
	}

	/**
	 * Return conversion map for current type.
	 * @return array Map.
	 */
	public static function getConversionMap()
	{
		return BaseType\StringType::getConversionMap();
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		global $APPLICATION;

		$selectorValue = null;
		$typeValue = array();
		$value = (array) $value;

		foreach ($value as $k => $v)
		{
			if (\CBPActivity::isExpression($v))
			{
				$selectorValue = $v;
			}
			else
			{
				$typeValue[$k] = $v;
			}
		}

		$value = $typeValue;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
			Array(
				'FM_MNEMONIC' => static::generateControlName($field),
				'ENTITY_ID'   => $fieldType->getDocumentType()[2],
				'ELEMENT_ID'  => 0,
				'TYPE_ID'     => mb_strtoupper($fieldType->getType()),
				'VALUES'      => $value
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);

		$renderResult = ob_get_clean();

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true, '', $fieldType);
		}

		return $renderResult;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		return static::renderControlSingle($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @inheritdoc
	 */
	public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();
		$result = static::extractValue($fieldType, $field, $request);

		if (is_array($result))
		{
			$keys1 = array_keys($result);
			foreach ($keys1 as $key1)
			{
				if (is_array($result[$key1]))
				{
					$keys2 = array_keys($result[$key1]);
					foreach ($keys2 as $key2)
					{
						if (!isset($result[$key1][$key2]["VALUE"]) || empty($result[$key1][$key2]["VALUE"]))
							unset($result[$key1][$key2]);
					}
					if (count($result[$key1]) <= 0)
						unset($result[$key1]);
				}
				else
				{
					unset($result[$key1]);
				}
			}
			if (count($result) <= 0)
				$result = null;
		}
		else
		{
			$result = null;
		}

		$nameText = $field['Field'].'_text';
		$text = isset($request[$nameText]) ? $request[$nameText] : null;
		if (\CBPActivity::isExpression($text))
		{
			$result = $text;
		}

		return $result;
	}

	public static function extractValueMultiple(FieldType $fieldType, array $field, array $request)
	{
		return static::extractValueSingle($fieldType, $field, $request);
	}
}