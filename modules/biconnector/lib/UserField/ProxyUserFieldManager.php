<?php

namespace Bitrix\BIConnector\UserField;

final class ProxyUserFieldManager
{
	public static function getText(array $userField)
	{
		global $USER_FIELD_MANAGER;

		$bypassResult = self::getBypassByUserFieldType($userField['USER_TYPE_ID'] ?? '')?->getText($userField);
		if (!empty($bypassResult))
		{
			return $bypassResult;
		}

		return $USER_FIELD_MANAGER->getPublicText($userField);
	}

	private static function getBypassByUserFieldType(string $ufType): ?Bypass\Bypass
	{
		return match ($ufType)
		{
			'iblock_element' => new Bypass\IBlockElement(),
			'iblock_section' => new Bypass\IBlockSection(),
			default => null,
		};
	}
}