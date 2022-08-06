<?php

namespace Bitrix\Crm\Ml\Model;

use Bitrix\Crm\Ml\Internals\ModelTrainingTable;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;
use Bitrix\Ml\Model;

abstract class Base implements \JsonSerializable
{
	protected $name = "";
	protected $mlModel;

	const TRAINING_SET_SIZE_CACHE_PATH = "/crm/ml/model_training_set/";
	const TRAINING_SET_SIZE_CACHE_TTL = 86400; //1 day

	public function __construct($name)
	{
		$this->name = $name;
		if(Loader::includeModule("ml"))
		{
			$this->mlModel = Model::loadWithName($this->name);
		}
	}

	/**
	 * Return name of the model.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns true if model is ready for real-time prediction.
	 *
	 * @return bool
	 */
	public function isReady()
	{
		return $this->mlModel? $this->mlModel->getState() === Model::STATE_READY : false;
	}

	public function setMlModel(Model $mlModel)
	{
		if($this->mlModel)
		{
			throw new SystemException("ML model is already associated with the scoring model");
		}

		$this->mlModel = $mlModel;
	}

	public function getMlModel()
	{
		return $this->mlModel;
	}

	public function unassociateMlModel()
	{
		$this->mlModel = null;
	}

	/**
	 * Returns id of the model.
	 *
	 * @return int|false
	 */
	public function getModelId()
	{
		return $this->mlModel? $this->mlModel->getId() : false;
	}

	/**
	 * @return string|false
	 */
	public function getState()
	{
		return $this->mlModel? $this->mlModel->getState() : false;
	}

	/**
	 * Return name of the row id field in the feature vector.
	 *
	 * @return string|false
	 */
	public function getRowIdField()
	{
		foreach($this->getPossibleFields() as $fieldName => $fieldDescription)
		{
			if(isset($fieldDescription["isRowId"]) && $fieldDescription["isRowId"])
			{
				return $fieldName;
			}
		}

		return false;
	}

	/**
	 * Return name of the target field in the feature vector.
	 *
	 * @return string|false
	 */
	public function getTargetField()
	{
		foreach($this->getPossibleFields() as $fieldName => $fieldDescription)
		{
			if(isset($fieldDescription["isTarget"]) && $fieldDescription["isTarget"])
			{
				return $fieldName;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		[$recordsSuccess, $recordsFailed] = $this->getTrainingSetSize();
		return [
			"name" => $this->getName(),
			"state" => $this->getState(),
			"title" => $this->getTitle(),
			"recordsSuccess" => $recordsSuccess,
			"recordsFailed" => $recordsFailed,
		];
	}

	/**
	 * Should return array of field descriptions.
	 *
	 * @return array
	 */
	abstract public function getPossibleFields();

	/**
	 * Should return array of available name for the model type.
	 *
	 * @return string[]
	 */
	public static function getModelNames()
	{
		return [];
	}

	/**
	 * Should return feature vector for the crm entity.
	 *
	 * @param int $entityId Id of the entity.
	 * @return array|false
	 */
	abstract public function buildFeaturesVector($entityId);

	/**
	 * Should return count of successful and failed records in the training set for this model.
	 *
	 * @return [$successfulCount, $failedCount]
	 */
	abstract public function getTrainingSetSize();

	/**
	 * Cached version of getTrainingSetSize()
	 *
	 * @return [$successfulCount, $failedCount]
	 */
	public function getCachedTrainingSetSize()
	{
		$cacheId = $this->getName();
		$cache = Application::getInstance()->getCache();
		if($cache->initCache(static::TRAINING_SET_SIZE_CACHE_TTL, $cacheId, static::TRAINING_SET_SIZE_CACHE_PATH))
		{
			$result = $cache->getVars();
		}
		else
		{
			$result = $this->getTrainingSetSize();

			$cache->startDataCache();
			$cache->endDataCache($result);
		}

		return $result;
	}

	/**
	 * Should return array of the ids of the entities, that should be used for building the next part of the training set
	 *
	 * @param int $fromId Id of the starting entity.
	 * @param int $limit Maximum count of the records in the training subset.
	 * @return int[]
	 */
	abstract public function getTrainingSet($fromId, $limit);

	/**
	 * Should return title for this model
	 *
	 * @return string
	 */
	abstract public function getTitle();

	abstract public function hasAccess(int $userId = 0);

	/**
	 * Returns current training fields
	 */
	public function getCurrentTraining()
	{
		$row = ModelTrainingTable::getList([
			"filter" => [
				"=MODEL_NAME" => $this->getName(),
			],
			"order" => ["ID" => "desc"],
			"limit" => 1
		])->fetch();

		[$successfulRecords, $failedRecords] = $this->getTrainingSetSize();

		if($row["DATE_FINISH"] instanceof DateTime)
		{
			$now = time();
			$daysSinceTrain = round(($now - $row["DATE_FINISH"]->getTimestamp()) / 86400 );
			$daysToTrain = Scoring::RETRAIN_PERIOD - $daysSinceTrain;
			if($daysToTrain < 0)
			{
				$daysToTrain = 0;
			}
		}
		else
		{
			$daysToTrain = Scoring::RETRAIN_PERIOD;
		}

		if($row["DATE_FINISH"] instanceof DateTime)
		{
			$nextDate = clone $row["DATE_FINISH"];
			$nextDate->add(Scoring::RETRAIN_PERIOD . " day");
		}

		return [
			"ID" => (int)$row["ID"],
			"MODEL_NAME" => $this->getName(),
			"AREA_UNDER_CURVE" => (float)$row["AREA_UNDER_CURVE"],
			"DATE_START" => $row["DATE_START"],
			"DATE_FINISH" => $row["DATE_FINISH"],
			"LAST_ID" => (int)$row["LAST_ID"],
			"RECORDS_SUCCESS" => (int)$row["RECORDS_SUCCESS"],
			"RECORDS_FAILED" => (int)$row["RECORDS_FAILED"],
			"STATE" => $row["STATE"],

			"RECORDS_SUCCESS_DELTA" => $successfulRecords - $row["RECORDS_SUCCESS"],
			"RECORDS_FAILED_DELTA" => $failedRecords - $row["RECORDS_FAILED"],
			"DAYS_TO_TRAIN" => $daysToTrain,
			"NEXT_DATE" => $nextDate ?? null,
		];
	}
}