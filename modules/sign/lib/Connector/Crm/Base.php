<?php

namespace Bitrix\Sign\Connector\Crm;

use Bitrix\Crm\Field;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Connector\FieldCollection;
use Closure;

abstract class Base implements Contract\FilterableConnector, Contract\Connector
{
	protected ?Closure $excludeFieldsRule = null;
	protected ?Closure $includeFieldsRule = null;

	abstract public function getCrmEntityTypeId(): int;
	abstract public function getEntityId(): int;

	public function fetchFields(): FieldCollection
	{
		$result = new FieldCollection();
		if (!Loader::includeModule('crm'))
		{
			return $result;
		}

		$crmEntityFields =
			Container::getInstance()->getFactory($this->getCrmEntityTypeId())
			?->getItem($this->getEntityId())
			?->getCompatibleData();

		$crmEntityFields ??= [];

		$useIncludeFilter = $this->includeFieldsRule !== null;
		$useExcludeFilter = $this->excludeFieldsRule !== null;

		foreach ($crmEntityFields as $fieldName => $value)
		{
			if (!Item\Connector\Field::isValueTypeSupported($value))
			{
				continue;
			}
			if ($useExcludeFilter && ($this->excludeFieldsRule)($fieldName))
			{
				continue;
			}
			if ($useIncludeFilter && !($this->includeFieldsRule)($fieldName))
			{
				continue;
			}

			$result->add(
				new Item\Connector\Field($fieldName, $value)
			);
		}

		return $result;
	}

	public function setExcludeFilterRule(?Closure $rule): static
	{
		$this->excludeFieldsRule = $rule;
		return $this;
	}

	public function setIncludeFilterRule(?Closure $rule): static
	{
		$this->includeFieldsRule = $rule;
		return $this;
	}
}