<?php

namespace Bitrix\Crm\Ml;

use Bitrix\Crm\Ml\Internals\EO_PredictionQueue;
use Bitrix\Crm\Ml\Internals\PredictionQueueTable;
use Bitrix\Main\Type\DateTime;

class PredictionQueue extends EO_PredictionQueue
{
	const STATE_IDLE = "idle";
	const STATE_EXECUTING = "executing";
	const STATE_DELAYED = "delayed";

	const QUEUE_PROCESS_TIME_LIMIT = 30; //seconds

	/**
	 * Agent to process prediction requests queue.
	 *
	 * @return string
	 */
	public static function processQueue()
	{
		if (!Scoring::isScoringAvailable())
		{
			return '';
		}

		$started = microtime(true);
		$timeElapsed = 0;

		while ($timeElapsed < self::QUEUE_PROCESS_TIME_LIMIT)
		{
			$nextRequestId = static::getNextRequestId();
			if(!$nextRequestId)
			{
				break;
			}

			static::executeRequest($nextRequestId);

			$timeElapsed = microtime(true) - $started;
		}

		return "\Bitrix\Crm\Ml\PredictionQueue::processQueue();";
	}

	/**
	 * Return id of the next unprocessed request in queue.
	 *
	 * @return int|false
	 */
	public static function getNextRequestId()
	{
		$row = PredictionQueueTable::getRow([
			"select" => ["ID"],
			"filter" => [
				"LOGIC" => "OR",
				[
					"=STATE" => self::STATE_IDLE
				],
				[
					"=STATE" => self::STATE_DELAYED,
					"<DELAYED_UNTIL" => new DateTime()
				]
			]
		]);

		return $row ? (int)$row["ID"] : false;
	}

	/**
	 * Executes request from the queue.
	 *
	 * @param int $id Id of the request.
	 * @return bool
	 */
	public static function executeRequest($id)
	{
		$id = (int)$id;
		if(!$id)
		{
			return false;
		}

		$instance = PredictionQueueTable::getById($id)->fetchObject();
		if(!$instance)
		{
			return false;
		}

		return $instance->execute();
	}

	/**
	 * Executes prediction update request
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function execute()
	{
		$this->setState(self::STATE_EXECUTING);
		$this->save();
		$scoringModel = Scoring::getScoringModel($this->getEntityTypeId(), $this->getEntityId());
		if(!$scoringModel || !$scoringModel->isReady())
		{
			$this->delete();
			return false;
		}

		$additionalParameters = $this->getAdditionalParameters();
		if(!is_array($additionalParameters))
		{
			$additionalParameters = [];
		}

		$updateResult = Scoring::updatePrediction($this->getEntityTypeId(), $this->getEntityId(), $additionalParameters);
		if(!$updateResult->isSuccess())
		{
			$created = $this->getCreated();
			$yesterday = time() - 86400; // 60 * 60 * 24
			if ($created->getTimestamp() > $yesterday)
			{
				$error = join("; ", $updateResult->getErrorMessages());
				$this->setError($error);
				$this->delay();
				return false;
			}
		}

		$this->delete();
		return true;
	}

	public function delay()
	{
		$delayedUntil = new DateTime();
		$delayedUntil->add("1 hour");
		$this->setDelayedUntil($delayedUntil);
		$this->setState(self::STATE_DELAYED);
		$this->save();
	}
}
