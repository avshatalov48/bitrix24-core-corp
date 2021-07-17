<?php

namespace Bitrix\Rpa\Controller;

use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\FileInputUtility;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UserField\Types\DateTimeType;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Filter\Item\Provider;
use Bitrix\Rpa\Filter\Item\Settings;
use Bitrix\Rpa\Model\ItemSortTable;
use Bitrix\Rpa\UserField\Display;
use Bitrix\Rpa\UserField\UserField;

class Item extends Base
{
	protected $uploadedFiles = [];

	public function configureActions(): array
	{
		$configureActions = parent::configureActions();
		$configureActions['getFile'] = [
			'-prefilters' => [
				Csrf::class,
			],
		];

		return $configureActions;
	}

	protected function processFields(\Bitrix\Rpa\Model\Item $item, array $fields): void
	{
		$userFieldCollection = $item->getType()->getUserFieldCollection();
		foreach($userFieldCollection as $userField)
		{
			if(!isset($fields[$userField->getName()]))
			{
				continue;
			}

			if(empty($fields[$userField->getName()]))
			{
				$item->set($userField->getName(), null);
				continue;
			}

			if($userField->isBaseTypeFile())
			{
				$this->processFileField($userField, $item, $fields);
			}
			elseif($this->getScope() === self::SCOPE_REST && $userField->isBaseTypeDate())
			{
				if($userField->getUserTypeId() === DateTimeType::USER_TYPE_ID)
				{
					$convertDateMethod = 'unConvertDateTime';
				}
				else
				{
					$convertDateMethod = 'unConvertDate';
				}
				if($userField->isMultiple())
				{
					$result = [];
					$value = $fields[$userField->getName()];
					if(!is_array($value))
					{
						$value = [$value];
					}
					foreach($value as $date)
					{
						$result[] = \CRestUtil::$convertDateMethod($date);
					}
					$item->set($userField->getName(), $result);
				}
				else
				{
					$item->set($userField->getName(), \CRestUtil::$convertDateMethod($fields[$userField->getName()]));
				}
			}
			else
			{
				$item->set($userField->getName(), $fields[$userField->getName()]);
			}
		}
	}

	protected function processFileField(UserField $userField, \Bitrix\Rpa\Model\Item $item, array $fields): void
	{
		$userFieldName = $userField->getName();
		if($userField->isMultiple())
		{
			$fileData = $fields[$userFieldName];
			if(!is_array($fileData))
			{
				return;
			}

			$result = [];
			$currentFiles = array_flip($item->get($userFieldName) ?? []);
			foreach($fileData as $file)
			{
				$fileId = (int) $file['id'];
				if($fileId > 0)
				{
					if(isset($currentFiles[$fileId]))
					{
						$result[] = $fileId;
						$this->registerFile($userField, $fileId);
					}

					continue;
				}

				$fileId = $this->uploadFile($file);
				if($fileId > 0)
				{
					$result[] = $fileId;
					$this->uploadedFiles[] = $fileId;
					$this->registerFile($userField, $fileId);
				}
			}

			$item->set($userFieldName, $result);
		}
		else
		{
			$fileData = $fields[$userFieldName];
			$currentFile = $item->get($userFieldName);
			if(isset($fileData['id']))
			{
				if((int) $fileData['id'] === $currentFile)
				{
					return;
				}
			}
			$fileId = $this->uploadFile($fileData);
			if($fileId > 0)
			{
				$this->uploadedFiles[] = $fileId;
				$this->registerFile($userField, $fileId);
			}
			$item->set($userFieldName, $fileId);
		}
	}

	protected function registerFile(UserField $userField, int $fileId): void
	{
		$fileInputUtility = FileInputUtility::instance();
		$controlId = $fileInputUtility->getUserFieldCid($userField->toArray());

		$fileInputUtility->registerControl($controlId, $controlId);
		$fileInputUtility->registerFile($controlId, $fileId);
	}

	protected function deleteUploadedFiles(): void
	{
		foreach($this->uploadedFiles as $fileId)
		{
			if($fileId > 0)
			{
				\CFile::Delete($fileId);
			}
		}
	}

	public function getAction(\Bitrix\Rpa\Model\Type $type, int $id): ?array
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		return [
			'item' => $this->prepareItemData($item),
		];
	}

	public function addAction(\Bitrix\Rpa\Model\Type $type, array $fields = [], string $eventId = null): ?array
	{
		$item = $type->createItem();

		$this->processFields($item, $fields);

		$command = Driver::getInstance()->getFactory()->getAddCommand($item);
		if($eventId)
		{
			$command->setPullEventId($eventId);
		}
		$result = $command->run();
		if($result->isSuccess())
		{
			$item = $command->getItem();

			return [
				'item' => $this->prepareItemData($item),
			];
		}

		$this->deleteUploadedFiles();
		$this->addErrors($result->getErrors());

		return null;
	}

	public function updateAction(\Bitrix\Rpa\Model\Type $type, int $id, array $fields, string $eventId = null): ?array
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		$previousItemId = null;
		if(isset($fields['previousItemId']))
		{
			$previousItemId = (int) $fields['previousItemId'];
		}

		$stageId = $item->getStageId();
		if(isset($fields['stageId']))
		{
			$item->setStageId($fields['stageId']);
		}
		$this->processFields($item, $fields);
		$command = Driver::getInstance()->getFactory()->getUpdateCommand($item);
		if($eventId)
		{
			$command->setPullEventId($eventId);
		}
		$result = $command->run();
		if($result->isSuccess())
		{
			$item = $command->getItem();
			if($previousItemId !== null && $item->getStageId() === $stageId)
			{
				$userId = Driver::getInstance()->getUserId();
				if($userId > 0)
				{
					$sort = $this->getSortByPreviousItemId($item, $userId, $previousItemId);
					ItemSortTable::setSortForItem($item, $userId, $sort);
				}
			}
			return [
				'item' => $this->prepareItemData($item, [
					'withDisplay' => ($this->getScope() !== self::SCOPE_REST),
					'withTasks' => true,
					'withUsers' => true,
					'withPermissions' => ($this->getScope() !== self::SCOPE_REST),
				]),
			];
		}

		$this->deleteUploadedFiles();
		$this->addErrors($result->getErrors());

		return null;
	}

	public function sortAction(\Bitrix\Rpa\Model\Type $type, int $id, int $previousItemId = 0): ?array
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}
		$userId = Driver::getInstance()->getUserId();
		if($userId > 0)
		{
			$sort = $this->getSortByPreviousItemId($item, $userId, $previousItemId);
			$result = ItemSortTable::setSortForItem($item, $userId, $sort);
			if($result->isSuccess())
			{
				return [
					'item' => $this->prepareItemData($item),
				];
			}

			$this->addErrors($result->getErrors());
			return null;
		}

		return null;
	}

	public function listAction(
		\Bitrix\Rpa\Model\Type $type,
		array $order = null,
		array $filter = null,
		PageNavigation $pageNavigation = null
	): ?Page
	{
		if(!Driver::getInstance()->getUserPermissions()->canViewType($type->getId()))
		{
			$this->addError(new Error(Loc::getMessage('RPA_COMMON_TYPE_VIEW_ACCESS_DENIED')));
			return null;
		}
		$parameters = [];
		$parameters['filter'] = $this->removeDotsFromKeys($this->prepareFilter($type, $filter));
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		if(is_array($order))
		{
			$parameters['order'] = $converter->process($order);
		}
		$parameters['select'] = ['*'];

		if($pageNavigation)
		{
			$parameters['offset'] = $pageNavigation->getOffset();
			$parameters['limit'] = $pageNavigation->getLimit();
		}

		$result = [];
		$items = $type->getItems($parameters);
		foreach($items as $item)
		{
			$result[] = $this->prepareItemData($item, [
				'withDisplay' => false,
				'withTasks' => false,
				'withUsers' => false,
				'withPermissions' => false,
			]);
		}

		return new Page('items', $result, static function() use ($parameters, $type)
		{
			return $type->getItemsCount($parameters['filter']);
		});
	}

	protected function prepareFilter(\Bitrix\Rpa\Model\Type $type, array $filter = null): array
	{
		if(empty($filter))
		{
			return Driver::getInstance()->getUserPermissions()->getFilterForViewableItems($type);
		}

		$converter = new Converter(Converter::TO_UPPER | Converter::TO_SNAKE);
		$logic = null;
		$tasks = null;
		foreach($filter as $name => $value)
		{
			if($name === 'logic')
			{
				$logic = $value;
				unset($filter[$name]);
				continue;
			}
			if($name === 'tasks')
			{
				$tasks = $value;
				unset($filter[$name]);
				continue;
			}
			if(is_numeric($name))
			{
				$filter[$name] = $this->prepareFilter($type, $filter[$name]);
			}
			elseif(strpos($name, 'UF_RPA_') === false)
			{
				$nameCamel = $converter->process($name);
				$filter[$nameCamel] = $filter[$name];
				unset($filter[$name]);
			}
		}
		if($logic)
		{
			$filter = array_merge(['LOGIC' => $logic], $filter);
		}
		if($tasks)
		{
			$settings = new Settings([
				'ID' => $this->getScope().'_list',
			], $type);

			$itemProvider = new Provider($settings);
			$itemProvider->processTasksFilter($tasks, $filter);
		}

		if($this->getScope() === self::SCOPE_REST)
		{
			$this->prepareDateTimeFieldsForFilter($filter, $this->getItemDateTimeFieldNames($type));
		}

		return [
			$filter,
			Driver::getInstance()->getUserPermissions()->getFilterForViewableItems($type),
		];
	}

	public function deleteAction(\Bitrix\Rpa\Model\Type $type, $id): void
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error('Item of type '.$type->getName().' with id '.$id.' is not found'));
			return;
		}
		$command = Driver::getInstance()->getFactory()->getDeleteCommand($item);
		$result = $command->run();
		if(!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}
	}

	public function getEditorAction(int $typeId, int $id, int $stageId = null, string $eventId = null): Component
	{
		return new Component('bitrix:rpa.item.editor', '', [
			'typeId' => $typeId,
			'id' => $id,
			'stageId' => $stageId,
			'isEmbedded' => true,
			'eventId' => $eventId,
		]);
	}

	public function prepareItemData(\Bitrix\Rpa\Model\Item $item, array $params = []): array
	{
		$withDisplay = $params['withDisplay'] ?? false;
		$withTasks = $params['withTasks'] ?? true;
		$withPermissions = $params['withPermissions'] ?? true;
		$withUsers = $params['withUsers'] ?? true;

		$userPermissions = Driver::getInstance()->getUserPermissions();
		$canModifyItemsInStage = $userPermissions->canModifyItemsInStage($item->getType(), $item->getStageId());
		$canMoveFromStage = $userPermissions->canMoveFromStage($item->getType(), $item->getStageId());
		$canMoveToStage = $userPermissions->canMoveToStage($item->getStage());

		$data = [];
		$data['id'] = $item->getId();
		$data['stageId'] = $item->getStageId();
		$data['previousStageId'] = $item->getPreviousStageId();
		$data['name'] = $item->getName();
		$data['typeId'] = $item->getType()->getId();
		$data['createdBy'] = $item->getCreatedBy();
		$data['updatedBy'] = $item->getUpdatedBy();
		$data['movedBy'] = $item->getMovedBy();
		$data['createdTime'] = $this->prepareDateTimeValue($item->getCreatedTime());
		$data['updatedTime'] = $item->getUpdatedTime() ? $this->prepareDateTimeValue($item->getUpdatedTime()) : null;
		$data['movedTime'] = $item->getMovedTime() ? $this->prepareDateTimeValue($item->getMovedTime()) : null;
		$data['detailUrl'] = Driver::getInstance()->getUrlManager()->getItemDetailUrl(
			$item->getType()->getId(),
			$item->getId()
		);

		if($item->getStage())
		{
			$userFieldCollection = $item->getStage()->getUserFieldCollection();
		}
		else
		{
			$userFieldCollection = $item->getType()->getUserFieldCollection();
		}
		if(
			$canModifyItemsInStage
			|| $canMoveFromStage
			|| ($canMoveToStage && $item->getMovedBy() === $userPermissions->getUserId())
		)
		{
			foreach($userFieldCollection as $userField)
			{
//				if($userField->isVisible())
//				{
					$value = $this->prepareValue($userField, $item);
					$data[$userField->getName()] = $value;
//				}
			}
		}

		if($withPermissions)
		{
			$hasEmptyNotEditableMandatoryFields = false;
			if(!$canModifyItemsInStage)
			{
				foreach($userFieldCollection as $userField)
				{
					if(
						$userField->isMandatory() &&
						$item->isEmptyUserFieldValue($userField->getName()) &&
						(!$userField->isVisible() || !$userField->isEditable())
					)
					{
						$hasEmptyNotEditableMandatoryFields = true;
						break;
					}
				}
			}
			$data['permissions'] = [
				'draggable' => (
					($canMoveFromStage && !$hasEmptyNotEditableMandatoryFields) ||
					($canMoveToStage && $item->getMovedBy() === $userPermissions->getUserId())
				),
				'droppable' => ($canModifyItemsInStage || $canMoveFromStage || $canMoveToStage),
				'canDelete' => $userPermissions->canDeleteItem($item),
			];
		}

		if($withTasks)
		{
			$data['tasksCounter'] = 0;
			$data['tasksFaces'] = [];
			if ($item->getId())
			{
				$taskManager = Driver::getInstance()->getTaskManager();
				if($taskManager)
				{
					$data['tasksCounter'] = $taskManager->getUserItemIncompleteCounter($item);
					$data['tasksFaces'] = $taskManager->getItemFaces($item->getType()->getId(), $item->getId());
				}
			}
		}

		if($withDisplay)
		{
			$display = new Display($item->getType(), [
				$item->getId() => $data,
			]);
			$data['display'] = $display->getValues($item->getId());
		}

		if($withUsers)
		{
			$userIds = $item->getUserIds();

			if(isset($data['tasksFaces']['all']) && is_array($data['tasksFaces']['all']))
			{
				$userIds = array_merge($userIds, $data['tasksFaces']['all']);
			}

			$data['users'] = \Bitrix\Rpa\Components\Base::getUsers($userIds);
		}

		return $data;
	}

	protected function prepareValue(UserField $userField, \Bitrix\Rpa\Model\Item $item)
	{
		$value = $item->get($userField->getName());

		if(is_object($value))
		{
			if($value instanceof Date)
			{
				return $value;
			}
			if(class_exists($value) && method_exists($value, '__toString'))
			{
				return (string) $value;
			}

			return null;
		}

		if($userField->isBaseTypeFile() && $this->getScope() === self::SCOPE_REST)
		{
			if(is_array($value) && $userField->isMultiple())
			{
				foreach($value as &$file)
				{
					if($file > 0)
					{
						$file = [
							'id' => $file,
							'url' => Driver::getInstance()->getUrlManager()->getFileUrl($item->getType()->getId(), $item->getId(), $userField->getName(), $file),
						];
					}
				}
			}
			elseif($value > 0)
			{
				$value = [
					'id' => $value,
					'url' => Driver::getInstance()->getUrlManager()->getFileUrl($item->getType()->getId(), $item->getId(), $userField->getName(), $value),
				];
			}
		}

		return $value;
	}

	public function getSortByPreviousItemId(\Bitrix\Rpa\Model\Item $item, int $userId, int $previousItemId = 0): int
	{
		return ItemSortTable::getSort($userId, $item->getType(), $item->getStageId(), $previousItemId);
	}

	protected function getItemDateTimeFieldNames(\Bitrix\Rpa\Model\Type $type): array
	{
		$fields = ['CREATED_TIME', 'UPDATED_TIME', 'MOVED_TIME'];
		foreach($type->getUserFieldCollection() as $userField)
		{
			if($userField->isBaseTypeDate())
			{
				$fields[] = $userField->getName();
			}
		}

		return $fields;
	}

	public function getFileAction(\Bitrix\Rpa\Model\Type $type, int $id, string $fieldName, int $file_id): ?BFile
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		$userFieldCollection = $item->getType()->getUserFieldCollection();
		$userField = $userFieldCollection->getByName($fieldName);
		if (!$userField || !$userField->isBaseTypeFile())
		{
			$this->addError(new Error('Field ' . $fieldName . ' is not a file field'));
			return null;
		}

		$value = $item->get($fieldName);
		if(
			($value === $file_id)
			||
			(is_array($value) && in_array($file_id, $value))
		)
		{
			return BFile::createByFileId($file_id);
		}

		return null;
	}

	public function getTasksAction(\Bitrix\Rpa\Model\Type $type, int $id): ?array
	{
		$item = $type->getItem($id);
		if(!$item)
		{
			$this->addError(new Error(Loc::getMessage('RPA_ITEM_NOT_FOUND_ERROR')));
			return null;
		}
		if(!Driver::getInstance()->getUserPermissions()->canViewItem($item))
		{
			$this->addError(new Error(Loc::getMessage('RPA_VIEW_ITEM_ACCESS_DENIED')));
			return null;
		}

		$taskManager = Driver::getInstance()->getTaskManager();
		if(!$taskManager)
		{
			return null;
		}

		return [
			'tasks' => $taskManager->getTimelineTasks($item)
		];
	}
}