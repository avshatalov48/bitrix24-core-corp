<?php

namespace Bitrix\Crm\Service\Converter;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Model;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Main\ArgumentException;

class Type extends OrmObject
{
	/**
	 * @param Model\Dynamic\Type $model
	 *
	 * @return array
	 * @throws ArgumentException
	 */
	public function toJson($model, ?bool $allData = true): array
	{
		if (!($model instanceof Model\Dynamic\Type))
		{
			throw new ArgumentException('Model should be an instance of ' . Model\Dynamic\Type::class, 'model');
		}

		$data = $this->convertKeysToCamelCase($model->collectValues());

		unset($data['name'], $data['tableName'], $data['isCrmTrackingEnabled']);

		if ($allData)
		{
			$this->fillRelations($model, $data);
			$this->fillLinkedUserFields($model, $data);
			$this->fillCustomSections($model, $data);
		}

		$data = $this->prepareData($data);

		return $data;
	}

	protected function fillRelations(Model\Dynamic\Type $type, array &$data): void
	{
		if ($type->isNew())
		{
			return;
		}

		$relationManager = Container::getInstance()->getRelationManager();

		$parent = [];
		foreach ($relationManager->getParentRelations($type->getEntityTypeId()) as $parentRelation)
		{
			$parent[] = [
				'entityTypeId' => $parentRelation->getParentEntityTypeId(),
				'isChildrenListEnabled' => $parentRelation->isChildrenListEnabled(),
				'isPredefined' => $parentRelation->isPredefined(),
			];
		}

		$child = [];
		foreach ($relationManager->getChildRelations($type->getEntityTypeId()) as $childRelation)
		{
			$child[] = [
				'entityTypeId' => $childRelation->getChildEntityTypeId(),
				'isChildrenListEnabled' => $childRelation->isChildrenListEnabled(),
				'isPredefined' => $childRelation->isPredefined(),
			];
		}

		$data['relations'] = [
			'parent' => $parent,
			'child' => $child,
		];
	}

	protected function fillLinkedUserFields(Model\Dynamic\Type $type, array &$data): void
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($type->getEntityTypeId());

		$linkedUserFields = [];
		foreach (UserFieldManager::getLinkedUserFieldsMap() as $userFieldName => $userField)
		{
			$isEnabled = (
				!$type->isNew()
				&& UserFieldManager::isEntityEnabledInUserField($userField, $entityTypeName)
			);

			$linkedUserFields[$userFieldName] = $isEnabled;
		}

		$data['linkedUserFields'] = $linkedUserFields;
	}

	protected function fillCustomSections(Model\Dynamic\Type $type, array &$data): void
	{
		$customSections = $this->getCustomSections($type);
		if (is_null($customSections))
		{
			return;
		}

		$data['customSections'] = $customSections;

		$activeSections = array_filter(
			$customSections,
			static function (array $section): bool {
				return ($section['isSelected'] ?? false);
			}
		);
		/** @var array $activeSection */
		$activeSection = reset($activeSections);

		$data['customSectionId'] = $activeSection ? (int)$activeSection['id'] : null;
	}

	/**
	 * @param Model\Dynamic\Type $type
	 *
	 * @return array[]|null
	 */
	protected function getCustomSections(Model\Dynamic\Type $type): ?array
	{
		$sections = IntranetManager::getCustomSections();
		if ($sections === null)
		{
			return null;
		}

		$selectedCustomSectionId = $this->getSelectedCustomSectionId($type);

		$result = [];
		foreach ($sections as $section)
		{
			$result[] = [
				'id' => $section->getId(),
				'title' => $section->getTitle(),
				'isSelected' => $section->getId() === $selectedCustomSectionId,
			];
		}

		return $result;
	}

	private function getSelectedCustomSectionId(Model\Dynamic\Type $type): ?int
	{
		$automatedSolutionId = $type->getCustomSectionId();
		if ($automatedSolutionId <= 0)
		{
			return null;
		}

		$automatedSolution = Container::getInstance()->getAutomatedSolutionManager()->getAutomatedSolution(
			$automatedSolutionId,
		);
		if (!$automatedSolution)
		{
			return null;
		}

		return $automatedSolution['INTRANET_CUSTOM_SECTION_ID'] ?? null;
	}
}
