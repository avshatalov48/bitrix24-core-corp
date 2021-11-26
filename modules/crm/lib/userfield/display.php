<?php

namespace Bitrix\Crm\UserField;

use \Bitrix\Crm\Service\Factory;
use Bitrix\Crm\UserField\DisplayStrategy\BaseStrategy;
use Bitrix\Crm\UserField\DisplayStrategy\DefaultStrategy;
use Bitrix\Crm\Service\Container;

class Display
{
	/** @var Factory */
	protected $factory;
	protected $values;
	protected $userFields;
	/** @var BaseStrategy */
	protected $strategy;

	public function __construct(Factory $factory, array $values = [])
	{
		$this->factory = $factory;
		$this->values = $values;

		global $USER_FIELD_MANAGER;
		$this->userFields = $USER_FIELD_MANAGER->getUserFields($this->factory->getUserFieldEntityId(), 0, LANGUAGE_ID);

		$this->setStrategy(new DefaultStrategy($factory->getEntityTypeId()));
	}

	public static function createByEntityTypeId(int $entityTypeId): Display
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			// waiting when we will have factories for every entity type
			// really sorry for this:
			$factory = new class extends \Bitrix\Crm\Service\Factory\Contact
			{
				protected $entityTypeIdInternal;

				public function setEntityTypeIdInternal(int $entityTypeId)
				{
					$this->entityTypeIdInternal = $entityTypeId;
				}
				public function getUserFieldEntityId(): string
				{
					return (\CCrmOwnerType::ResolveUserFieldEntityID($this->entityTypeIdInternal));
				}
				public function getEntityTypeId(): int
				{
					return $this->entityTypeIdInternal;
				}
			};
			$factory->setEntityTypeIdInternal($entityTypeId);
		}

		return new self($factory);
	}

	public function setStrategy(BaseStrategy $strategy): Display
	{
		$this->strategy = $strategy;
		$this->strategy->setUserFields($this->userFields);

		return $this;
	}

	public function addValues(int $itemId, array $values): Display
	{
		if(!isset($this->values[$itemId]))
		{
			$this->values[$itemId] = [];
		}

		$this->values[$itemId] = array_merge($this->values[$itemId], $values);

		return $this;
	}

	protected function processValues(): array
	{
		return $this->strategy->processValues($this->values);
	}

	protected function getUserFields(): array
	{
		return $this->userFields;
	}

	public function getAllValues(): array
	{
		return $this->processValues();
	}

	public function getValues(int $itemId): ?array
	{
		return $this->processValues()[$itemId];
	}

	public function getValue(int $itemId, string $fieldName): ?string
	{
		$values = $this->getValues($itemId);
		if(!$values)
		{
			return null;
		}

		return $values[$fieldName];
	}
}
