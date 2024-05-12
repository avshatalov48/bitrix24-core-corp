<?php

namespace Bitrix\Crm\Summary;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Summary\UI\ClientSummaryAdapter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;

final class ClientSummary
{
	private bool $isAccountCurrencyIdUsed = false;

	/**
	 * @param ItemIdentifier $client
	 * @param int $entityTypeId
	 * @param Item[] $items
	 * @param \Bitrix\Crm\Integration\AI\Result|null $latestSummarizeCallResult
	 * @param int|null $latestWebformActivityId
	 *
	 * @throws ArgumentException|InvalidOperationException
	 */
	public function __construct(
		private ItemIdentifier $client,
		private int $entityTypeId,
		private array $items,
		private ?\Bitrix\Crm\Integration\AI\Result $latestSummarizeCallResult = null,
		private ?int $latestWebformActivityId = null,
	)
	{
		foreach ($this->items as $item)
		{
			if ($item->getEntityTypeId() !== $this->entityTypeId)
			{
				throw new ArgumentException(
					"All items should be of the provided type. Expected {$this->entityTypeId}, got {$item->getEntityTypeId()}"
				);
			}

			if (!$item->isStagesEnabled())
			{
				throw new InvalidOperationException('Client summary is possible only for items that have stages');
			}

			if (!$item->hasField(Item::FIELD_NAME_OPPORTUNITY))
			{
				throw new InvalidOperationException('Client summary is possible only for items that have products');
			}
		}

		usort($this->items, function (Item $left, Item $right) {
			return (int)$right->getCreatedTime()?->getTimestamp() - (int)$left->getCreatedTime()?->getTimestamp();
		});
	}

	public function getUIAdapter(): ClientSummaryAdapter
	{
		return new ClientSummaryAdapter($this);
	}

	public function getClientIdentifier(): ItemIdentifier
	{
		return $this->client;
	}

	public function getItemsEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getItemsCount(): int
	{
		return count($this->items);
	}

	public function getSuccessfulItemsCount(): int
	{
		return count($this->getSuccessfulItems());
	}

	private function getSuccessfulItems(): array
	{
		return array_filter(
			$this->items,
			fn(Item $item) => PhaseSemantics::isSuccess($this->getItemSemantics($item)),
		);
	}

	public function getTotalOpportunityOfSuccessfulItems(): float
	{
		$sum = 0.0;
		$currencyOfTotal = $this->getCurrencyIdOfSuccessfulItems();
		foreach ($this->getSuccessfulItems() as $item)
		{
			$sum += Currency\Conversion::toSpecifiedCurrency($item->getOpportunity(), $item->getCurrencyId(), $currencyOfTotal);
		}

		return $sum;
	}

	public function getCurrencyIdOfSuccessfulItems(): ?string
	{
		if ($this->isAccountCurrencyIdUsed)
		{
			return Currency::getAccountCurrencyId();
		}

		$successfulItems = $this->getSuccessfulItems();
		if (empty($successfulItems))
		{
			return null;
		}

		$currencies = array_unique(
			array_map(fn(Item $item) => $item->getCurrencyId(), $successfulItems),
		);
		if (count($currencies) === 1)
		{
			return reset($currencies);
		}

		$this->isAccountCurrencyIdUsed = true;

		return Currency::getAccountCurrencyId();
	}

	public function isAccountCurrencyIdUsed(): bool
	{
		return $this->isAccountCurrencyIdUsed;
	}

	public function getLostItemsCount(): int
	{
		return count(
			array_filter(
				$this->items,
				fn(Item $item) => PhaseSemantics::isLost($this->getItemSemantics($item)),
			),
		);
	}

	private function getItemSemantics(Item $item): ?string
	{
		if ($item->hasField(Item::FIELD_NAME_STAGE_SEMANTIC_ID))
		{
			// fast and efficient way for items that support it
			return $item->getStageSemanticId();
		}

		return ComparerBase::getStageSemantics($item->getEntityTypeId(), $item->getStageId());
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function getItemIdentifierList(): array
	{
		return array_map(fn(Item $item) => ItemIdentifier::createByItem($item), $this->items);
	}

	public function getLatestItemIdentifier(): ?ItemIdentifier
	{
		$item = reset($this->items);

		return $item ? ItemIdentifier::createByItem($item) : null;
	}

	public function getLatestClosedItemIdentifier(): ?ItemIdentifier
	{
		foreach ($this->items as $item)
		{
			if (PhaseSemantics::isFinal($this->getItemSemantics($item)))
			{
				return ItemIdentifier::createByItem($item);
			}
		}

		return null;
	}

	public function getLatestCallActivityId(): ?int
	{
		return $this->latestSummarizeCallResult?->getTarget()?->getEntityId();
	}

	public function getLatestCallActivityOwner(): ?ItemIdentifier
	{
		return $this->getLatestItemIdentifier();
	}

	public function getLatestWebFormActivityId(): ?int
	{
		return $this->latestWebformActivityId;
	}

	public function getLatestWebFormActivityOwner(): ?ItemIdentifier
	{
		return $this->getLatestItemIdentifier();
	}
}
