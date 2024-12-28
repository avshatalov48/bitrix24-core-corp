<?php

namespace Bitrix\Crm\AutomatedSolution;

use Bitrix\Crm\AutomatedSolution\Action\Add;
use Bitrix\Crm\AutomatedSolution\Action\BindTypeToAutomatedSolution;
use Bitrix\Crm\AutomatedSolution\Action\Delete;
use Bitrix\Crm\AutomatedSolution\Action\LegacySet;
use Bitrix\Crm\AutomatedSolution\Action\Read\Fetch;
use Bitrix\Crm\AutomatedSolution\Action\Read\FetchBoundTypeIds;
use Bitrix\Crm\AutomatedSolution\Action\UnbindTypeFromAutomatedSolution;
use Bitrix\Crm\AutomatedSolution\Action\Update;
use Bitrix\Crm\AutomatedSolution\Support\TypeFilter;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\DynamicTypesMap;
use Bitrix\Main\Result;

final class AutomatedSolutionManager
{
	public const MODULE_ID = 'crm';

	/** @var CustomSection[] | null $customSections */
	protected ?array $intranetCustomSections = null;

	protected ?array $automatedSolutions = null;

	private DynamicTypesMap $dynamicTypesMap;

	public function __construct()
	{
		$this->dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
	}

	/**
	 * @param array $fields
	 *
	 * @return Result<array{fields: array}>
	 */
	public function addAutomatedSolution(array $fields): Result
	{
		$result = (new Add($fields))->execute();

		$this->cleanRuntimeCache();

		return $result;
	}

	/**
	 * @param int $id
	 * @param array $fields
	 *
	 * @return Result<array{fields: array}>
	 */
	public function updateAutomatedSolution(int $id, array $fields): Result
	{
		$currentFields = $this->getAutomatedSolution($id);
		if (!$currentFields)
		{
			return (new Result())->addError(ErrorCode::getNotFoundError());
		}

		$result = (new Update($id, $currentFields, $fields))->execute();

		$this->cleanRuntimeCache();

		return $result;
	}

	public function deleteAutomatedSolution(int $id): Result
	{
		$result = (new Delete($id))->execute();

		$this->cleanRuntimeCache();

		return $result;
	}

	public function getAutomatedSolution(int $id): ?array
	{
		foreach ($this->getExistingAutomatedSolutions() as $solution)
		{
			if ((int)$solution['ID'] === $id)
			{
				return $solution;
			}
		}

		return null;
	}

	/**
	 * @param Type[] $types
	 * @param int $automatedSolutionId
	 *
	 * @return Result
	 */
	public function setTypeBindingsInAutomatedSolution(array $types, int $automatedSolutionId): Result
	{
		[$typesToBind, $typesToUnbind] = $this->findTypesToBindAndUnbind($types, $automatedSolutionId);

		if (empty($typesToBind) && empty($typesToUnbind))
		{
			// nothing to change
			return new Result();
		}

		$overallResult = new Result();
		$userPermissions = Container::getInstance()->getUserPermissions();
		if (!empty($typesToBind) && !$userPermissions->canEditAutomatedSolutions())
		{
			$overallResult->addError(ErrorCode::getAccessDeniedError());

			return $overallResult;
		}
		if (!empty($typesToUnbind) && !$userPermissions->isCrmAdmin())
		{
			$overallResult->addError(ErrorCode::getAccessDeniedError());

			return $overallResult;
		}

		foreach ($typesToBind as $type)
		{
			$bindResult = (new BindTypeToAutomatedSolution($type, $automatedSolutionId))->execute();
			if (!$bindResult->isSuccess())
			{
				$overallResult->addErrors($bindResult->getErrors());
			}
		}

		foreach ($typesToUnbind as $type)
		{
			$unbindResult = (new UnbindTypeFromAutomatedSolution($type, $automatedSolutionId))->execute();
			if (!$unbindResult->isSuccess())
			{
				$overallResult->addErrors($unbindResult->getErrors());
			}
		}

		Container::getInstance()->getRouter()->reInit();
		$this->cleanRuntimeCache();

		return $overallResult;
	}

	private function findTypesToBindAndUnbind(array $typesToSet, int $automatedSolutionId): array
	{
		$previousTypeIds = $this->getBoundTypeIds($automatedSolutionId);
		$currentTypeIds = array_map(fn(Type $type) => $type->getId(), $typesToSet);

		$typeIdsToBind = array_diff($currentTypeIds, $previousTypeIds);
		$typeIdsToUnbind = array_diff($previousTypeIds, $currentTypeIds);

		$map = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);

		return [
			$map->getBunchOfTypesByIds($typeIdsToBind),
			$map->getBunchOfTypesByIds($typeIdsToUnbind),
		];
	}

	public function bindTypeToAutomatedSolution(Type $type, int $automatedSolutionId): Result
	{
		$result = (new BindTypeToAutomatedSolution($type, $automatedSolutionId))->execute();

		if ($result->isSuccess())
		{
			Container::getInstance()->getRouter()->reInit();
		}

		$this->cleanRuntimeCache();

		return $result;
	}

	public function unbindTypeFromAutomatedSolution(Type $type, int $automatedSolutionId): Result
	{
		$result = (new UnbindTypeFromAutomatedSolution($type, $automatedSolutionId))->execute();

		if ($result->isSuccess())
		{
			Container::getInstance()->getRouter()->reInit();
		}

		$this->cleanRuntimeCache();

		return $result;
	}

	public function getBoundTypeIds(int $automatedSolutionId): array
	{
		$map = $this->getBoundTypeIdsForMultipleAutomatedSolutions([$automatedSolutionId]);

		return $map[$automatedSolutionId];
	}

	public function isTypeBoundToAnyAutomatedSolution(Type $type): bool
	{
		return TypeFilter::isTypeBoundToAnyAutomatedSolution($type);
	}

	public function isTypeBoundToAnyAutomatedSolutionById(int $typeId): bool
	{
		$type = Container::getInstance()->getType($typeId);

		return $this->isTypeBoundToAnyAutomatedSolution($type);
	}

	/**
	 * @param int[] $automatedSolutionIds
	 *
	 * @return Array<int, int[]>
	 */
	public function getBoundTypeIdsForMultipleAutomatedSolutions(array $automatedSolutionIds): array
	{
		$result = (new FetchBoundTypeIds($automatedSolutionIds))->execute();

		if (!$result->isSuccess())
		{
			return [];
		}

		return $result->getData()['typeIdsMap'] ?? [];
	}

	/**
	 * @deprecated
	 */
	public function setAutomatedSolutions(
		Type $type,
		array $fields,
		bool $checkLimits = true,
	): Result
	{
		$action = new LegacySet(
			$type,
			$fields,
			$this->getExistingAutomatedSolutions(),
			$this->getExistingIntranetCustomSections(),
			$checkLimits,
		);

		$result = $action->execute();

		if ($result->isSuccess())
		{
			Container::getInstance()->getRouter()->reInit();
		}

		$this->cleanRuntimeCache();

		return $result;
	}

	/**
	 * @internal
	 * will be removed soon, after a complete transition to storage in CRM tables
	 *
	 * @return CustomSection[]
	 */
	public function getExistingIntranetCustomSections(): array
	{
		if (!isset($this->intranetCustomSections))
		{
			$customSections = IntranetManager::getCustomSections() ?? [];

			$result = [];
			foreach ($customSections as $customSection)
			{
				$result[$customSection->getId()] = $customSection;
			}

			$this->intranetCustomSections = $result;
		}

		return $this->intranetCustomSections;
	}

	public function getExistingAutomatedSolutions(): array
	{
		if (!isset($this->automatedSolutions))
		{
			$this->automatedSolutions = [];

			$result = (new Fetch())->execute();

			$automatedSolutions = $result->getData()['automatedSolutions'] ?? [];
			foreach ($automatedSolutions as $solution)
			{
				$this->automatedSolutions[$solution['INTRANET_CUSTOM_SECTION_ID']] = $solution;
			}
		}

		return $this->automatedSolutions;
	}

	public function getAutomatedSolutionsFilteredByPermissions(?int $userId = null): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		$result = [];
		foreach ($this->getExistingAutomatedSolutions() as $intranetCustomSectionId => $existingAutomatedSolution)
		{
			if ($userPermissions->isAutomatedSolutionAdmin($existingAutomatedSolution['ID']))
			{
				$result[$intranetCustomSectionId] = $existingAutomatedSolution;
			}
		}

		return $result;
	}

	private function cleanRuntimeCache(): void
	{
		$this->automatedSolutions = null;
		$this->intranetCustomSections = null;
	}
}
