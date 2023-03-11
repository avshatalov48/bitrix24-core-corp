<?php

namespace Bitrix\ImConnector;

use Bitrix\Main\Event;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Application;
use Bitrix\ImConnector\Model\StatusConnectorsTable;


/**
 * Class Status
 * @package Bitrix\ImConnector
 */
class Status
{
	/** @var array<string, array<int, self>> */
	private static array $instance = [];

	/** @var array<int, array> */
	private static array $rowsCacheTable = [];

	private static bool $flagSaveStatusEvent = false;
	private static bool $flagGenerationUpdateEvent = false;
	private bool $flagUpdated = false;

	private bool $active = false;
	private bool $connection = false;
	private bool $register = false;
	private bool $error = false;
	private int $id;
	private string $connector;
	private int $line;
	private $data = null;


	/**
	 * Status constructor.
	 *
	 * @param string $connector
	 * @param int $line
	 */
	private function __construct(string $connector, int $line)
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->flagUpdated = false;
		$status = null;

		$cache = Cache::createInstance();
		if ($cache->initCache(Library::CACHE_TIME_STATUS, Connector::getCacheIdConnector($line, $connector), Library::CACHE_DIR_STATUS))
		{
			$status = $cache->getVars();
		}
		else
		{
			if (empty(self::$rowsCacheTable))
			{
				self::$rowsCacheTable = StatusConnectorsTable::getList()->fetchAll();
			}

			foreach(self::$rowsCacheTable as $row)
			{
				if ($row['CONNECTOR'] == $connector && $row['LINE'] == $line)
				{
					$status = $row;
				}

				if ($cache->startDataCache(Library::CACHE_TIME_STATUS, Connector::getCacheIdConnector($row['LINE'], $row['CONNECTOR']), Library::CACHE_DIR_STATUS))
				{
					$cache->endDataCache($row);
				}
			}
		}

		if(!empty($status))
		{
			$this->id = (int)$status['ID'];
			$this->active = ($status['ACTIVE'] == 'Y');
			$this->connection = ($status['CONNECTION'] == 'Y');
			$this->register = ($status['REGISTER'] == 'Y');
			$this->error = ($status['ERROR'] == 'Y');
			$this->data = $status['DATA'];
		}
		else
		{
			$add = StatusConnectorsTable::add([
				'LINE' => $line,
				'CONNECTOR' => $connector,
			]);

			if ($add->isSuccess())
			{
				$this->id = (int)$add->getId();
			}

			$dataEvent = [
				'connector' => $connector,
				'line' => $line
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_ADD, $dataEvent);
			$event->send();
		}
	}

	public function __clone()
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	public function __wakeup()
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	/**
	 * Receiving a state object of a specific connector lines.
	 *
	 * @param string $connector
	 * @param int $line
	 * @return self
	 */
	public static function getInstance(string $connector, int $line): self
	{
		$connector = Connector::getConnectorRealId($connector);

		if (empty(self::$instance[$connector][$line]) || !(self::$instance[$connector][$line] instanceof Status))
		{
			self::$instance[$connector][$line] = new self($connector, $line);
		}

		return self::$instance[$connector][$line];
	}

	/**
	 * Sets a new state object for specific connector line.
	 *
	 * @param string $connector
	 * @param int $line
	 * @param self $status
	 *
	 * @return void
	 */
	public static function setInstance(string $connector, int $line, self $status): void
	{
		$connector = Connector::getConnectorRealId($connector);
		self::$instance[$connector][$line] = $status;
	}

	/**
	 * Receiving a state object of a specific connector all lines.
	 *
	 * @param string $connector
	 * @return array<int, Status>
	 */
	public static function getInstanceAllLine(string $connector): array
	{
		$connector = Connector::getConnectorRealId($connector);

		$raw = StatusConnectorsTable::getList([
			'select' => [
				'LINE'
			],
			'filter' => [
				'=CONNECTOR' => $connector
			]
		]);

		while ($row = $raw->fetch())
		{
			if (empty(self::$instance[$connector][$row['LINE']]) )
			{
				self::$instance[$connector][$row['LINE']] = new self($connector, (int)$row['LINE']);
			}
		}

		if (empty(self::$instance[$connector]))
		{
			return [];
		}
		else
		{
			return self::$instance[$connector];
		}
	}

	/**
	 * Receiving the status of all connectors and lines.
	 *
	 * @return array<string, array<int, Status>>
	 */
	public static function getInstanceAll(): array
	{
		$raw = StatusConnectorsTable::getList([
			'select' => [
				'LINE', 'CONNECTOR'
			]
		]);

		while ($row = $raw->fetch())
		{
			if (empty(self::$instance[$row['CONNECTOR']][$row['LINE']]) )
			{
				self::$instance[$row['CONNECTOR']][$row['LINE']] = new self($row['CONNECTOR'], (int)$row['LINE']);
			}
		}

		if (empty(self::$instance))
		{
			return [];
		}
		else
		{
			return self::$instance;
		}
	}

	/**
	 * Removal of information about the all connector.
	 *
	 * @param int $line
	 * @return bool
	 */
	public static function deleteAll(int $line): bool
	{
		if (!empty(self::$instance))
		{
			foreach (self::$instance as $connector => &$lines)
			{
				unset($lines[$line]);
			}
		}

		$result = true;

		$raw = StatusConnectorsTable::getList([
			'select' => ['ID', 'CONNECTOR'],
			'filter' => [
				'=LINE' => $line,
			]
		]);

		while ($row = $raw->fetch())
		{
			$dataEvent = [
				'connector' => $row['CONNECTOR'],
				'line' => $line,
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_DELETE, $dataEvent);
			$event->send();

			$delete = StatusConnectorsTable::delete((int)$row['ID']);
			if ($delete->isSuccess())
			{
				self::cleanCache($row['CONNECTOR'], $line);
			}
			else
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Removal of information about the connector.
	 *
	 * @param string $connector ID connector.
	 * @param int $line ID open line.
	 * @return bool
	 */
	public static function delete(string $connector, int $line): bool
	{
		if (!empty(self::$instance[$connector]))
		{
			unset(self::$instance[$connector][$line]);
		}

		$result = true;

		$raw = StatusConnectorsTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=LINE' => $line,
				'=CONNECTOR' => $connector
			]
		]);
		while ($row = $raw->fetch())
		{
			$delete = StatusConnectorsTable::delete($row['ID']);
			if ($delete->isSuccess())
			{
				self::cleanCache($connector, $line);
			}
			else
			{
				$result = false;
			}
		}

		$dataEvent = [
			'connector' => $connector,
			'line' => $line,
		];
		$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_DELETE, $dataEvent);
		$event->send();

		return $result;
	}

	/**
	 * Removal of all lines for the connector, except one
	 *
	 * @param string $connector ID connector.
	 * @param int $lineToKeep ID open line to be kipped.
	 * @return bool
	 */
	public static function deleteLinesExcept(string $connector, int $lineToKeep): bool
	{
		if (!empty(self::$instance[$connector]) && is_array(self::$instance[$connector]))
		{
			foreach (self::$instance[$connector] as $lineId => $lines)
			{
				if ($lineId != $lineToKeep)
				{
					unset(self::$instance[$connector][$lineId]);
				}
			}
		}

		$result = true;

		$raw = StatusConnectorsTable::getList([
			'select' => ['ID', 'LINE'],
			'filter' => [
				'!=LINE' => $lineToKeep,
				'=CONNECTOR' => $connector
			]
		]);
		while ($row = $raw->fetch())
		{
			$delete = StatusConnectorsTable::delete($row['ID']);
			if ($delete->isSuccess())
			{
				self::cleanCache($connector, (int)$row['LINE']);
			}
			else
			{
				$result = false;
			}

			$dataEvent = [
				'connector' => $connector,
				'line' => $row['LINE'],
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_DELETE, $dataEvent);
			$event->send();
		}

		return $result;
	}

	/**
	 * Adds a handler to the save changes.
	 * @returm void
	 */
	public static function addEventHandlerSave(): void
	{
		if (self::$flagSaveStatusEvent !== true)
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'save'],
				[],
				Application::JOB_PRIORITY_NORMAL
			);

			self::$flagSaveStatusEvent = true;
		}
	}

	/**
	 * Adds a handler to generate change events connector.
	 * @returm void
	 */
	public static function addEventHandlerGenerationUpdateEvent(): void
	{
		if (self::$flagGenerationUpdateEvent !== true)
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'sendUpdateEvent'],
				[],
				Application::JOB_PRIORITY_LOW
			);

			self::$flagGenerationUpdateEvent = true;
		}
	}

	/**
	 * Resets cache to all connectors.
	 * @return void
	 */
	public static function cleanCacheAll(): void
	{
		$allConnector = self::getInstanceAll();

		foreach ($allConnector as $connector => $item)
		{
			foreach ($item as $line => $status)
			{
				self::cleanCache($connector, (int)$line);
			}
		}
	}

	/**
	 * Resets cache to the specific connector.
	 * @param string $connector ID connector.
	 * @param int $line ID line.
	 * @returm void
	 */
	public static function cleanCache(string $connector, int $line): void
	{
		$cache = Cache::createInstance();
		$cache->clean(Connector::getCacheIdConnector($line, $connector), Library::CACHE_DIR_STATUS);
	}

	/**
	 * Saves status data at script complete.
	 * @return void
	 */
	public static function save(): void
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$connector = self::$instance[$currentConnector][$line];
				if (
					$connector instanceof Status
					&& !empty($connector->id)
					&& $connector->flagUpdated === true
				)
				{
					$fields = [
						'ACTIVE' => $connector->active ? 'Y' : 'N',
						'CONNECTION' => $connector->connection ? 'Y' : 'N',
						'REGISTER' => $connector->register ? 'Y' : 'N',
						'ERROR' => $connector->error ? 'Y' : 'N',
					];

					if ($connector->data !== null)
					{
						$fields['DATA'] = $connector->data;
					}

					StatusConnectorsTable::update($connector->id, $fields);
					self::cleanCache($currentConnector, (int)$line);
				}
			}
		}
	}

	/**
	 * The generation of update events connector
	 * @return void
	 */
	public static function sendUpdateEvent(): void
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$connector = self::$instance[$currentConnector][$line];
				if (
					$connector instanceof Status
					&& $connector->flagUpdated === true
				)
				{
					$fields = [
						'ACTIVE' => $connector->active ? 'Y' : 'N',
						'CONNECTION' => $connector->connection ? 'Y' : 'N',
						'REGISTER' => $connector->register ? 'Y' : 'N',
						'ERROR' => $connector->error ? 'Y' : 'N',
					];

					if ($connector->data !== null)
					{
						$fields['DATA'] = $connector->data;
					}

					$dataEvent = [
						'connector' => $currentConnector,
						'line' => $line,
						'fields' => $fields
					];
					$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_UPDATE, $dataEvent);
					$event->send();

					$connector->flagUpdated = false;
				}
			}
		}
	}

	/**
	 * @return void
	 */
	public static function cleanupDuplicates()
	{
		$statuses = [];

		$rows = StatusConnectorsTable::getList()->fetchAll();

		$connectors = Connector::getListConnectorReal();

		foreach ($rows as $row)
		{
			if($row['ACTIVE'] === 'N' || empty($connectors[$row['CONNECTOR']]))
			{
				StatusConnectorsTable::delete($row['ID']);
			}
			else
			{
				if (empty($statuses[$row['CONNECTOR']][$row['LINE']]))
				{
					$statuses[$row['CONNECTOR']][$row['LINE']] = $row;
				}
				else
				{
					$new = $row;
					$old = $statuses[$row['CONNECTOR']][$row['LINE']];
					$result = 'old';

					if ($old['REGISTER'] !== 'Y' && $new['REGISTER'] === 'Y')
					{
						$result = 'new';
					}
					elseif ($old['REGISTER'] !== 'Y' && $new['REGISTER'] !== 'Y')
					{
						if ($old['CONNECTION'] !== 'Y' && $new['CONNECTION'] === 'Y')
						{
							$result = 'new';
						}
						elseif ($old['CONNECTION'] !== 'Y' && $new['CONNECTION'] !== 'Y')
						{
							if (empty($old['DATA']) && !empty($new['DATA']))
							{
								$result = 'new';
							}
							elseif (empty($old['DATA']) && empty($new['DATA']))
							{
								if ($old['ERROR'] === 'Y' && $new['ERROR'] !== 'Y')
								{
									$result = 'new';
								}
								elseif ($old['ERROR'] === 'Y' && $new['ERROR'] === 'Y')
								{
									if ($new['ID'] < $old['ID'])
									{
										$result = 'new';
									}
								}
							}
						}
					}

					if ($result == 'new')
					{
						StatusConnectorsTable::delete($old['ID']);
						$statuses[$row['CONNECTOR']][$row['LINE']] = $new;
					}
					else
					{
						StatusConnectorsTable::delete($new['ID']);
						$statuses[$row['CONNECTOR']][$row['LINE']] = $old;
					}
				}
			}
		}

		self::cleanCacheAll();
	}

	//region: Setters

	/**
	 * Sets active state for connector.
	 *
	 * @param bool $state Status.
	 * @returm self
	 */
	public function setActive(bool $state): self
	{
		if ($this->active != $state)
		{
			$this->flagUpdated = true;
			self::addEventHandlerGenerationUpdateEvent();
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->active = $state;
		}

		return $this;
	}

	/**
	 * Sets connected state for connector.
	 *
	 * @param bool $state Status.
	 * @returm self
	 */
	public function setConnection(bool $state): self
	{
		if ($this->connection != $state)
		{
			$this->flagUpdated = true;
			self::addEventHandlerGenerationUpdateEvent();
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->connection = $state;
		}

		return $this;
	}

	/**
	 * Sets registered state for connector.
	 *
	 * @param bool $state Status.
	 * @return self
	 */
	public function setRegister(bool $state): self
	{
		if ($this->register != $state)
		{
			$this->flagUpdated = true;
			self::addEventHandlerGenerationUpdateEvent();
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->register = $state;
		}

		return $this;
	}

	/**
	 * To establish the presence or absence of error in the connector.
	 *
	 * @param bool $state Status.
	 * @return self
	 */
	public function setError(bool $state): self
	{
		if ($this->error != $state)
		{
			$this->flagUpdated = true;
			self::addEventHandlerGenerationUpdateEvent();
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->error = $state;
		}

		return $this;
	}

	/**
	 * Sets the additional data for connector.
	 *
	 * @param array|null $data Data to save.
	 * @return self
	 */
	public function setData($data): self
	{
		if (serialize($this->data) !== serialize($data))
		{
			$this->flagUpdated = true;
			self::addEventHandlerGenerationUpdateEvent();
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->data = $data;
		}

		return $this;
	}

	//endregion

	//region: Getters

	/**
	 * Returns the ID status.
	 *
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Returns line id status of the connector.
	 *
	 * @return int
	 */
	public function getLine(): int
	{
		return $this->line;
	}

	/**
	 * Returns the ID connector status of the connector.
	 *
	 * @return string
	 */
	public function getConnector(): string
	{
		return $this->connector;
	}

	/**
	 * Returns active state of the connector.
	 *
	 * @return bool
	 */
	public function getActive(): bool
	{
		return $this->active;
	}

	/**
	 * Returns the connection status of the connector.
	 *
	 * @return bool
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Returns the status of the check connector.
	 *
	 * @return bool
	 */
	public function getRegister(): bool
	{
		return $this->register;
	}

	/**
	 * Returns an error in the connector.
	 *
	 * @return bool
	 */
	public function getError(): bool
	{
		return $this->error;
	}

	/**
	 * Returns additional data in the connector.
	 *
	 * @return string|array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Checks whether the connector is configured to work.
	 *
	 * @return bool
	 */
	public function isConfigured(): bool
	{
		return $this->getConnection() && $this->getRegister() && $this->getActive();
	}

	/**
	 * Checks whether you can use the connector to work.
	 *
	 * @return bool
	 */
	public function isStatus(): bool
	{
		return $this->isConfigured() && !$this->getError();
	}

	//endregion
}