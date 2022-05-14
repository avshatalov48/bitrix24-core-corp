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

class IsReturnCustomer extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		if ($this->isProcessionNeeded($item))
		{
			$item->set($this->getName(), $this->isReturnCustomer($item));
		}

		return new Result();
	}

	private function isProcessionNeeded(Item $item): bool
	{
		if (!$this->isPrimarySource())
		{
			return true;
		}

		return (
			$item->isNew()
			|| !$this->isItemOnSuccessfulStage($item)
		);
	}

	/**
	 * Returns true if this field should behave as if it belongs to an item of an entity type,
	 * that is a primary (like primordial) source of customer entities, e.g., Lead
	 *
	 * @return bool
	 */
	private function isPrimarySource(): bool
	{
		$isPrimarySource = $this->getSettings()['isPrimarySource'] ?? false;

		return (bool)$isPrimarySource;
	}

	private function isItemOnSuccessfulStage(Item $item): bool
	{
		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if ($factory && $factory->isStagesSupported())
		{
			$semantics = $factory->getStageSemantics((string)$item->getStageId());

			if ($semantics === PhaseSemantics::SUCCESS)
			{
				return true;
			}
		}

		return false;
	}

	private function isReturnCustomer(Item $item): bool
	{
		if ($this->isPrimarySource())
		{
			return !$item->isClientEmpty();
		}

		return (
			!$item->isClientEmpty()
			&& $this->isPreviousSuccessfulItemExists($item)
		);
	}

	private function isPreviousSuccessfulItemExists(Item $item): bool
	{
		$getItemsParams = [
			'select' => [Item::FIELD_NAME_ID],
			'filter' => [
				'=' . Item::FIELD_NAME_STAGE_SEMANTIC_ID => PhaseSemantics::SUCCESS,
			],
			'limit' => 1,
			'order' => [
				Item::FIELD_NAME_ID => 'ASC',
			],
		];

		if ($item->getCompanyId() > 0)
		{
			$getItemsParams['filter']['=' . Item::FIELD_NAME_COMPANY_ID] = $item->getCompanyId();
		}
		elseif (!is_null($item->getPrimaryContact()))
		{
			$getItemsParams['filter']['=' . Item::FIELD_NAME_CONTACT_ID] = $item->getPrimaryContact()->getId();
			$getItemsParams['filter']['<=' . Item::FIELD_NAME_COMPANY_ID] = 0;
		}
		else
		{
			throw new InvalidOperationException('The item has no client');
		}

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());

		if (!$factory)
		{
			return false;
		}

		$previousSuccessfulItem = $factory->getItems($getItemsParams)[0] ?? null;

		return !is_null($previousSuccessfulItem);
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		if ($this->isPrimarySource())
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
		}

		return new FieldAfterSaveResult();
	}

	private function enrichClient(Item $item): void
	{
		try
		{
			$sourceMerger = Merger\EntityMergerFactory::create($item->getEntityTypeId(), 0, false);
		}
		catch (NotSupportedException $sourceTypeNotSupportedException)
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
		catch (Merger\EntityMergerException $companyEnrichException)
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
		catch (Merger\EntityMergerException $contactEnrichException)
		{
		}
	}
}
