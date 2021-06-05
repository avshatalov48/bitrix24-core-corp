<?php

namespace Bitrix\Disk\Search\Reindex;

use Bitrix\Disk\Driver;
use Bitrix\Main\Config\Option;

abstract class Stepper extends \Bitrix\Main\Update\Stepper
{
	const PORTION = 30;

	const STATUS_FINISH = 'F';
	const STATUS_PAUSE  = 'P';
	const STATUS_INDEX  = 'Y';

	protected $portionSize = self::PORTION;
	protected static $moduleId = Driver::INTERNAL_MODULE_ID;

	/**
	 * @return string
	 */
	abstract public static function getName();

	public static function isReady()
	{
		return self::getStatus() === self::STATUS_FINISH;
	}

	public static function getStatus()
	{
		return Option::get(static::getModuleId(), 'need' . static::getName(), self::STATUS_INDEX);
	}

	public static function pauseExecution()
	{
		Option::set(static::getModuleId(), 'need' . static::getName(), self::STATUS_PAUSE);
	}

	public static function finishExecution()
	{
		Option::set(static::getModuleId(), 'need' . static::getName(), self::STATUS_FINISH);
		Option::delete(static::getModuleId(), ['name' => static::getName()]);

		static::handleFinishExecution();
	}

	public static function handleFinishExecution()
	{}

	public static function restartExecution()
	{
		Option::set(static::getModuleId(), 'need' . static::getName(), self::STATUS_INDEX);
		Option::delete(static::getModuleId(), ['name' => static::getName()]);

		static::bind();
	}

	public static function continueExecution()
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			if ($status !== self::STATUS_INDEX)
			{
				Option::set(static::getModuleId(), 'need' . static::getName(), self::STATUS_INDEX);
			}
			static::bind();

			return true;
		}

		return false;
	}

	public static function continueExecutionWithoutAgent($portion = self::PORTION)
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			if ($status !== self::STATUS_INDEX)
			{
				Option::set(static::getModuleId(), 'need' . static::getName(), self::STATUS_INDEX);
			}

			$resultData = [];
			$indexer = new static();
			$indexer
				->setPortionSize($portion)
				->execute($resultData)
			;

			return true;
		}

		return false;
	}

	public function setPortionSize($portionSize)
	{
		$this->portionSize = $portionSize;

		return $this;
	}

	public function getPortionSize()
	{
		return $this->portionSize;
	}

	/**
	 * @param $lastId
	 *
	 * @return array
	 */
	abstract protected function processStep($lastId);

	public function execute(array &$result)
	{
		$statusAgent = self::getStatus();
		if ($statusAgent === self::STATUS_FINISH || $statusAgent === self::STATUS_PAUSE)
		{
			return self::FINISH_EXECUTION;
		}

		$status = $this->loadCurrentStatus();
		if (empty($status['count']) || $status['count'] < 0 || $status['steps'] >= $status['count'])
		{
			self::finishExecution();

			return self::FINISH_EXECUTION;
		}

		$newStatus = [
			'count' => $status['count'],
			'steps' => $status['steps'],
		];

		[
			'lastId' => $newStatus['lastId'],
			'steps' => $steps
		] = $this->processStep($status['lastId']);

		$newStatus['steps'] += $steps;
		if (!empty($newStatus['lastId']))
		{
			Option::set(static::getModuleId(), static::getName(), serialize($newStatus));
			$result = [
				'count' => $newStatus['count'],
				'steps' => $newStatus['steps'],
			];

			return self::CONTINUE_EXECUTION;
		}

		self::finishExecution();

		return self::FINISH_EXECUTION;
	}

	public function loadCurrentStatus()
	{
		$status = Option::get(static::getModuleId(), static::getName(), 'default');
		$status = ($status !== 'default'? @unserialize($status, ['allowed_classes' => false]) : []);
		$status = (is_array($status) ? $status : []);

		if (empty($status))
		{
			$status = [
				'lastId' => 0,
				'steps' => 0,
				'count' => $this->getCount()
			];
		}

		return $status;
	}

	/**
	 * @return int
	 */
	abstract protected function getCount();
}