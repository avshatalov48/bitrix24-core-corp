<?php
namespace Bitrix\Crm\Update\Product;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Crm;

class PriceAccount extends Main\Update\Stepper
{
	protected const PAGE_SIZE = 100;

	protected const QUERY_UPDATE = 'UPDATE';
	protected const QUERY_WHERE = 'WHERE';

	protected static $moduleId = 'crm';

	/** @var Main\Data\Connection|Main\DB\Connection */
	protected $connection;
	/** @var Main\DB\SqlHelper */
	protected $sqlHelper;
	/** @var array */
	protected $query;
	/** @var array */
	protected $entityCache;

	public function __destruct()
	{
		unset($this->entityCache);
		unset($this->query);
		unset($this->sqlHelper);
		unset($this->connection);

		parent::__destruct();
	}

	public static function getTitle()
	{
		return Loc::getMessage('CRM_PRODUCT_ROW_PRICE_ACCOUNT_UPDATER_TITLE');
	}

	public function execute(array &$option): bool
	{
		$result = Main\Update\Stepper::FINISH_EXECUTION;
		if (
			!ModuleManager::isModuleInstalled('bitrix24')
			|| !Loader::includeModule('crm')
		)
		{
			return $result;
		}

		$this->initialize();

		if (empty($option) || empty($option['count']))
		{
			$option = $this->getCurrentStatus();
		}

		if (empty($option))
		{
			return $result;
		}

		$option = $this->normalizeOptions($option);

		if ($option['count'] > 0)
		{
			$option = $this->updatePriceAccount($option);
		}
		if (!empty($option) && $option['count'] > 0)
		{
			$result = Main\Update\Stepper::CONTINUE_EXECUTION;
		}

		return $result;
	}

	protected function initialize(): void
	{
		$this->connection = Main\Application::getConnection();
		$this->sqlHelper = $this->connection->getSqlHelper();

		$this->query = [
			self::QUERY_UPDATE => 'update ' . $this->sqlHelper->quote(Crm\ProductRowTable::getTableName())
				. ' set '.$this->sqlHelper->quote('PRICE_ACCOUNT') . ' = \'',
			self::QUERY_WHERE => '\' where '.$this->sqlHelper->quote('ID').' = '
		];

		$this->entityCache = [];
	}

	protected function getCurrentStatus(): array
	{
		return $this->getDefaultProgress(Crm\ProductRowTable::getCount(
			$this->getProductFilter()
		));
	}

	protected function getDefaultProgress(int $count): array
	{
		return [
			'lastId' => 0,
			'steps' => 0,
			'errors' => 0,
			'count' => $count,
		];
	}

	protected function normalizeOptions(array $options): array
	{
		return [
			'lastId' => (int)($options['lastId'] ?? 0),
			'steps' => (int)($options['steps'] ?? 0),
			'errors' => (int)($options['errors'] ?? 0),
			'count' => (int)($options['count'] ?? 0),
		];
	}

	protected function updatePriceAccount(array $options): array
	{
		$found = false;
		$iterator = Crm\ProductRowTable::getList(
			$this->getProductListParameters($options)
		);
		while ($row = $iterator->fetch())
		{
			$found = true;
			$row['ID'] = (int)$row['ID'];
			$row['OWNER_ID'] = (int)$row['OWNER_ID'];
			$row['PRICE'] = (float)$row['PRICE'];

			$ownerData = $this->getOwnerData($row);
			if (!empty($ownerData))
			{
				$accountData = \CCrmAccountingHelper::PrepareAccountingData([
					'CURRENCY_ID' => $ownerData['CURRENCY_ID'],
					'SUM' => $row['PRICE'] ?? null,
					'EXCH_RATE' => $ownerData['EXCH_RATE'] ?? null
				]);

				if (is_array($accountData))
				{
					$this->connection->query($this->query[self::QUERY_UPDATE]
						. $accountData['ACCOUNT_SUM']
						. $this->query[self::QUERY_WHERE] . $row['ID']
					);
				}
			}
			$options['lastId'] = $row['ID'];
			$options['steps']++;
		}

		if (!$found)
		{
			$options = [];
		}

		return $options;
	}

	protected function getProductListParameters(array $options): array
	{
		return [
			'select' => [
				'ID',
				'OWNER_TYPE',
				'OWNER_ID',
				'PRICE',
			],
			'filter' => $this->getProductFilter($options),
			'order' => [
				'ID' => 'ASC',
			],
			'limit' => $this->getPageSize($options),
		];
	}

	protected function getProductFilter(array $options = []): array
	{
		$result = [
			'@OWNER_TYPE' => [
				\CCrmOwnerTypeAbbr::Deal,
				\CCrmOwnerTypeAbbr::Lead,
				\CCrmOwnerTypeAbbr::Quote,
			],
			'>PRICE' => 0,
			'=PRICE_ACCOUNT' => 0
		];
		if (
			isset($options['lastId'])
			&& $options['lastId'] > 0
		)
		{
			$result['>ID'] = $options['lastId'];
		}

		return $result;
	}

	protected function getOwnerData(array $row): ?array
	{
		$cacheId = $row['OWNER_TYPE'] . '-' . $row['OWNER_ID'];
		if (!isset($this->entityCache[$cacheId]))
		{
			$this->entityCache[$cacheId] = $this->getEntityRow($row);
		}

		return !empty($this->entityCache[$cacheId]) ? $this->entityCache[$cacheId] : null;
	}

	protected function getEntityRow(array $row): array
	{
		$ownerType = $row['OWNER_TYPE'];
		$parameters = $this->getEntityRowParameters($ownerType, $row['OWNER_ID']);

		$result = null;
		switch ($ownerType)
		{
			case \CCrmOwnerTypeAbbr::Deal:
				$result = Crm\DealTable::getRow($parameters);
				break;
			case \CCrmOwnerTypeAbbr::Lead:
				$result = Crm\LeadTable::getRow($parameters);
				break;
			case \CCrmOwnerTypeAbbr::Quote:
				$result = Crm\QuoteTable::getRow($parameters);
				break;
		}

		return $result ?? [];
	}

	protected function getEntityRowParameters(string $type, int $id): array
	{
		$result = [];

		$result['select'] = [
			'ID',
			'CURRENCY_ID',
		];
		if (
			$type === \CCrmOwnerTypeAbbr::Deal
			|| $type === \CCrmOwnerTypeAbbr::Quote
		)
		{
			$result['select'][] = 'EXCH_RATE';
		}

		$result['filter'] = ['=ID' => $id];

		return $result;
	}

	protected function getPageSize(array $options): int
	{
		return self::PAGE_SIZE;
	}
}
