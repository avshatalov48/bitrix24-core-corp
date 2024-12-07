<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\Synchronization\Dto\RuleDto;
use Bitrix\AI\Synchronization\Enum\SyncMode;
use Bitrix\AI\Synchronization\Repository\BaseDisplayRuleRepository;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\DateTime;

abstract class BaseSync implements SyncInterface
{
	protected DataManager $dataManager;
	protected string $region;

	/**
	 * Return DataManager
	 *
	 * @return DataManager
	 */
	abstract protected function getDataManager(): DataManager;

	protected function getQueryBuilder(): Query
	{
		return $this->getDataManager()::query();
	}

	protected function add(array $fields): AddResult
	{
		return $this->getDataManager()::add($fields);
	}

	protected function delete(string $id): DeleteResult
	{
		return $this->getDataManager()::delete($id);
	}

	protected function update(string $id, array $fields): UpdateResult
	{
		return $this->getDataManager()::update($id, $fields);
	}

	/**
	 * Returns ids by filter
	 *
	 * @param array $filter
	 *
	 * @return array|null
	 */
	protected function getIdsByFilter(array $filter): array|null
	{
		$query = $this->getQueryBuilder()->setSelect(['ID']);
		$query->setFilter($filter);
		$ids = $query->fetchAll();

		return array_column($ids, 'ID');
	}

	/**
	 * Return result of add or update
	 *
	 * @param array $fields
	 *
	 * @return AddResult|UpdateResult
	 */
	protected function addOrUpdate(array $fields, ?array $rules = null): AddResult|UpdateResult
	{
		$fields = array_change_key_case($fields, CASE_UPPER);
		if (is_null($rules))
		{
			$rules = $this->getRules($fields['RULES'] ?? []);
		}

		if (array_key_exists('RULES', $fields))
		{
			unset($fields['RULES']);
		}

		$filterExists = [];
		$exists = null;

		if (array_key_exists('CODE', $fields))
		{
			$filterExists['=CODE'] = $fields['CODE'];
		}

		if (!empty($filterExists))
		{
			$exists = $this->getByFilter($filterExists);
		}

		if (!array_key_exists('RULE', $fields))
		{
			unset($fields['RULES']);
		}

		if (is_null($exists))
		{
			$result = $this->add($fields);
			if ($result->isSuccess())
			{
				$this->updateRules((int)$result->getId(), $rules);
			}

			return $result;
		}

		if ($exists['HASH'] === ($fields['HASH'] ?? null))
		{
			return $this->getFakeUpdateResult($exists['ID']);
		}

		$fields['DATE_MODIFY'] = new DateTime();

		$result = $this->update($exists['ID'], $fields);
		if ($result->isSuccess())
		{
			$this->updateRules((int)$exists['ID'], $rules, true);
		}

		return $result;
	}

	protected function getFakeUpdateResult(string $id): UpdateResult
	{
		$result = new UpdateResult();
		$result->setPrimary(['ID' => $id]);
		return $result;
	}

	protected function getByFilter(array $filter): array|null
	{
		$item = $this->getQueryBuilder()
			->setSelect(['ID', 'HASH'])
			->setFilter($filter)
			->setLimit(1)
			->fetch()
		;

		return is_array($item) ? $item : null;
	}

	protected function log(string $message): void
	{
		AddMessage2Log($message);
	}

	/**
	 * Synchronization table with items by filter
	 *
	 * @param array $items
	 * @param array $filter
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function sync(array $items, array $filter = [], SyncMode $mode = SyncMode::Standard): void
	{
		if ($mode !== SyncMode::Partitional)
		{
			$oldIds = $this->getIdsByFilter($filter);
		}

		$currentIds = [];
		foreach ($items as $item)
		{
			$rules = $this->getRules($item['rules'] ?? []);
			if ($this->hasRuleForHidden($rules, $item))
			{
				continue;
			}

			$result = $this->addOrUpdate($item, $rules);
			if ($result->isSuccess())
			{
				$currentIds[] = $result->getId();
			}
			else
			{
				$this->log('AI_DB_SYNC_ERROR: ' . implode('; ', $result->getErrorMessages()));
			}
		}

		if ($mode === SyncMode::Partitional)
		{
			return;
		}

		$idsForDelete = array_diff($oldIds, $currentIds);
		foreach ($idsForDelete as $id)
		{
			$this->delete($id);
		}
	}

	/**
	 * @param array $codes
	 *
	 * @return void
	 */
	public function deleteByCodes(array $codes): void
	{
		if (empty($codes))
		{
			return;
		}

		$items = $this->getDataManager()::query()->setSelect(['ID'])->whereIn('CODE', $codes)->fetchAll();
		foreach ($items as $item)
		{
			$this->delete($item['ID']);
		}
	}

	/**
	 * @param int $entityId
	 * @param RuleDto[] $rules
	 * @param bool $needDeleteOld
	 * @return void
	 */
	protected function updateRules(int $entityId, array $rules, bool $needDeleteOld = false): void
	{
		$repository = $this->getDisplayRuleRepository();
		if (empty($repository))
		{
			return;
		}

		if ($needDeleteOld)
		{
			$repository->deleteByEntityId($entityId);
		}

		if (empty($rules))
		{
			return;
		}

		$repository
			->addRulesForEntityId(
				$entityId,
				array_filter($rules, fn($rule) => $rule->getRuleName() == RuleName::Lang)
			)
		;
	}

	/**
	 * @param $rules
	 * @return RuleDto[]
	 */
	protected function getRules($rules): array
	{
		if (empty($rules))
		{
			return [];
		}

		$result = [];
		foreach ($rules as $rule)
		{
			if (
				!array_key_exists('IS_CHECK_INVERT', $rule)
				|| !array_key_exists('NAME', $rule)
				|| !array_key_exists('VALUES', $rule)
			)
			{
				continue;
			}

			$ruleName = RuleName::tryFrom($rule['NAME']);
			if (empty($rule['VALUES']) || is_null($ruleName))
			{
				continue;
			}

			foreach ($rule['VALUES'] as $value)
			{
				$result[] = new RuleDto((bool)$rule['IS_CHECK_INVERT'], $ruleName, (string)$value);
			}
		}

		return $result;
	}

	/**
	 * @param RuleDto[] $rules
	 * @param array $item
	 * @return bool
	 */
	protected function hasRuleForHidden(array $rules, array $item = []): bool
	{
		if (empty($rules))
		{
			return false;
		}

		$hasRuleForShow = false;
		$hasRuleWithForCheck = false;
		foreach ($rules as $rule)
		{
			if ($rule->getRuleName() !== RuleName::Region)
			{
				continue;
			}

			$hasRuleWithForCheck = true;
			if ($rule->isCheckInvert())
			{
				if ($rule->getValue() === $this->getRegion())
				{
					return true;
				}

				$hasRuleForShow = true;
				continue;
			}

			if (!$hasRuleForShow && ($rule->getValue() === $this->getRegion()))
			{
				$hasRuleForShow = true;
			}
		}

		if (!$hasRuleWithForCheck)
		{
			return false;
		}

		return !$hasRuleForShow;
	}

	protected function getRegion(): ?string
	{
		if (empty($this->region))
		{
			$this->region = Application::getInstance()->getLicense()->getRegion();
		}

		return $this->region;
	}

	protected function getDisplayRuleRepository(): ?BaseDisplayRuleRepository
	{
		return null;
	}
}
