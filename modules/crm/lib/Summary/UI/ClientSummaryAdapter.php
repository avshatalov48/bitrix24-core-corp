<?php

namespace Bitrix\Crm\Summary\UI;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Format\Money;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Broker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Summary\ClientSummary;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

final class ClientSummaryAdapter
{
	private const ACTIVITY_PLANER_ENDPOINT = '/bitrix/components/bitrix/crm.activity.planner/slider.php';

	public function __construct(
		private ClientSummary $summary,
		private ?Router $router = null,
		private ?Factory $itemsFactory = null,
		private Broker\Contact|Broker\Company|null $clientBroker = null,
	)
	{
		$this->router ??= Container::getInstance()->getRouter();
		$this->itemsFactory ??= Container::getInstance()->getFactory($this->summary->getItemsEntityTypeId());
		if ($this->itemsFactory?->getEntityTypeId() !== $this->summary->getItemsEntityTypeId())
		{
			throw new ArgumentException('Factory should be same type as items in summary');
		}

		$this->clientBroker ??= Container::getInstance()->getEntityBroker(
			$this->summary->getClientIdentifier()->getEntityTypeId()
		);
	}

	/**
	 * Default method to show a total sum of summary in the UI.
	 * If there is not enough data to render, it will fall back to an empty value placeholder.
	 *
	 * @return string
	 */
	public function renderTotalOpportunity(): string
	{
		$rendered = $this->renderOpportunityWithCurrency();
		if ($rendered === null)
		{
			return Money::format(0, Currency::getBaseCurrencyId());
		}

		if ($this->summary->isAccountCurrencyIdUsed())
		{
			return (string)Loc::getMessage(
				'CRM_SUMMARY_CLIENT_UI_ACCOUNT_CURRENCY_USED',
				['#OPPORTUNITY_WITH_CURRENCY#' => $rendered],
			);
		}

		return $rendered;
	}

	/**
	 * Render just opportunity with currency, like $1. If there is not enough data to render, will return null.
	 *
	 * @return string|null
	 */
	public function renderOpportunityWithCurrency(): ?string
	{
		$opportunity = $this->summary->getTotalOpportunityOfSuccessfulItems();
		$currency = $this->summary->getCurrencyIdOfSuccessfulItems();

		if (is_string($currency))
		{
			return Money::format($opportunity, $currency);
		}

		return null;
	}

	public function getItemListSliderEndpoint(): ?Uri
	{
		return $this->prepareItemListEndpoint();
	}

	public function getSuccessfulItemListSliderEndpoint(): ?Uri
	{
		return $this->prepareItemListEndpoint(['STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS]);
	}

	public function getLostItemListSliderEndpoint(): ?Uri
	{
		return $this->prepareItemListEndpoint(['STAGE_SEMANTIC_ID' => PhaseSemantics::FAILURE]);
	}

	private function prepareItemListEndpoint(array $addToGridFilter = []): ?Uri
	{
		$endpoint = $this->router->getItemListSliderUrl($this->summary->getItemsEntityTypeId());
		if (!$endpoint)
		{
			return null;
		}

		$columns = [
			// it was composed for crm.deal.list - may be other types column names will be different
			$this->itemsFactory->getEntityFieldNameByMap(Item::FIELD_NAME_CREATED_TIME),
			$this->itemsFactory->getEntityName() . '_SUMMARY',
			'SUM',
			'ASSIGNED_BY',
			$this->itemsFactory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID),
		];

		$listComponentIncludeParams = [
			'template' => '',
			'signedParameters' => \CCrmInstantEditorHelper::signComponentParams(
				[
					'DEFAULT_COLUMNS' => $columns,
					'COLUMNS_ORDER' => $columns,
					'GRID_ID_SUFFIX' => 'CLIENT_SUMMARY_' . $this->summary->getClientIdentifier()->getEntityTypeId(),
				],
				str_replace('bitrix:', '', $this->router->getItemListComponentName($this->summary->getItemsEntityTypeId())),
			),
		];

		$clientEntityTypeName = \CCrmOwnerType::ResolveName($this->summary->getClientIdentifier()->getEntityTypeId());

		$queryParams = [
			// slider endpoint params
			'PARAMS' => $listComponentIncludeParams,
			'site' => SITE_ID,

			// predefined grid filter
			'apply_filter' => 'Y',
			"{$clientEntityTypeName}_ID" => Json::encode([
				$clientEntityTypeName => [$this->summary->getClientIdentifier()->getEntityId()],
			]),
			"{$clientEntityTypeName}_ID_label" => $this->getClientHeading(),
		] + $addToGridFilter;

		return $endpoint->addParams(array_filter($queryParams));
	}

	private function getClientHeading(): ?string
	{
		if ($this->clientBroker instanceof Broker\Contact)
		{
			return $this->clientBroker->getFormattedName($this->summary->getClientIdentifier()->getEntityId());
		}

		if ($this->clientBroker instanceof Broker\Company)
		{
			return $this->clientBroker->getTitle($this->summary->getClientIdentifier()->getEntityId());
		}

		return null;
	}

	public function getLatestItemUrl(): ?Uri
	{
		$item = $this->summary->getLatestItemIdentifier();

		return $item ? $this->router->getItemDetailUrl($item->getEntityTypeId(), $item->getEntityId()) : null;
	}

	public function getLatestClosedItemUrl(): ?Uri
	{
		$item = $this->summary->getLatestClosedItemIdentifier();

		return $item ? $this->router->getItemDetailUrl($item->getEntityTypeId(), $item->getEntityId()) : null;
	}

	/**
	 * Returns params for BX.Crm.AI.Call.Summary. You can use \CUtil::PhpToJSObject to inject these params to frontend
	 *
	 * @see \CUtil::PhpToJSObject()
	 *
	 * @return array|null - returns null if there was no activity found
	 */
	public function getLatestCallSummaryJsParams(): ?array
	{
		$owner = $this->summary->getLatestCallActivityOwner();
		$activityId = $this->summary->getLatestCallActivityId();

		if ($owner === null || $activityId === null)
		{
			return null;
		}

		return [
			'ownerTypeId' => $owner->getEntityTypeId(),
			'ownerId' => $owner->getEntityId(),
			'activityId' => $activityId,
		];
	}

	public function getLatestWebFormSliderEndpoint(): ?Uri
	{
		$activityId = $this->summary->getLatestWebFormActivityId();
		if ($activityId === null)
		{
			return null;
		}

		$endpoint = new Uri(self::ACTIVITY_PLANER_ENDPOINT);

		return $endpoint->addParams([
			'site_id' => SITE_ID,
			'ajax_action' => 'ACTIVITY_VIEW',
			'activity_id' => $activityId,
		]);
	}
}
