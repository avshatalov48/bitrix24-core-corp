<?php

namespace Bitrix\Crm\Entity;

use CCrmOwnerType;

final class EntityEditorOptionParser
{
	private readonly EntityEditorOptionMap $map;

	private const REGEXP = '/(?<returning>returning_)?(?<entityDetails>(?<entityName>[\w]+)_details)(_[cC]_?(?<categoryId>\d+))?/';

	public function __construct()
	{
		$this->map = new EntityEditorOptionMap();
	}

	public function parse(string $entityOption): EntityEditorOptionParseResult
	{
		$result = new EntityEditorOptionParseResult();
		$entityTypeId = $this->parseEntityTypeId($entityOption);
		if ($entityTypeId === null)
		{
			return $result;
		}

		$result
			->setEntityTypeId($entityTypeId)
			->setCategoryId($this->parseCategoryId($entityOption));

		if ($entityTypeId === CCrmOwnerType::Lead)
		{
			$result->setIsReturning($this->parseReturning($entityOption));
		}

		return $result;
	}

	private function parseEntityTypeId(string $entityOption): ?int
	{
		$matches = $this->matchEntityOption($entityOption);

		$entityDetails = $matches['entityDetails'] ?? null;
		if ($entityDetails === null)
		{
			return null;
		}

		$entityTypeId = $this->map->entityTypeId($entityDetails);
		if ($entityTypeId !== null)
		{
			return $entityTypeId;
		}

		$entityName = $matches['entityName'] ?? null;
		$entityTypeId = CCrmOwnerType::ResolveID($entityName);

		return $entityTypeId === CCrmOwnerType::Undefined ? null : $entityTypeId;
	}

	private function parseCategoryId(string $entityOption): ?int
	{
		$matches = $this->matchEntityOption($entityOption);

		return $matches['categoryId'] ?? null;
	}

	private function parseReturning(string $entityOption): bool
	{
		$matches = $this->matchEntityOption($entityOption);

		return $matches['returning'] ?? false;
	}

	private function matchEntityOption(string $entityOption): array
	{
		preg_match(self::REGEXP, $entityOption, $matches);

		return $matches;
	}
}
