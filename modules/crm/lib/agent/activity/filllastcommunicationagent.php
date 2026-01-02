<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\LastCommunication\SyncLastCommunication;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Config\Option;

final class FillLastCommunicationAgent extends AgentBase
{
	private const MODULE = 'crm';
	private const DEFAULT_LIMIT_VALUE = 250;
	private const OPTION_LIMIT_VALUE = 'fill_last_communication_table_option_limit_value';
	private const OPTION_LAST_FILLED_ACTIVITY_ID = 'fill_last_communication_table_option_last_activity_value';
	private const OPTION_CURRENT_PROVIDER = 'fill_last_communication_table_option_current_provider';

	private array $providers = [];
	private ?string $currentProvider = null;

	public static function doRun(): bool
	{
		return (new self())->execute();
	}

	private function execute(): bool
	{
		$this->providers = (new SyncLastCommunication())->getAllowedProviders();
		$this->currentProvider = Option::get(self::MODULE, self::OPTION_CURRENT_PROVIDER, $this->providers[0]);

		$activityIds = $this->getActivityIds();

		if (empty($activityIds))
		{
			if (!$this->setNextProvider())
			{
				$this->cleanAfterWork();

				return false;
			}

			Option::set(self::MODULE, self::OPTION_CURRENT_PROVIDER, $this->currentProvider);
			Option::set(self::MODULE, self::OPTION_LAST_FILLED_ACTIVITY_ID, '0');

			return true;
		}

		$activities = ActivityTable::query()
			->setSelect(['ID', 'PROVIDER_ID', 'CREATED'])
			->whereIn('ID', $activityIds)
			->setOrder(['ID' => 'ASC'])
			->exec()
		;

		$lastId = '';
		$counter = 0;
		while($item = $activities->fetch())
		{
			if (!$item['CREATED'])
			{
				continue;
			}

			(new SyncLastCommunication())
				->upsertLastCommunication((int)$item['ID'], $item['PROVIDER_ID'], $item['CREATED'])
			;

			$lastId = $item['ID'];
			$counter++;
			if ($counter % 10 === 0)
			{
				Option::set(self::MODULE, self::OPTION_LAST_FILLED_ACTIVITY_ID, $lastId);
			}
		}

		$limit = (int)(Option::get(self::MODULE, self::OPTION_LIMIT_VALUE, null) ?? self::DEFAULT_LIMIT_VALUE);

		if ($counter < $limit)
		{
			$lastId = 0;
			if (!$this->setNextProvider())
			{
				$this->cleanAfterWork();

				return false;
			}
		}

		Option::set(self::MODULE, self::OPTION_LAST_FILLED_ACTIVITY_ID, $lastId);
		Option::set(self::MODULE, self::OPTION_CURRENT_PROVIDER, $this->currentProvider);

		return true;
	}

	private function getActivityIds(): array
	{
		$currentProvider = Option::get(self::MODULE, self::OPTION_CURRENT_PROVIDER, $this->providers[0]);

		$limit = Option::get(self::MODULE, self::OPTION_LIMIT_VALUE, null) ?? (string)self::DEFAULT_LIMIT_VALUE;
		$lastId = Option::get(self::MODULE, self::OPTION_LAST_FILLED_ACTIVITY_ID, null) ?? '0';

		return array_column(ActivityTable::query()
			->setSelect(['ID'])
			->where('PROVIDER_ID', $currentProvider)
			->where('ID', '>', $lastId)
			->setOrder(['ID' => 'ASC'])
			->setLimit($limit)
			->fetchAll(), 'ID');
	}

	private function setNextProvider(): bool
	{
        $currentProviderKey = array_search($this->currentProvider, $this->providers, true);

		if ($currentProviderKey !== false )
		{
			$this->currentProvider = $this->providers[$currentProviderKey + 1] ?? null;
		}
		else
		{
			$this->currentProvider = null;
		}

		return (bool)$this->currentProvider;
	}

	private function cleanAfterWork(): void
	{
		\COption::RemoveOption(self::MODULE, 'enable_last_communication_fields');
		\COption::RemoveOption(self::MODULE, self::OPTION_LAST_FILLED_ACTIVITY_ID);
		\COption::RemoveOption(self::MODULE, self::OPTION_CURRENT_PROVIDER);
	}
}
