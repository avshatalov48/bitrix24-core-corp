<?php

namespace Bitrix\Crm\Format;

use Bitrix\Crm\Integration\UI\EntitySelector\PlaceholderProvider;

final class PlaceholderFormatter
{
	private static array $placeholdersCache = [];

	public static function convertToDisplayFormat(int $entityTypeId, string $input): string
	{
		$placeholders = self::getPlaceholders($entityTypeId);
		$replaceMap = [];

		foreach ($placeholders as $code => $placeholder)
		{
			$replaceMap["{{$code}}"] = "{{$placeholder}}";
		}

		return strtr($input, $replaceMap);
	}

	public static function convertToExternalFormat(int $entityTypeId, string $input): string
	{
		$placeholders = self::getPlaceholders($entityTypeId);
		$replaceMap = [];

		foreach ($placeholders as $code => $placeholder)
		{
			$replaceMap["{{$placeholder}}"] = "{{$code}}";
		}

		return strtr($input, $replaceMap);
	}

	private static function getPlaceholders(int $entityTypeId): array
	{
		if (isset(self::$placeholdersCache[$entityTypeId]))
		{
			return self::$placeholdersCache[$entityTypeId];
		}

		$result = [];
		$placeholderProvider = new PlaceholderProvider(['entityTypeId' => $entityTypeId]);

		foreach ($placeholderProvider->getItems([]) as $item)
		{
			$result = array_merge($result, self::getPlaceholdersByItem($item));
		}

		self::$placeholdersCache[$entityTypeId] = $result;

		return $result;
	}

	private static function getPlaceholdersByItem($item): array
	{
		$result = [];

		foreach ($item->getChildren() as $child)
		{
			if ($child->getChildren())
			{
				$result = array_merge($result, self::getPlaceholdersByItem($child));
			}

			$result[$child->getId()] = $child->getCustomData()->get('text');
		}

		return $result;
	}
}
