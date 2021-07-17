<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField;

/**
 * Class Item
 * @see \Bitrix\Rpa\Model\PrototypeItem
 * @method int getStageId()
 * @method setStageId(int $stageId)
 * @method int getPreviousStageId()
 * @method setPreviousStageId(int $previousStageId)
 * @method int getCreatedBy()
 * @method setCreatedBy(int $userId)
 * @method int|null getUpdatedBy()
 * @method setUpdatedBy(int $userId)
 * @method int|null getMovedBy()
 * @method setMovedBy(int $userId)
 * @method DateTime getCreatedTime()
 * @method setCreatedTime(DateTime $dateTime)
 * @method DateTime|null getUpdatedTime()
 * @method setUpdatedTime(DateTime $dateTime)
 * @method DateTime|null getMovedTime()
 * @method setMovedTime(DateTime $dateTime)
 * @method int remindActualStageId()
 */
class Item extends UserField\Internal\Item
{
	protected $stage;
	protected $type;

	public function getType(): Type
	{
		if($this->type === null)
		{
			$data = UserField\Internal\Registry::getInstance()->getTypeByEntity($this->entity);
			if($data)
			{
				unset($data['code']);
				$this->type = Type::wakeUp($data);
			}
		}

		return $this->type;
	}

	public function getStage(): ?Stage
	{
		$stage = null;
		$type = $this->getType();
		if($type)
		{
			$stage = $type->getStages()->getByPrimary($this->getStageId());
		}

		return $stage;
	}

	public function getPreviousStage(): ?Stage
	{
		$stage = null;
		$type = $this->getType();
		if($type && $this->getPreviousStageId() > 0)
		{
			$stage = $type->getStages()->getByPrimary($this->getPreviousStageId());
		}

		return $stage;
	}

	public function getName(): string
	{
		$fieldName = $this->getType()->getItemUfNameFieldName();
		$name = '';
		if($this->sysGetEntity()->hasField($fieldName))
		{
			$name = $this->get($fieldName);
		}
		if(empty($name))
		{
			if($this->getId() > 0)
			{
				$name = $this->getType()->getTitle().' #'.$this->getId();
			}
			else
			{
				$name = Loc::getMessage('RPA_MODEL_ITEM_NEW_NAME', ['#TYPE_NAME#' => $this->getType()->getTitle()]);
			}
		}

		if (is_array($name))
		{
			$name = reset($name);
		}

		return $name;
	}

	public function isEmptyUserFieldValue($fieldName): bool
	{
		return empty($this->get($fieldName));
	}

	public function getChangedUserFieldNames(): array
	{
		$fields = [];

		foreach($this->getType()->getUserFieldCollection() as $userField)
		{
			if($this->isValueChanged($userField->getName()))
			{
				$fields[] = $userField->getName();
			}
		}

		return $fields;
	}

	public function getUserIds(): array
	{
		$userIds = [];
		if($this->getCreatedBy())
		{
			$userIds[$this->getCreatedBy()] = $this->getCreatedBy();
		}
		if($this->getUpdatedBy())
		{
			$userIds[$this->getUpdatedBy()] = $this->getUpdatedBy();
		}
		if($this->getMovedBy())
		{
			$userIds[$this->getMovedBy()] = $this->getMovedBy();
		}

		return $userIds;
	}

	public function isValueChanged(string $fieldName): bool
	{
		$field = $this->entity->getField($fieldName);
		if($field instanceof ArrayField)
		{
			$actualValue = $this->remindActual($fieldName);
			$newValue = $this->get($fieldName);
			if(is_array($newValue))
			{
				$newValue = array_filter($newValue);
			}

			if(!is_array($actualValue) || !is_array($newValue))
			{
				return true;
			}

			return (!empty(array_diff($actualValue, $newValue)) || !empty(array_diff($newValue, $actualValue)));
		}

		return $this->isChanged($fieldName);
	}
}