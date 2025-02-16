<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Integration;
use Bitrix\Crm\Model\Dynamic;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Relation;
use Bitrix\Crm\Relation\Collection;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;

class Type extends Base
{
	public function getAutoWiredParameters(): array
	{
		$params = parent::getAutoWiredParameters();

		$params[] = new ExactParameter(
			Dynamic\Type::class,
			'type',
			function($className, $id)
			{
				$id = (int)$id;
				$type = Container::getInstance()->getType($id);

				if (!$type || \CCrmOwnerType::isDynamicTypeBasedStaticEntity($type->getEntityTypeId()))
				{
					$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));
					return null;
				}

				return $type;
			}
		);

		return $params;
	}

	public function fieldsAction(): ?array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		if (!$userPermissions->isCrmAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}
		$fieldsInfo = TypeTable::getFieldsInfo();

		return [
			'fields' => $this->prepareFieldsInfo($fieldsInfo),
		];
	}

	public function getAction(Dynamic\Type $type): ?array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		if (
			!$userPermissions->isAdminForEntity($type->getEntityTypeId())
			&& !$userPermissions->canReadType($type->getEntityTypeId())
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		return [
			'type' => $type->jsonSerialize(),
		];
	}

	public function getByEntityTypeIdAction(int $entityTypeId): ?array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		if (
			!$userPermissions->isAdminForEntity($entityTypeId)
			&& !$userPermissions->isCrmAdmin()
			&& !$userPermissions->canReadType($entityTypeId)
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		if (!$type)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));

			return null;
		}

		return [
			'type' => $type->jsonSerialize(),
		];
	}

	public function listAction(array $order = null, array $filter = null, PageNavigation $pageNavigation = null): ?Page
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		if (!$userPermissions->isCrmAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}
		$parameters = [];

		$parameters['filter'] = $this->removeDotsFromKeys($this->convertKeysToUpper((array)$filter));

		$typeTable = Container::getInstance()->getDynamicTypeDataClass();

		$allowedFields = array_keys($typeTable::getFieldsInfo());

		if (!$this->validateFilter($parameters['filter'], $allowedFields))
		{
			return null;
		}

		$parameters['filter'][] = [
			'!@ENTITY_TYPE_ID' => \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds(),
		];

		if(is_array($order))
		{
			$parameters['order'] = $this->convertKeysToUpper($order);
			$parameters['order'] = $this->convertValuesToUpper(
				$parameters['order'],
				Converter::TO_UPPER | Converter::VALUES,
			);
			if (!$this->validateOrder($parameters['order'], $allowedFields))
			{
				return null;
			}
		}

		if($pageNavigation)
		{
			$parameters['offset'] = $pageNavigation->getOffset();
			$parameters['limit'] = $pageNavigation->getLimit();
		}

		$types = [];

		$list = $typeTable::getList($parameters);
		while($type = $list->fetchObject())
		{
			$types[] = $type->jsonSerialize(false);
		}

		return new Page('types', $types, static function() use ($parameters, $typeTable)
		{
			return $typeTable::getCount($parameters['filter'] ?? []);
		});
	}

	public function addAction(array $fields): ?array
	{
		$builder = (new Integration\Analytics\Builder\Automation\Type\CreateEvent())
			->setSection(Integration\Analytics\Dictionary::SECTION_REST)
		;

		if ($this->isRest())
		{
			$builder
				->setStatus(Integration\Analytics\Dictionary::STATUS_ATTEMPT)
				->buildEvent()
				->send()
			;
		}

		$result = $this->add($fields);

		if ($this->isRest())
		{
			if (isset($result['type']['id']))
			{
				$builder->setId($result['type']['id']);
			}

			if ($this->getErrors())
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_ERROR);
			}
			else
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_SUCCESS);
			}

			$builder
				->buildEvent()
				->send()
			;
		}

		return $result;
	}

	private function add(array $fields): ?array
	{
		$entityTypeId = $fields['entityTypeId'] ?? 0;
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		$automatedSolutionId = $fields['customSectionId'] ?? 0;
		$hasPermissions = $automatedSolutionId ? $userPermissions->isAutomatedSolutionAdmin($automatedSolutionId) : $userPermissions->isCrmAdmin();
		if (!$hasPermissions)
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}
		$dataClass = Container::getInstance()->getDynamicTypeDataClass();
		$fields['name'] = $dataClass::generateName($fields['title']);
		if (
			!empty($entityTypeId)
			&& in_array((int)$entityTypeId, \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds(), true)
		)
		{
			$this->addError(new Error('entityTypeId is out of allowed range', ErrorCode::INVALID_ARG_VALUE));

			return null;
		}

		$type = $dataClass::createObject();

		return $this->update($type, $fields);
	}

	public function updateAction(?Dynamic\Type $type = null, array $fields = []): ?array
	{
		$builder = (new Integration\Analytics\Builder\Automation\Type\EditEvent())
			->setSection(Integration\Analytics\Dictionary::SECTION_REST)
		;
		if ($type)
		{
			$builder->setId($type->getId());
		}

		if ($this->isRest())
		{
			$builder
				->setStatus(Integration\Analytics\Dictionary::STATUS_ATTEMPT)
				->buildEvent()
				->send()
			;
		}

		$result = $this->update($type, $fields);

		if ($this->isRest())
		{
			if ($this->getErrors())
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_ERROR);
			}
			else
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_SUCCESS);
			}

			$builder
				->buildEvent()
				->send()
			;
		}

		return $result;
	}

	private function update(?Dynamic\Type $type = null, array $fields = []): ?array
	{
		if($type === null)
		{
			return null;
		}
		$isNew = ($type->getId() <= 0);

		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		$automatedSolutionId = (int)($fields['customSectionId'] ?? 0);
		if ($isNew)
		{
			$hasPermissions = $automatedSolutionId ? $userPermissions->isAutomatedSolutionAdmin($automatedSolutionId) : $userPermissions->isCrmAdmin();
		}
		else
		{
			$hasPermissions = $userPermissions->isAdminForEntity($type->getEntityTypeId());
		}

		if (!$hasPermissions)
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}
		$originalFields = $fields;
		$fields = $this->convertKeysToUpper($fields);
		$fieldKeysToUnset = ['ID', 'IS_EXTERNAL', 'CREATED_TIME', 'CREATED_BY', 'UPDATED_TIME', 'UPDATED_BY'];
		$fieldKeysToNotSetToType = ['CUSTOM_SECTION_ID'];

		$restriction = RestrictionManager::getDynamicTypesLimitRestriction();
		if ($isNew && $restriction->isCreateTypeRestricted())
		{
			$this->addError($restriction->getCreateTypeRestrictedError());
			return null;
		}

		if (!$isNew && $restriction->isTypeSettingsRestricted($type->getEntityTypeId()))
		{
			$this->addError($restriction->getUpdateTypeRestrictedError());
			return null;
		}

		// if try to change CustomSectionId in existed smart process, user must be both admins in old and new automated solutions
		if (
			!$isNew
			&& (int)$type->getCustomSectionId() !== (int)$automatedSolutionId
			&& (
				(!$type->getCustomSectionId() && !$userPermissions->isCrmAdmin())
				||
				(!$type->getCustomSectionId() && !$userPermissions->isAutomatedSolutionAdmin($automatedSolutionId))
			)
		)
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		$isExternal = isset($fields['IS_EXTERNAL']) && $fields['IS_EXTERNAL'] === 'true';
		$isCustomSectionSelected = isset($fields['CUSTOM_SECTION_ID']) && $fields['CUSTOM_SECTION_ID'] !== '0';
		if ($isExternal && $isNew && !$isCustomSectionSelected)
		{
			$this->addError(
				new Error(Loc::getMessage('CRM_CONTROLLER_TYPE_EXTERNAL_TYPE_WITHOUT_CUSTOM_SECTION_ERROR')),
			);

			return null;
		}

		if (isset($fields['TITLE']))
		{
			$fields['TITLE'] = trim($fields['TITLE']);
		}

		if (!$isNew)
		{
			$fieldKeysToUnset = array_merge(['ENTITY_TYPE_ID', 'NAME'], $fieldKeysToUnset);
		}

		foreach ($fieldKeysToUnset as $fieldKeyToUnset)
		{
			if (isset($fields[$fieldKeyToUnset]))
			{
				unset($fields[$fieldKeyToUnset]);
			}
		}

		foreach($fields as $name => $value)
		{
			if($type->entity->hasField($name) && !in_array($name, $fieldKeysToNotSetToType, true))
			{
				$type->set($name, $value);
			}
		}

		$result = $type->save();
		if($result->isSuccess())
		{
			$this->saveConversionMap($type->getEntityTypeId(), $fields);
			if ($type->getIsUseInUserfieldEnabled())
			{
				$this->saveLinkedUserFields(\CCrmOwnerType::ResolveName($type->getEntityTypeId()), $originalFields);
			}
			$relationsResult = $this->saveRelations($type->getEntityTypeId(), $fields);
			if (!$relationsResult->isSuccess())
			{
				$this->addErrors($relationsResult->getErrors());
			}

			$typeFromContainer = Container::getInstance()->getDynamicTypesMap()->getTypesCollection()->getByPrimary($type->getId());  // to avoid inconsistent with data from \Bitrix\Crm\Service\Container::getTypeByEntityTypeId
			$customSectionsResult = $this->saveCustomSections($typeFromContainer, $fields);
			if (!$customSectionsResult->isSuccess())
			{
				$this->addErrors($customSectionsResult->getErrors());
			}
			$type->setCustomSectionId($typeFromContainer->getCustomSectionId());
			Container::getInstance()->getTypeByEntityTypeId($type->getEntityTypeId())?->setCustomSectionId($typeFromContainer->getCustomSectionId()); // update cached object

			$result = $this->getAction($type);
			if (is_array($result) && ($this->getScope() === static::SCOPE_AJAX))
			{
				$result['urlTemplates'] = Container::getInstance()->getRouter()->getTemplatesForJsRouter();
				$result['isUrlChanged'] = $customSectionsResult->getData()['isCustomSectionChanged'] ?? false;
			}

			return $result;
		}

		$this->addErrors($result->getErrors());
		return null;
	}

	public function deleteAction(?Dynamic\Type $type = null): ?array
	{
		$builder = (new Integration\Analytics\Builder\Automation\Type\DeleteEvent())
			->setSection(Integration\Analytics\Dictionary::SECTION_REST)
		;
		if ($type)
		{
			$builder->setId($type->getId());
		}

		if ($this->isRest())
		{
			$builder
				->setStatus(Integration\Analytics\Dictionary::STATUS_ATTEMPT)
				->buildEvent()
				->send()
			;
		}

		$result = $this->delete($type);

		if ($this->isRest())
		{
			if ($this->getErrors())
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_ERROR);
			}
			else
			{
				$builder->setStatus(Integration\Analytics\Dictionary::STATUS_SUCCESS);
			}

			$builder
				->buildEvent()
				->send()
			;
		}

		return $result;
	}

	private function delete(?Dynamic\Type $type = null): ?array
	{
		if($type === null)
		{
			return null;
		}
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());
		if (!$userPermissions->isAdminForEntity($type->getEntityTypeId()) && !$userPermissions->isCrmAdmin())
		{
			$this->addError(ErrorCode::getAccessDeniedError());
			return null;
		}

		$customSection = Integration\IntranetManager::getCustomSectionByEntityTypeId($type->getEntityTypeId());

		$deleteResult = $type->delete();
		if(!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());
			return null;
		}

		$result = [];
		if ($this->getScope() === static::SCOPE_AJAX)
		{
			$result['isUrlChanged'] = !is_null($customSection);
		}

		return $result;
	}

	protected function saveConversionMap(int $entityTypeId, array $fields): void
	{
		// $conversionMap = $fields['CONVERSION_MAP'] ?? null;
		// if (!is_array($conversionMap))
		// {
		// 	return;
		// }
		//
		// if (array_key_exists('sourceTypes', $conversionMap))
		// {
		// 	$sourceTypes = $this->normalizeTypes((array)$conversionMap['sourceTypes']);
		// 	\Bitrix\Crm\Conversion\ConversionManager::setSourceTypes($entityTypeId, $sourceTypes);
		// }
		//
		// if (array_key_exists('destinationTypes', $conversionMap))
		// {
		// 	$destinationTypes = $this->normalizeTypes((array)$conversionMap['destinationTypes']);
		// 	\Bitrix\Crm\Conversion\ConversionManager::setDestinationTypes($entityTypeId, $destinationTypes);
		// }
	}

	protected function saveLinkedUserFields(string $entityTypeName, array $fields): void
	{
		$settings = $fields['linkedUserFields'] ?? null;
		if (!is_array($settings))
		{
			return;
		}

		$userFieldsMap = UserFieldManager::getLinkedUserFieldsMap();

		foreach ($settings as $name => $isEnabled)
		{
			if (isset($userFieldsMap[$name]))
			{
				UserFieldManager::enableEntityInUserField(
					$userFieldsMap[$name],
					$entityTypeName,
					$isEnabled === 'true'
				);
			}
		}
	}

	protected function saveRelations(int $entityTypeId, array $fields): Result
	{
		$result = new Result();
		$relations = $fields['RELATIONS'] ?? null;
		if (!is_array($relations))
		{
			return $result;
		}
		$relationManager = Container::getInstance()->getRelationManager();
		if (array_key_exists('PARENT', $relations))
		{
			$availableForBindingEntityTypes = $relationManager->getAvailableForParentBindingEntityTypes($entityTypeId);
			$selectedParentTypes = $this->prepareRelationsData((array)$relations['PARENT']);

			$relationsCollection = $relationManager->getRelations($entityTypeId);
			foreach ($availableForBindingEntityTypes as $availableTypeId => $description)
			{
				$typeResult = $this->processRelation(
					$relationsCollection,
					new RelationIdentifier($availableTypeId, $entityTypeId),
					$selectedParentTypes[$availableTypeId] ?? null
				);
				if (!$typeResult->isSuccess())
				{
					$result->addErrors($typeResult->getErrors());
				}
			}
		}

		if (array_key_exists('CHILD', $relations))
		{
			$availableForBindingEntityTypes = $relationManager->getAvailableForChildBindingEntityTypes($entityTypeId);
			$selectedChildTypes = $this->prepareRelationsData((array)$relations['CHILD']);

			$relationsCollection = $relationManager->getRelations($entityTypeId);
			foreach ($availableForBindingEntityTypes as $availableTypeId => $description)
			{
				$typeResult = $this->processRelation(
					$relationsCollection,
					new RelationIdentifier($entityTypeId, $availableTypeId),
					$selectedChildTypes[$availableTypeId] ?? null
				);
				if (!$typeResult->isSuccess())
				{
					$result->addErrors($typeResult->getErrors());
				}
			}
		}

		return $result;
	}

	protected function prepareRelationsData(array $relations): array
	{
		$result = [];

		foreach ($relations as $relationData)
		{
			if (!isset($relationData['ENTITY_TYPE_ID']))
			{
				continue;
			}
			$entityTypeId = (int)$relationData['ENTITY_TYPE_ID'];
			if ($entityTypeId > 0)
			{
				$result[$entityTypeId] = [
					'entityTypeId' => $entityTypeId,
					'isChildrenListEnabled' => $relationData['IS_CHILDREN_LIST_ENABLED'] === 'true',
				];
			}
		}

		return $result;
	}

	/**
	 * Processes data about relation.
	 * If there is data
	 * - if relation exists - update it
	 * - if no relation - create it
	 * If there is not data
	 * - if relation exists - remove it
	 *
	 * @param Collection $relations
	 * @param RelationIdentifier $identifier
	 * @param array|null $relationData
	 *
	 * @return Result
	 */
	protected function processRelation(
		Relation\Collection $relations,
		RelationIdentifier $identifier,
		?array $relationData
	): Result
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$relation = $relations->get($identifier);
		if ($relationData)
		{
			if ($relation)
			{
				if ($relation->isChildrenListEnabled() !== $relationData['isChildrenListEnabled'])
				{
					$relation->setChildrenListEnabled($relationData['isChildrenListEnabled']);
					return $relationManager->updateTypesBinding($relation);
				}
			}
			else
			{
				$settings = (new Relation\Settings())
					->setIsChildrenListEnabled($relationData['isChildrenListEnabled']);
				return $relationManager->bindTypes(
					new Relation(
						$identifier,
						$settings,
					)
				);
			}
		}
		elseif ($relation)
		{
			return $relationManager->unbindTypes($relation->getIdentifier());
		}

		return new Result();
	}

	/**
	 * Process custom sections.
	 * - delete existing sections that do not present in query
	 * - update existing sections
	 * - add new sections
	 * - if page exists and section is another - update record
	 * - if page exists and there is not sectionId - delete record
	 * - if page does not exist - add record.
	 */
	protected function saveCustomSections(Dynamic\Type $type, array $fields): Result
	{
		return Container::getInstance()->getAutomatedSolutionManager()->setAutomatedSolutions($type, $fields);
	}
}
