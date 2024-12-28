<?php

namespace Bitrix\Crm\Controller\Ml;

use Bitrix\Crm\Ml\ViewHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use CCrmOwnerType;

class Scoring extends Controller
{
	/**
	 * Tries to create first prediction for the given entity.
	 *
	 * @param string $entityType String type of the entity.
	 * @param int $entityId Id of the entity
	 *
	 * @return array|null
	 */
	public function tryCreateFirstPredictionAction($entityType, $entityId)
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
			$this->addError(new Error("Access denied"));

			return null;
		}

		if (!Loader::includeModule("ml"))
		{
			$this->addError(new Error("ML module is not installed"));
			return null;
		}

		if (
			!\Bitrix\Crm\Ml\Scoring::isMlAvailable()
			|| !\Bitrix\Crm\Ml\Scoring::isEnabled()
			|| !\Bitrix\Crm\Ml\Scoring::isScoringAvailable()
		)
		{
			$this->addError(new Error("Scoring is not available for this portal"));

			return null;
		}

		$model = \Bitrix\Crm\Ml\Scoring::getScoringModel($entityTypeId, $entityId);
		if (!$model || $model->getState() !== \Bitrix\Ml\Model::STATE_READY)
		{
			$this->addError(new Error("Scoring model is not in ready state"));
			return null;
		}

		if (ViewHelper::isEntityFinal($entityTypeId, $entityId))
		{
			$this->addError(new Error("Entity is in final state"));
			return null;
		}

		$isPredictionCreated = \Bitrix\Crm\Ml\Scoring::tryCreateFirstPrediction($entityTypeId, $entityId, true);
		$currentPrediction = \Bitrix\Crm\Ml\Scoring::getCurrentPrediction($entityTypeId, $entityId);

		return [
			'predictionCreated' => $isPredictionCreated,
			'currentPrediction' => $currentPrediction
		];
	}

	public function getModelsAction(CurrentUser $currentUser)
	{
		$currentUserId = $currentUser->getId();
		$modelNames = [];

		$modelClasses = \Bitrix\Crm\Ml\Scoring::getModelClasses();
		foreach ($modelClasses as $modelClass)
		{
			if(method_exists($modelClass, "getModelNames"))
			{
				$modelNames = array_merge($modelNames, $modelClass::getModelNames());
			}
		}

		$modelNames = array_filter(
			$modelNames,
			function($modelName) use ($currentUserId)
			{
				return \Bitrix\Crm\Ml\Scoring::hasAccess($modelName, $currentUserId);
			}
		);

		return [
			'modelNames' => $modelNames
		];
	}

	public function getTrainingSetSizeAction(string $modelName)
	{
		$model = \Bitrix\Crm\Ml\Scoring::getModelByName($modelName);
		if (!$model)
		{
			$this->addError(new Error("Model is not found", "NOT_FOUND"));
			return null;
		}
		if (!$model->hasAccess())
		{
			$this->addError(new Error("Access denied", "ACCESS_DENIED"));
			return null;
		}

		[$successfulCount, $failedCount] = $model->getTrainingSetSize();

		return [
			'successfulCount' => $successfulCount,
			'failedCount' => $failedCount
		];
	}
}
