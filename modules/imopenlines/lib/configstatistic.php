<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main\EventManager,
	\Bitrix\Main\DB\SqlExpression;

use \Bitrix\ImOpenLines\Model\ConfigStatisticTable;

/**
 * Class ConfigStatistic
 * @package Bitrix\ImOpenLines
 */
class ConfigStatistic
{
	/** @var array(\Bitrix\ImOpenLines\ConfigStatistic) */
	private static $instance = [];
	private static $flagEvent = false;

	protected $id;
	protected $closed = 0;
	protected $inWork = 0;
	protected $session = 0;
	protected $lead = 0;
	protected $message = 0;

	/**
	 * @param $lineId
	 * @return self
	 */
	public static function getInstance($lineId)
	{
		if (empty(self::$instance[$lineId]) )
		{
			self::$instance[$lineId]= new self($lineId);
		}

		return self::$instance[$lineId];
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
	 * The event handler OnAfterEpilog.
	 * Data is saved only when the script completes.
	 *
	 * @throws \Exception
	 */
	public static function save()
	{
		foreach (self::$instance as $id => $updateStatistic)
		{
			$statisticManager = self::$instance[$id];
			if(!empty($statisticManager) && $statisticManager instanceof self && !empty($statisticManager->id))
			{
				if(!empty(ConfigStatisticTable::getRowById($statisticManager->id)))
				{
					$fieldsUpdate = [];

					if(!empty($statisticManager->closed))
					{
						$fieldsUpdate['CLOSED'] = new SqlExpression("?# + " . $statisticManager->closed , "CLOSED");
					}
					if(!empty($statisticManager->inWork))
					{
						$fieldsUpdate['IN_WORK'] = new SqlExpression("?# + " . $statisticManager->inWork , "IN_WORK");
					}
					if(!empty($statisticManager->session))
					{
						$fieldsUpdate['SESSION'] = new SqlExpression("?# + " . $statisticManager->session , "SESSION");
					}
					if(!empty($statisticManager->lead))
					{
						$fieldsUpdate['LEAD'] = new SqlExpression("?# + " . $statisticManager->lead , "LEAD");
					}
					if(!empty($statisticManager->message))
					{
						$fieldsUpdate['MESSAGE'] = new SqlExpression("?# + " . $statisticManager->message , "MESSAGE");
					}

					if(!empty($fieldsUpdate))
					{
						ConfigStatisticTable::update($statisticManager->id, $fieldsUpdate);
					}
				}
			}
		}
	}

	/**
	 * @param $lineId
	 * @return bool
	 * @throws \Exception
	 */
	public static function add($lineId)
	{
		$result = false;

		$resultAdd = ConfigStatisticTable::add([
			'CONFIG_ID' => $lineId
		]);

		if($resultAdd->isSuccess() && !empty($resultAdd->getId()))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @param $lineId
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete($lineId)
	{
		$result = false;

		$resultAdd = ConfigStatisticTable::delete($lineId);

		if($resultAdd->isSuccess())
		{
			unset(self::$instance[$lineId]);

			$result = true;
		}

		return $result;
	}

	/**
	 * ConfigStatistic constructor.
	 * @param $lineId
	 */
	private function __construct($lineId)
	{
		$this->id = $lineId;
	}

	private function __clone()
	{

	}
	private function __wakeup()
	{

	}

	/**
	 * @return $this
	 */
	public function addClosed()
	{
		self::addEventHandlerSave();

		$this->closed++;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function deleteClosed()
	{
		self::addEventHandlerSave();

		$this->closed--;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function addInWork()
	{
		self::addEventHandlerSave();

		$this->inWork++;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function deleteInWork()
	{
		self::addEventHandlerSave();

		$this->inWork--;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function addSession()
	{
		self::addEventHandlerSave();

		$this->session++;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function deleteSession()
	{
		self::addEventHandlerSave();

		$this->session--;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function addLead()
	{
		self::addEventHandlerSave();

		$this->lead++;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function deleteLead()
	{
		self::addEventHandlerSave();

		$this->lead--;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function addMessage()
	{
		self::addEventHandlerSave();

		$this->message++;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function deleteMessage()
	{
		self::addEventHandlerSave();

		$this->message--;

		return $this;
	}
}