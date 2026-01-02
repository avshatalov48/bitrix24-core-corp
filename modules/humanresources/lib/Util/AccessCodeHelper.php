<?php

namespace Bitrix\HumanResources\Util;

use Bitrix\HumanResources\Type\AccessCodeType;

class AccessCodeHelper
{
	/**
	 * Makes structure node access code by node id
	 * @param int $id
	 *
	 * @return string
	 */
	public static function makeCodeByTypeAndId(int $id, AccessCodeType $accessCodeType = AccessCodeType::HrStructureNodeType): string
	{
		return $accessCodeType->buildAccessCode($id);
	}

	/**
	 * Extracts structure node id from structure access code
	 * @param string $accessCode
	 *
	 * @return int|null
	 */
	public static function extractIdFromCode(string $accessCode, AccessCodeType $accessCodeType = AccessCodeType::HrStructureNodeType): ?int
	{
		$prefix = $accessCodeType->value;

		if (mb_strpos($accessCode, $prefix) !== 0)
		{
			return null;
		}
		$id = mb_substr($accessCode, mb_strlen($prefix));
		if (is_numeric($id))
		{
			return (int)$id;
		}

		return null;
	}
}