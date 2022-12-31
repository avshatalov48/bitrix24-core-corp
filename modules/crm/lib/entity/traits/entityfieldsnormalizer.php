<?php

namespace Bitrix\Crm\Entity\Traits;

/**
 * Trait EntityFieldsNormalizer
 * @package Bitrix\Crm\Entity\Traits
 */
trait EntityFieldsNormalizer
{
	public function normalizeEntityFields(array &$fields): void
	{
		global $DB;

		$columns = $DB->GetTableFields(self::TABLE_NAME);
		foreach ($columns as $name => $info)
		{
			$value = ($fields[$name] ?? null);
			if ($value === null || is_scalar($value))
			{
				continue;
			}

			if (!is_object($value) || !method_exists($value , '__toString'))
			{
				$fields[$name] = null;
			}
		}
	}
}
