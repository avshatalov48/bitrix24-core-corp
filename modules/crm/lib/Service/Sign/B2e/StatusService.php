<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use CCrmOwnerType;

/**
 * Service for working with b2e status.
 */
final class StatusService
{
	public function makeName(int $documentCategoryId, string $status): string
	{
		return sprintf('DT%d_%d:%s', CCrmOwnerType::SmartB2eDocument, $documentCategoryId, $status);
	}

	/**
	 * @param int $documentCategoryId
	 * @param array{string, string} $defaultTriggers
	 * @return array{string, string}
	 */
	public function makeTriggerNames(int $documentCategoryId, array $defaultTriggers): array
	{
		$triggers = [];
		foreach ($defaultTriggers AS $className => $status) {
			$triggers[$className] = $this->makeName($documentCategoryId, $status);
		}

		return $triggers;
	}
}
