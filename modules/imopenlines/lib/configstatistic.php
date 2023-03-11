<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\ImOpenLines\Model\ConfigStatisticTable;

/**
 * Class ConfigStatistic
 * @package Bitrix\ImOpenLines
 */
class ConfigStatistic
{
	/** @var array<int, ConfigStatistic> */
	private static array $instance = [];
	private static bool $flagEvent = false;

	protected bool $updated = false;
	protected int $id = 0;
	protected int $closed = 0;
	protected int $inWork = 0;
	protected int $session = 0;
	protected int $lead = 0;
	protected int $message = 0;

	/**
	 * @param int $lineId
	 * @return self
	 */
	public static function getInstance($lineId): self
	{
		if (empty(self::$instance[$lineId]) )
		{
			self::$instance[$lineId] = new self((int)$lineId);
		}

		return self::$instance[$lineId];
	}

	/**
	 * Add a handler to the save changes.
	 */
	public static function addEventHandlerSave(): void
	{
		if (empty(self::$flagEvent))
		{
			Application::getInstance()->addBackgroundJob(
				[__CLASS__, 'save'],
				[],
				Application::JOB_PRIORITY_LOW
			);

			self::$flagEvent = true;
		}
	}

	/**
	 * The event handler OnAfterEpilog.
	 * Data is saved only when the script completes.
	 * @return void
	 */
	public static function save(): void
	{
		foreach (self::$instance as $statisticManager)
		{
			if (
				$statisticManager instanceof self
				&& $statisticManager->id > 0
				&& $statisticManager->updated === true
			)
			{
				if (!empty(ConfigStatisticTable::getRowById($statisticManager->id)))
				{
					$fieldsUpdate = [];

					if ($statisticManager->closed != 0)
					{
						$fieldsUpdate['CLOSED'] = new SqlExpression("?# + " . $statisticManager->closed , "CLOSED");
					}
					if ($statisticManager->inWork != 0)
					{
						$fieldsUpdate['IN_WORK'] = new SqlExpression("?# + " . $statisticManager->inWork , "IN_WORK");
					}
					if ($statisticManager->session != 0)
					{
						$fieldsUpdate['SESSION'] = new SqlExpression("?# + " . $statisticManager->session , "SESSION");
					}
					if ($statisticManager->lead != 0)
					{
						$fieldsUpdate['LEAD'] = new SqlExpression("?# + " . $statisticManager->lead , "LEAD");
					}
					if ($statisticManager->message != 0)
					{
						$fieldsUpdate['MESSAGE'] = new SqlExpression("?# + " . $statisticManager->message , "MESSAGE");
					}

					if (!empty($fieldsUpdate))
					{
						ConfigStatisticTable::update($statisticManager->id, $fieldsUpdate);
					}
					$statisticManager->updated = false;
				}
			}
		}
	}

	/**
	 * @param $lineId
	 * @return bool
	 */
	public static function add($lineId): bool
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
	 */
	public static function delete($lineId): bool
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

	public function __clone()
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	public function __wakeup()
	{
		throw new \Bitrix\Main\NotImplementedException();
	}

	/**
	 * @return self
	 */
	public function addClosed(): self
	{
		self::addEventHandlerSave();

		$this->closed++;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function deleteClosed(): self
	{
		self::addEventHandlerSave();

		$this->closed--;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function addInWork(): self
	{
		self::addEventHandlerSave();

		$this->inWork++;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function deleteInWork(): self
	{
		self::addEventHandlerSave();

		$this->inWork--;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function addSession(): self
	{
		self::addEventHandlerSave();

		$this->session++;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function deleteSession(): self
	{
		self::addEventHandlerSave();

		$this->session--;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function addLead(): self
	{
		self::addEventHandlerSave();

		$this->lead++;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function deleteLead(): self
	{
		self::addEventHandlerSave();

		$this->lead--;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function addMessage(): self
	{
		self::addEventHandlerSave();

		$this->message++;
		$this->updated = true;

		return $this;
	}

	/**
	 * @return self
	 */
	public function deleteMessage(): self
	{
		self::addEventHandlerSave();

		$this->message--;
		$this->updated = true;

		return $this;
	}
}