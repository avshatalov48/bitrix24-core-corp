<?php

namespace Bitrix\BIConnector\Integration\Crm\Tracking\ExpensesProvider;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Seo;

final class Provider
{
	private Seo\Analytics\Account $account;

	public function __construct(
		protected readonly int $id,
		protected readonly string $name,
		protected readonly string $seoCode,
		protected readonly ?string $accountId,
		protected readonly ?string $clientId
	)
	{
		$service = Seo\Analytics\Service::getInstance()->setClientId($this->clientId);
		$this->account = $service::getAccount($this->seoCode);
	}

	/**
	 * Result data: array<array{
	 *       SOURCE_ID: int,
	 *       EXPENSES: float,
	 *       DATE?: ?Date,
	 *       CAMPAIGN_NAME: string,
	 *       CAMPAIGN_ID: string,
	 *         GROUP_NAME: string,
	 *       GROUP_ID: string,
	 *       AD_NAME: string,
	 *       AD_ID: string,
	 *       CURRENCY: string,
	 *       CPM: float,
	 *       CPC: float,
	 *       CLICKS: int,
	 *       IMPRESSIONS: int,
	 *       ACTIONS: int,
	 *         UTM_MEDIUM: string,
	 *       UTM_SOURCE: string,
	 *       UTM_CAMPAIGN: string,
	 *       UTM_CONTENT: string
	 *   }>
	 *
	 * @return Result
	 */
	public function getDailyExpenses(?Date $dateFrom, ?Date $dateTo): Result
	{
		$result = new Result();
		if ($this->account->hasAccounts() && !$this->accountId)
		{
			return $result->setData([]);
		}

		if (!$this->account->hasDailyExpensesReport())
		{
			return $result->setData([]);
		}

		Seo\Analytics\Service::getInstance()->setClientId($this->clientId);
		$accountResult = $this->account->getDailyExpensesReport($this->accountId, $dateFrom, $dateTo);
		if (!$accountResult->isSuccess())
		{
			foreach ($accountResult->getErrors() as $error)
			{
				$result->addError($this->wrapErrorToSourceIdPrefix($error));
			}

			return $result;
		}

		/** @var Seo\Analytics\Internals\ExpensesCollection $expensesCollection */
		$expensesCollection = $accountResult->getData()['expenses'] ?? null;
		if (!$expensesCollection instanceof Seo\Analytics\Internals\ExpensesCollection)
		{
			$collectionClassName = get_class($expensesCollection);
			$error = new Error("[{$this->name}] Daily expenses result data expected 'Bitrix\Seo\Analytics\Internals\ExpensesCollection' instance, '{$collectionClassName}' got");
			$result->addError($this->wrapErrorToSourceIdPrefix($error));

			return $result;
		}

		$expensesResult = [];
		/** @var Seo\Analytics\Internals\Expenses $expenses */
		foreach ($expensesCollection as $expenses)
		{
			$expensesResult[] = $this->parseRow($expenses);
		}

		return $result->setData($expensesResult);
	}

	/**
	 * @param Seo\Analytics\Internals\Expenses $row
	 * @return array{
	 *     SOURCE_ID: int,
	 *     EXPENSES: float,
	 *     DATE?: ?Date,
	 *     CAMPAIGN_NAME: string,
	 *     CAMPAIGN_ID: string,
	 *     GROUP_NAME: string,
	 *     GROUP_ID: string,
	 *     AD_NAME: string,
	 *     AD_ID: string,
	 *     CURRENCY: string,
	 *     CPM: float,
	 *     CPC: float,
	 *     CLICKS: int,
	 *     IMPRESSIONS: int,
	 *     ACTIONS: int,
	 *     UTM_MEDIUM: string,
	 *     UTM_SOURCE: string,
	 *     UTM_CAMPAIGN: string,
	 *     UTM_CONTENT: string
	 * }
	 */
	private function parseRow(Seo\Analytics\Internals\Expenses $row): array
	{
		return [
			'SOURCE_ID' => $this->id,
			'EXPENSES' => $row->getSpend(),
			'DATE' => $row->getDate()?->format('Y-m-d H:i:s'),
			'CAMPAIGN_NAME' => $row->getCampaignName(),
			'CAMPAIGN_ID' => $row->getCampaignId(),
			'GROUP_NAME' => $row->getGroupName(),
			'GROUP_ID' => $row->getGroupId(),
			'AD_NAME' => $row->getAdName(),
			'AD_ID' => $row->getAdId(),
			'CURRENCY' => $row->getCurrency(),
			'CPM' => $row->getCpm(),
			'CPC' => $row->getCpc(),
			'CLICKS' => $row->getClicks(),
			'IMPRESSIONS' => $row->getImpressions(),
			'ACTIONS' => $row->getActions(),
			'UTM_MEDIUM' => $row->getUtmMedium(),
			'UTM_SOURCE' => $row->getUtmSource(),
			'UTM_CAMPAIGN' => $row->getUtmCampaign(),
			'UTM_CONTENT' => $row->getUtmContent(),
		];
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function getUtmSources(): array
	{
		return \Bitrix\Crm\Tracking\Internals\SourceFieldTable::getSourceField(
			$this->getId(),
			\Bitrix\Crm\Tracking\Internals\SourceFieldTable::FIELD_UTM_SOURCE
		);
	}

	private function wrapErrorToSourceIdPrefix(Error $error): Error
	{
		return new Error("[source id: {$this->getId()}]{$error->getMessage()}", $error->getCode());
	}
}
