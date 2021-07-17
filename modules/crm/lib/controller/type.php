<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Relation;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\UserField\UserFieldManager;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionTable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;

class Type extends Base
{
	public function getDefaultPreFilters(): array
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new class extends ActionFilter\Base {
			public function onBeforeAction(Event $event): ?EventResult
			{
				if ($this->getAction()->getController()->getScope() === Controller::SCOPE_REST)
				{
					Container::getInstance()->getContext()->setScope(Context::SCOPE_REST);
				}
				$userPermissions = Container::getInstance()->getUserPermissions();
				if (!$userPermissions->canWriteConfig())
				{
					$this->addError(new Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED')));
				}

				return new EventResult(
					$this->errorCollection->isEmpty() ? EventResult::SUCCESS : EventResult::ERROR,
					null,
					null,
					$this
				);
			}
		};

		return $preFilters;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getAutoWiredParameters(): array
	{
		$params = parent::getAutoWiredParameters();

		$params[] = new ExactParameter(
			\Bitrix\Crm\Model\Dynamic\Type::class,
			'type',
			static function($className, $id)
			{
				return Container::getInstance()->getType((int)$id);
			}
		);

		$params[] = new ExactParameter(
			\Bitrix\Crm\Model\Dynamic\Type::class,
			'type',
			static function($className, $entityTypeId)
			{
				return Container::getInstance()->getTypeByEntityTypeId((int)$entityTypeId);
			}
		);

		return $params;
	}

	public function getAction(\Bitrix\Crm\Model\Dynamic\Type $type): ?array
	{
		return [
			'type' => $type->jsonSerialize(),
		];
	}

	public function listAction(array $select = ['*'], array $order = null, array $filter = null, PageNavigation $pageNavigation = null): ?Page
	{
		if(is_array($filter))
		{
			$filter = $this->removeDotsFromKeys($this->convertKeysToUpper($filter));
		}
		if(is_array($order))
		{
			$order = $this->convertKeysToUpper($order);
		}
		if(is_array($select))
		{
			$select = $this->removeDotsFromValues($this->convertValuesToUpper($select));
		}

		$types = [];
		$typeTable = Container::getInstance()->getDynamicTypeDataClass();
		$list = $typeTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'offset' => $pageNavigation ? $pageNavigation->getOffset() : null,
			'limit' => $pageNavigation ? $pageNavigation->getLimit(): null,
		]);
		/** @var \Bitrix\Crm\Model\Dynamic\Type $type */
		while($type = $list->fetchObject())
		{
			$types[] = $type->jsonSerialize();
		}

		return new Page('types', $types, static function() use ($filter, $typeTable)
		{
			return $typeTable::getCount($filter);
		});
	}

	public function addAction(array $fields): ?array
	{
		$dataClass = Container::getInstance()->getDynamicTypeDataClass();
		$fields['name'] = $dataClass::generateName($fields['title']);
		$type = $dataClass::createObject();

		return $this->updateAction($type, $fields);
	}

	public function updateAction(\Bitrix\Crm\Model\Dynamic\Type $type, array $fields): ?array
	{
		$originalFields = $fields;
		$fields = $this->convertKeysToUpper($fields);
		$isNew = $type->getId() <= 0;
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
		unset($fields['ID']);
		$fields['TITLE'] = trim($fields['TITLE']);
		if(!$isNew)
		{
			unset($fields['ENTITY_TYPE_ID']);
		}
		foreach($fields as $name => $value)
		{
			if($type->entity->hasField($name))
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

			$customSectionsResult = $this->saveCustomSections($type, $fields);
			if (!$customSectionsResult->isSuccess())
			{
				$this->addErrors($customSectionsResult->getErrors());
			}

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

	public function deleteAction(?\Bitrix\Crm\Model\Dynamic\Type $type): ?array
	{
		if($type === null)
		{
			$this->addError(new Error(Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND')));
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
		$conversionMap = $fields['CONVERSION_MAP'] ?? null;
		if (!is_array($conversionMap))
		{
			return;
		}

		if (array_key_exists('sourceTypes', $conversionMap))
		{
			$sourceTypes = $this->normalizeTypes((array)$conversionMap['sourceTypes']);
			ConversionManager::setSourceTypes($entityTypeId, $sourceTypes);
		}

		if (array_key_exists('destinationTypes', $conversionMap))
		{
			$destinationTypes = $this->normalizeTypes((array)$conversionMap['destinationTypes']);
			ConversionManager::setDestinationTypes($entityTypeId, $destinationTypes);
		}
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

	protected function normalizeTypes(array $types): array
	{
		$arrayOfIntegers = array_map('intval', $types);
		return array_filter($arrayOfIntegers);
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

			foreach ($availableForBindingEntityTypes as $availableTypeId => $description)
			{
				$typeResult = $this->processRelation(
					$relationManager,
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
			foreach ($availableForBindingEntityTypes as $availableTypeId => $description)
			{
				$typeResult = $this->processRelation(
					$relationManager,
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
	 * @param Relation\RelationManager $relationManager
	 * @param RelationIdentifier $identifier
	 * @param array|null $relationData
	 * @return Result
	 */
	protected function processRelation(
		Relation\RelationManager $relationManager,
		RelationIdentifier $identifier,
		?array $relationData
	): Result
	{
		$relation = $relationManager->getRelation($identifier);
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
				return $relationManager->bindTypes(
					Relation::create(
						$identifier->getParentEntityTypeId(),
						$identifier->getChildEntityTypeId(),
						$relationData['isChildrenListEnabled']
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
	 *
	 * @param \Bitrix\Crm\Model\Dynamic\Type $type
	 * @param array $fields
	 * @return Result
	 * @todo refactor it!
	 */
	protected function saveCustomSections(\Bitrix\Crm\Model\Dynamic\Type $type, array $fields): Result
	{
		$result = new Result();
		$result->setData(['isCustomSectionChanged' => false]);

		if (!Integration\IntranetManager::isCustomSectionsAvailable())
		{
			return $result;
		}
		$customSectionsArrays = $fields['CUSTOM_SECTIONS'] ?? null;
		$settings = Integration\IntranetManager::preparePageSettingsForItemsList($type->getEntityTypeId());
		if ($customSectionsArrays === null)
		{
			if (array_key_exists('CUSTOM_SECTION_ID', $fields) && (int)$fields['CUSTOM_SECTION_ID'] === 0)
			{
				$pagesList = CustomSectionPageTable::getList([
					'select' => ['ID'],
					'filter' => [
						'=MODULE_ID' => 'crm',
						'=SETTINGS' => $settings,
					],
				]);
				/** @var array $pageRow */
				while ($pageRow = $pagesList->fetch())
				{
					CustomSectionPageTable::delete($pageRow['ID']);
					$result->setData(['isCustomSectionChanged' => true]);
				}
			}
			return $result;
		}
		if (!is_array($customSectionsArrays))
		{
			$customSectionsArrays = [];
		}
		$customSectionId = $fields['CUSTOM_SECTION_ID'] ?? 0;
		$realCustomSectionId = null;
		if (!empty($customSectionId) && mb_strpos($customSectionId, 'new') !== 0)
		{
			$customSectionId = (int)$customSectionId;
			$realCustomSectionId = $customSectionId;
		}
		$existingPageId = null;

		$customSections = [];
		foreach ($customSectionsArrays as $customSectionsArray)
		{
			$customSections[$customSectionsArray['ID']] = CustomSection\Assembler::constructCustomSection($customSectionsArray);
		}

		$existingCustomSections = $this->moveIdToKey(Integration\IntranetManager::getCustomSections() ?? []);

		foreach ($existingCustomSections as $id => $section)
		{
			if (!isset($customSections[$id]))
			{
				$deleteResult = CustomSectionTable::delete($id);
				if (!$deleteResult->isSuccess())
				{
					$result->addErrors($deleteResult->getErrors());
				}
			}
		}

		foreach ($customSections as $id => $section)
		{
			if (isset($existingCustomSections[$id]))
			{
				if (!empty($section->getTitle()) && ($section->getTitle() !== $existingCustomSections[$id]->getTitle()))
				{
					$updateResult = CustomSectionTable::update($id, [
						'TITLE' => $section->getTitle(),
					]);
					if (!$updateResult->isSuccess())
					{
						$result->addErrors($updateResult->getErrors());
					}
				}
				foreach ($existingCustomSections[$id]->getPages() as $page)
				{
					if ($page->getSettings() === $settings)
					{
						$existingPageId = $page->getId();
						break;
					}
				}
			}
			elseif (!empty($section->getTitle()))
			{
				$addResult = CustomSectionTable::add([
					'TITLE' => $section->getTitle(),
					'MODULE_ID' => 'crm',
				]);
				if (!$addResult->isSuccess())
				{
					$result->addErrors($addResult->getErrors());
				}
				elseif ($id === $customSectionId)
				{
					$realCustomSectionId = $addResult->getId();
				}
			}
		}

		$isCustomSectionChanged = false;
		if ($customSectionId !== null && $realCustomSectionId > 0)
		{
			$isCustomSectionChanged = true;
			if ($existingPageId > 0)
			{
				$updatePageResult = CustomSectionPageTable::update($existingPageId, [
					'CUSTOM_SECTION_ID' => $realCustomSectionId,
					'TITLE' => $type->getTitle(),
					// empty string to provoke CODE regeneration
					'CODE' => '',
				]);
				if (!$updatePageResult)
				{
					$result->addErrors($updatePageResult->getErrors());
				}
			}
			else
			{
				$addPageResult = CustomSectionPageTable::add([
					'TITLE' => $type->getTitle(),
					'MODULE_ID' => 'crm',
					'CUSTOM_SECTION_ID' => $realCustomSectionId,
					'SETTINGS' => $settings,
					'SORT' => 100,
				]);
				if (!$addPageResult->isSuccess())
				{
					$result->addErrors($addPageResult->getErrors());
				}
			}
		}
		elseif ($existingPageId > 0)
		{
			$isCustomSectionChanged = true;
			$deletePageResult = CustomSectionPageTable::delete($existingPageId);
			if (!$deletePageResult->isSuccess())
			{
				$result->addErrors($deletePageResult->getErrors());
			}
		}

		if ($result->isSuccess())
		{
			Container::getInstance()->getRouter()->reInit();
		}

		$result->setData(['isCustomSectionChanged' => $isCustomSectionChanged]);

		return $result;
	}

	/**
	 * @param CustomSection[] $array
	 *
	 * @return CustomSection[]
	 */
	protected function moveIdToKey(array $array): array
	{
		$result = [];
		foreach ($array as $customSection)
		{
			$result[$customSection->getId()] = $customSection;
		}

		return $result;
	}
}
