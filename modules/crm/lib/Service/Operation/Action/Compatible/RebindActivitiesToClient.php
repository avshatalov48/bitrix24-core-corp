<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class RebindActivitiesToClient extends Action
{
	/** @var string */
	private $targetStageId;

	public function __construct(string $targetStageId)
	{
		parent::__construct();

		$this->targetStageId = $targetStageId;
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			$result->addError(new Error('itemBeforeSave is required in ' . __METHOD__));

			return $result;
		}

		if ($this->isOnTargetStage($itemBeforeSave))
		{
			$this->rebindActivities($itemBeforeSave);
		}

		return $result;
	}

	private function isOnTargetStage(Item $item): bool
	{
		if (!$item->hasField(Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		return ($item->getStageId() === $this->targetStageId);
	}

	private function rebindActivities(Item $item): void
	{
		if ($item->hasField(Item::FIELD_NAME_CONTACTS) && $item->getPrimaryContact())
		{
			\CCrmActivity::ChangeOwner(
				$item->getEntityTypeId(),
				$item->getId(),
				\CCrmOwnerType::Contact,
				$item->getPrimaryContact()->getId(),
			);
		}
		elseif ($item->hasField(Item::FIELD_NAME_COMPANY_ID) && $item->getCompanyId() > 0)
		{
			\CCrmActivity::ChangeOwner(
				$item->getEntityTypeId(),
				$item->getId(),
				\CCrmOwnerType::Company,
				$item->getCompanyId(),
			);
		}
	}
}
