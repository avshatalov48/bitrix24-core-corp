<?php

namespace Bitrix\Sign\Connector\Crm;

use Bitrix\Main\Loader;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;
use CCrmOwnerType;

class Company extends Base implements Contract\RequisiteConnector
{
	public function __construct(private int $entityId)
	{
	}

	public function getCrmEntityTypeId(): int
	{
		if (!Loader::includeModule('crm'))
		{
			return 0;
		}

		return CCrmOwnerType::Company;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function fetchRequisite(?Item\Connector\FetchRequisiteModifier $fetchModifier = null): Item\Connector\RequisiteFieldCollection
	{
		$result = new Item\Connector\RequisiteFieldCollection;
		if (!Loader::includeModule('crm'))
		{
			return $result;
		}

		$fieldSetValues = CRM::getRequisitesEntityFieldSetValues(
			CCrmOwnerType::Company,
			$this->entityId,
			$fetchModifier?->presetId
		);
		foreach ($fieldSetValues as $fieldSetCode => $fieldSetValue)
		{
			$value = $fieldSetValue['value'] ?? null;
			if (Item\Connector\RequisiteField::isValueTypeSupported($value))
			{
				$result->add(
					new Item\Connector\RequisiteField(
						$fieldSetCode,
						$fieldSetValue['label'] ?? '',
						$value,
					)
				);
			}
		}

		return $result;
	}

	public function getName(): string
	{
		return $this->fetchFields()->getFirstByName('TITLE')?->data ?? '';
	}
}