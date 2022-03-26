<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Ml\TrainingState;
use Bitrix\Main\Result;
use Bitrix\Main\Engine\ActionFilter\CloseSession;


class CrmMlEntityDetailAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('crm');
	}

	public function configureActions()
	{
		return [
			'continueTraining' => [
				'+prefilters' => [new CloseSession()],
			],
			'getCurrentPrediction' => [
				'+prefilters' => [new CloseSession()],
			],
		];
	}

	/**
	 * Runs next training step for the model.
	 *
	 * @param string $modelName The name of the model.
	 * @return array|false|null
	 */
	public function continueTrainingAction($modelName)
	{
		$model = \Bitrix\Crm\Ml\Scoring::getModelByName($modelName);
		if(!$model)
		{
			$this->addError(new \Bitrix\Main\Error("Model " . $modelName . " is not found"));
			return null;
		}

		$lastTraining = \Bitrix\Crm\Ml\Scoring::getLastTraining($model);
		if(!$lastTraining)
		{
			$this->addError(new \Bitrix\Main\Error("Model " . $modelName . " is not training now"));
			return null;
		}

		if($lastTraining["STATE"] == \Bitrix\Crm\Ml\TrainingState::GATHERING || $lastTraining["STATE"] === TrainingState::PENDING_CREATION)
		{
			\Bitrix\Crm\Ml\Agent\ModelTrainer::run((int)$lastTraining["ID"]);
		}

		return [
			"model" => $model,
			"currentTraining" => $lastTraining
		];
	}

	/**
	 * Return current prediction record for the crm entity.
	 *
	 * @param int $entityTypeId Type id of the entity.
	 * @param int $entityId Id of the entity.
	 */
	public function getCurrentPredictionAction($entityTypeId, $entityId)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!CCrmAuthorizationHelper::CheckReadPermission($entityTypeId, $entityId, $userPermissions))
		{
			$this->addError(new \Bitrix\Main\Error("Access denied"));
			return null;
		}

		$lastPrediction = \Bitrix\Crm\Ml\Internals\PredictionHistoryTable::getRow([
			"filter" => [
				"=ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY_ID" => $entityId
			],
			"order" => [
				"ID" => "DESC"
			]
		]);

		if ($lastPrediction)
		{
			return [
				"prediction" => $lastPrediction
			];
		}

		$updateResult = \Bitrix\Crm\Ml\Scoring::updatePrediction($entityTypeId, $entityId);
		if(!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
			return null;
		}

		return [
			"prediction" => $updateResult->getData()
		];
	}

	public function getResultAction($entityType, $entityId)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityId, $userPermissions))
		{
			$this->addError(new \Bitrix\Main\Error("Access denied"));
			return null;
		}

		CBitrixComponent::includeComponentClass('bitrix:crm.ml.entity.detail');
		$component = new CCrmMlEntityDetailComponent();
		$component->setEntity($entityType, $entityId);
		$result = $component->prepareResult();

		return [
			"model" => $result["MODEL"],
			"mlModelExists" => (bool)$result["ML_MODEL_EXISTS"],
			"canStartTraining" => (bool)$result["CAN_START_TRAINING"],
			"currentTraining" => $result["CURRENT_TRAINING"],
			"entity" => $result["ITEM"],
			"predictionHistory" => $result["PREDICTION_HISTORY"],
			"associatedEvents" => $result["ASSOCIATED_EVENTS"],
			"trainingHistory" => $result["TRAINING_HISTORY"],
			"errors" => $result["ERRORS"]
		];
	}

	public function startModelTrainingAction($modelName)
	{
		$model = \Bitrix\Crm\Ml\Scoring::getModelByName($modelName);
		if (!$model || !$model->hasAccess())
		{
			$this->addError(new \Bitrix\Main\Error("Access denied"));
			return null;
		}
		$startTrainingResult = \Bitrix\Crm\Ml\Scoring::startModelTraining($model);

		if(!$startTrainingResult->isSuccess())
		{
			$this->errorCollection->add($startTrainingResult->getErrors());
			return null;
		}

		CBitrixComponent::includeComponentClass('bitrix:crm.ml.entity.detail');
		$component = new CCrmMlEntityDetailComponent();

		return [
			'model' => $model,
			'currentTraining' => $component->getCurrentTraining($model),
		];
	}

	public function disableScoringAction($modelName)
	{
		$model = \Bitrix\Crm\Ml\Scoring::getModelByName($modelName);
		if (!$model || !$model->hasAccess())
		{
			$this->addError(new \Bitrix\Main\Error("Access denied"));
			return null;
		}
		$deletionResult = \Bitrix\Crm\Ml\Scoring::deleteMlModel($model);

		if(!$deletionResult->isSuccess())
		{
			$this->errorCollection->add($deletionResult->getErrors());
			return null;
		}

		return [
			'model' => $model,
			'currentTraining' => $model->getCurrentTraining()
		];
	}
}
