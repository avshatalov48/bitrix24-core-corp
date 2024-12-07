<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Exception;
use Throwable;

final class Preset extends Stepper
{
	protected static $moduleId = 'tasks';
	private const LIMIT = 500;
	private const FILTER_CATEGORY = 'main.ui.filter';
	private const REMOVE_CONTROL = 'tasks_disable_role_control';

	private int $lastId;
	private array $filters = [];

	public static function isRolePresetsEnabled(): bool
	{
		return Option::get(self::$moduleId, self::REMOVE_CONTROL, 'N') === 'Y';
	}

	public function execute(array &$option): bool
	{
		try
		{
			$this
				->setLastId( $option['lastId'] ?? 0)
				->setFilters();
			if (empty($this->filters))
			{
				Option::set(self::$moduleId, self::REMOVE_CONTROL, 'Y');
				return self::FINISH_EXECUTION;
			}

			$this
				->convertFilters()
				->setOptions($option);
		}
		catch (Throwable $throwable)
		{
			$this->writeToLog($throwable);
		}

		return self::CONTINUE_EXECUTION;
	}

	private function convertFilters(): self
	{
		$connection = Application::getConnection();
		foreach ($this->filters as $filter)
		{
			if ($this->isScrumFilter($filter))
			{
				continue;
			}

			if ($filter['VALUE'] === false)
			{
				continue;
			}

			$rolePresets = $this->makeRolePresets($filter);
			$currentPresets = $this->clearPresets(is_array($filter['VALUE']['filters'] ?? null) ? $filter['VALUE']['filters'] : []);
			$filter['VALUE']['filters'] = array_merge($currentPresets, $rolePresets);
			$value = serialize($filter['VALUE']);
			$value = $connection->getSqlHelper()->forSql($value);
			try
			{
				$connection->query("UPDATE b_user_option SET VALUE = '{$value}' WHERE ID = {$filter['ID']}");
			}
			catch (SqlQueryException $exception)
			{
				$message = [
					'entityId' => $filter['ID'],
					'userId' => $filter['USER_ID'],
					'error' => $exception->getMessage(),
					'value' => $value,
				];
				LogFacade::log($message);
			}
			$this->setLastId($filter['ID']);
		}

		return $this;
	}

	private function setOptions(array &$options): self
	{
		$options['lastId'] = $this->lastId;
		return $this;
	}

	private function makeRolePresets(array $filter): array
	{
		$filters = $this->clearPresets($filter['VALUE']['filters'] ?? []);
		$filterSorts = array_map(
			static fn ($item): int => is_array($item) ? (int)($item['sort'] ?? null) : 0,
			$filters
		);
		$maxSort = empty($filterSorts) ? 0 : max($filterSorts);
		$counter = $maxSort + 1;
		$rolePresets = Filter::getRolePresets();

		foreach ($rolePresets as &$item)
		{
			$item['sort'] = $counter;
			$counter++;
		}

		return $rolePresets;
	}

	private function setLastId(int $id = 0): self
	{
		$this->lastId = $id;
		return $this;
	}

	/**
	 * @throws SqlQueryException
	 */
	private function setFilters(): self
	{
		$filterRows = Application::getConnection()->query($this->getQuery())->fetchAll();

		$this->filters = array_map(static function (array $filter): array {
			$filter['ID'] = (int)$filter['ID'];
			$filter['USER_ID'] = (int)$filter['USER_ID'];
			$filter['VALUE'] = unserialize($filter['VALUE'], ['allowed_classes' => false]);
			return $filter;
		}, $filterRows);

		return $this;
	}

	private function getQuery(): string
	{
		$category = self::FILTER_CATEGORY;
		$name = '%' . mb_strtoupper(self::$moduleId) . '%_ROLE_ID_%';
		$limit = self::LIMIT;

		return "
			select *
			from b_user_option
			where category='{$category}' and name like '{$name}' and id > {$this->lastId}
			limit {$limit}
		";
	}

	private function isScrumFilter(array $filter): bool
	{
		return in_array(Filter::SCRUM_PRESET, array_keys($filter['VALUE']['default_presets'] ?? []), true);
	}

	private function clearPresets(array $filter): array
	{
		unset($filter[Filter::RESPONSIBLE_PRESET]);
		unset($filter[Filter::ACCOMPLICE_PRESET]);
		unset($filter[Filter::ORIGINATOR_PRESET]);
		unset($filter[Filter::AUDITOR_PRESET]);

		return $filter;
	}
}
