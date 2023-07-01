<?php

namespace Bitrix\ImConnector;

use Bitrix\ImOpenLines\Config;
use Bitrix\Main\Event;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Application;
use Bitrix\ImConnector\Model\StatusConnectorsTable;


/**
 * Class Status
 * @package Bitrix\ImConnector
 */
class Status implements \JsonSerializable
{
	/** @var array<string, array<int, self>> */
	private static array $instance = [];

	private static bool $flagSaveStatusEvent = false;
	private static bool $flagGenerationUpdateEvent = false;
	private bool $flagUpdated = false;

	private bool $active = false;
	private bool $connection = false;
	private bool $register = false;
	private bool $error = false;
	private ?int $id = null;
	private ?string $connector = null;
	private ?int $line = null;
	private $data = null;

	/**
	 * Status constructor.
	 *
	 * @param string $connector
	 * @param int $line
	 * @param array|null $status
	 */
	private function __construct(string $connector, int $line, ?array $status = null)
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->flagUpdated = false;

		if (!empty($status))
		{
			$this->id = (int)$status['ID'];
			$this->active = ($status['ACTIVE'] == 'Y');
			$this->connection = ($status['CONNECTION'] == 'Y');
			$this->register = ($status['REGISTER'] == 'Y');
			$this->error = ($status['ERROR'] == 'Y');
			$this->data = $status['DATA'];
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

		if (
			!isset(self::$instance[$connector][$line])
			|| !(self::$instance[$connector][$line] instanceof Status)
		)
		{
			$status = null;
			$raw = StatusConnectorsTable::getList([
				'filter' => [
					'=CONNECTOR' => $connector,
					'=LINE' => $line
				],
				'order' => [
					'ID' => 'ASC'
				]
			]);
			if ($row = $raw->fetch())
			{
				$status = $row;
			}

			self::$instance[$connector][$line] = new self($connector, $line, $status);
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
			'filter' => [
				'=CONNECTOR' => $connector
			],
			'order' => [
				'ID' => 'ASC'
			]
		]);
		while ($row = $raw->fetch())
		{
			if (empty(self::$instance[$connector][$row['LINE']]) )
			{
				self::$instance[$connector][$row['LINE']] = new self($connector, (int)$row['LINE'], $row);
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
	 * Receiving a state object of a specific line for all connectors.
	 *
	 * @param int $lineId
	 * @return array<string, array<int, Status>>
	 */
	public static function getInstanceAllConnector(int $lineId): array
	{
		$result = [];
		$raw = StatusConnectorsTable::getList([
			'filter' => [
				'=LINE' => $lineId
			],
			'order' => [
				'ID' => 'ASC'
			]
		]);
		while ($row = $raw->fetch())
		{
			if (empty(self::$instance[$row['CONNECTOR']][$lineId]))
			{
				self::$instance[$row['CONNECTOR']][$lineId] = new self($row['CONNECTOR'], $lineId, $row);
			}
			$result[] = self::$instance[$row['CONNECTOR']][$lineId];
		}

		return $result;
	}

	/**
	 * Receiving the status of all connectors and lines.
	 *
	 * @return array<string, array<int, Status>>
	 */
	public static function getInstanceAll(): array
	{
		$raw = StatusConnectorsTable::getList([
			'order' => [
				'ID' => 'ASC'
			]
		]);
		while ($row = $raw->fetch())
		{
			if (empty(self::$instance[$row['CONNECTOR']][$row['LINE']]))
			{
				self::$instance[$row['CONNECTOR']][$row['LINE']] = new self($row['CONNECTOR'], (int)$row['LINE'], $row);
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
			if (!$delete->isSuccess())
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
			$deleteResult = StatusConnectorsTable::delete($row['ID']);
			if (!$deleteResult->isSuccess())
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
			$deleteResult = StatusConnectorsTable::delete($row['ID']);
			if (!$deleteResult->isSuccess())
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
	 * Add bg tasks.
	 * @returm void
	 */
	public static function addBackgroundTasks($connector, $line): void
	{
		self::addEventHandlerGenerationUpdateEvent([$connector, $line]);
		self::addEventHandlerSave([$connector, $line]);
	}

	/**
	 * Adds a handler to the save changes.
	 *
	 * @returm void
	 * @param array $args
	 */
	private static function addEventHandlerSave(array $args = []): void
	{
		if (self::$flagSaveStatusEvent !== true)
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'saveAll'],
				$args,
				Application::JOB_PRIORITY_NORMAL
			);

			self::$flagSaveStatusEvent = true;
		}
	}

	/**
	 * Adds a handler to generate change events connector.
	 *
	 * @returm void
	 * @param array $args
	 */
	private static function addEventHandlerGenerationUpdateEvent(array $args = []): void
	{
		if (self::$flagGenerationUpdateEvent !== true)
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'fireUpdateEventAll'],
				$args,
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
	public static function saveAll(): void
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$status = self::$instance[$currentConnector][$line];
				if ($status instanceof Status)
				{
					$status->save(false);
				}
			}
		}
	}

	/**
	 * Saves status data at script complete.
	 *
	 * @param bool $fireEvent
	 * @return void
	 */
	public function save(bool $fireEvent = false): void
	{
		$fields = [
			'ACTIVE' => $this->active ? 'Y' : 'N',
			'CONNECTION' => $this->connection ? 'Y' : 'N',
			'REGISTER' => $this->register ? 'Y' : 'N',
			'ERROR' => $this->error ? 'Y' : 'N',
		];
		if ($this->data !== null)
		{
			$fields['DATA'] = $this->data;
		}

		if ($this->id)
		{
			if ($this->flagUpdated === true)
			{
				$updateResult = StatusConnectorsTable::update($this->id, $fields);
				if ($updateResult->isSuccess())
				{
					if ($fireEvent)
					{
						$this->fireUpdateEvent();
					}
				}
			}
		}
		else
		{
			$fields['LINE'] = $this->line;
			$fields['CONNECTOR'] = $this->connector;

			$addResult = StatusConnectorsTable::add($fields);
			if ($addResult->isSuccess())
			{
				$this->flagUpdated = true;
				$this->id = (int)$addResult->getId();

				//if ($fireEvent)
				{
					$this->fireAddEvent();
				}
			}
		}
	}

	/**
	 * The generation of update events connector
	 * @return void
	 */
	public static function fireUpdateEventAll(): void
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$status = self::$instance[$currentConnector][$line];
				if ($status instanceof Status)
				{
					$status->fireUpdateEvent();
				}
			}
		}
	}

	/**
	 * Generates the add event for connector.
	 * @event `imconnector:OnAddStatusConnector`
	 * @return void
	 */
	public function fireAddEvent(): void
	{
		$dataEvent = [
			'connector' => $this->connector,
			'line' => $this->line
		];
		$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_ADD, $dataEvent);
		$event->send();
	}

	/**
	 * Generates the update event for connector.
	 * @event `imconnector:OnUpdateStatusConnector`
	 * @return void
	 */
	public function fireUpdateEvent(): void
	{
		if ($this->flagUpdated === true)
		{
			$fields = [
				'ACTIVE' => $this->active ? 'Y' : 'N',
				'CONNECTION' => $this->connection ? 'Y' : 'N',
				'REGISTER' => $this->register ? 'Y' : 'N',
				'ERROR' => $this->error ? 'Y' : 'N',
			];

			if ($this->data !== null)
			{
				$fields['DATA'] = $this->data;
			}

			$dataEvent = [
				'connector' => $this->connector,
				'line' => $this->line,
				'fields' => $fields
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_UPDATE, $dataEvent);
			$event->send();

			$this->flagUpdated = false;
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

		//self::cleanCacheAll();
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
			self::addBackgroundTasks($this->connector, $this->line);
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
			self::addBackgroundTasks($this->connector, $this->line);
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
			self::addBackgroundTasks($this->connector, $this->line);
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
			self::addBackgroundTasks($this->connector, $this->line);
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
			self::addBackgroundTasks($this->connector, $this->line);
			$this->data = $data;
		}

		return $this;
	}

	//endregion

	//region: Getters

	/**
	 * Returns the ID status.
	 *
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Returns line id status of the connector.
	 *
	 * @return int|null
	 */
	public function getLine(): ?int
	{
		return $this->line;
	}

	public function getLineConfig()
	{
		return Config::getInstance()->get($this->line);
	}

	/**
	 * Returns the ID connector status of the connector.
	 *
	 * @return string|null
	 */
	public function getConnector(): ?string
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
	public function getConnection(): bool
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

	public function jsonSerialize(): array
	{
		return [
			'lineId' => $this->line,
			'lineName' => $this->getLineConfig()['LINE_NAME'],
		];
	}
}