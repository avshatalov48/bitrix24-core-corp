<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;

class IsReturnCustomer extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$determineValueByClientOnly = $this->getSettings()['determineValueByClientOnly'] ?? false;

		if ($determineValueByClientOnly)
		{
			$isReturnCustomer = $this->isReturnCustomerByClientOnly($item);
		}
		else
		{
			$isReturnCustomer = $this->isReturnCustomerByPreviousSuccessfulItem($item);
		}

		$item->set($this->getName(), $isReturnCustomer);

		return new Result();
	}

	private function isReturnCustomerByClientOnly(Item $item): bool
	{
		return !$item->isClientEmpty();
	}

	private function isReturnCustomerByPreviousSuccessfulItem(Item $item): bool
	{
		if ($item->isClientEmpty())
		{
			// no client bound - entirely new customer, not returned
			return false;
		}

		return $this->isPreviousSuccessfulItemExists($item);
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
}
