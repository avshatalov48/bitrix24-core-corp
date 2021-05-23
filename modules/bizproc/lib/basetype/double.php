<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Double
 * @package Bitrix\Bizproc\BaseType
 */
class Double extends Base
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::DOUBLE;
	}

	/**
	 * Normalize single value.
	 *
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return mixed Normalized value
	 */
	public static function toSingleValue(FieldType $fieldType, $value)
	{
		if (is_array($value))
		{
			reset($value);
			$value = current($value);
		}
		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		/** @var Base $toTypeClass */
		$type = $toTypeClass::getType();
		switch ($type)
		{
			case FieldType::BOOL:
				$value = (bool)$value ? 'Y' : 'N';
				break;
			case FieldType::DATE:
				$value = date(Main\Type\Date::convertFormatToPhp(\FORMAT_DATE), (int)$value);
				break;
			case FieldType::DATETIME:
				$value = date(Main\Type\DateTime::convertFormatToPhp(\FORMAT_DATETIME), (int)$value);
				break;
			case FieldType::DOUBLE:
				$value = (float)$value;
				break;
			case FieldType::INT:
				$value = (int)$value;
				break;
			case FieldType::STRING:
			case FieldType::TEXT:
				$value = (string) $value;
				break;
			case FieldType::USER:
				$value = 'user_'.(int)$value;
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * Return conversion map for current type.
	 * @return array Map.
	 */
	public static function getConversionMap()
	{
		return array(
			array(
				FieldType::BOOL,
				FieldType::DATE,
				FieldType::DATETIME,
				FieldType::DOUBLE,
				FieldType::INT,
				FieldType::STRING,
				FieldType::TEXT,
				FieldType::USER
			)
		);
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param mixed $value
	 * @param bool $allowSelection
	 * @param int $renderMode
	 * @return string
	 */
	protected static function renderControl(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		if ($allowSelection && !($renderMode & FieldType::RENDER_MODE_PUBLIC))
		{
			return static::renderControlSelector($field, $value, 'combine', '', $fieldType);
		}

		$name = static::generateControlName($field);
		$controlId = static::generateControlId($field);
		$className = static::generateControlClassName($fieldType, $field);

		if ($renderMode & FieldType::RENDER_MODE_PUBLIC)
		{
			$selectorAttributes = '';
			if ($allowSelection)
			{
				$selectorAttributes = sprintf(
					'data-role="inline-selector-target" data-property="%s" ',
					htmlspecialcharsbx(Main\Web\Json::encode($fieldType->getProperty()))
				);
			}

			$renderResult = sprintf(
				'<input type="text" class="%s" name="%s" value="%s" placeholder="%s" %s/>',
				htmlspecialcharsbx($className),
				htmlspecialcharsbx($name),
				htmlspecialcharsbx((string)$value),
				htmlspecialcharsbx($fieldType->getDescription()),
				$selectorAttributes
			);
		}
		else
		{
			$renderResult = '<input type="text" class="'.htmlspecialcharsbx($className)
				.'" size="10" id="'.htmlspecialcharsbx($controlId).'" name="'
				.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string) $value).'"/>';
		}

		return $renderResult;
	}

	/**
	 * @param int $renderMode Control render mode.
	 * @return bool
	 */
	public static function canRenderControl($renderMode)
	{
		return true;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$value = static::toSingleValue($fieldType, $value);
		return static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		if (empty($value))
			$value[] = null;

		$controls = array();

		foreach ($value as $k => $v)
		{
			$singleField = $field;
			$singleField['Index'] = $k;
			$controls[] = static::renderControl(
				$fieldType,
				$singleField,
				$v,
				$allowSelection,
				$renderMode
			);
		}

		if ($renderMode & FieldType::RENDER_MODE_PUBLIC)
		{
			$renderResult = static::renderPublicMultipleWrapper($fieldType, $field, $controls);
		}
		else
		{
			$renderResult = static::wrapCloneableControls($controls, static::generateControlName($field));
		}

		return $renderResult;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return float|null
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);

		if ($value !== null && is_string($value) && $value <> '')
		{
			if (\CBPActivity::isExpression($value))
				return $value;

			$value = str_replace(' ', '', str_replace(',', '.', $value));
			if (is_numeric($value))
			{
				$value = (float) $value;
			}
			else
			{
				$value = null;
				static::addError(array(
					'code' => 'ErrorValue',
					'message' => Loc::getMessage('BPDT_DOUBLE_INVALID'),
					'parameter' => static::generateControlName($field),
				));
			}
		}
		else
		{
			$value = null;
		}

		return $value;
	}
}