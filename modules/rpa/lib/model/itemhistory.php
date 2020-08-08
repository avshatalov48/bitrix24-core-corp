<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Rpa\Driver;

class ItemHistory extends EO_ItemHistory
{
	public const ACTION_ADD = 'ADD';
	public const ACTION_MOVE = 'MOVE';
	public const ACTION_UPDATE = 'UPDATE';
	public const ACTION_MOVE_UPDATE = 'MOVE_UPDATE';
	public const ACTION_TASK_COMPLETE = 'TASK_COMPLETE';

	/** @var Item */
	protected $item;
	protected $fields;

	protected function setItem(Item $item): ItemHistory
	{
		$this->item = $item;

		return $this;
	}

	protected function getItem(): ?Item
	{
		if(!$this->item)
		{
			$typeId = $this->getTypeId();
			$itemId = $this->getItemId();
			if($itemId > 0 && $typeId > 0)
			{
				$type = Driver::getInstance()->getType($typeId);
				if($type)
				{
					$this->item = $type->getItem($itemId);
				}
			}
		}

		return $this->item;
	}

	public static function createByItem(Item $item): ItemHistory
	{
		$record = new static(true);
		$clonedItem = clone $item;
		$record->setItem($clonedItem);

		return $record;
	}

	public function getTypeId(): ?int
	{
		if($this->item && empty(parent::getTypeId()))
		{
			return $this->item->getType()->getId();
		}

		return parent::getTypeId();
	}

	public function getItemId(): ?int
	{
		if($this->item && empty(parent::getItemId()))
		{
			return $this->item->getId();
		}

		return parent::getItemId();
	}

	public function getAction(): ?string
	{
		if(empty(parent::getAction()))
		{
			if($this->getTaskId() > 0)
			{
				return static::ACTION_TASK_COMPLETE;
			}
			if($this->item)
			{
				if($this->item->getId() > 0)
				{
					$isStageChanged = ($this->getStageId() !== $this->getNewStageId());
					$isEmptyFields = empty($this->getFields());
					if($isStageChanged && !$isEmptyFields)
					{
						return static::ACTION_MOVE_UPDATE;
					}

					if($isStageChanged && $isEmptyFields)
					{
						return static::ACTION_MOVE;
					}

					return static::ACTION_UPDATE;
				}

				return static::ACTION_ADD;
			}
		}

		return parent::getAction();
	}

	public function getFields(): array
	{
		if($this->fields === null)
		{
			$this->fields = [];
			if($this->item)
			{
				$this->fields = $this->item->getChangedUserFieldNames();
			}
			elseif($this->getId() > 0)
			{
				$list = ItemHistoryFieldTable::getList([
					'select' => [
						'FIELD_NAME',
					],
					'filter' => [
						'=ITEM_HISTORY_ID' => $this->getId(),
					],
				]);
				while($field = $list->fetch())
				{
					$this->fields[] = $field['FIELD_NAME'];
				}
			}
		}

		return $this->fields;
	}

	public function getStageId(): ?int
	{
		if($this->item && empty(parent::getStageId()))
		{
			return $this->item->remindActual('STAGE_ID');
		}

		return parent::getStageId();
	}

	public function getNewStageId(): ?int
	{
		if($this->item && empty(parent::getNewStageId()))
		{
			return $this->item->getStageId();
		}

		return parent::getNewStageId();
	}

	public function fillEmptyValues(): void
	{
		if(empty(parent::getTypeId()))
		{
			$this->setTypeId($this->getTypeId());
		}
		if(empty(parent::getItemId()))
		{
			$this->setItemId($this->getItemId());
		}
		if(empty(parent::getAction()))
		{
			$this->setAction($this->getAction());
		}
		if(empty(parent::getStageId()))
		{
			$this->setStageId($this->getStageId());
		}
		if(empty(parent::getNewStageId()))
		{
			$this->setNewStageId($this->getNewStageId());
		}
	}

	public function createTimelineRecord(): ?Timeline
	{
		if(!$this->getId())
		{
			return null;
		}
		$item = $this->getItem();
		if(!$item)
		{
			return null;
		}

		$data = [
			'item' => [
				'name' => $item->getName(),
			],
			'scope' => $this->getScope(),
		];
		$data['stageFrom']['id'] = $this->getStageId();
		if($this->getStageId() > 0)
		{
			$stageFrom = $item->getType()->getStage($this->getStageId());
			if($stageFrom)
			{
				$data['stageFrom'] = [
					'id' => $stageFrom->getId(),
					'name' => $stageFrom->getName(),
				];
			}
		}
		if($this->getNewStageId() > 0)
		{
			$data['stageTo'] = [
				'id' => $this->getNewStageId(),
			];
			$stageTo = $item->getType()->getStage($this->getNewStageId());
			if($stageTo)
			{
				$data['stageTo']['name'] = $stageTo->getName();
			}
		}
		$fields = $this->getFields();
		if(!empty($fields))
		{
			$type = Driver::getInstance()->getType($this->getTypeId());
			if($type)
			{
				$userFields = $type->getUserFieldCollection();
				foreach($fields as $fieldName)
				{
					$field = $userFields->getByName($fieldName);
					if($field)
					{
						$data['fields'][] = [
							'name' => $fieldName,
							'title' => $field->getTitle(),
						];
					}
				}
			}
		}
		$taskId = $this->getTaskId();
		if($taskId > 0)
		{
			$taskManager = Driver::getInstance()->getTaskManager();
			if($taskManager)
			{
				$data['task'] = $taskManager->getTaskById($taskId);
			}
		}

		$timeline = Timeline::createForItem($item);
		$timeline->setItemId($this->getItemId());
		$timeline->setUserId($this->getUserId());
		$timeline->setCreatedTime($this->getCreatedTime());
		$timeline->setAction($this->getTimelineAction());
		$timeline->setData($data);

		return $timeline;
	}

	protected function getTimelineAction(): ?string
	{
		$action = $this->getAction();
		$actionsMap = [
			static::ACTION_ADD => Timeline::ACTION_ITEM_CREATE,
			static::ACTION_MOVE => Timeline::ACTION_STAGE_CHANGE,
			static::ACTION_UPDATE => Timeline::ACTION_FIELDS_CHANGE,
			static::ACTION_MOVE_UPDATE => Timeline::ACTION_STAGE_CHANGE,
			static::ACTION_TASK_COMPLETE => Timeline::ACTION_TASK_COMPLETE,
		];

		return $actionsMap[$action];
	}
}