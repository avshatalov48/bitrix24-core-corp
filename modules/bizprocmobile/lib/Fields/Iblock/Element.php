<?php

namespace Bitrix\BizprocMobile\Fields\Iblock;

use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Bitrix\Main\Type;

Loader::requireModule('iblock');

class Element extends \Bitrix\Iblock\BizprocType\UserTypePropertyElist
{
	public static function convertPropertyToView(FieldType $fieldType, int $viewMode, array $property): array
	{
		if ($viewMode === FieldType::RENDER_MODE_JN_MOBILE)
		{
			$property['Settings'] = [
				'selectorType' => 'iblock-property-element',
				'provider' => [
					'options' => [
						'iblockId' => (int) $fieldType->getOptions(),
					]
				]
			];

			$value = $fieldType->getValue();
			if ($value)
			{
				$property['Settings']['entityList'] = static::loadMobileEntityList(
					\CBPHelper::flatten($value)
				);
			}

			return $property;
		}

		return parent::convertPropertyToView($fieldType, $viewMode, $property);
	}

	private static function loadMobileEntityList(array $ids): array
	{
		Type\Collection::normalizeArrayValuesByInt($ids, false);
		if (empty($ids))
		{
			return [];
		}

		$result = [];

		$iterator = Iblock\ElementTable::getList([
			'select' => [
				'ID',
				'IBLOCK_ID',
				'NAME',
			],
			'filter' => [
				'@ID' => $ids,
			],
		]);
		while ($row = $iterator->fetch())
		{
			$result[] = [
				'id' => $row['ID'],
				'title' => $row['NAME'],
			];
		}

		return $result;
	}
}
