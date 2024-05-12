<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\Controller;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;

class RestrictionsByAttributes
{
	private array $progressStepsCache = [];

	private DepartmentProvider $departmentProvider;

	public function __construct(private Controller\Base $controller)
	{
		$this->departmentProvider = DepartmentProvider::getInstance();
	}


	public function getRestrictions(Collection $attributesCollection, QueryBuilderOptions $options): array
	{
		$restrictionData = [];

		$userId = $attributesCollection->getUserId();

		$userDepartmentIDs = $this->getUserDepartmentIDs($userId);

		$permissionEntityTypes = $attributesCollection->getAllowedEntityTypes();

		foreach ($permissionEntityTypes as $permissionEntityType)
		{
			$entityAttributes = $attributesCollection->getByEntityType($permissionEntityType);
			if (empty($entityAttributes))
			{
				continue;
			}

			$permissionSets = $this->createPermissionSets($entityAttributes, $userId, $userDepartmentIDs);

			$permissionSets = $this->joinPermissionSetsProgressSteps($permissionSets);

			$restrictionData[$permissionEntityType] = $this->createEntityRestrictionData($permissionSets);
		}

		$canSkipCategoryRestrictions = $this->canSkipCategoryRestrictions(
			$options,
			$attributesCollection,
			$restrictionData,
		);

		return $this->createRestrictionMap($restrictionData, $canSkipCategoryRestrictions);
	}

	private function canSkipCategoryRestrictions(
		QueryBuilderOptions $options,
		Collection $attributesCollection,
		array $restrictionData,
	): bool
	{
		if (!$options->canSkipCheckOtherEntityTypes())
		{
			return false;
		}

		if (!$attributesCollection->areAllEntityTypesAllowed())
		{
			return false;
		}

		foreach ($restrictionData as $permissionEntityType => $restrictions)
		{
			if (empty($restrictions))
			{
				continue;
			}
			foreach ($restrictions as $restriction)
			{
				if (
					!(
						count($restriction) === 1
						&& isset($restriction['PROGRESS_STEPS'])
						&& empty($this->getProgressSteps($permissionEntityType, $restriction))
					)
				)
				{
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * @param array $departmentIds
	 * @return int[]
	 */
	protected function getDepartmentsUsers(array $departmentIds): array
	{
		return $this->departmentProvider->getDepartmentsUsers($departmentIds);
	}

	/**
	 * @param int $userId
	 * @return int[]
	 */
	protected function getUserDepartmentIDs(int $userId): array
	{
		return $this->departmentProvider->getUserDepartmentIDs($userId);
	}

	protected function addTypeAndCategoryToRestrictionMap(
		array &$restrictionMap,
		string $permissionEntityType,
		bool $canSkipCategoryRestrictions = false
	): void
	{
		if (!isset($restrictionMap['ENTITY_TYPES']))
		{
			$restrictionMap['ENTITY_TYPES'] = [];
		}
		$restrictionMap['ENTITY_TYPES'][] = $permissionEntityType;

		if (!isset($restrictionMap['CATEGORY_ID']))
		{
			$restrictionMap['CATEGORY_ID'] = [];
		}

		if ($this->controller->hasCategories() && !$canSkipCategoryRestrictions)
		{
			$restrictionMap['CATEGORY_ID'][] = $this->controller->extractCategoryId($permissionEntityType);
		}
	}

	protected function getProgressSteps(string $permissionEntityType, array $restriction): array
	{
		$allProgressSteps = $this->loadProgressSteps($permissionEntityType);
		$progressSteps = $restriction['PROGRESS_STEPS'] ?? [];
		if (!empty($progressSteps))
		{
			sort($progressSteps, SORT_STRING);
			if (empty(array_diff($allProgressSteps, $progressSteps)))
			{
				$progressSteps = [];
			}
		}

		return $progressSteps;
	}

	protected function loadProgressSteps(string $permissionEntityType): array
	{
		if (!isset($this->progressStepsCache[$permissionEntityType]))
		{
			$this->progressStepsCache[$permissionEntityType] = $this->controller->hasProgressSteps()
				? $this->controller->getProgressSteps($permissionEntityType)
				: []
			;
			if (!empty($this->progressStepsCache[$permissionEntityType]))
			{
				sort($this->progressStepsCache[$permissionEntityType], SORT_STRING);
			}
		}

		return $this->progressStepsCache[$permissionEntityType];
	}


	/**
	 * @param mixed $permissionSets
	 * @return array
	 */
	public function joinPermissionSetsProgressSteps(array $permissionSets): array
	{
		$permissionFurl = [];
		foreach ($permissionSets as $permissionSet) {
			$userID = $permissionSet['USER_ID'];
			$departmentIDs = $permissionSet['DEPARTMENT_IDS'];
			$isOpened = $permissionSet['OPENED'];
			if (!empty($departmentIDs)) {
				sort($departmentIDs, SORT_NUMERIC);
			}
			$hash = md5(
				'U:' . $userID
				. 'D:' . (!empty($departmentIDs) ? implode(',', $departmentIDs) : '-')
				. 'O:' . ($isOpened ? 'Y' : 'N'));

			if (!isset($permissionFurl[$hash])) {
				$permissionFurl[$hash] = $permissionSet;
			}
			elseif (!empty($permissionSet['PROGRESS_STEPS'])) {
				$permissionFurl[$hash]['PROGRESS_STEPS'] = array_merge(
					$permissionFurl[$hash]['PROGRESS_STEPS'],
					array_diff(
						$permissionSet['PROGRESS_STEPS'],
						$permissionFurl[$hash]['PROGRESS_STEPS']
					)
				);
			}
		}
		return array_values($permissionFurl);
	}

	public function createPermissionSets(array $entityAttributes, int $userId, array $userDepartmentIDs): array
	{
		$permissionSets = [];
		foreach ($entityAttributes as $attributes) {
			if (empty($attributes)) {
				continue;
			}

			$permissionSet = [
				'USER_ID' => 0,
				'DEPARTMENT_IDS' => [],
				'PROGRESS_STEPS' => [],
				'OPENED' => false,
			];
			for ($i = 0; $i < count($attributes); $i++) {
				$attributeValue = $attributes[$i];

				$parsedAttributeValue = '';

				if (
					$this->controller->hasProgressSteps()
					&& $this->controller->tryParseProgressStep($attributeValue, $parsedAttributeValue)
					&& $parsedAttributeValue != ''
				)
				{
					$permissionSet['PROGRESS_STEPS'][] = $parsedAttributeValue;
				}
				elseif ($attributeValue === 'O')
				{
					$permissionSet['OPENED'] = true;
				}
				elseif (
					AttributesUtils::tryParseUser($attributeValue, $parsedAttributeValue)
					&& $parsedAttributeValue > 0
				)
				{
					$permissionSet['USER_ID'] = (int)$parsedAttributeValue;
				}
				elseif (
					AttributesUtils::tryParseDepartment($attributeValue, $parsedAttributeValue)
					&& $parsedAttributeValue > 0
				)
				{
					$permissionSet['DEPARTMENT_IDS'][] = (int)$parsedAttributeValue;
				}
			}

			$permissionSets[] = $permissionSet;
			if ($permissionSet['OPENED']) // if opened are allowed, also my and my department are allowed
			{
				$permissionSets[] = [
					'USER_ID' => $userId,
					'DEPARTMENT_IDS' => [],
					'PROGRESS_STEPS' => $permissionSet['PROGRESS_STEPS'],
					'OPENED' => false,
				];

				if (!empty($userDepartmentIDs)) {
					$permissionSets[] = [
						'USER_ID' => 0,
						'DEPARTMENT_IDS' => $userDepartmentIDs,
						'PROGRESS_STEPS' => $permissionSet['PROGRESS_STEPS'],
						'OPENED' => false,
					];
				}
			}
		}
		return $permissionSets;
	}


	private function createEntityRestrictionData(array $permissionSets): array
	{
		$data = [];
		foreach ($permissionSets as $permissionSet)
		{
			$hash = '-';
			$progressSteps = $permissionSet['PROGRESS_STEPS'];
			if (!empty($progressSteps))
			{
				sort($progressSteps, SORT_STRING);
				$hash = md5(implode(',', $permissionSet['PROGRESS_STEPS']));
			}

			if (!isset($data[$hash]))
			{
				$restriction = ['PROGRESS_STEPS' => $progressSteps];
			}
			else
			{
				$restriction = $data[$hash];
			}

			if ($permissionSet['OPENED'])
			{
				$restriction['OPENED'] = true;
			}

			$userID = $permissionSet['USER_ID'];
			if ($userID > 0)
			{
				if (!isset($restriction['USER_IDS']))
				{
					$restriction['USER_IDS'] = [];
				}
				if (!in_array($userID, $restriction['USER_IDS'], true))
				{
					$restriction['USER_IDS'][] = $userID;
				}
			}

			if (!empty($permissionSet['DEPARTMENT_IDS']))
			{
				if (!isset($restriction['USER_IDS']))
				{
					$restriction['USER_IDS'] = [];
				}
				$restriction['USER_IDS'] = array_unique(
					array_merge(
						$restriction['USER_IDS'],
						$this->getDepartmentsUsers($permissionSet['DEPARTMENT_IDS'])
					)
				);
			}
			$data[$hash] = $restriction;
		}

		return $data;
	}

	public function createRestrictionMap(array $restrictionData, bool $canSkipCategoryRestrictions): array
	{
		$restrictionMap = [];
		foreach ($restrictionData as $permissionEntityType => $restrictions) {
			if (empty($restrictions)) {
				if (!isset($restrictionMap['-'])) {
					$restrictionMap['-'] = [];
				}
				$this->addTypeAndCategoryToRestrictionMap(
					$restrictionMap['-'],
					$permissionEntityType,
					$canSkipCategoryRestrictions
				);

				continue;
			}

			foreach ($restrictions as $restriction) {
				$isProcessed = false;

				$progressSteps = $this->getProgressSteps($permissionEntityType, $restriction);

				$userIDs = $restriction['USER_IDS'] ?? [];
				if (!empty($userIDs)) {
					sort($userIDs, SORT_NUMERIC);

					$hash = md5(
						(!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-')
						. 'U:' . (!empty($userIDs) ? implode(',', $userIDs) : '-')
					);

					if (!isset($restrictionMap[$hash])) {
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
							'USER_IDS' => $userIDs,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType
					);

					$isProcessed = true;
				}

				$isOpened = isset($restriction['OPENED']) && $restriction['OPENED'];
				if ($isOpened) {
					$hash = md5(
						(!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-')
						. 'O:' . 'Y'
					);

					if (!isset($restrictionMap[$hash])) {
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
							'OPENED' => true,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType
					);

					$isProcessed = true;
				}

				if (!$isProcessed) {
					$hash = md5(
						!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-'
					);

					if (!isset($restrictionMap[$hash])) {
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType,
						$canSkipCategoryRestrictions
					);
				}
			}
		}
		return array_values($restrictionMap);
	}
}