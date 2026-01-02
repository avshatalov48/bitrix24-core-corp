<?php

namespace Bitrix\Crm\Entity\Compatibility\Adapter;

use Bitrix\Crm\Entity\Compatibility\Adapter;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class LastActivity extends Adapter
{
	use Adapter\Traits\PreviousFields;

	private Factory $factory;
	/** @var array<int, Item> */
	private array $items = [];

	private string $lastActivityTimeField;
	private string $lastActivityByField;

	final public function __construct(Factory $factory)
	{
		$this->factory = $factory;

		$this->lastActivityTimeField = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_TIME);
		$this->lastActivityByField = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_LAST_ACTIVITY_BY);
	}

	final protected function doGetFieldsInfo(): array
	{
		return [
			$this->lastActivityTimeField => [
				'TYPE' => Field::TYPE_DATETIME,
				'ATTRIBUTES' => [],
			],
			$this->lastActivityByField => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [],
			],
		];
	}

	final protected function doGetFields(): array
	{
		return [
			$this->lastActivityByField => [
				'FIELD' => $this->getTableAlias() . '.' . $this->lastActivityByField,
				'TYPE' => 'int',
			],
			$this->lastActivityTimeField => [
				'FIELD' => $this->getTableAlias() . '.' . $this->lastActivityTimeField,
				'TYPE' => 'datetime',
			],
		];
	}

	final protected function doPerformAdd(array &$fields, array $compatibleOptions): Result
	{
		$createdTimeField = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME);
		$createdByField = $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_BY);

		$fields[$this->lastActivityTimeField] = $fields[$createdTimeField] ?? $this->getNowString();
		$fields[$this->lastActivityByField] = $fields[$createdByField] ?? $this->getUserId($compatibleOptions);

		return new Result();
	}

	/**
	 * Extracted for testing
	 *
	 * @return string
	 */
	protected function getNowString(): string
	{
		return (new DateTime())->toString();
	}

	private function getUserId(array $compatibleOptions): int
	{
		if (isset($compatibleOptions['CURRENT_USER']))
		{
			return (int)$compatibleOptions['CURRENT_USER'];
		}

		return Container::getInstance()->getContext()->getUserId();
	}

	final protected function doPerformUpdate(int $id, array &$fields, array $compatibleOptions): Result
	{
		if (
			!array_key_exists($this->lastActivityTimeField, $fields)
			&& !array_key_exists($this->lastActivityByField, $fields)
		)
		{
			return new Result();
		}

		$previousFields = $this->getPreviousFields($id) ?? [];
		$previousTime = $this->getLastActivityTime($previousFields);
		if ($previousTime === null)
		{
			unset($fields[$this->lastActivityTimeField], $fields[$this->lastActivityByField]);

			return \Bitrix\Crm\Result::fail('No or invalid previous last activity time');
		}

		$currentTime = $this->getLastActivityTime($fields);
		if ($currentTime === null)
		{
			unset($fields[$this->lastActivityTimeField], $fields[$this->lastActivityByField]);

			return \Bitrix\Crm\Result::fail('No or invalid current last activity time');
		}

		if ($currentTime->getTimestamp() <= $previousTime->getTimestamp())
		{
			unset($fields[$this->lastActivityTimeField], $fields[$this->lastActivityByField]);

			return new Result();
		}

		return new Result();
	}

	private function getLastActivityTime(array $fields): ?DateTime
	{
		$time = $fields[$this->lastActivityTimeField] ?? null;

		if ($time instanceof DateTime)
		{
			return $time;
		}

		if (is_string($time) && !empty($time))
		{
			return DateTime::createFromUserTime($time);
		}

		return null;
	}

	final protected function doPerformDelete(int $id, array $compatibleOptions): Result
	{
		return new Result();
	}
}
