<?php

namespace Bitrix\Crm\Ml;

use Bitrix\Bitrix24\Feature;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Ml\Agent\ModelTrainer;
use Bitrix\Crm\Ml\Controller\Details;
use Bitrix\Crm\Ml\Internals\ModelTrainingTable;
use Bitrix\Crm\Ml\Internals\PredictionHistoryTable;
use Bitrix\Crm\Ml\Internals\PredictionQueueTable;
use Bitrix\Crm\Ml\Model;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Timeline\ScoringController;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Ml\Client;

class Scoring
{
	const PREDICTION_BATCH = "batch";
	const PREDICTION_REAL_TIME = "realtime";
	const PREDICTION_IMMEDIATE = "immediate";

	const EVENT_INITIAL_PREDICTION = "initial";
	const EVENT_ENTITY_UPDATE = "update";
	const EVENT_ACTIVITY = "activity";

	const MINIMAL_TRAINING_SET = 2000;
	const MINIMAL_CLASS_SIZE = 200;

	const RETRAIN_PERIOD = 90; // days

	const ERROR_MODEL_ALREADY_EXISTS = "model_already_exists";
	const ERROR_NOT_ENOUGH_DATA = "not_enough_data";
	const ERROR_TOO_SOON = "too_soon";

	public static function getMinimalTrainingSetSize()
	{
		return defined("CRM_ML_MINIMAL_TRAINING_SET_SIZE") ? CRM_ML_MINIMAL_TRAINING_SET_SIZE : static::MINIMAL_TRAINING_SET;
	}

	public static function getMinimalClassSize()
	{
		return defined("CRM_ML_MINIMAL_TRAINING_CLASS_SIZE") ? CRM_ML_MINIMAL_TRAINING_CLASS_SIZE : static::MINIMAL_CLASS_SIZE;
	}

	/**
	 * Starts training of the model, if all of the pre-requirements are met, such as:
	 *  - ml model should not exists, trainer will create it later
	 *  - last training should be in state finished
	 *
	 * @param Model\Base $model Scoring model to train.
	 * @return Result
	 */
	public static function startModelTraining(Model\Base $model)
	{
		$result = new Result();
		if(!Loader::includeModule("ml"))
		{
			return $result->addError(new Error("ML module is not installed"));
		}

		if(!static::isEnabled())
		{
			return $result->addError(new Error("Scoring is not enabled for your tariff"));
		}

		if($model->getMlModel())
		{
			return $result->addError(new Error("ML model should be deleted prior to starting learning process"));
		}

		$lastTraining = static::getLastTraining($model);
		if($lastTraining && !in_array($lastTraining["STATE"], [TrainingState::FINISHED, TrainingState::CANCELED]))
		{
			return $result->addError(new Error("Model " . $model->getName() . " is already in training"));
		}

		list($successfulRecords, $failedRecords) = $model->getTrainingSetSize();
		$totalRecords = $successfulRecords + $failedRecords;

		if($totalRecords < static::getMinimalTrainingSetSize()
			|| $successfulRecords < static::getMinimalClassSize()
			|| $failedRecords < static::getMinimalClassSize()
		)
		{
			return $result->addError(new Error("Not enough data to start model training"));
		}

		// check model training state

		$scheduleResult = ModelTrainer::scheduleTraining($model);
		if(!$scheduleResult->isSuccess())
		{
			return $result->addErrors($scheduleResult->getErrors());
		}

		return $result;
	}

	/**
	 * Checks if scoring model is suitable to start training.
	 *
	 * @param Model\Base $model Scoring model.
	 * @return Result
	 */
	public static function canStartTraining(Model\Base $model, $useCache = false)
	{
		$result = new Result();

		if($model->getState() !== false)
		{
			return $result->addError(new Error("Model already exists", static::ERROR_MODEL_ALREADY_EXISTS));
		}

		if($useCache)
		{
			list($successfulRecords, $failedRecords) = $model->getCachedTrainingSetSize();
		}
		else
		{
			list($successfulRecords, $failedRecords) = $model->getTrainingSetSize();
		}
		$totalRecords = $successfulRecords + $failedRecords;
		if($totalRecords < static::getMinimalTrainingSetSize()
			|| $successfulRecords < static::getMinimalClassSize()
			|| $failedRecords < static::getMinimalClassSize()
		)
		{
			return $result->addError(new Error("Not enough data to train model", static::ERROR_NOT_ENOUGH_DATA));
		}

		$lastTraining = static::getLastTraining($model);
		if($lastTraining)
		{
			if($lastTraining["DATE_FINISH"] instanceof DateTime)
			{
				$lastTrainingTimestamp = $lastTraining["DATE_FINISH"]->getTimestamp();
				$retrainPeriodInSeconds = static::RETRAIN_PERIOD * 24 * 60 * 60;
				if((time() - $lastTrainingTimestamp) < $retrainPeriodInSeconds)
				{
					return $result->addError(new Error("You can not start training. Too little time passed since the last training", static::ERROR_TOO_SOON));
				}
			}
		}

		return $result;
	}

	public static function deleteMlModel(Model\Base $model)
	{
		$result = new Result();
		if(!Loader::includeModule("ml"))
		{
			return $result->addError(new Error("ML module is not installed"));
		}

		$lastTraining = static::getLastTraining($model);
		if($lastTraining["STATE"] !== TrainingState::FINISHED)
		{
			ModelTrainer::cancelTraining($lastTraining["ID"]);
		}

		$mlModel = $model->getMlModel();
		if(!$mlModel)
		{
			return $result;
		}

		$deletionResult = $mlModel->deleteCascade();
		if(!$deletionResult->isSuccess())
		{
			return $result->addErrors($deletionResult->getErrors());
		}

		$model->unassociateMlModel();

		return $result;
	}

	/**
	 * Return id of the scheduled request.
	 *
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param string $type
	 * @param array $additionalParameters
	 *  - EVENT_TYPE
	 *  - ASSOCIATED_ACTIVITY_ID
	 *
	 * @return int
	 */
	public static function queuePredictionUpdate($entityTypeId, $entityId, array $additionalParameters = [])
	{
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		
		if(!static::isMlAvailable() || !static::isEnabled() || !$entityTypeId || !$entityId)
		{
			return false;
		}

		$scoringModel = static::getScoringModel($entityTypeId, $entityId);
		if(!$scoringModel || !$scoringModel->isReady())
		{
			return false;
		}

		if(isset($additionalParameters['TYPE']))
		{
			$type = $additionalParameters['TYPE'];
			unset($additionalParameters['TYPE']);
		}
		else
		{
			$type = self::PREDICTION_REAL_TIME;
		}

		// 1. checking for another pending request
		$latestPrediction = PredictionQueueTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			],
			"limit" => 1,
		])->fetch();

		if($latestPrediction)
		{
			return $latestPrediction["ID"];
		}

		$scheduledRequest = new PredictionQueue();
		$scheduledRequest->setEntityTypeId($entityTypeId);
		$scheduledRequest->setEntityId($entityId);
		$scheduledRequest->setType($type);
		$scheduledRequest->setAdditionalParameters($additionalParameters);
		$insertResult = $scheduledRequest->save();

		if(!$insertResult->isSuccess())
		{
			return false;
		}

		$scheduledId = $insertResult->getId();
		if($type === self::PREDICTION_REAL_TIME && $scoringModel->isReady())
		{
			\Bitrix\Main\Application::getInstance()->addBackgroundJob([PredictionQueue::class, "executeRequest"], [$scheduledId]);
			$scheduledRequest->setState(PredictionQueue::STATE_EXECUTING);
			$scheduledRequest->save();
		}
		else if($type === self::PREDICTION_IMMEDIATE && $scoringModel->isReady())
		{
			$scheduledRequest->setState(PredictionQueue::STATE_EXECUTING);
			$scheduledRequest->save();
			PredictionQueue::executeRequest($scheduledId);
		}
		else
		{
			$scheduledRequest->delay();
		}

		return $scheduledRequest->getId();
	}

	/**
	 * @param $entityTypeId
	 * @param $entityId
	 * @param array $parameters
	 * - EVENT_TYPE string
	 * - ASSOCIATED_ACTIVITY_ID int
	 * @return Result
	 */
	public static function updatePrediction($entityTypeId, $entityId, array $parameters = [])
	{
		$result = new Result();
		if(!Loader::includeModule("ml"))
		{
			return $result->addError(new Error("ML module is not installed"));
		}
		if(!static::isEnabled())
		{
			return $result->addError(new Error("Scoring is not enabled for your tariff"));
		}

		$scoringModel = static::getScoringModel($entityTypeId, $entityId);

		if(!$scoringModel || !$scoringModel->isReady())
		{
			$result->addError(new Error($scoringModel ? "Scoring model is not ready" : "Scoring model is not found"));
			return $result;
		}
		$featuresVector = $scoringModel->buildFeaturesVector($entityId);

		$mlClient = new Client();
		$predictionResult = $mlClient->predictRecord([
			"modelName" => $scoringModel->getName(),
			"fields" => $featuresVector
		]);

		if(!$predictionResult->isSuccess())
		{
			$result->addErrors($predictionResult->getErrors());
			return $result;
		}
		$answer = $predictionResult->getData();

		$predictionHistoryRecord = [
			"ENTITY_TYPE_ID" => $entityTypeId,
			"ENTITY_ID" => $entityId,
			"ANSWER" => $answer["label"],
			"SCORE" => round($answer["score"], 2),
			"MODEL_NAME" => $scoringModel->getName(),
			"EVENT_TYPE" => (string)$parameters["EVENT_TYPE"] ?: null,
			"ASSOCIATED_ACTIVITY_ID" => (int)$parameters["ASSOCIATED_ACTIVITY_ID"] ?: null,
		];

		$previousPrediction = PredictionHistoryTable::getRow([
			"filter" => [
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			],
			"order" => [
				"ID" => "DESC"
			]
		]);

		if($previousPrediction)
		{
			$delta = $predictionHistoryRecord["SCORE"] - $previousPrediction["SCORE"];
			$predictionHistoryRecord["SCORE_DELTA"] = round($delta, 2);

			// skip adding new prediction record, if score is not changed
			if($delta < 0.01)
			{
				return $result;
			}
		}

		$addResult = PredictionHistoryTable::add($predictionHistoryRecord);
		if(!$addResult->isSuccess())
		{
			$result->addErrors($addResult->getErrors());
			return $result;
		}
		$predictionId = $addResult->getId();
		$predictionHistoryRecord["ID"] = $predictionId;
		\Bitrix\Crm\Timeline\ScoringController::getInstance()->onCreate($predictionId, ["FIELDS" => $predictionHistoryRecord]);

		if($entityTypeId == \CCrmOwnerType::Deal)
		{
			$dealManager = new \CCrmDeal();
			$dealFields = [
				"PROBABILITY" => floor($predictionHistoryRecord['SCORE'] * 100)
			];
			$dealManager->Update($entityId, $dealFields, false, true, [
				"REGISTER_SONET_EVENT" => false,
				"ENABLE_SYSTEM_EVENTS" => false,
				"IS_SYSTEM_ACTION" => true
			]);
		}

		static::sendPredictionUpdatePullEvent($entityTypeId, $entityId, $predictionHistoryRecord);
		$result->setData($predictionHistoryRecord);
		return $result;
	}

	/**
	 * Deletes prediction with the given id.
	 *
	 * @param int $historyId
	 */
	public static function deletePrediction($historyId)
	{
		$historyRecord = PredictionHistoryTable::getRowById($historyId);

		if(!$historyRecord)
		{
			return false;
		}

		PredictionHistoryTable::delete($historyId);

		ScoringController::getInstance()->onDelete($historyId, [
			"ENTITY_TYPE_ID" => $historyRecord["ENTITY_TYPE_ID"],
			"ENTITY_ID" => $historyRecord["ENTITY_ID"],
		]);

		return true;
	}

	/**
	 * Removes references to this activity
	 *
	 * @param $activityId
	 */
	public static function onActivityDelete($activityId)
	{
		$cursor = PredictionHistoryTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=ASSOCIATED_ACTIVITY_ID" => $activityId
			]
		]);

		while ($row = $cursor->fetch())
		{
			PredictionHistoryTable::update($row["ID"], [
				"ASSOCIATED_ACTIVITY_ID" => null
			]);
		}
	}

	/**
	 * Deletes prediction history records, associated with the entity.
	 *
	 * @param int $entityTypeId Entity type.
	 * @param int $entityId Entity id.
	 * @return void
	 */
	public static function onEntityDelete($entityTypeId, $entityId)
	{
		PredictionHistoryTable::deleteBatch([
			"=ENTITY_TYPE_ID" => $entityTypeId,
			"=ENTITY_ID" => $entityId
		]);
	}

	/**
	 * Replaces entity type and id in history records.
	 *
	 * @param int $entityTypeId Old entity type.
	 * @param int $entityId Old entity id.
	 * @param int @newEntityTypeId New entity type.
	 * @param int @newEntityId New entity id.
	 * @return void
	 */
	public static function replaceAssociatedEntity($entityTypeId, $entityId, $newEntityTypeId, $newEntityId)
	{
		PredictionHistoryTable::updateBatch(
			[
				"ENTITY_TYPE_ID" => $newEntityTypeId,
				"ENTITY_ID" => $newEntityId
			],
			[
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			]
		);
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @return Model\Base
	 */
	public static function getScoringModel($entityTypeId, $entityId)
	{
		static $cache = [];
		$key = "{$entityTypeId}_{$entityId}";
		if (isset($cache[$key]))
		{
			return $cache[$key];
		}

		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				$cache[$key] = new Model\LeadScoring(Model\LeadScoring::MODEL_NAME);
				return $cache[$key];
			case \CCrmOwnerType::Deal:
				$modelName = Model\DealScoring::getModelNameByDeal($entityId);
				if(!$modelName)
				{
					return null;
				}
				$cache[$key] = new Model\DealScoring($modelName);
				return $cache[$key];
			default:
				return null;
		}
	}

	public static function getAvailableModelNames()
	{
		$result = Model\DealScoring::getModelNames();
		if(LeadSettings::isEnabled())
		{
			$result = array_merge(Model\LeadScoring::getModelNames(), $result);
		}
		return $result;
	}

	/**
	 * @param $modelId
	 * @return Model\Base
	 */
	public static function getModelByName($modelName)
	{
		static $cache = [];
		if (isset($cache[$modelName]))
		{
			return $cache[$modelName];
		}
		$possibleModels = [Model\LeadScoring::class, Model\DealScoring::class];

		foreach ($possibleModels as $model)
		{
			$possibleNames = $model::getModelNames();

			if(in_array($modelName, $possibleNames))
			{
				$cache[$modelName] = new $model($modelName);
				return $cache[$modelName];
			}
		}

		return null;
	}

	/**
	 * Returns available classes to work with scoring models.
	 *
	 * @return array
	 */
	public static function getModelClasses()
	{
		$result = [];
		if (LeadSettings::isEnabled())
		{
			$result[] = Model\LeadScoring::class;
		}
		$result[] = Model\DealScoring::class;
		return $result;
	}

	public static function hasAccess(string $modelName, int $userId = 0): bool
	{
		$model = static::getModelByName($modelName);
		if (!$model)
		{
			return false;
		}

		return $model->hasAccess($userId);
	}

	/**
	 * Return current training fields for the specified model.
	 *
	 * @param Model\Base $model
	 * @return array|false
	 */
	public static function getLastTraining(Model\Base $model)
	{
		return ModelTrainingTable::getList([
			"filter" => [
				"=MODEL_NAME" => $model->getName()
			],
			"order" => [
				"ID" => "desc"
			],
			"limit" => 1
		])->fetch();
	}

	/**
	 * @param Event $event
	 */
	public static function onMlModelStateChange(Event $event)
	{
		if(!Loader::includeModule("ml"))
		{
			return;
		}

		$mlModel = $event->getParameter("model");
		$model = Scoring::getModelByName($mlModel->getName());
		Details::onModelUpdate($model);

		$currentTraining = Scoring::getLastTraining($model);
		if($currentTraining && !in_array($currentTraining["STATE"], [TrainingState::FINISHED, TrainingState::CANCELED]))
		{
			$updatedTrainingFields = [];
			// update latest training
			// update performance metric
			switch ($model->getState())
			{
				case \Bitrix\Ml\Model::STATE_TRAINING:
					$updatedTrainingFields["STATE"] = TrainingState::TRAINING;
					break;
				case \Bitrix\Ml\Model::STATE_EVALUATING:
					$updatedTrainingFields["STATE"] = TrainingState::EVALUATING;
					break;
				case \Bitrix\Ml\Model::STATE_READY:
					$updatedTrainingFields["STATE"] = TrainingState::FINISHED;
					$updatedTrainingFields["DATE_FINISH"] = new DateTime();
					$performance = $event->getParameter("performance");
					if($performance && $performance["AUC"])
					{
						$updatedTrainingFields["AREA_UNDER_CURVE"] = (float)$performance["AUC"];
					}
					break;
				default:
					break;
			}

			ModelTrainingTable::update($currentTraining["ID"], $updatedTrainingFields);

			$currentTraining = array_merge($currentTraining, $updatedTrainingFields);
			Details::onTrainingProgress($model, $currentTraining);
		}
	}

	/**
	 * Returns true if machine learning is installed for this instance.
	 *
	 * @return bool
	 */
	public static function isMlAvailable()
	{
		return ModuleManager::isModuleInstalled("ml");
	}

	/**
	 * Returns true if scoring is enabled for this portal by the tariffs.
	 */
	public static function isEnabled()
	{
		if(!Loader::includeModule("bitrix24"))
		{
			return true;
		}

		return Feature::isFeatureEnabled("crm_scoring");
	}

	/**
	 * Returns current prediction record or false if prediction is not found.
	 *
	 * @param int $entityTypeId Entity type id.
	 * @param int $entityId Id of the entity.
	 * @return array|false
	 */
	public static function getCurrentPrediction($entityTypeId, $entityId)
	{
		$model = static::getScoringModel($entityTypeId, $entityId);
		if(!$model)
		{
			return false;
		}

		return Internals\PredictionHistoryTable::getList([
			'select' => [
				'ANSWER',
				'SCORE',
				'SCORE_DELTA',
				'CREATED',
				'EVENT_TYPE',
				'ASSOCIATED_ACTIVITY_ID'
			],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
				'=MODEL_NAME' => $model->getName(),
				'=IS_PENDING' => 'N'
			],
			'order' => [
				'CREATED' => 'desc'
			],
			'limit' => 1
		])->fetch();
	}

	/**
	 * Tries to create first prediction for the given entity. Preconditions:
 	 *  - ml module should be installed
	 *  - model for this entity type should be in ready state
	 *  - this entity should not have another predictions
	 *
	 * @param int $entityTypeId Type of the entity.
	 * @param int $entityId Id of the entity.
	 * @param bool $isImmediate Should prediction request be executed immediately.
	 *
	 * @return bool
	 */
	public static function tryCreateFirstPrediction($entityTypeId, $entityId, $isImmediate = false)
	{
		if(!static::isMlAvailable() || !Loader::includeModule("ml") || !static::isEnabled())
		{
			return false;
		}

		$model = static::getScoringModel($entityTypeId, $entityId);
		if(!$model || $model->getState() !== \Bitrix\Ml\Model::STATE_READY)
		{
			return false;
		}

		$predictionCheck = PredictionHistoryTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			]
		]);

		if($predictionCheck->fetch())
		{
			return false;
		}

		$queueCheck = PredictionQueueTable::getList([
			"select" => ["ID"],
			"filter" => [
				"=STATE" => PredictionQueue::STATE_IDLE,
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			]
		]);

		if($row = $queueCheck->fetch())
		{
			PredictionQueue::executeRequest($row["ID"]);
		}
		else
		{
			static::queuePredictionUpdate($entityTypeId, $entityId, [
				"TYPE" => $isImmediate ? static::PREDICTION_IMMEDIATE : static::PREDICTION_REAL_TIME,
				"EVENT_TYPE" => static::EVENT_INITIAL_PREDICTION
			]);
		}

		return true;
	}

	public static function sendPredictionUpdatePullEvent($entityTypeId, $entityId, $predictionRecord)
	{
		if(!Loader::includeModule("pull"))
		{
			return;
		}

		\CPullWatch::AddToStack(
			static::getPredictionUpdatePullTag($entityTypeId, $entityId),
			[
				"module_id" => "crm",
				"command" => "predictionUpdate",
				"params" => [
					"entityType" => \CCrmOwnerType::ResolveName($entityTypeId),
					"entityId" => $entityId,
					"predictionRecord" => $predictionRecord
				]
			]
		);
	}

	public static function getPredictionUpdatePullTag($entityTypeId, $entityId)
	{
		$entityType = \CCrmOwnerType::ResolveName($entityTypeId);
		return "CRM_ML_SCORING_PREDICTION_" . $entityType . "_" . $entityId;
	}

	public static function getLicenseInfoTitle()
	{
		return Loc::getMessage("CRM_SCORING_LICENSE_TITLE");
	}

	public static function getLicenseInfoText()
	{
		$result =
				"<p>".Loc::getMessage("CRM_SCORING_LICENSE_TEXT_P1")."</p>".
				"<p>".Loc::getMessage("CRM_SCORING_LICENSE_TEXT_P2")."</p>".
				"<p>".Loc::getMessage("CRM_SCORING_LICENSE_TEXT_P3")."</p>";

		return $result;
	}
}