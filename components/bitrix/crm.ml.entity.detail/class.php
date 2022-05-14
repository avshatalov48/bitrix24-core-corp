<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmMlEntityDetailComponent extends CBitrixComponent
{
	protected $entityType;
	protected $entityTypeId;
	protected $entityId;
	/** @var \Bitrix\Crm\Ml\Model\Base */
	protected $model;
	protected $currentTraining;

	protected $successfulRecords;
	protected $failedRecords;

	protected $errorCollection;

	const ALLOWED_TYPES = [CCrmOwnerType::DealName, CCrmOwnerType::LeadName];

	public function __construct($component = null)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::includeModule('ui');
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function executeComponent()
	{
		$this->setEntity($this->arParams['TYPE'], $this->arParams['ID']);

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if (!CCrmAuthorizationHelper::CheckReadPermission($this->arParams['TYPE'], $this->arParams['ID'], $userPermissions))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("Access denied");
		}
		if(!\Bitrix\Main\Loader::includeModule("ml"))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("ML module is not installed");
		}
		if(!$this->model)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("Could not create model for this entity");
		}

		if($this->errorCollection->isEmpty())
		{
			$this->arResult = $this->prepareResult();
			$this->subscribePullEvents($this->model);
		}
		else
		{
			$this->arResult = [
				"ERRORS" => array_map(
					function($err)
					{
						return ($err instanceof JsonSerializable ? $err->jsonSerialize() : $err);
					},
					$this->errorCollection->toArray()
				),
			];
		}

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			if($this->model && $this->model->getState() === \Bitrix\Ml\Model::STATE_READY && $this->arResult['ITEM'])
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

	public function setEntity($entityType, $entityId)
	{
		$this->entityId = $entityId;
		$this->entityType = $entityType;
		$this->entityTypeId = CCrmOwnerType::ResolveID($this->entityType);
		$this->model = \Bitrix\Crm\Ml\Scoring::getScoringModel($this->entityTypeId, $this->entityId);
		$this->currentTraining = $this->model ? $this->getCurrentTraining($this->model) : null;
	}

	public function prepareResult()
	{
		$result = [];

		$result['MODEL'] = $this->model;
		$result['MODEL_EXISTS'] = !is_null($this->model->getMlModel());
		$result['CURRENT_TRAINING'] = $this->currentTraining;
		$result['ITEM'] = $this->getEntityProperties();

		$result['ML_MODEL_EXISTS'] = !is_null($this->model->getMlModel());
		$result['SCORING_ENABLED'] = \Bitrix\Crm\Ml\Scoring::isEnabled();
		$canStartTraining = \Bitrix\Crm\Ml\Scoring::canStartTraining($this->model);
		$result['CAN_START_TRAINING'] = $canStartTraining->isSuccess();
		if(!$canStartTraining->isSuccess())
		{
			$result['TRAINING_ERROR'] = $canStartTraining->getErrors()[0];
		}

		if($result['ITEM']['IS_FINAL'] == 'N')
		{
			\Bitrix\Crm\Ml\Scoring::tryCreateFirstPrediction(
				$this->entityTypeId,
				$this->entityId,
				\Bitrix\Crm\Ml\Scoring::PREDICTION_IMMEDIATE
			);
		}

		$result['PREDICTION_HISTORY'] = $this->getPredictionHistory();
		$result['ASSOCIATED_EVENTS'] = $this->prepareAssociatedEvents($result['PREDICTION_HISTORY']);
		$result['TRAINING_HISTORY'] = $this->getTrainingHistory();

		$result['FEEDBACK_PARAMS'] = $this->getFeedbackParams();

		return $result;
	}

	public function subscribePullEvents(\Bitrix\Crm\Ml\Model\Base $model)
	{
		global $USER;
		if(!\Bitrix\Main\Loader::includeModule("pull"))
		{
			return;
		}

		$tag = \Bitrix\Crm\Ml\Controller\Details::getPushTag($model);
		CPullWatch::Add($USER->GetID(), $tag, true);
	}

	public function getCurrentTraining(\Bitrix\Crm\Ml\Model\Base $model)
	{
		$dateFormat = \Bitrix\Main\Type\Date::convertFormatToPhp(\Bitrix\Main\Context::getCurrent()->getCulture()->getDateFormat());

		$result = $model->getCurrentTraining();
		$result["DATE_START"] = $result["DATE_START"] instanceof \Bitrix\Main\Type\DateTime ? $result["DATE_START"]->format(DATE_ATOM) : null;
		$result["DATE_FINISH"] = $result["DATE_FINISH"] instanceof \Bitrix\Main\Type\DateTime ? $result["DATE_FINISH"]->format(DATE_ATOM) : null;
		$result["NEXT_DATE"] = $result["NEXT_DATE"] instanceof \Bitrix\Main\Type\DateTime ? $result["NEXT_DATE"]->format($dateFormat) : "";

		return $result;
	}

	public function getEntityProperties()
	{
		$result = [
			'ENTITY_TYPE' => $this->entityType,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($this->entityType),
			'ENTITY_ID' => $this->entityId,
			'TITLE' => \CCrmOwnerType::GetCaption($this->entityTypeId, $this->entityId, false),
			'IS_FINAL' => \Bitrix\Crm\Ml\ViewHelper::isEntityFinal($this->entityTypeId, $this->entityId) ? 'Y' : 'N'
		];

		return $result;
	}

	public function getPredictionHistory()
	{
		$historyCursor = \Bitrix\Crm\Ml\Internals\PredictionHistoryTable::getList([
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
				'CREATED' => $row["CREATED"] instanceof \Bitrix\Main\Type\DateTime ? $row["CREATED"]->format(DATE_ATOM) : null ,
				'EVENT_TYPE' => $row['EVENT_TYPE'],
				'ASSOCIATED_ACTIVITY_ID' => (int)$row['ASSOCIATED_ACTIVITY_ID'] ?: null
			];
		}
		return array_reverse($result);
	}

	public function prepareAssociatedEvents(array $predictionHistory)
	{
		$actIds = [];

		foreach ($predictionHistory as $prediction)
		{
			if($prediction["EVENT_TYPE"] == "activity" && $prediction["ASSOCIATED_ACTIVITY_ID"] > 0)
			{
				$actIds[] = $prediction["ASSOCIATED_ACTIVITY_ID"];
			}
		}

		if(count($actIds) === 0)
		{
			return [];
		}

		$actCursor = CCrmActivity::GetList(
			[],
			["@ID" => $actIds],
			false,
			false,
			["ID", "SUBJECT"],
			["CHECK_PERMISSIONS" => false]
		);
		$activities = [];
		while($row = $actCursor->fetch())
		{
			$activities[$row["ID"]] = $row;
		}

		$result = [];
		foreach ($predictionHistory as $prediction)
		{
			if($prediction["EVENT_TYPE"] === "initial")
			{
				continue;
			}
			if($prediction["EVENT_TYPE"] === "activity" && !$activities[$prediction["ASSOCIATED_ACTIVITY_ID"]])
			{
				continue;
			}

			$result[] = [
				"EVENT_TYPE" => $prediction["EVENT_TYPE"],
				"ASSOCIATED_ACTIVITY_ID" => $prediction["ASSOCIATED_ACTIVITY_ID"],
				"SCORE_DELTA" => $prediction["SCORE_DELTA"],
				"ACTIVITY" => $activities[$prediction["ASSOCIATED_ACTIVITY_ID"]]
			];
		}
		return $result;
	}

	public function getTrainingHistory()
	{
		$cursor = \Bitrix\Crm\Ml\Internals\ModelTrainingTable::getList([
			'select' => [
				'DATE_START',
				'DATE_FINISH',
				'RECORDS_SUCCESS',
				'RECORDS_FAILED',
				'AREA_UNDER_CURVE',
			],
			'filter' => [
				'=MODEL_NAME' => $this->model->getName(),
				'=STATE' => \Bitrix\Crm\Ml\TrainingState::FINISHED
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
				"DATE_START" => $row["DATE_START"] instanceof \Bitrix\Main\Type\DateTime ? $row["DATE_START"]->format(DATE_ATOM) : null,
				"DATE_FINISH" => $row["DATE_FINISH"] instanceof \Bitrix\Main\Type\DateTime ? $row["DATE_FINISH"]->format(DATE_ATOM) : null,
				"RECORDS_FAILED" => (int)$row["RECORDS_FAILED"],
				"RECORDS_SUCCESS" => (int)$row["RECORDS_SUCCESS"],
				"AREA_UNDER_CURVE" => (float)$row["AREA_UNDER_CURVE"],
			];
		}
		return $result;
	}

	public function getFeedbackParams()
	{
		$auc = "";
		if($this->currentTraining)
		{
			$auc = round($this->currentTraining["AREA_UNDER_CURVE"] * 100);
		}

		return [
			'ID' => 'crm-scoring',
			'FORM' => $this->getFeedbackForm(),
			'PORTAL' => 'https://product-feedback.bitrix24.com',
			'PRESETS' => [
				'c_name' => \Bitrix\Main\Engine\CurrentUser::get()->getFullName(),
				'b24_plan' => \Bitrix\Main\Loader::includeModule("bitrix24") ? CBitrix24::getLicenseType() : "",
				'percent_lead' => $this->model instanceof \Bitrix\Crm\Ml\Model\LeadScoring ? $auc : '',
				'percent_deal' => $this->model instanceof \Bitrix\Crm\Ml\Model\DealScoring ? $auc : ''
			]
		];
	}

	public function getFeedbackForm()
	{
		$forms = [
			['zones' => ['com.br'], 'id' => '122','lang' => 'br', 'sec' => '8f7j4h'],
			['zones' => ['es'], 'id' => '120','lang' => 'la', 'sec' => '1y5u12'],
			['zones' => ['de'], 'id' => '118','lang' => 'de', 'sec' => 'glv0sq'],
			['zones' => ['ua'], 'id' => '124','lang' => 'ua', 'sec' => '3whqvr'],
			['zones' => ['ru', 'by', 'kz'], 'id' => '114','lang' => 'ru', 'sec' => '9iboyg'],
			['zones' => ['en'], 'id' => '116','lang' => 'en', 'sec' => 'h4pdb1'],
		];

		if (\Bitrix\Main\Loader::includeModule("bitrix24"))
		{
			$zone = \CBitrix24::getPortalZone();
			$defaultForm = null;
			foreach ($forms as $form)
			{
				if (!isset($form['zones']) || !is_array($form['zones']))
				{
					continue;
				}

				if (in_array($zone, $form['zones']))
				{
					return $form;
				}

				if (in_array('en', $form['zones']))
				{
					$defaultForm = $form;
				}
			}

			return $defaultForm;
		}
		else
		{
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
}