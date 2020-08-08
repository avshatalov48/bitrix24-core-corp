<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 * @package Bitrix\Bizproc\BaseType
 */
class Base
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::STRING;
	}

	/** @var array $formats	 */
	protected static $formats = array(
		'printable' => 	array(
			'callable' =>'formatValuePrintable',
			'separator' => ', ',
		)
	);

	/**
	 * @param string $format
	 * @return callable|null
	 */
	protected static function getFormatCallable($format)
	{
		$format = mb_strtolower($format);
		$formats = static::getFormats();
		if (isset($formats[$format]['callable']))
		{
			$callable = $formats[$format]['callable'];
			if (is_string($callable))
			{
				$callable = array(get_called_class(), $callable);
			}

			return $callable;
		}
		return null;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	protected static function getFormatSeparator($format)
	{
		$format = mb_strtolower($format);
		$separator = ', '; //default - coma
		$formats = static::getFormats();
		if (isset($formats[$format]['separator']))
		{
			$separator = $formats[$format]['separator'];
		}
		return $separator;
	}

	/**
	 * @param string $name Format name.
	 * @param array $options Format options.
	 * @throws Main\ArgumentException
	 * @return void
	 */
	public static function addFormat($name, array $options)
	{
		$name = mb_strtolower($name);
		if (empty($options['callable']))
			throw new Main\ArgumentException('Callable property in format options is not set.');

		static::$formats[$name] = $options;
	}

	/**
	 * Get formats list.
	 * @return array
	 */
	public static function getFormats()
	{
		return static::$formats;
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
		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $format Format name.
	 * @return string
	 */
	public static function formatValueMultiple(FieldType $fieldType, $value, $format = 'printable')
	{
		$value = (array) $value;

		foreach ($value as $k => $v)
		{
			$value[$k] = static::formatValueSingle($fieldType, $v, $format);
		}

		return implode(static::getFormatSeparator($format), $value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $format Format name.
	 * @return mixed|null
	 */
	public static function formatValueSingle(FieldType $fieldType, $value, $format = 'printable')
	{
		$callable = static::getFormatCallable($format);
		$value = static::toSingleValue($fieldType, $value);

		if (is_callable($callable))
		{
			return call_user_func($callable, $fieldType, $value);
		}
		//return original value if format not found
		return $value;
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		return static::convertValueSingle($fieldType, $value, StringType::class);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return array
	 */
	public static function convertValueMultiple(FieldType $fieldType, $value, $toTypeClass)
	{
		$value = (array) $value;
		foreach ($value as $k => $v)
		{
			$value[$k] = static::convertValueSingle($fieldType, $v, $toTypeClass);
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return bool|int|float|string
	 * @throws Main\ArgumentException
	 */
	public static function convertValueSingle(FieldType $fieldType, $value, $toTypeClass)
	{
		$value = static::toSingleValue($fieldType, $value);
		/** @var Base $toTypeClass */
		$result = static::convertTo($fieldType, $value, $toTypeClass);
		if ($result === null)
			$result = $toTypeClass::convertFrom($fieldType, $value, get_called_class());

		if ($result !== null)
			$fieldType->setTypeClass($toTypeClass);

		return $result !== null? $result : $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return null
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		return null;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $fromTypeClass Type class name.
	 * @return null
	 */
	public static function convertFrom(FieldType $fieldType, $value, $fromTypeClass)
	{
		return null;
	}

	/**
	 * Return conversion map for current type.
	 * @return array Map.
	 */
	public static function getConversionMap()
	{
		return array(
			//to
			array(),
			//from
			array()
		);
	}

	/**
	 * @var array
	 */
	protected static $errors = array();

	/**
	 * @param mixed $error Error description.
	 */
	public static function addError($error)
	{
		static::$errors[] = $error;
	}

	/**
	 * @param array $errors Errors description.
	 * @return void
	 */
	public static function addErrors(array $errors)
	{
		static::$errors = array_merge(static::$errors, $errors);
	}

	/**
	 * @return array
	 */
	public static function getErrors()
	{
		return static::$errors;
	}

	/**
	 * Clean errors
	 */
	protected static function cleanErrors()
	{
		static::$errors = array();
	}

	/**
	 * @param array $field
	 * @return string
	 */
	protected static function generateControlId(array $field)
	{
		$id = 'id_'.$field['Field'];
		$index = isset($field['Index']) ? $field['Index'] : null;
		if ($index !== null)
		{
			$id .= '__n'.$index.'_';
		}
		return $id;
	}

	/**
	 * @param array $field
	 * @return string
	 */
	protected static function generateControlName(array $field)
	{
		$name = $field['Field'];
		$index = isset($field['Index']) ? $field['Index'] : null;
		if ($index !== null)
		{
			//new multiple name style
			$name .= '[]';
		}
		return $name;
	}

	protected static function generateControlClassName(FieldType $fieldType, array $field)
	{
		$prefix = 'bizproc-type-control';
		$classes = array($prefix);
		$classes[] = $prefix.'-'.static::getType();

		if ($fieldType->isMultiple())
		{
			$classes[] = $prefix.'-multiple';
		}
		if ($fieldType->isRequired())
		{
			$classes[] = $prefix.'-required';
		}

		return implode(' ', $classes);
	}

	/**
	 * @param array $controls
	 * @param string $wrapperId
	 * @return string
	 */
	protected static function wrapCloneableControls(array $controls, $wrapperId)
	{
		$wrapperId = (string) $wrapperId;
		$renderResult = '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="BizprocCloneable_'
			.htmlspecialcharsbx($wrapperId).'">';

		foreach ($controls as $control)
		{
			$renderResult .= '<tr><td>'.$control.'</td></tr>';
		}
		$renderResult .= '</table>';
		$renderResult .= '<input type="button" value="'.Loc::getMessage('BPDT_BASE_ADD')
			.'" onclick="BX.Bizproc.cloneTypeControl(\'BizprocCloneable_'
			.htmlspecialcharsbx($wrapperId).'\')"/><br />';

		return $renderResult;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $controls
	 * @return string
	 */
	protected static function renderPublicMultipleWrapper(FieldType $fieldType, array $field, array $controls)
	{
		$messageAdd = Loc::getMessage('BPDT_BASE_ADD');

		$name = Main\Text\HtmlFilter::encode(\CUtil::JSEscape(static::generateControlName($field)));
		$property = Main\Text\HtmlFilter::encode(Main\Web\Json::encode($fieldType->getProperty()));

		$renderResult = implode('', $controls) . <<<HTML
				<div>
					<a onclick="BX.Bizproc.FieldType.cloneControl({$property}, '{$name}', this.parentNode); return false;"
						class="bizproc-type-control-clone-btn">
						{$messageAdd}
					</a>
				</div>
HTML;
		return $renderResult;
	}

	/**
	 * Low-level control rendering method
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param mixed $value
	 * @param bool $allowSelection
	 * @param int $renderMode
	 * @return string - HTML rendering
	 */
	protected static function renderControl(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$name = static::generateControlName($field);
		$controlId = static::generateControlId($field);
		$className = static::generateControlClassName($fieldType, $field);

		if ($renderMode & FieldType::RENDER_MODE_PUBLIC)
		{
			return '<input type="text" class="'.htmlspecialcharsbx($className)
				.'" name="'.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string) $value)
				.'" placeholder="'.htmlspecialcharsbx($fieldType->getDescription()).'" value="'.htmlspecialcharsbx((string) $value).'"'
				.($allowSelection ? ' data-role="inline-selector-target"' : '')
				.'/>';
		}

		// example: control rendering
		return '<input type="text" class="'.htmlspecialcharsbx($className).'" size="40" id="'.htmlspecialcharsbx($controlId).'" name="'
			.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string) $value).'"/>';
	}

	/**
	 * @param int $renderMode Control render mode.
	 * @return bool
	 */
	public static function canRenderControl($renderMode)
	{
		if ($renderMode & FieldType::RENDER_MODE_MOBILE)
		{
			return false;
		}

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
		$selectorValue = null;
		if ($allowSelection && \CBPActivity::isExpression($value))
		{
			$selectorValue = $value;
			$value = null;
		}

		$renderResult = static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true, '', $fieldType);
		}

		return $renderResult;
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
		$selectorValue = null;
		$typeValue = array();
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $v)
		{
			if (\CBPActivity::isExpression($v))
				$selectorValue = $v;
			else
				$typeValue[] = $v;
		}
		// need to show at least one control
		if (empty($typeValue))
			$typeValue[] = null;

		$controls = [];

		foreach ($typeValue as $k => $v)
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

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true, '', $fieldType);
		}

		return $renderResult;
	}

	/**
	 * @param array $field
	 * @param null|string $value
	 * @param bool $showInput
	 * @param string $selectorMode
	 * @param FieldType $fieldType
	 * @return string
	 */
	protected static function renderControlSelector(array $field, $value = null, $showInput = false, $selectorMode = '', FieldType $fieldType = null)
	{
		$html = '';
		$controlId = static::generateControlId($field);
		if ($showInput)
		{
			$controlId = $controlId.'_text';
			$name = static::generateControlName($field).'_text';
			$html = '<input type="text" id="'.htmlspecialcharsbx($controlId).'" name="'
					.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string)$value).'">';
		}
		$html .= static::renderControlSelectorButton($controlId, $fieldType, $selectorMode);

		return $html;
	}

	protected static function renderControlSelectorButton($controlId, FieldType $fieldType, $selectorMode = '')
	{
		$baseType = $fieldType ? $fieldType->getBaseType() : null;
		$selectorProps = Main\Web\Json::encode(array(
			'controlId' => $controlId,
			'baseType' => $baseType,
			'type' => $fieldType ? $fieldType->getType() : null,
			'documentType' => $fieldType ? $fieldType->getDocumentType() : null,
			'documentId' => $fieldType ? $fieldType->getDocumentId() : null,
		));

		return '<input type="button" value="..." onclick="BPAShowSelector(\''
			.\CUtil::jsEscape(htmlspecialcharsbx($controlId)).'\', \''.\CUtil::jsEscape(htmlspecialcharsbx($baseType)).'\', '
			.($selectorMode ? '\''.\CUtil::jsEscape(htmlspecialcharsbx($selectorMode)).'\'' : 'null').', null, '
			.htmlspecialcharsbx(Main\Web\Json::encode($fieldType ? $fieldType->getDocumentType() : null)).');"'
			.' data-role="bp-selector-button" data-bp-selector-props="'.htmlspecialcharsbx($selectorProps).'">';
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $callbackFunctionName Client callback function name.
	 * @param mixed $value Field value.
	 * @return string
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		return '';
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|mixed
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$name = $field['Field'];
		$value = isset($request[$name]) ? $request[$name] : null;
		$fieldIndex = isset($field['Index']) ? $field['Index'] : null;
		if (is_array($value) && !\CBPHelper::isAssociativeArray($value))
		{
			if ($fieldIndex !== null)
				$value = isset($value[$fieldIndex]) ? $value[$fieldIndex] : null;
			else
			{
				reset($value);
				$value = current($value);
			}
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return mixed|null
	 */
	public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();
		$result = static::extractValue($fieldType, $field, $request);
		if ($result === null || $result === '')
		{
			$nameText = $field['Field'].'_text';
			$text = isset($request[$nameText]) ? $request[$nameText] : null;
			if (\CBPActivity::isExpression($text))
				$result = $text;
		}
		return $result;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return array
	 */
	public static function extractValueMultiple(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();

		$name = $field['Field'];
		$value = isset($request[$name]) ? $request[$name] : array();

		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $k => $v)
		{
			$field['Index'] = $k;
			$result = static::extractValue($fieldType, $field, $request);
			if ($result === null || $result === '')
			{
				unset($value[$k]);
			}
			else
				$value[$k] = $result;
		}

		//append selector value
		$nameText = $field['Field'].'_text';
		$text = isset($request[$nameText]) ? $request[$nameText] : null;
		if (\CBPActivity::isExpression($text))
			$value[] = $text;

		return array_values($value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return void
	 */
	public static function clearValueSingle(FieldType $fieldType, $value)
	{
		//Method fires when workflow was complete
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return void
	 */
	public static function clearValueMultiple(FieldType $fieldType, $value)
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $v)
		{
			static::clearValueSingle($fieldType, $v);
		}
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function internalizeValue(FieldType $fieldType, $objectName, $value)
	{
		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function internalizeValueSingle(FieldType $fieldType, $objectName, $value)
	{
		return static::internalizeValue($fieldType, $objectName, $value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function internalizeValueMultiple(FieldType $fieldType, $objectName, $value)
	{
		if (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = static::internalizeValue($fieldType, $objectName, $v);
			}
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function externalizeValue(FieldType $fieldType, $objectName, $value)
	{
		if (is_object($value) && method_exists($value, '__toString'))
		{
			return (string) $value;
		}
		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function externalizeValueSingle(FieldType $fieldType, $objectName, $value)
	{
		return static::externalizeValue($fieldType, $objectName, $value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $objectName Value owner name (Document, Variable etc.)
	 * @param mixed $value Field value.
	 * @return mixed
	 */
	public static function externalizeValueMultiple(FieldType $fieldType, $objectName, $value)
	{
		if (!is_array($value) || \CBPHelper::isAssociativeArray($value))
		{
			$value = array($value);
		}

		foreach ($value as $k => $v)
		{
			$value[$k] = static::externalizeValue($fieldType, $objectName, $v);
		}
		return $value;
	}

	/**
	 * @param mixed $valueA First value.
	 * @param mixed $valueB Second value.
	 * @return int Returns 1, -1 or 0
	 */
	public static function compareValues($valueA, $valueB)
	{
		if ($valueA > $valueB)
		{
			return 1;
		}

		if ($valueA < $valueB)
		{
			return -1;
		}

		return 0;
	}
}