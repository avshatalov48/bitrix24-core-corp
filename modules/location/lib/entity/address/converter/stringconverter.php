<?php

namespace Bitrix\Location\Entity\Address\Converter;

use Bitrix\Location\Entity\Address;
use Bitrix\Location\Entity\Format;
use Bitrix\Main\ArgumentOutOfRangeException;

final class StringConverter
{
	const STRATEGY_TYPE_TEMPLATE = 'template';
	const STRATEGY_TYPE_FIELD_SORT = 'field_sort';
	const STRATEGY_TYPE_FIELD_TYPE = 'field_type';

	const CONTENT_TYPE_HTML = 'html';
	const CONTENT_TYPE_TEXT = 'text';

	/**
	 * Converts addrerss to string with given format
	 * @param Address $address
	 * @param Format $format
	 * @param string $strategyType
	 * @param string $contentType
	 * @return string
	 */
	public static function convertToString(Address $address, Format $format, string $strategyType, string $contentType): string
	{
		if($strategyType === self::STRATEGY_TYPE_TEMPLATE)
		{
			$result = self::convertToStringTemplate($address, $format, $contentType);
		}
		else if($strategyType === self::STRATEGY_TYPE_FIELD_SORT)
		{
			$result = self::convertToStringByField($address, $format, $contentType);
		}
		else if($strategyType === self::STRATEGY_TYPE_FIELD_TYPE)
		{
			$fieldSorter = static function(Format\Field $a, Format\Field $b): int
			{
				$aType = $a->getType();
				$bType = $b->getType();

				if($aType === 0)
				{
					$result = -1;
				}
				elseif ($bType === 0)
				{
					$result = 1;
				}
				else
				{
					$result = $aType - $bType;
				}

				return $result;
			};

			$result = self::convertToStringByField($address, $format, $contentType, $fieldSorter);
		}
		else
		{
			throw new ArgumentOutOfRangeException('strategyType');
		}

		return $result;
	}

	/**
	 * If format has template
	 * @param Address $address
	 * @param Format $format
	 * @param string $contentType
	 * @return string
	 */
	protected static function convertToStringTemplate(Address $address, Format $format, string $contentType): string
	{
		$result = $format->getTemplate();

		if($contentType === self::CONTENT_TYPE_HTML)
		{
			$result = str_replace("\n", '<br/>', $result);
		}

		$matches = [];

		// find placeholders witch looks like {{ ... }}
		if(preg_match_all('/{{.*?}}/ms', $result, $matches))
		{
			foreach($matches[0] as $component)
			{
				$fields = [];

				// find placeholders wich looks like # ... #
				if(!preg_match('/#([0-9A-Z_]*?)#/', $component, $fields))
				{
					continue;
				}

				if(!isset($fields[1]) || !is_string($fields[1]))
				{
					continue;
				}

				if(!defined(Address\FieldType::class.'::'.$fields[1]))
				{
					continue;
				}

				$type = constant(Address\FieldType::class.'::'.$fields[1]);
				$addressFieldValue = $address->getFieldValue($type);

				if($addressFieldValue === null)
				{
					continue;
				}

				if($contentType === self::CONTENT_TYPE_HTML)
				{
					$addressFieldValue = htmlspecialcharsbx($addressFieldValue);
				}

				$componentReplacer = str_replace($fields[0], $addressFieldValue, $component);
				$componentReplacer = trim($componentReplacer, '{}');
				$result = str_replace($component, $componentReplacer, $result);
			}
		}

		// Remove redundant placeholders
		$result = preg_replace('/({{.*?}})/ms', '', $result);

		// Remove redundant line breaks
		if($contentType === self::CONTENT_TYPE_HTML)
		{
			$result = preg_replace('/(<br\/>)+/', '<br/>',  $result);
		}
		else
		{
			$result = preg_replace("/(\n)+/", "\n", $result);
		}

		// Remove line break if it goes in the beginning
		$lineBreak = ($contentType === self::CONTENT_TYPE_HTML) ? '<br/>' : "\n";
		$result = (mb_strpos($result, $lineBreak) === 0) ? mb_substr($result, mb_strlen($lineBreak)) : $result;

		return $result;
	}

	/**
	 * If format has not template
	 * @param Address $address
	 * @param Format $format
	 * @param string $contentType
	 * @param callable $fieldSorter
	 * @return string
	 */
	protected static function convertToStringByField(Address $address, Format $format, string $contentType, callable $fieldSorter = null): string
	{
		$result = '';
		$fields = array_values($format->getFieldCollection()->getItems());

		if($fieldSorter !== null)
		{
			usort($fields, $fieldSorter);
		}

		foreach($fields as $field)
		{
			$fieldValue = $address->getFieldValue($field->getType());

			if($fieldValue === null)
			{
				continue;
			}

			if($contentType === self::CONTENT_TYPE_HTML)
			{
				$fieldValue = htmlspecialcharsbx($fieldValue);
			}

			if($result !== '')
			{
				$result .= $format->getDelimiter();
			}

			$result .= $fieldValue;
		}

		return $result;
	}
}