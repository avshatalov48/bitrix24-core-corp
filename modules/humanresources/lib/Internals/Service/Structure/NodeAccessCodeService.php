<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Service\Structure;

use Bitrix\HumanResources\Internals\Service\Container as InternalContainer;
use Bitrix\HumanResources\Type\AccessCodeType;
use InvalidArgumentException;

final class NodeAccessCodeService
{
	public function getNodeIdsByAccessCodes(array $accessCodes): array
	{
		$allPrefixes = $this->getSupportedPrefixes();
		// Sort by length (from longest to shortest) to avoid conflicts
		usort($allPrefixes, fn($a, $b) => strlen($b) <=> strlen($a));

		$nodeIds = [];
		$internalAccessCodes = [];
		foreach ($accessCodes as $accessCode)
		{
			foreach ($allPrefixes as $prefix)
			{
				if (str_starts_with($accessCode, $prefix))
				{
					if (in_array($prefix, AccessCodeType::getIntranetDepartmentTypesPrefixes()))
					{
						$nodeId = substr($accessCode, strlen($prefix));
						if (is_numeric($nodeId))
						{
							$internalAccessCodes[] = $accessCode;

							break;
						}
					}

					$nodeId = substr($accessCode, strlen($prefix));
					if (is_numeric($nodeId))
					{
						$nodeIds[] = (int)$nodeId;

						break;
					}
				}
			}
		}

		$this->validateNodeIdsSize($accessCodes, $nodeIds, $internalAccessCodes);
		if (!empty($internalAccessCodes))
		{
			$oldDepartmentIds = InternalContainer::getNodeAccessCodeRepository()->getNodeIdsByAccessCodes($internalAccessCodes);

			return array_merge($nodeIds, $oldDepartmentIds);
		}

		return $nodeIds;
	}

	private function getSupportedPrefixes(): array
	{
		return array_merge(
			AccessCodeType::getAllStructurePrefixes(),
			AccessCodeType::getIntranetDepartmentTypesPrefixes(),
		);
	}

	private function validateNodeIdsSize(array $accessCodes, array $filteredIds, array $internalAccessCodes): void
	{
		$expectedSize = count($accessCodes) - count($internalAccessCodes);
		if (count($filteredIds) === $expectedSize)
		{
			return;
		}

		throw new InvalidArgumentException('Invalid access codes detected in the array');
	}
}