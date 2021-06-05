<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Event,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\EventManager;
use \Bitrix\ImConnector\Model\StatusConnectorsTable;

/**
 * Class Status
 * @package Bitrix\ImConnector
 */
class Status
{
	/** @var array(\Bitrix\ImConnector\Status) */
	private static $instance = [];
	private static $flagEvent = false;
	private static $flagGenerationUpdateEvent = false;
	private static $rowsCacheTable = array();
	private $active = 'N';
	private $connection = 'N';
	private $register = 'N';
	private $error = 'N';
	private $id;
	private $connector;
	private $line;
	private $data = false;

	/**
	 * Receiving a state object of a specific connector all lines.
	 *
	 * @param $connector
	 * @return array|mixed
	 */
	public static function getInstanceAllLine($connector)
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

		while($row = $raw->fetch())
		{
			if (empty(self::$instance[$connector][$row["LINE"]]) )
			{
				self::$instance[$connector][$row["LINE"]] = new self($connector,$row["LINE"]);
			}
		}

		if(empty(self::$instance[$connector]))
			return array();
		else
			return self::$instance[$connector];
	}

	/**
	 * Receiving the status of all connectors and lines.
	 *
	 * @return array
	 */
	public static function getInstanceAll()
	{
		$raw = StatusConnectorsTable::getList([
			'select' => [
				'LINE', 'CONNECTOR'
			]
		]);

		while($row = $raw->fetch())
		{
			if (empty(self::$instance[$row["CONNECTOR"]][$row["LINE"]]) )
			{
				self::$instance[$row["CONNECTOR"]][$row["LINE"]]= new self($row["CONNECTOR"],$row["LINE"]);
			}
		}

		if(empty(self::$instance))
			return [];
		else
			return self::$instance;
	}

	/**
	 * Receiving a state object of a specific connector lines.
	 *
	 * @param $connector
	 * @param string $line
	 * @return mixed
	 */
	public static function getInstance($connector, $line = '#empty#')
	{
		$connector = Connector::getConnectorRealId($connector);

		if (empty(self::$instance[$connector][$line]) || !(self::$instance[$connector][$line] instanceof \Bitrix\ImConnector\Status))
		{
			self::$instance[$connector][$line] = new self($connector,$line);
		}

		return self::$instance[$connector][$line];
	}

	/**
	 * Removal of information about the all connector.
	 *
	 * @param string $line ID.
	 * @return bool.
	 */
	public static function deleteAll($line = '#empty#')
	{
		if (!empty(self::$instance) )
		{
			foreach (self::$instance as $connector)
				unset(self::$instance[$connector][$line]);
		}

		$raw = StatusConnectorsTable::getList([
			'select' => ['ID', 'CONNECTOR'],
			'filter' => [
				'=LINE' => $line,
			]
		]);

		while($row = $raw->fetch())
		{
			//Event
			$dataEvent = [
				"connector" => $row['CONNECTOR'],
				"line" => $line,
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_DELETE, $dataEvent);
			$event->send();

			$delete = StatusConnectorsTable::delete($row['ID']);
			self::cleanCache($row['CONNECTOR'], $line);
		}

		if(!empty($delete) && is_object($delete) && $delete->isSuccess())
			return true;
		else
			return false;
	}

	/**
	 * Removal of information about the connector.
	 *
	 * @param string $connector ID connector.
	 * @param string $line ID open line.
	 * @return bool
	 */
	public static function delete($connector, $line = '#empty#')
	{
		if (!empty(self::$instance[$connector][$line]))
		{
			unset(self::$instance[$connector][$line]);
		}

		$raw = StatusConnectorsTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=LINE' => $line,
				'=CONNECTOR' => $connector
			]
		]);
		while($row = $raw->fetch())
		{
			$delete = StatusConnectorsTable::delete($row['ID']);
			self::cleanCache($connector, $line);
		}

		//Event
		$dataEvent = [
			"connector" => $connector,
			"line" => $line,
		];
		$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_DELETE, $dataEvent);
		$event->send();

		if(!empty($delete) && is_object($delete) && $delete->isSuccess())
			return true;
		else
			return false;
	}

	/**
	 * Add a handler to the save changes.
	 */
	public static function addEventHandlerSave()
	{
		if(empty(self::$flagEvent))
		{
			EventManager::getInstance()->addEventHandler("main", "OnAfterEpilog", Array(__CLASS__, "save"), false, 1000);

			self::$flagEvent = true;
		}
	}

	/**
	 * Adding a handler to generate change events connector.
	 */
	public static function addEventHandlerGenerationUpdateEvent()
	{
		if(empty(self::$flagGenerationUpdateEvent))
		{
			EventManager::getInstance()->addEventHandler("main", "OnAfterEpilog", Array(__CLASS__, "sendUpdateEvent"), false, 1000);

			self::$flagGenerationUpdateEvent = true;
		}
	}

	/**
	 * A cache reset to all connector.
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function cleanCacheAll()
	{
		$allConnector = self::getInstanceAll();

		foreach ($allConnector as $connector => $item)
		{
			foreach ($item as $line => $status)
			{
				self::cleanCache($connector, $line);
			}
		}
	}

	/**
	 * A cache reset to the specific connector.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 */
	public static function cleanCache($connector, $line)
	{
		$cache = Cache::createInstance();
		$cache->clean(Connector::getCacheIdConnector($line, $connector), Library::CACHE_DIR_STATUS);
	}

	/**
	 * The event handler OnAfterEpilog.
	 * Data is saved only when the script completes.
	 */
	public static function save(): void
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$connector = self::$instance[$currentConnector][$line];
				if(
					!empty($connector)
					&& $connector instanceof Status
					&& !empty($connector->id)
				)
				{
					$fields = [];

					if(!empty($connector->active))
					{
						$fields['ACTIVE'] = $connector->active;
					}
					if(!empty($connector->connection))
					{
						$fields['CONNECTION'] = $connector->connection;
					}
					if(!empty($connector->register))
					{
						$fields['REGISTER'] = $connector->register;
					}
					if(!empty($connector->error))
					{
						$fields['ERROR'] = $connector->error;
					}
					if($connector->data !== false)
					{
						$fields['DATA'] = $connector->data;
					}

					StatusConnectorsTable::update($connector->id, $fields);
					self::cleanCache($currentConnector, $line);
				}
			}
		}
	}

	/**
	 * The event handler OnAfterEpilog.
	 * The generation of update events connector
	 */
	public static function sendUpdateEvent()
	{
		foreach (self::$instance as $currentConnector => $listLine)
		{
			foreach ($listLine as $line => $value)
			{
				$connector = self::$instance[$currentConnector][$line];
				$fields = array();

				if(!empty($connector->active))
					$fields["ACTIVE"] = $connector->active;
				if(!empty($connector->connection))
					$fields["CONNECTION"] = $connector->connection;
				if(!empty($connector->register))
					$fields["REGISTER"] = $connector->register;
				if(!empty($connector->error))
					$fields["ERROR"] = $connector->error;
				if($connector->data !== false)
					$fields["DATA"] = $connector->data;

				//Event
				$dataEvent = [
					"connector" => $currentConnector,
					"line" => $line,
					"fields" => $fields
				];
				$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_UPDATE, $dataEvent);
				$event->send();
			}
		}
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
				if(empty($statuses[$row['CONNECTOR']][$row['LINE']]))
				{
					$statuses[$row['CONNECTOR']][$row['LINE']] = $row;
				}
				else
				{
					$new = $row;
					$old = $statuses[$row['CONNECTOR']][$row['LINE']];
					$result = 'old';

					if($old['REGISTER'] !== 'Y' && $new['REGISTER'] === 'Y')
					{
						$result = 'new';
					}
					elseif($old['REGISTER'] !== 'Y' && $new['REGISTER'] !== 'Y')
					{
						if($old['CONNECTION'] !== 'Y' && $new['CONNECTION'] === 'Y')
						{
							$result = 'new';
						}
						elseif($old['CONNECTION'] !== 'Y' && $new['CONNECTION'] !== 'Y')
						{
							if(empty($old['DATA']) && !empty($new['DATA']))
							{
								$result = 'new';
							}
							elseif(empty($old['DATA']) && empty($new['DATA']))
							{
								if($old['ERROR'] === 'Y' && $new['ERROR'] !== 'Y')
								{
									$result = 'new';
								}
								elseif($old['ERROR'] === 'Y' && $new['ERROR'] === 'Y')
								{
									if($new['ID'] < $old['ID'])
									{
										$result = 'new';
									}
								}
							}
						}
					}

					if($result == 'new')
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

	/**
	 * Status constructor.
	 *
	 * @param $connector
	 * @param string $line
	 */
	private function __construct($connector, $line = '#empty#')
	{
		$this->connector = $connector;
		$this->line = $line;
		$status = null;

		$cache = Cache::createInstance();
		if ($cache->initCache(Library::CACHE_TIME_STATUS, Connector::getCacheIdConnector($line, $connector), Library::CACHE_DIR_STATUS))
		{
			$status = $cache->getVars();
		}
		else
		{
			if(empty(self::$rowsCacheTable))
				self::$rowsCacheTable = StatusConnectorsTable::getList()->fetchAll();

			foreach(self::$rowsCacheTable as $row)
			{
				if($row["CONNECTOR"] == $connector && $row["LINE"] == $line)
					$status = $row;

				if ($cache->startDataCache(Library::CACHE_TIME_STATUS, Connector::getCacheIdConnector($row["LINE"], $row["CONNECTOR"]), Library::CACHE_DIR_STATUS))
				{
					$cache->endDataCache($row);
				}
			}
		}

		if(!empty($status))
		{
			$this->id = $status["ID"];
			$this->active = $status["ACTIVE"];
			$this->connection = $status["CONNECTION"];
			$this->register = $status["REGISTER"];
			$this->error = $status["ERROR"];
			$this->data = $status["DATA"];
		}
		else
		{
			$add = StatusConnectorsTable::add([
				'LINE' => $line,
				'CONNECTOR' => $connector,
			]);

			if ($add->isSuccess())
			{
				$this->id = $add->getId();
			}

			//Event
			$dataEvent = [
				"connector" => $connector,
				"line" => $line
			];
			$event = new Event(Library::MODULE_ID, Library::EVENT_STATUS_ADD, $dataEvent);
			$event->send();
		}
	}

	private function __clone()
	{
	}
	private function __wakeup()
	{
	}

	/**
	 * To set the activity status of the connector.
	 *
	 * @param bool $status Status.
	 */
	public function setActive($status = false)
	{
		self::addEventHandlerGenerationUpdateEvent();

		if($this->active !== $status)
		{
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			if(empty($status))
				$this->active = 'N';
			else
				$this->active = 'Y';
		}
	}

	/**
	 * Set the connection state of the connector.
	 *
	 * @param bool $status Status.
	 */
	public function setConnection($status = false)
	{
		self::addEventHandlerGenerationUpdateEvent();

		if($this->connection !== $status)
		{
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			if (empty($status))
				$this->connection = 'N';
			else
				$this->connection = 'Y';
		}
	}

	/**
	 * To set the state of register connector.
	 *
	 * @param bool $status Status.
	 */
	public function setRegister($status = false)
	{
		self::addEventHandlerGenerationUpdateEvent();

		if($this->register !== $status)
		{
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			if(empty($status))
				$this->register = 'N';
			else
				$this->register = 'Y';
		}
	}

	/**
	 * To establish the presence or absence of error in the connector.
	 *
	 * @param bool $status Status.
	 */
	public function setError($status = false)
	{
		self::addEventHandlerGenerationUpdateEvent();

		if($this->error !== $status)
		{
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			if (empty($status))
				$this->error = 'N';
			else
				$this->error = 'Y';
		}
	}

	/**
	 * Sets the additional data connectors.
	 *
	 * @param string|array $data Data to save.
	 */
	public function setData($data = '')
	{
		self::addEventHandlerGenerationUpdateEvent();

		if(serialize($this->data) !== serialize($data))
		{
			self::addEventHandlerSave();
			self::cleanCache($this->connector, $this->line);

			$this->data = $data;
		}
	}

	/**
	 * Return the ID status of the connector.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return the ID line status of the connector.
	 *
	 * @return string
	 */
	public function getLine()
	{
		return $this->line;
	}

	/**
	 * Return the ID connector status of the connector.
	 *
	 * @return string
	 */
	public function getConnector()
	{
		return $this->connector;
	}

	/**
	 * Return the activity status of the connector.
	 *
	 * @return bool
	 */
	public function getActive()
	{
		if($this->active == 'Y')
			return true;
		else
			return false;
	}

	/**
	 * Return the connection status of the connector.
	 *
	 * @return bool
	 */
	public function getConnection()
	{
		if($this->connection == 'Y')
			return true;
		else
			return false;
	}

	/**
	 * Return the status of the check connector.
	 *
	 * @return bool
	 */
	public function getRegister()
	{
		if($this->register == 'Y')
			return true;
		else
			return false;
	}

	/**
	 * To return an error in the connector.
	 *
	 * @return bool
	 */
	public function getError()
	{
		if($this->error == 'Y')
			return true;
		else
			return false;
	}

	/**
	 * To return an data in the connector.
	 *
	 * @return string|array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Check whether the connector is configured to work.
	 *
	 * @return bool
	 */
	public function isConfigured()
	{
		if($this->getConnection() && $this->getRegister() && $this->getActive())
			return true;
		else
			return false;
	}

	/**
	 * To check whether you can use the connector to work.
	 *
	 * @return bool
	 */
	public function isStatus()
	{
		if($this->isConfigured() && !$this->getError())
			return true;
		else
			return false;
	}
}