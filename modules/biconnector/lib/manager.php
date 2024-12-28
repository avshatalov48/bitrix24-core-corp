<?php
namespace Bitrix\BIConnector;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class Manager
{
	const IS_VALID_URL = 1;
	const IS_EXTERNAL_URL = 2;

	protected static $instance = null;
	/** @deprecated @var $dataSources array  */
	protected $dataSources = [];
	protected $connectionName = '';
	protected $serviceId = '';
	protected $keyId = 0;
	protected $stime = [];

	/**
	 * Singleton instance production.
	 *
	 * @return Manager
	 */
	public static function getInstance(): Manager
	{
		if (static::$instance === null)
		{
			static::$instance = new Manager();
		}

		return static::$instance;
	}

	/**
	 * Fires OnBIConnectorCreateServiceInstance event and returns first matched service object instance.
	 *
	 * @param string $serviceId Service identifier.
	 *
	 * @return null|Service
	 */
	public function createService($serviceId)
	{
		$this->serviceId = $serviceId;

		$event = new \Bitrix\Main\Event('biconnector', 'OnBIConnectorCreateServiceInstance', [$serviceId, $this]);
		$event->send();
		foreach ($event->getResults() as $evenResult)
		{
			if ($evenResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				/** @var \Bitrix\BIConnector\Service $service */
				$service = $evenResult->getParameters();
				if ($service)
				{
					$this->serviceId = $service::getServiceId();
					return $service;
				}
			}
		}

		return null;
	}

	/**
	 * @deprecated
	 *
	 * Fires OnBIConnectorDataSources event to gather all available data sources.
	 *
	 * @param string $languageId Interface language.
	 *
	 * @return void
	 */
	protected function init($languageId = '')
	{
		if (!isset($this->dataSources[$languageId]))
		{
			$this->dataSources[$languageId] = [];
			$event = new \Bitrix\Main\Event('biconnector', 'OnBIConnectorDataSources', [
				$this,
				&$this->dataSources[$languageId],
				$languageId,
			]);
			$event->send();
		}
	}

	/**
	 * @deprecated
	 *
	 * Returns all available data sources descriptions.
	 *
	 * @param string $languageId Interface language.
	 *
	 * @return array
	 */
	public function getDataSources($languageId = '')
	{
		$this->init($languageId);

		return $this->dataSources[$languageId];
	}

	/**
	 * @deprecated
	 *
	 * Returns data source description by its code.
	 *
	 * @param string $table Data source code.
	 * @param string $languageId Interface language.
	 *
	 * @return null|array
	 */
	public function getTableDescription($table, $languageId = '')
	{
		$this->init($languageId);

		return $this->dataSources[$languageId][$table] ?? null;
	}

	/**
	 * Checks if the key exists and active.
	 * Strores key connection name for future connect.
	 *
	 * @param string $key Access key.
	 *
	 * @return bool
	 */
	public function checkAccessKey($key)
	{
		if ($key)
		{
			$dbKey = KeyTable::getList([
				'select' => [
					'ID',
					'CONNECTION',
				],
				'filter' => [
					'=ACCESS_KEY' => $key,
					'=ACTIVE' => 'Y',
				],
			])->fetch();
			if ($dbKey)
			{
				$this->connectionName = $dbKey['CONNECTION'];
				$pool = \Bitrix\Main\Application::getInstance()->getConnectionPool();
				if ($this->connectionName === $pool::DEFAULT_CONNECTION_NAME)
				{
					$this->connectionName .= '_' . __CLASS__;
				}
				$this->keyId = $dbKey['ID'];
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets most recent access key available for the current user.
	 *
	 * @return array
	 */
	public function getCurrentUserAccessKey()
	{
		/** @var \CUser $USER */
		global $USER;

		$filter = [
			'=ACTIVE' => 'Y',
		];
		if (!$USER->CanDoOperation('biconnector_key_manage'))
		{
			$filter['=PERMISSION.USER_ID'] = $USER->GetID();
		}

		$keyList = \Bitrix\BIConnector\KeyTable::getList([
			'select' => ['ACCESS_KEY'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
			'cache' => ['ttl' => 36000],
		]);

		return $keyList->fetch();
	}

	/**
	 * Returns dashboard list binded to the current user.
	 *
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getCurrentUserDashboardList()
	{
		/** @var \CUser $USER */
		global $USER;

		$result = \Bitrix\BIConnector\DashboardTable::getList([
			'filter' => [
				'=PERMISSION.USER_ID' => $USER->GetID(),
			],
			'order' => ['NAME' => 'ASC', 'ID' => 'DESC'],
			'cache' => ['ttl' => 36000],
		])->fetchAll();

		return $result;
	}

	/**
	 * Fires OnBIConnectorValidateDashboardUrl event and
	 * returns true on successful check.
	 *
	 * @param string $url External report public url.
	 * @param int $flag What to check.
	 *
	 * @return bool
	 */
	private function checkDashboardUrlFlag($url, $flag)
	{
		$event = new \Bitrix\Main\Event('biconnector', 'OnBIConnectorValidateDashboardUrl', [$url]);
		$event->send();
		foreach ($event->getResults() as $evenResult)
		{
			if ($evenResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$found = $evenResult->getParameters();
				if ($found & $flag)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns true if url provided is allowed.
	 *
	 * @param mixed $url External report public url.
	 *
	 * @return bool
	 */
	public function validateDashboardUrl($url)
	{
		return $this->checkDashboardUrlFlag($url, static::IS_VALID_URL);
	}

	/**
	 * Returns true if url provided is can not be opened in a slider.
	 *
	 * @param mixed $url External report public url.
	 *
	 * @return bool
	 */
	public function isExternalDashboardUrl($url)
	{
		return $this->checkDashboardUrlFlag($url, static::IS_EXTERNAL_URL);
	}

	/**
	 * Returns a list of available connections from .settings.php file
	 * which can be used as a bi source.
	 *
	 * @return array
	 */
	public function getConnections()
	{
		$biConnections = [];

		$configParams = \Bitrix\Main\Config\Configuration::getValue('connections');
		if (is_array($configParams))
		{
			foreach ($configParams as $connectionName => $connectionParams)
			{
				if (
					is_a($connectionParams['className'], '\Bitrix\BIConnector\Connection', true)
					|| is_a($connectionParams['className'], '\Bitrix\BIConnector\DB\MysqliConnection', true)
				)
				{
					$biConnections[$connectionName] = $connectionName;
				}
			}
		}

		if (!$biConnections)
		{
			$pool = \Bitrix\Main\Application::getInstance()->getConnectionPool();
			$biConnections[$pool::DEFAULT_CONNECTION_NAME] = $pool::DEFAULT_CONNECTION_NAME;
		}

		return $biConnections;
	}

	/**
	 * Returns database connection binded with last checked key.
	 *
	 * @return \Bitrix\BIConnector\DB\MysqliConnection
	 * @see \Bitrix\BIConnector\Manager::checkAccessKey
	 */
	public function getDatabaseConnection()
	{
		$pool = \Bitrix\Main\Application::getInstance()->getConnectionPool();
		$connectionName = $this->connectionName ?: $pool::DEFAULT_CONNECTION_NAME;

		$conn = $pool->getConnection($connectionName);
		if (!$conn)
		{
			$conn = $pool->getConnection();
			$connectionName = $pool::DEFAULT_CONNECTION_NAME;
		}

		if (!is_a($conn, '\Bitrix\BIConnector\DB\MysqliConnection'))
		{
			if (is_a($conn, '\Bitrix\Main\DB\MysqlCommonConnection'))
			{
				$pool->cloneConnection($connectionName , $connectionName . '~', [
					'className' => '\Bitrix\BIConnector\DB\MysqliConnection',
				]);
				$conn = $pool->getConnection($connectionName . '~');
			}
			else
			{
				throw new \Bitrix\Main\Config\ConfigurationException(sprintf(
					"Class '%s' for '%s' connection is not supported", get_class($conn), $this->connectionName
				));
			}
		}

		return $conn;
	}

	/**
	 * Adds query log entry.
	 *
	 * @param int $sourceId Data source ("table") name.
	 * @param string $fields Query select list (comma separated).
	 * @param string $filters Query where (json).
	 * @param string $input Raw command input.
	 * @param string $requestMethod Request method (POST, GET, etc.).
	 * @param string $requestUri Request URI.
	 *
	 * @return int|false
	 */
	public function startQuery($sourceId, $fields = '', $filters = '', $input = '', $requestMethod = '', $requestUri = '')
	{
		$now = new \Bitrix\Main\Type\DateTime();

		$statData = [
			'TIMESTAMP_X' => $now,
			'KEY_ID' => $this->keyId,
			'SERVICE_ID' => substr($this->serviceId, 0, 150),
			'SOURCE_ID' => substr($sourceId, 0, 150),
		];
		if ($fields)
		{
			$statData['FIELDS'] = $fields;
		}
		if ($filters)
		{
			$statData['FILTERS'] = $filters;
		}
		if ($input)
		{
			$statData['INPUT'] = $input;
		}
		if ($requestMethod)
		{
			$statData['REQUEST_METHOD'] = $requestMethod;
		}
		if ($requestUri)
		{
			$statData['REQUEST_URI'] = $requestUri;
		}

		if ($this->keyId)
		{
			\Bitrix\BIConnector\KeyTable::update($this->keyId, [
				'LAST_ACTIVITY_DATE' => $now,
			]);
		}

		$addResult = \Bitrix\BIConnector\LogTable::add($statData);
		if ($addResult->isSuccess())
		{
			$logId = $addResult->getId();
			$this->stime[$logId] = microtime(true);
			return $logId;
		}

		return false;
	}

	/**
	 * Updates query log.
	 *
	 * @param int $logId Log record identifier returned by startQuery.
	 * @param int $count How many data records was processed.
	 * @param int $size Http response body size in bytes.
	 * @param bool $isOverLimit True if there was limit over.
	 *
	 * @return void
	 */
	public function endQuery($logId, $count, $size = null, $isOverLimit = false)
	{
		if (isset($this->stime[$logId]))
		{
			$statData = [
				'TIMESTAMP_X' => new \Bitrix\Main\Type\DateTime(),
				'ROW_NUM' => $count,
				'DATA_SIZE' => $size,
				'REAL_TIME' => microtime(true) - $this->stime[$logId],
				'IS_OVER_LIMIT' => $isOverLimit,
			];

			$updateResult = \Bitrix\BIConnector\LogTable::update($logId, $statData);
			$updateResult->isSuccess();
		}
	}

	/**
	 * Returns true if at least one menu item can be shown.
	 *
	 * @return bool
	 */
	public function isAvailable()
	{
		return !empty($this->getMenuItems());
	}

	/**
	 * Returns menu items.
	 *
	 * @return array
	 */
	public function getMenuItems()
	{
		global $USER;

		$items = [];
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
			{
				return $items;
			}
		}

		if ($USER->canDoOperation('biconnector_key_view'))
		{
			$licence = Application::getInstance()->getLicense();
			if (
				($licence->getRegion() === 'ru' || $licence->getRegion() === 'by')
				&& (
					!class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
					|| ToolsManager::getInstance()->checkAvailabilityByMenuId('crm_bi_templates')
				)
			)
			{
				$items[] = [
					'id' => 'crm_bi_templates',
					'url' => '/biconnector/templates.php',
					'external' => false,
					'component_name' => 'bitrix:biconnector.templates',
					'component_parameters' => [
						'SHOW_TITLE' => 'N',
					],
				];
			}

			if (
				!class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
				|| ToolsManager::getInstance()->checkAvailabilityByMenuId('crm_microsoft_power_bi')
			)
			{
				$items[] = [
					'id' => 'crm_microsoft_power_bi',
					'external' => false,
					'component_name' => 'bitrix:biconnector.microsoftpbi',
					'component_parameters' => [
						'SHOW_TITLE' => 'N',
					],
				];
			}

			if (
				($licence->getRegion() === 'ru' || $licence->getRegion() === 'kz')
				&& (
					!class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
					|| ToolsManager::getInstance()->checkAvailabilityByMenuId('crm_yandex_datalens')
				)
			)
			{
				$items[] = [
					'id' => 'crm_yandex_datalens',
					'external' => false,
					'component_name' => 'bitrix:biconnector.yandexdl',
					'component_parameters' => [
						'SHOW_TITLE' => 'N',
					],
				];
			}

			if (
				!class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
				|| ToolsManager::getInstance()->checkAvailabilityByMenuId('crm_google_datastudio')
			)
			{
				$items[] = [
					'id' => 'crm_google_datastudio',
					'external' => false,
					'component_name' => 'bitrix:biconnector.googleds',
					'component_parameters' => [
						'SHOW_TITLE' => 'N',
					],
				];
			}
		}

		foreach ($this->getCurrentUserDashboardList() as $dashboard)
		{
			$items[] = [
				'id' => 'crm_bi_dashboard_' . $dashboard['ID'],
				'url' => '/biconnector/dashboard.php?id=' . $dashboard['ID'],
				'title' => $dashboard['NAME'],
			];
		}

		$items = array_merge($items, $this->getDashboardsForPlacement(Rest::BI_MENU_PLACEMENT));

		return $items;
	}

	public function getMenuSettingsItem()
	{
		global $USER;

		$items = [];
		if (\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled('biconnector'))
			{
				return $items;
			}
		}

		if ($USER->canDoOperation('biconnector_dashboard_manage'))
		{
			$items[] = [
				'id' => 'crm_bi_dashboard_manage',
				'url' => '/biconnector/dashboard_list.php',
			];

			$items[] = [
				'id' => 'crm_bi_key',
				'url' => '/biconnector/key_list.php',
			];
			$items[] = [
				'id' => 'crm_bi_usage',
				'url' => '/biconnector/usage_stat.php',
			];
		}

		return $items;
	}

	private function getDashboardsForPlacement(string $placementCode): array
	{
		$items = [];
		global $USER;
		if (!$USER->canDoOperation('biconnector_key_view'))
		{
			return $items;
		}
		if (!\Bitrix\Main\Loader::includeModule('rest'))
		{
			return $items;
		}

		$handlerList = \Bitrix\Rest\PlacementTable::getHandlersList($placementCode);

		foreach ($handlerList as $handlerData)
		{
			$items[] = [
				'id' => $handlerData['APP_ID'],
				'url' => '/biconnector/placement.php?id=' . $handlerData['ID'],//'/marketplace/app/'.$handlerData['APP_ID'].'/',
				'title' => !empty($handlerData['TITLE']) ? $handlerData['TITLE'] : $handlerData['APP_NAME'],
			];
		}

		return $items;
	}


	public static function isAdmin()
	{
		if (Loader::includeModule('intranet'))
		{
			return \Bitrix\Intranet\CurrentUser::get()->isAdmin();
		}

		return \Bitrix\Main\Engine\CurrentUser::get()->isAdmin();
	}
}
