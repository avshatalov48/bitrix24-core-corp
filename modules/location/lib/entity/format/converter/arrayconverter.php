<?php

namespace Bitrix\Location\Entity\Format\Converter;

use Bitrix\Location\Entity\Format;
use Bitrix\Location\Entity\Format\Field;

/**
 * Class ArrayConverter
 * @package Bitrix\Location\Entity\Format\Converter
 */
class ArrayConverter
{
	/**
	 * @param \Bitrix\Location\Entity\Format $format
	 * @return array
	 */
	public static function convertToArray(\Bitrix\Location\Entity\Format $format): array
	{
		return [
			'code' => $format->getCode(),
			'name' => $format->getName(),
			'description' => $format->getDescription(),
			'delimiter' => $format->getDelimiter(),
			'languageId' => $format->getLanguageId(),
			'template' => $format->getTemplate(),
			'fieldCollection' => self::convertFieldCollectionToArray($format),
			'fieldForUnRecognized' => $format->getFieldForUnRecognized()
		];
	}

	/**
	 * @param \Bitrix\Location\Entity\Format $format
	 * @return array
	 */
	public static function convertFieldCollectionToArray(\Bitrix\Location\Entity\Format $format): array
	{
		$result = [];

		/** @var Field $field */
		foreach ($format->getFieldCollection() as $field)
		{
			$result[] = [
				'sort' => $field->getSort(),
				'type' => $field->getType(),
				'name' => $field->getName(),
				'description' => $field->getDescription()
			];
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @param string $languageId
	 * @return Format
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function convertFromArray(array $data, string $languageId): Format
	{
		$result = (new Format($languageId))
			->setName((string)$data['name'])
			->setDescription((string)$data['description'])
			->setDelimiter((string)$data['delimiter'])
			->setCode((string)$data['code'])
			->setTemplate((string)$data['template'])
			->setFieldForUnRecognized($data['fieldForUnRecognized'])
			->setFieldCollection(
				new Format\FieldCollection()
			);

		foreach ($data['fieldCollection'] as $field)
		{
			$result->getFieldCollection()->addItem(
				(new Format\Field((int)$field['type']))
					->setName((string)$field['name'])
					->setDescription((string)$field['description'])
					->setSort((int)$field['sort'])
			);
		}

		return $result;
	}
}