<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Field;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Component\ParameterSigner;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\FileInputUtility;
use Bitrix\Main\UI\PageNavigation;

class Item extends Base
{
	/** @var Factory */
	protected $factory;

	public function setFactory(Factory $factory): self
	{
		$this->factory = $factory;

		return $this;
	}

	public function configureActions(): array
	{
		$configureActions = parent::configureActions();
		$configureActions['getFile'] = [
			'-prefilters' => [
				Csrf::class,
				Filter\Factory::class,
			],
		];

		return $configureActions;
	}

	/**
	 * @inheritDoc
	 */
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

				return new EventResult(
					$this->errorCollection->isEmpty() ? EventResult::SUCCESS : EventResult::ERROR,
					null,
					null,
					$this
				);
			}
		};

		$preFilters[] = new Filter\Factory();

		return $preFilters;
	}

	/**
	 * Return data about item by $id.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function getAction(int $id): ?array
	{
		$item = $this->factory->getItem($id);
		if (!$item)
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
				static::ERROR_CODE_NOT_FOUND
			));
			return null;
		}
		if (!Container::getInstance()->getUserPermissions()->canReadItem($item))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		return [
			'item' => $item,
		];
	}

	/**
	 * Return list of items.
	 *
	 * @param array|null $order
	 * @param array|null $filter
	 * @param PageNavigation|null $pageNavigation
	 * @return Page|null
	 */
	public function listAction(
		array $order = null,
		array $filter = null,
		PageNavigation $pageNavigation = null
	): ?Page
	{
		$parameters = [];
		$parameters['filter'] = $this->convertKeysToUpper((array)$filter);
		$parameters['filter'] = $this->prepareFilter($parameters['filter']);
		if(is_array($order))
		{
			$parameters['order'] = $this->convertKeysToUpper($order);
		}

		if($pageNavigation)
		{
			$parameters['offset'] = $pageNavigation->getOffset();
			$parameters['limit'] = $pageNavigation->getLimit();
		}

		$items = $this->factory->getItemsFilteredByPermissions($parameters);

		return new Page(
			'items',
			$items,
			function() use($parameters) {
				return $this->factory->getItemsCountFilteredByPermissions($parameters['filter']);
			}
		);
	}

	/**
	 * Delete item by $id.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function deleteAction(int $id): ?array
	{
		$item = $this->factory->getItem($id);
		if (!$item)
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
				static::ERROR_CODE_NOT_FOUND
			));
			return null;
		}
		if (!Container::getInstance()->getUserPermissions()->canDeleteItem($item))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		$categoryId = $item->getCategoryId();

		$operation = $this->factory->getDeleteOperation($item);
		$result = $operation->launch();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		if ($this->getScope() === static::SCOPE_AJAX)
		{
			return [
				'redirectUrl' => Container::getInstance()->getRouter()
					->getItemListUrlInCurrentView($this->factory->getEntityTypeId(), $categoryId),
			];
		}

		return [];
	}

	/**
	 * Process $fields for $item by fields $collection. This method do not perform saving, only set values in $item.
	 *
	 * @param \Bitrix\Crm\Item $item
	 * @param array $fields
	 * @param Field\Collection $collection
	 */
	public function processFields(
		\Bitrix\Crm\Item $item,
		array $fields,
		Field\Collection $collection
	): void
	{
		foreach($collection as $field)
		{
			$fieldName = $field->getName();
			if (!array_key_exists($fieldName, $fields))
			{
				continue;
			}

			if (empty($fields[$fieldName]))
			{
				$item->set($fieldName, null);
				continue;
			}

			if ($field->isFileUserField())
			{
				$this->processFileField($field, $item, $fields[$fieldName]);
			}
			elseif (
				$this->getScope() === self::SCOPE_REST
				&& (
					$field->getType() === Field::TYPE_DATE
					|| $field->getType() === Field::TYPE_DATETIME
				)
			)
			{
				if ($field->getType() === Field::TYPE_DATETIME)
				{
					$convertDateMethod = 'unConvertDateTime';
				}
				else
				{
					$convertDateMethod = 'unConvertDate';
				}
				if($field->isMultiple())
				{
					$result = [];
					$value = (array)$fields[$fieldName];
					foreach($value as $date)
					{
						$result[] = \CRestUtil::$convertDateMethod($date);
					}
					$item->set($fieldName, $result);
				}
				else
				{
					$item->set($fieldName, \CRestUtil::$convertDateMethod($fields[$fieldName]));
				}
			}
			else
			{
				$value = $fields[$fieldName];
				if ($field->getType() === Field::TYPE_BOOLEAN)
				{
					$value = $this->prepareBooleanFieldValue($value);
				}
				$item->set($fieldName, $value);
			}
		}
	}

	public function processFileField(Field $field, \Bitrix\Crm\Item $item, $fileData): void
	{
		$fieldName = $field->getName();
		if ($field->isMultiple())
		{
			$fileData = (array)$fileData;

			$result = [];
			$currentFiles = array_flip($item->get($fieldName) ?? []);
			foreach ($fileData as $file)
			{
				$fileId = (int)$file['ID'];
				if ($fileId > 0)
				{
					if (isset($currentFiles[$fileId]))
					{
						$result[] = $fileId;
					}

					continue;
				}

				$fileId = $this->uploadFile($field, $file);
				if ($fileId > 0)
				{
					$result[] = $fileId;
				}
			}

			$item->set($fieldName, $result);
		}
		else
		{
			if (isset($fileData['ID']))
			{
				if ((int)$fileData['ID'] === $item->get($fieldName))
				{
					return;
				}

				$fileId = 0;
			}
			else
			{
				$fileId = $this->uploadFile($field, $fileData);
			}
			$item->set($fieldName, $fileId);
		}
	}

	protected function prepareBooleanFieldValue($value): bool
	{
		if (is_bool($value))
		{
			return $value;
		}
		if ($value === 'true' || $value === 'y' || $value === 'Y')
		{
			return true;
		}

		return false;
	}

	/**
	 * Add new item with $fields.
	 *
	 * @param array $fields
	 * @return array|null
	 */
	public function addAction(array $fields): ?array
	{
		$item = $this->factory->createItem();

		$fields = $this->convertKeysToUpper($fields);
		$this->processFields($item, $fields, $this->factory->getFieldsCollection());

		if (!Container::getInstance()->getUserPermissions()->canAddItem($item))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		$operation = $this->factory->getAddOperation($item);
		$result = $operation->launch();
		if ($result->isSuccess())
		{
			$item = $operation->getItem();

			return [
				'item' => $item,
			];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	/**
	 * Update item by $id with $fields.
	 *
	 * @param int $id
	 * @param array $fields
	 * @return array|null
	 */
	public function updateAction(int $id, array $fields): ?array
	{
		$item = $this->factory->getItem($id);
		if (!$item)
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
				static::ERROR_CODE_NOT_FOUND
			));
			return null;
		}
		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($item))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		$fields = $this->convertKeysToUpper($fields);
		$this->processFields($item, $fields, $this->factory->getFieldsCollection());
		$operation = $this->factory->getUpdateOperation($item);
		$result = $operation->launch();
		if ($result->isSuccess())
		{
			$item = $operation->getItem();

			return [
				'item' => $item,
			];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	/**
	 * Return editor for item by $id.
	 *
	 * @param int $id
	 * @param string|null $guid
	 * @param string|null $configId
	 * @param int|null $categoryId
	 * @param string|null $stageId
	 * @param array $params
	 * @return Component|null
	 */
	public function getEditorAction(
		int $id,
		string $guid = null,
		string $configId = null,
		int $categoryId = null,
		string $stageId = null,
		array $params = []
	): ?Component
	{
		$entityTypeId = $this->factory->getEntityTypeId();
		$componentName = $params['componentName']
			?? Container::getInstance()->getRouter()->getItemDetailComponentName($entityTypeId);
		if (!$componentName)
		{
			$this->addError(new Error('Component for entity ' . $entityTypeId . ' not found'));
			return null;
		}

		$componentClassName = \CBitrixComponent::includeComponentClass($componentName);
		$component = new $componentClassName;
		if (!($component instanceof FactoryBased))
		{
			$this->addError(new Error('Component for entity ' . $entityTypeId . ' not found'));
			return null;
		}
		$component->initComponent($componentName);
		$component->arParams = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $id,
			'categoryId' => $categoryId,
		];

		$component->init();
		if (!empty($component->getErrors()))
		{
			$this->addErrors($component->getErrors());
			return null;
		}

		$component->initializeEditorAdapter();

		$editorConfig = $component->getEditorConfig();
		$editorConfig['ENTITY_CONFIG'] = $component->getInlineEditorEntityConfig();

		if ($stageId)
		{
			$editorConfig['CONTEXT']['STAGE_ID'] = $stageId;
		}

		$forceDefaultConfig = $params['forceDefaultConfig'] ?? 'N';
		$editorConfig['FORCE_DEFAULT_CONFIG'] = ($forceDefaultConfig === 'Y');
		$editorConfig['IS_EMBEDDED'] = true;
		$editorConfig['GUID'] = $guid ?? $editorConfig['GUID'];
		$editorConfig['CONFIG_ID'] = $configId ?? $editorConfig['CONFIG_ID'];

		$editorConfig['COMPONENT_AJAX_DATA']['SIGNED_PARAMETERS'] = ParameterSigner::signParameters(
			$component->getName(),
			$component->arParams
		);

		$disabledOptions = [
			'ENABLE_SECTION_EDIT',
			'ENABLE_SECTION_CREATION',
			'ENABLE_SECTION_DRAG_DROP',
			'ENABLE_FIELD_DRAG_DROP',
			'ENABLE_MODE_TOGGLE',
			'ENABLE_TOOL_PANEL',
			'ENABLE_BOTTOM_PANEL',
			'ENABLE_USER_FIELD_CREATION',
			'ENABLE_PAGE_TITLE_CONTROLS',
			'ENABLE_FIELDS_CONTEXT_MENU',
			'ENABLE_PERSONAL_CONFIGURATION_UPDATE',
			'ENABLE_COMMON_CONFIGURATION_UPDATE',
			'ENABLE_CONFIG_SCOPE_TOGGLE',
			'ENABLE_SETTINGS_FOR_ALL',
			'ENABLE_REQUIRED_FIELDS_INJECTION',
		];
		foreach ($disabledOptions as $option)
		{
			$editorConfig[$option] = $params[$option] ?? false;
		}

		$editorConfig['ENABLE_USER_FIELD_MANDATORY_CONTROL'] = true;
		$editorConfig['ENABLE_AJAX_FORM'] = true;
		$editorConfig['INITIAL_MODE'] = 'edit';

		$requiredFields = array_unique($params['requiredFields'] ?? []);
		$entityConfig = $editorConfig['ENTITY_CONFIG'];
		if (!empty($requiredFields))
		{
			$entityConfig = [
				[
					'elements' => [],
				],
			];

			foreach ($requiredFields as $field)
			{
				$entityConfig[0]['elements'][] = ['name' => $field];
			}

			$editorConfig['ENTITY_FIELDS'] = EditorAdapter::markFieldsAsRequired($editorConfig['ENTITY_FIELDS'], $requiredFields);
		}

		$editorConfig['ENTITY_CONFIG'] = EditorAdapter::combineConfigIntoOneSection($entityConfig, $params['title'] ?? '');

		return new Component('bitrix:crm.entity.editor', '', $editorConfig);
	}

	/**
	 * Prepare filter for getList.
	 *
	 * @param array $filter
	 * @return array
	 */
	public function prepareFilter(array $filter): array
	{
		if($this->getScope() === static::SCOPE_REST)
		{
			$this->prepareDateTimeFieldsForFilter($filter, $this->factory->getFieldsCollection());
		}

		$filter = $this->removeDotsFromKeys($filter);

		return $filter;
	}

	/**
	 * Return information about fields.
	 *
	 * @return array|null
	 */
	public function fieldsAction(): ?array
	{
		if (!Container::getInstance()->getUserPermissions()->checkReadPermissions($this->factory->getEntityTypeId()))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		$fieldsInfo = $this->factory->getFieldsInfo() + $this->factory->getUserFieldsInfo();

		foreach ($fieldsInfo as &$fieldInfo)
		{
			$fieldInfo['CAPTION'] = $fieldInfo['TITLE'] ?? null;
		}
		unset($fieldInfo);

		$fieldsInfo = \CCrmRestHelper::prepareFieldInfos($fieldsInfo);

		return [
			'fields' => $this->convertKeysToCamelCase($fieldsInfo),
		];
	}

	/**
	 * Return file content of item with $id by $fieldName and $file_id.
	 *
	 * @param int $id
	 * @param string $fieldName
	 * @param int $fileId
	 * @return BFile|null
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function getFileAction(int $id, string $fieldName, int $fileId, int $entityTypeId = null): ?BFile
	{
		if (!$entityTypeId)
		{
			$entityTypeId = (int)$this->getRequest()->get('entityTypeId');
		}

		$this->factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$this->factory)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_TYPE_TYPE_NOT_FOUND'),
					\Bitrix\Crm\Controller\Base::ERROR_CODE_NOT_FOUND)
			);
			return null;
		}

		$item = $this->factory->getItem($id);
		if (!$item)
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_TYPE_ITEM_NOT_FOUND'),
				static::ERROR_CODE_NOT_FOUND
			));
			return null;
		}
		if (!Container::getInstance()->getUserPermissions()->canReadItem($item))
		{
			$this->addError(new Error(
				Loc::getMessage('CRM_COMMON_READ_ACCESS_DENIED'),
				static::ERROR_CODE_ACCESS_DENIED
			));
			return null;
		}

		$field = $this->factory->getFieldsCollection()->getField($fieldName);
		if (!$field || !$field->isFileUserField())
		{
			$this->addError(new Error('Field ' . $fieldName . ' is not a file field'));
			return null;
		}
		$value = $item->get($fieldName);
		if(
			($value === $fileId)
			||
			(is_array($value) && in_array($fileId, $value))
		)
		{
			return BFile::createByFileId($fileId);
		}

		return null;
	}
}
