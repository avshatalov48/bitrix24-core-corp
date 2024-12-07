<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Merger;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Result;

class IsReturnCustomerForLead extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($this->shouldCalculateIsReturnCustomer($item))
		{
			$isReturnCustomer = !$item->isClientEmpty();

			$item->set($this->getName(), $isReturnCustomer);
		}

		return new Result();
	}

	private function shouldCalculateIsReturnCustomer(Item $item): bool
	{
		if ($item->isNew())
		{
			return true;
		}

		$successfulStageId = $this->getSuccessfulStageId($item->getEntityTypeId());

		return (
			!$this->isItemOnSuccessfulStage($item, $successfulStageId)
			&& !$this->isItemWasOnSuccessfulStageInThePast($item, $successfulStageId)
		);
	}

	private function getSuccessfulStageId(int $entityTypeId): string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isStagesSupported())
		{
			foreach ($factory->getStages() as $stage)
			{
				if (PhaseSemantics::isSuccess($stage->getSemantics()))
				{
					return $stage->getStatusId();
				}
			}
		}

		throw new InvalidOperationException('No successful stage found, but it should exist');
	}

	private function isItemOnSuccessfulStage(Item $item, string $successfulStageId): bool
	{
		return (string)$item->getStageId() === $successfulStageId;
	}

	private function isItemWasOnSuccessfulStageInThePast(Item $item, string $successfulStageId): bool
	{
		if ($item->getEntityTypeId() !== \CCrmOwnerType::Lead)
		{
			throw new NotSupportedException('Only lead supported here');
		}

		return \Bitrix\Crm\History\LeadStatusHistoryEntry::checkStatus($item->getId(), $successfulStageId);
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$isChanged = $itemBeforeSave->remindActual($this->getName()) !== $item->get($this->getName());

		$wasChangedToTrue = (
			!$itemBeforeSave->isNew()
			&& $isChanged
			&& $item->get($this->getName()) === true
		);

		if ($wasChangedToTrue)
		{
			$this->enrichClient($item);
		}

		return new FieldAfterSaveResult();
	}

	private function enrichClient(Item $item): void
	{
		try
		{
			$sourceMerger = Merger\EntityMergerFactory::create($item->getEntityTypeId(), 0, false);
		}
		catch (NotSupportedException)
		{
			return;
		}

		if ($item->hasField(Item::FIELD_NAME_COMPANY_ID) && $item->getCompanyId() > 0)
		{
			$this->enrichCompany($sourceMerger, $item->getId(), $item->getCompanyId());
		}

		if ($item->hasField(Item::FIELD_NAME_CONTACTS) && $item->getPrimaryContact())
		{
			$this->enrichContact($sourceMerger, $item->getId(), $item->getPrimaryContact()->getId());
		}
	}

	private function enrichCompany(Merger\EntityMerger $sourceMerger, int $sourceId, int $companyId): void
	{
		$companyMerger = Merger\EntityMergerFactory::create(\CCrmOwnerType::Company, 0, false);

		try
		{
			$companyMerger->enrich(
				$sourceMerger,
				$sourceId,
				$companyId,
			);
		}
		catch (Merger\EntityMergerException)
		{
		}
	}

	private function enrichContact(Merger\EntityMerger $sourceMerger, int $sourceId, int $contactId): void
	{
		$contactMerger = Merger\EntityMergerFactory::create(\CCrmOwnerType::Contact, 0, false);

		try
		{
			$contactMerger->enrich(
				$sourceMerger,
				$sourceId,
				$contactId,
			);
		}
		catch (Merger\EntityMergerException)
		{
		}
	}
}
