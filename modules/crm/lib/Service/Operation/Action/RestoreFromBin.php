<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Integration\Recyclebin\DateTimeConverter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

class RestoreFromBin extends Action
{
	private ?DateTime $movedToBinDateTime = null;

	public function __construct(?DateTime $movedToBinDateTime = null)
	{
		parent::__construct();

		$this->movedToBinDateTime = $movedToBinDateTime;
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		$dateTimeConverter = new DateTimeConverter($this->movedToBinDateTime);
		if (!$dateTimeConverter->needConvert())
		{
			return $result;
		}

		$userFields = $this->getUserFields($item);
		foreach ($item->getData() as $fieldName => $field)
		{
			if ($field instanceof DateTime && !in_array($fieldName, $userFields, true))
			{
				$field->toUserTime();
			}
		}

		return $result;
	}

	private function getUserFields(Item $item): array
	{
		global $USER_FIELD_MANAGER;
		$userType = new \CCrmUserType(
			$USER_FIELD_MANAGER,
			\CCrmOwnerType::ResolveUserFieldEntityID($item->getEntityTypeId())
		);

		return array_keys($userType->GetEntityFields(0));
	}
}
