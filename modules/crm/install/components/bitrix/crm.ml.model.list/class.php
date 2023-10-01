<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Ml\Controller\Details;
use Bitrix\Crm\Ml\Model\Base;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class CCrmMlModelListComponent extends CBitrixComponent
{
	const ALLOWED_TYPES = [CCrmOwnerType::DealName, CCrmOwnerType::LeadName]; // TODO: not used?

	protected ErrorCollection $errorCollection;
	
	public function __construct($component = null)
	{
		parent::__construct($component);

		Loader::includeModule('ui');
		
		$this->errorCollection = new ErrorCollection();
	}

	public function executeComponent(): void
	{
		$this->arResult['MODELS'] = $this->getModels();
		$this->arResult['TRAINING_LIST'] = $this->getLastTrainingList($this->arResult['MODELS']);
		$this->arResult['IS_SCORING_ENABLED'] = Scoring::isEnabled();
		$this->arResult['TRAINING_ERRORS'] = [];

		foreach ($this->arResult['MODELS'] as $model)
		{
			$canStartTrainingResult = Scoring::canStartTraining($model, true);
			if (!$canStartTrainingResult->isSuccess())
			{
				$this->arResult['TRAINING_ERRORS'][$model->getName()] = $canStartTrainingResult->getErrors()[0];
			}
		}

		$this->subscribePullEvents($this->arResult['MODELS']);

		$this->includeComponentTemplate();
	}

	public function subscribePullEvents(array $models): void
	{
		global $USER;

		if (!Loader::includeModule('pull'))
		{
			return;
		}

		foreach ($models as $model)
		{
			CPullWatch::Add(
				$USER->GetID(),
				Details::getPushTag($model),
				true
			);
		}
	}

	public function getModels(): array
	{
		$possibleModelNames = Scoring::getAvailableModelNames();

		$result = [];
		foreach ($possibleModelNames as $modelName)
		{
			$model = Scoring::getModelByName($modelName);

			if ($model && $model->hasAccess())
			{
				$result[$modelName] = $model;
			}
		}

		return $result;
	}

	/**
	 * @param Base[] $models
	 *
	 * @return array
	 */
	public function getLastTrainingList(array $models = []): array
	{
		$result = [];
		foreach ($models as $name => $model)
		{
			$training = $model->getCurrentTraining();

			$training['DATE_START'] = isset($training['DATE_START']) && $training['DATE_START'] instanceof DateTime
				? $training['DATE_START']->format(DATE_ATOM)
				: null;
			$training['DATE_FINISH'] = isset($training['DATE_FINISH']) && $training['DATE_FINISH'] instanceof DateTime
				? $training['DATE_FINISH']->format(DATE_ATOM)
				: null;
			$training['NEXT_DATE'] = isset($training['NEXT_DATE']) && $training['NEXT_DATE'] instanceof DateTime
				? $training['NEXT_DATE']->format(DATE_ATOM)
				: '';

			$result[$name] = $training;
		}

		return $result;
	}
}
