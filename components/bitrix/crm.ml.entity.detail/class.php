<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Ml\Controller\Details;
use Bitrix\Crm\Ml\Internals\ModelTrainingTable;
use Bitrix\Crm\Ml\Internals\PredictionHistoryTable;
use Bitrix\Crm\Ml\Model\Base;
use Bitrix\Crm\Ml\Model\DealScoring;
use Bitrix\Crm\Ml\Model\LeadScoring;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Crm\Ml\TrainingState;
use Bitrix\Crm\Ml\ViewHelper;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class CCrmMlEntityDetailComponent extends CBitrixComponent
{
	private const FEEDBACK_PORTAL = 'https://product-feedback.bitrix24.com';
	const ALLOWED_TYPES = [CCrmOwnerType::DealName, CCrmOwnerType::LeadName]; // TODO: not used?

	protected ?string $entityType;
	protected ?int $entityTypeId;
	protected ?int $entityId;
	protected ?Base $model;
	protected ?array $currentTraining;
	protected ErrorCollection $errorCollection;

	public function __construct($component = null)
	{
		parent::__construct($component);

		Loader::includeModule('ui');
		
		$this->errorCollection = new ErrorCollection();
	}

	public function executeComponent(): void
	{
		$this->setEntity($this->arParams['TYPE'], $this->arParams['ID']);

		$categoryId = Container::getInstance()
			->getFactory($this->entityTypeId)
			?->getItemCategoryId($this->entityId)
		;
		
		if (
			!Container::getInstance()->getUserPermissions()->checkReadPermissions(
				$this->entityTypeId,
				$this->entityId,
				$categoryId
			)
		)
		{
			$this->errorCollection[] = new Error('Access denied');
		}

		if (!Loader::includeModule('ml'))
		{
			$this->errorCollection[] = new Error('ML module is not installed');
		}

		if (!$this->model)
		{
			$this->errorCollection[] = new Error('Could not create model for this entity');
		}

		$this->arResult['ERRORS'] = [];
		if ($this->errorCollection->isEmpty())
		{
			$this->arResult = $this->prepareResult();
			$this->subscribePullEvents($this->model);
		}
		else
		{
			$this->arResult = [
				'ERRORS' => array_map(
					static function($err)
					{
						return ($err instanceof JsonSerializable ? $err->jsonSerialize() : $err);
					},
					$this->errorCollection->toArray()
				),
			];
		}

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			if (
				$this->model
				&& $this->model->getState() === \Bitrix\Ml\Model::STATE_READY
				&& $this->arResult['ITEM']
			)
			{
				$GLOBALS['APPLICATION']->SetTitle($this->arResult['ITEM']['TITLE']);
			}
			else
			{
				$GLOBALS['APPLICATION']->SetTitle(
					Loc::getMessage('CRM_ML_ENTITY_DETAIL_TITLE_2')
				);
			}
		}

		$this->includeComponentTemplate();
	}

	public function setEntity(string $entityType, int $entityId): void
	{
		$this->entityId = $entityId;
		$this->entityType = $entityType;
		$this->entityTypeId = CCrmOwnerType::ResolveID($this->entityType);
		$this->model = Scoring::getScoringModel($this->entityTypeId, $this->entityId);
		$this->currentTraining = $this->model && $this->model->getMlModel()
			? $this->getCurrentTraining($this->model)
			: null;
	}

	public function prepareResult(): array
	{
		$result = [];

		$result['MODEL'] = $this->model;
		$result['MODEL_EXISTS'] = !is_null($this->model->getMlModel());
		$result['CURRENT_TRAINING'] = $this->currentTraining;
		$result['ITEM'] = $this->getEntityProperties();

		$result['ML_MODEL_EXISTS'] = !is_null($this->model->getMlModel());
		$result['SCORING_ENABLED'] = Scoring::isEnabled();
		$canStartTraining = Scoring::canStartTraining($this->model);
		$result['CAN_START_TRAINING'] = $canStartTraining->isSuccess();
		$result['TRAINING_ERROR'] = null;
		if (!$canStartTraining->isSuccess())
		{
			$result['TRAINING_ERROR'] = $canStartTraining->getErrors()[0];
		}

		if (isset($result['ITEM']['IS_FINAL']) && $result['ITEM']['IS_FINAL'] === 'N')
		{
			Scoring::tryCreateFirstPrediction(
				$this->entityTypeId,
				$this->entityId,
				Scoring::PREDICTION_IMMEDIATE
			);
		}

		$result['PREDICTION_HISTORY'] = $this->getPredictionHistory();
		$result['ASSOCIATED_EVENTS'] = $this->prepareAssociatedEvents($result['PREDICTION_HISTORY']);
		$result['TRAINING_HISTORY'] = $this->getTrainingHistory();
		$result['FEEDBACK_PARAMS'] = $this->getFeedbackParams();

		return $result;
	}

	public function subscribePullEvents(Base $model): void
	{
		global $USER;

		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$tag = Details::getPushTag($model);

		CPullWatch::Add($USER->GetID(), $tag, true);
	}

	public function getCurrentTraining(Base $model): array
	{
		$dateFormat = Date::convertFormatToPhp(Context::getCurrent()->getCulture()->getDateFormat());

		$result = $model->getCurrentTraining();
		$result['DATE_START'] = $result['DATE_START'] instanceof DateTime ? $result['DATE_START']->format(DATE_ATOM) : null;
		$result['DATE_FINISH'] = $result['DATE_FINISH'] instanceof DateTime ? $result['DATE_FINISH']->format(DATE_ATOM) : null;
		$result['NEXT_DATE'] = $result['NEXT_DATE'] instanceof DateTime ? $result['NEXT_DATE']->format($dateFormat) : "";

		return $result;
	}

	public function getEntityProperties(): array
	{
		return [
			'ENTITY_TYPE' => $this->entityType,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($this->entityType),
			'ENTITY_ID' => $this->entityId,
			'TITLE' => CCrmOwnerType::GetCaption($this->entityTypeId, $this->entityId, false),
			'IS_FINAL' => ViewHelper::isEntityFinal($this->entityTypeId, $this->entityId) ? 'Y' : 'N',
		];
	}

	public function getPredictionHistory(): array
	{
		$historyCursor = PredictionHistoryTable::getList([
			'select' => [
				'ANSWER',
				'SCORE',
				'SCORE_DELTA',
				'CREATED',
				'EVENT_TYPE',
				'ASSOCIATED_ACTIVITY_ID'
			],
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->entityTypeId,
				'=ENTITY_ID' => $this->entityId,
				'=MODEL_NAME' => $this->model->getName(),
				'=IS_PENDING' => 'N'
			],
			'order' => [
				'CREATED' => 'desc'
			],
			'limit' => 6
		]);

		$result = [];
		while ($row = $historyCursor->fetch())
		{
			$result[] = [
				'ANSWER' => (int)$row['ANSWER'],
				'SCORE' => (float)$row['SCORE'],
				'SCORE_DELTA' => (float)$row['SCORE_DELTA'],
				'CREATED' => $row['CREATED'] instanceof DateTime ? $row['CREATED']->format(DATE_ATOM) : null ,
				'EVENT_TYPE' => $row['EVENT_TYPE'],
				'ASSOCIATED_ACTIVITY_ID' => (int)$row['ASSOCIATED_ACTIVITY_ID'] ?: null
			];
		}

		return array_reverse($result);
	}

	public function prepareAssociatedEvents(array $predictionHistory): array
	{
		$actIds = [];

		foreach ($predictionHistory as $prediction)
		{
			if ($prediction['EVENT_TYPE'] === 'activity' && $prediction['ASSOCIATED_ACTIVITY_ID'] > 0)
			{
				$actIds[] = $prediction['ASSOCIATED_ACTIVITY_ID'];
			}
		}

		if (count($actIds) === 0)
		{
			return [];
		}

		$actCursor = CCrmActivity::GetList(
			[],
			['@ID' => $actIds],
			false,
			false,
			['ID', 'SUBJECT'],
			['CHECK_PERMISSIONS' => false]
		);
		$activities = [];
		while($row = $actCursor->fetch())
		{
			$activities[$row['ID']] = $row;
		}

		$result = [];
		foreach ($predictionHistory as $prediction)
		{
			if ($prediction['EVENT_TYPE'] === 'initial')
			{
				continue;
			}
			if ($prediction['EVENT_TYPE'] === 'activity' && !$activities[$prediction['ASSOCIATED_ACTIVITY_ID']])
			{
				continue;
			}

			$result[] = [
				'EVENT_TYPE' => $prediction['EVENT_TYPE'],
				'ASSOCIATED_ACTIVITY_ID' => $prediction['ASSOCIATED_ACTIVITY_ID'],
				'SCORE_DELTA' => $prediction['SCORE_DELTA'],
				'ACTIVITY' => $activities[$prediction['ASSOCIATED_ACTIVITY_ID']]
			];
		}

		return $result;
	}

	public function getTrainingHistory(): array
	{
		$cursor = ModelTrainingTable::getList([
			'select' => [
				'DATE_START',
				'DATE_FINISH',
				'RECORDS_SUCCESS',
				'RECORDS_FAILED',
				'AREA_UNDER_CURVE',
			],
			'filter' => [
				'=MODEL_NAME' => $this->model->getName(),
				'=STATE' => TrainingState::FINISHED
			],
			'order' => [
				'DATE_START' => 'asc'
			],
			'limit' => 6
		]);

		$result = [];
		while ($row = $cursor->fetch())
		{
			$result[] = [
				'DATE_START' => $row['DATE_START'] instanceof DateTime ? $row['DATE_START']->format(DATE_ATOM) : null,
				'DATE_FINISH' => $row['DATE_FINISH'] instanceof DateTime ? $row['DATE_FINISH']->format(DATE_ATOM) : null,
				'RECORDS_FAILED' => (int)$row['RECORDS_FAILED'],
				'RECORDS_SUCCESS' => (int)$row['RECORDS_SUCCESS'],
				'AREA_UNDER_CURVE' => (float)$row['AREA_UNDER_CURVE'],
			];
		}

		return $result;
	}

	public function getFeedbackParams(): array
	{
		$auc = '';
		if ($this->currentTraining)
		{
			$auc = round($this->currentTraining['AREA_UNDER_CURVE'] * 100);
		}

		return [
			'ID' => 'crm-scoring',
			'FORM' => $this->getFeedbackForm(),
			'PORTAL' => self::FEEDBACK_PORTAL,
			'PRESETS' => [
				'c_name' => CurrentUser::get()->getFullName(),
				'b24_plan' => Loader::includeModule('bitrix24') ? CBitrix24::getLicenseType() : '',
				'percent_lead' => $this->model instanceof LeadScoring ? $auc : '',
				'percent_deal' => $this->model instanceof DealScoring ? $auc : ''
			]
		];
	}

	public function getFeedbackForm(): array
	{
		$forms = [
			['zones' => ['com.br'], 'id' => '122','lang' => 'br', 'sec' => '8f7j4h'],
			['zones' => ['es'], 'id' => '120','lang' => 'la', 'sec' => '1y5u12'],
			['zones' => ['de'], 'id' => '118','lang' => 'de', 'sec' => 'glv0sq'],
			['zones' => ['ua'], 'id' => '124','lang' => 'ua', 'sec' => '3whqvr'],
			['zones' => ['ru', 'by', 'kz'], 'id' => '114','lang' => 'ru', 'sec' => '9iboyg'],
			['zones' => ['en'], 'id' => '116','lang' => 'en', 'sec' => 'h4pdb1'],
		];

		if (Loader::includeModule('bitrix24'))
		{
			$zone = CBitrix24::getPortalZone();
			$defaultForm = null;
			foreach ($forms as $form)
			{
				if (!isset($form['zones']) || !is_array($form['zones']))
				{
					continue;
				}

				if (in_array($zone, $form['zones'], true))
				{
					return $form;
				}

				if (in_array('en', $form['zones'], true))
				{
					$defaultForm = $form;
				}
			}

			return $defaultForm;
		}

		$lang = LANGUAGE_ID;
		$defaultForm = null;
		foreach ($forms as $form)
		{
			if (!isset($form['lang']))
			{
				continue;
			}

			if ($lang === $form['lang'])
			{
				return $form;
			}

			if ($form['lang'] === 'en')
			{
				$defaultForm = $form;
			}
		}

		return $defaultForm;
	}
}
