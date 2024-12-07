<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Ml\Agent\ModelTrainer;
use Bitrix\Crm\Ml\Internals\PredictionHistoryTable;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Crm\Ml\TrainingState;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;

class CrmMlEntityDetailAjaxController extends Controller
{
	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		Loader::includeModule('crm');
	}

	public function configureActions(): array
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
	 *
	 * @return array|false|null
	 */
	public function continueTrainingAction($modelName): ?array
	{
		$model = Scoring::getModelByName($modelName);
		if (!$model)
		{
			$this->addError(new Error('Model ' . $modelName . ' is not found'));

			return null;
		}

		$lastTraining = Scoring::getLastTraining($model);
		if (!$lastTraining)
		{
			$this->addError(new Error('Model ' . $modelName . ' is not training now'));

			return null;
		}

		if (
			$lastTraining['STATE'] === TrainingState::GATHERING
			|| $lastTraining['STATE'] === TrainingState::PENDING_CREATION
		)
		{
			ModelTrainer::run((int)$lastTraining['ID']);
		}

		return [
			'model' => $model,
			'currentTraining' => $lastTraining,
		];
	}

	/**
	 * Return current prediction record for the crm entity.
	 *
	 * @param int $entityTypeId Type id of the entity.
	 * @param int $entityId Entity Id.
	 */
	public function getCurrentPredictionAction($entityTypeId, $entityId): ?array
	{
		$categoryId = Container::getInstance()
			->getFactory($entityTypeId)
			?->getItemCategoryId($entityId)
		;

		if (
			!Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$entityTypeId,
				$entityId,
				$categoryId
			)
		)
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		$lastPrediction = PredictionHistoryTable::getRow([
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId
			],
			'order' => [
				'ID' => 'DESC'
			]
		]);

		if ($lastPrediction)
		{
			return [
				'prediction' => $lastPrediction,
			];
		}

		$updateResult = Scoring::updatePrediction($entityTypeId, $entityId);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return null;
		}

		return [
			'prediction' => $updateResult->getData(),
		];
	}

	public function getResultAction($entityType, $entityId): ?array
	{
		$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		$categoryId = Container::getInstance()
			->getFactory($entityTypeId)
			?->getItemCategoryId($entityId)
		;

		if (
			!Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$entityTypeId,
				$entityId,
				$categoryId
			)
		)
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		CBitrixComponent::includeComponentClass('bitrix:crm.ml.entity.detail');
		$component = new CCrmMlEntityDetailComponent();
		$component->setEntity($entityType, $entityId);
		$result = $component->prepareResult();

		return [
			'model' => $result['MODEL'],
			'mlModelExists' => (bool)$result['ML_MODEL_EXISTS'],
			'canStartTraining' => (bool)$result['CAN_START_TRAINING'],
			'currentTraining' => $result['CURRENT_TRAINING'],
			'entity' => $result['ITEM'],
			'predictionHistory' => $result['PREDICTION_HISTORY'],
			'associatedEvents' => $result['ASSOCIATED_EVENTS'],
			'trainingHistory' => $result['TRAINING_HISTORY'],
			'errors' => $result['ERRORS'] ?? []
		];
	}

	public function startModelTrainingAction($modelName): ?array
	{
		$model = Scoring::getModelByName($modelName);
		if (!$model || !$model->hasAccess())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		$startTrainingResult = Scoring::startModelTraining($model);
		if (!$startTrainingResult->isSuccess())
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

	public function disableScoringAction($modelName): ?array
	{
		$model = Scoring::getModelByName($modelName);
		if (!$model || !$model->hasAccess())
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		$deletionResult = Scoring::deleteMlModel($model);
		if (!$deletionResult->isSuccess())
		{
			$this->errorCollection->add($deletionResult->getErrors());

			return null;
		}

		return [
			'model' => $model,
			'currentTraining' => $model->getCurrentTraining(),
		];
	}
}
