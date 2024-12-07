<?php

namespace Bitrix\Crm\Ml\Agent;

use Bitrix\Crm\Ml\Controller\Details;
use Bitrix\Crm\Ml\Internals\Lock;
use Bitrix\Crm\Ml\Internals\ModelTrainingTable;
use Bitrix\Crm\Ml\Model;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Crm\Ml\TrainingState;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Ml\Client;

class ModelTrainer
{
	private const RECORDS_LIMIT = 25;
	private const LOCK_NAME = 'crm-ml-training-lock';

	/**
	 * Executes next part of the training request.
	 *
	 * @param int $trainingId Id of the training request.
	 * @return string
	 */
	public static function run($trainingId)
	{
		if (!Loader::includeModule('ml'))
		{
			return '';
		}

		$trainingId = (int)$trainingId;

		if (!Scoring::isScoringAvailable())
		{
			self::cancelTraining($trainingId);

			return '';
		}

		$training = ModelTrainingTable::getRowById($trainingId);
		if (
			!$training
			|| in_array($training['STATE'], [TrainingState::FINISHED, TrainingState::CANCELED], true)
		)
		{
			return '';
		}

		$model = Scoring::getModelByName($training["MODEL_NAME"]);
		$lastId = $training["LAST_ID"];
		$rowIdField = $model?->getRowIdField();
		$targetField = $model?->getTargetField();

		if (!$rowIdField)
		{
			return '';
		}

		if (!Lock::get(static::getLockName()))
		{
			// repeat iteration later
			return __CLASS__ . "::run({$trainingId});";
		}

		// create ml model if it does not exist yet
		if ($training["STATE"] === TrainingState::PENDING_CREATION)
		{
			if(!$model?->getModelId())
			{
				$creationResult = \Bitrix\Ml\Model::create(
					$model?->getName(),
					\Bitrix\Ml\Model::TYPE_BINARY,
					$model?->getPossibleFields()
				);

				if (!$creationResult->isSuccess())
				{
					// repeat iteration later
					return __CLASS__ . "::run({$trainingId});";
				}

				$mlModel = $creationResult->getData()['model'];
				$model?->setMlModel($mlModel);
			}

			if ($model?->getModelId())
			{
				ModelTrainingTable::update(
					$trainingId,
					[
						'STATE' => TrainingState::IDLE,
					]
				);
			}
		}

		$trainingSet = $model?->getTrainingSet($lastId, static::RECORDS_LIMIT);

		$trainingSetWithFeatures = [];
		$client = new Client();
		if (count($trainingSet) > 0)
		{
			foreach ($trainingSet as $entityId)
			{
				// Writing LAST_ID before building features because buildFeaturesVector can run too long (or even fail)
				// in some extreme cases, so we will skip broken entity next time.
				ModelTrainingTable::update(
					$trainingId,
					[
						'STATE' => TrainingState::GATHERING,
						'LAST_ID' => $entityId
					]
				);
				$trainingSetWithFeatures[] = $model?->buildFeaturesVector($entityId);
			}

			$appendResult = $client->appendLearningData([
				'modelName' => $model?->getName(),
				'records' => $trainingSetWithFeatures
			]);

			if ($appendResult->isSuccess())
			{
				$successRecords = 0;
				$failedRecords = 0;
				foreach ($trainingSetWithFeatures as $trainingRecord)
				{
					if ($trainingRecord[$targetField] === 'Y')
					{
						$successRecords++;
					}
					else
					{
						$failedRecords++;
					}
				}
				ModelTrainingTable::update(
					$trainingId,
					[
						'STATE' => TrainingState::GATHERING,
						'RECORDS_SUCCESS' => new SqlExpression("?# + ?i", 'RECORDS_SUCCESS', $successRecords),
						'RECORDS_FAILED' => new SqlExpression("?# + ?i", 'RECORDS_FAILED', $failedRecords)
					]
				);

				Details::onTrainingProgress($model, ModelTrainingTable::getRowById($trainingId));
			}

			// repeat iteration later
			Lock::release(static::getLockName());

			return __CLASS__ . "::run({$trainingId});";
		}
		else
		{
			$startResult = $client->startTraining([
				'modelName' => $model?->getName()
			]);

			if($startResult->isSuccess())
			{
				ModelTrainingTable::update(
					$trainingId,
					[
						'STATE' => TrainingState::TRAINING
					]
				);

				// success
				Lock::release(static::getLockName());

				return '';
			}
			else
			{
				// repeat iteration later
				Lock::release(static::getLockName());

				return __CLASS__ . "::run({$trainingId});";
			}
		}
	}

	/**
	 * Schedules training for the model.
	 *
	 * @param Model\Base $model Model to train.
	 * @return Result
	 */
	public static function scheduleTraining(Model\Base $model)
	{
		$result = new Result();

		$currentTraining = Scoring::getLastTraining($model);

		if($currentTraining && !in_array($currentTraining["STATE"], [TrainingState::FINISHED, TrainingState::CANCELED]))
		{
			return $result->addError(new Error("Can not schedule training of the model " . $model->getName() . " because it is already in training"));
		}

		$addResult = ModelTrainingTable::add([
			"MODEL_NAME" => $model->getName(),
			"STATE" => TrainingState::PENDING_CREATION,
		]);

		if(!$addResult->isSuccess())
		{
			return $result->addErrors($addResult->getErrors());
		}
		$trainingId = (int)$addResult->getId();

		\CAgent::AddAgent(
			__CLASS__."::run({$trainingId});",
			"crm",
			"Y",
			30
		);

		return $result;
	}

	public static function cancelTraining($id)
	{
		$training = ModelTrainingTable::getByPrimary($id)->fetch();

		if($training["STATE"] === TrainingState::FINISHED || $training["STATE"] === TrainingState::CANCELED)
		{
			return;
		}

		ModelTrainingTable::update($id, [
			"STATE" => TrainingState::CANCELED,
			"DATE_FINISH" => new DateTime()
		]);
	}

	public static function getLockName()
	{
		return static::LOCK_NAME . "_" . \CMain::getServerUniqID();
	}
}
