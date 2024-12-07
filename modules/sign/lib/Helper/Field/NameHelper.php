<?php

namespace Bitrix\Sign\Helper\Field;

use Bitrix\Main;
use Bitrix\Sign\Type\BlockCode;
use CCrmOwnerType;

final class NameHelper
{
	public static function create(
		string $blockCode,
		string $fieldType,
		int $party,
		?string $fieldCode = null,
		?string $subfieldCode = null
	): string
	{
		if (BlockCode::isCommon($blockCode))
		{
			$fieldCode ??= Main\Security\Random::getString(32) . time();
		}
		// signature and stamp block has no fieldCode
		$fieldCode ??= '__' . $blockCode;

		return "$fieldCode.$fieldType.$blockCode.$party.$subfieldCode";
	}

	/**
	 * @return array{fieldCode: string, fieldType: string, blockCode: string, party: int, subfieldCode: string}
	 * @see static::createFieldName
	 */
	public static function parse(string $fieldName): array
	{
		$data = explode(".", $fieldName);
		return [
			'fieldCode' => $data[0] ?? '',
			'fieldType' => $data[1] ?? '',
			'blockCode' => $data[2] ?? '',
			'party' => (int)($data[3] ?? -1),
			'subfieldCode' => $data[4] ?? ''
		];
	}

	public static function parseFieldCode(string $fieldCode, string $entityType): array
	{
		if (str_starts_with($fieldCode, $entityType))
		{
			$fieldName = mb_substr($fieldCode, (mb_strlen($entityType) + 1));
			$fieldEntityType = $entityType;
		}
		else
		{
			[$fieldEntityType, $fieldName] = explode('_', $fieldCode, 2);
		}

		return [$fieldEntityType, $fieldName];
	}
}