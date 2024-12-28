<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Permissions\WriteConfig;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Security\Role\Manage\Entity\Trait\FilterableByAutomatedSolution as FilterableByAutomatedSolutionTrait;

final class AutomatedSolutionConfig implements PermissionEntity, FilterableByAutomatedSolution
{
	use FilterableByAutomatedSolutionTrait;

	public const ENTITY_CODE_PREFIX = 'AS_CONFIG_';

	public function make(): array
	{
		$result = [];

		$automatedSolutions = $this->getAutomatedSolutions();
		foreach ($automatedSolutions as $automatedSolution)
		{
			$result[] = new EntityDTO(
				self::generateEntity($automatedSolution['ID']),
				self::generateName($automatedSolution['TITLE']),
				[],
				$this->permissions(),
			);
		}

		return $result;
	}

	private function permissions(): array
	{
		return [
			new WriteConfig(PermissionAttrPresets::allowedYesNo()),
		];
	}

	private function getAutomatedSolutions(): array
	{
		$manager = Container::getInstance()->getAutomatedSolutionManager();
		if ($this->filterByAutomatedSolutionId !== null)
		{
			$automatedSolution = $manager->getAutomatedSolution($this->filterByAutomatedSolutionId);

			return $automatedSolution === null ? [] : [$automatedSolution];
		}

		return $manager->getExistingAutomatedSolutions();
	}

	public static function generateEntity(int $automatedSolutionId): string
	{
		$prefix = self::ENTITY_CODE_PREFIX;

		return "{$prefix}{$automatedSolutionId}";
	}

	public static function decodeAutomatedSolutionId(string $entity): ?int
	{
		$parts = explode(self::ENTITY_CODE_PREFIX, $entity);
		$possibleAutomatedSolutionId = $parts[1] ?? null;

		if (!is_numeric($possibleAutomatedSolutionId))
		{
			return null;
		}

		return (int)$possibleAutomatedSolutionId;
	}

	public static function generateName(string $automatedSolutionTitle): string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_ENTITY_TYPE_AUTOMATED_SOLUTION_CONFIG_NAME', [
			'#AUTOMATED_SOLUTION_TITLE#' => $automatedSolutionTitle,
		]);
	}
}
