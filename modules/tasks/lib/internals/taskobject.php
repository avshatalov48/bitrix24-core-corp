<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main;
use Bitrix\Tasks\Util\Entity\DateTimeField;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class TaskObject
 *
 * @package Bitrix\Tasks\Internals
 */
class TaskObject extends EO_Task
{
	/**
	 * @param $data
	 * @return TaskObject
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function wakeUpObject($data): TaskObject
	{
		if (!is_array($data))
		{
			return parent::wakeUp($data);
		}

		$fields = TaskTable::getEntity()->getFields();

		$wakeUpData = [];
		$customData = [];
		foreach ($data as $field => $value)
		{
			if (array_key_exists($field, $fields))
			{

				if (
					$fields[$field] instanceof DateTimeField
					&& is_numeric($value)
				)
				{
					$wakeUpData[$field] = DateTime::createFromTimestampGmt($value);
				}
				else
				{
					$wakeUpData[$field] = $value;
				}
			}
			else
			{
				$customData[$field] = $value;
			}
		}

		$object = parent::wakeUp($wakeUpData);
		foreach ($customData as $field => $value)
		{
			$object->customData->set($field, $value);
		}

		return $object;
	}

	/**
	 * @return bool
	 */
	public function isDeleted()
	{
		return $this->getZombie();
	}

	/**
	 * @param int $userId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function isMuted(int $userId): bool
	{
		return UserOption::isOptionSet($this->getId(), $userId, UserOption\Option::MUTED);
	}

	/**
	 * @return bool
	 */
	public function isExpired(): bool
	{
		$status = (int)$this->getStatus();
		$completedStates = [\CTasks::STATE_SUPPOSEDLY_COMPLETED, \CTasks::STATE_COMPLETED, \CTasks::STATE_DEFERRED];

		if (!$this->getDeadline() || in_array($status, $completedStates, true))
		{
			return false;
		}

		return (DateTime::createFrom($this->getDeadline()))->checkLT(new DateTime());
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$fields = TaskTable::getEntity()->getFields();

		$data = [];
		foreach ($fields as $fieldName => $field)
		{
			if (
				$field instanceof Main\ORM\Fields\Relations\Reference
				|| $field instanceof Main\ORM\Fields\Relations\OneToMany
				|| $field instanceof Main\ORM\Fields\Relations\ManyToMany
				|| $field instanceof Main\ORM\Fields\ExpressionField
			)
			{
				continue;
			}

			$data[$fieldName] = $this->get($fieldName);

			if ($data[$fieldName] instanceof DateTime)
			{
				$data[$fieldName] = $data[$fieldName]->getTimestamp();
			}
		}
		return $data;
	}
}