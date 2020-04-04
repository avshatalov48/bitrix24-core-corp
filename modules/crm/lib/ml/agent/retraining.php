<?php

namespace Bitrix\Crm\Ml\Agent;

use Bitrix\Crm\Ml\Model\DealScoring;
use Bitrix\Crm\Ml\Model\LeadScoring;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Crm\Ml\TrainingState;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Ml\Model;

/**
 * Agent to check model last training date and retrain it.
 *
 * @package Bitrix\Crm\Ml\Agent
 */
class Retraining
{
	/**
	 * Executes this agent.
	 *
	 * @return string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function run()
	{
		if(!Loader::includeModule("ml"))
		{
			return static::class . "::run();";
		}

		$possibleModels = [];

		$possibleModels = array_merge($possibleModels, DealScoring::getModelNames());
		$possibleModels = array_merge($possibleModels, LeadScoring::getModelNames());

		foreach ($possibleModels as $modelName)
		{
			$model = Scoring::getModelByName($modelName);
			if(!$model || $model->getState() !== Model::STATE_READY)
			{
				continue;
			}

			$lastTraining = Scoring::getLastTraining($model);
			if(!$lastTraining || !in_array($lastTraining["STATE"], [TrainingState::FINISHED, TrainingState::CANCELED]))
			{
				continue;
			}

			/** @var DateTime $trainingFinished */
			$trainingFinished = $lastTraining["DATE_FINISH"];
			$now = time();
			$diffSeconds = $now - $trainingFinished->getTimestamp();
			if($diffSeconds < Scoring::RETRAIN_PERIOD * 86400)
			{
				continue;
			}

			$deletionResult = Scoring::deleteMlModel($model);
			if(!$deletionResult->isSuccess())
			{
				continue;
			}

			Scoring::startModelTraining($model);
		}

		return static::class . "::run();";
	}
}