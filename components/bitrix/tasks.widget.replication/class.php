<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Tasks\Item\Task\Template\Field\ReplicateParams;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetReplicationComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $errorCollection;

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'startReplication' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'stopReplication' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function startReplicationAction($templateId)
	{
		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$templateModel = \Bitrix\Tasks\Access\Model\TemplateModel::createFromId($templateId);
		$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $templateModel, $templateModel);
		if (!$isAccess)
		{
			$this->addForbiddenError();
			return [];
		}

		$this->toggleReplication($templateId, true);

		return [
			'ID' => $templateId,
		];
	}

	public function stopReplicationAction($templateId)
	{
		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$templateModel = \Bitrix\Tasks\Access\Model\TemplateModel::createFromId($templateId);
		$isAccess = (new TemplateAccessController($this->userId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $templateModel, $templateModel);
		if (!$isAccess)
		{
			$this->addForbiddenError();
			return [];
		}

		$this->toggleReplication($templateId, false);

		return [
			'ID' => $templateId,
		];
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function toggleReplication($id, $way)
	{
		$result = [];

		$result['ID'] = $id;

		$template = new Template($id);
		$template['REPLICATE'] = $way ? 'Y' : 'N';

		if($way)
		{
			$template['TPARAM_REPLICATION_COUNT'] = 0;
		}

		$taskId = intval($template['TASK_ID']);

		$saveResult = $template->save();
		$this->errorCollection->load($saveResult->getErrors());

		if($saveResult->isSuccess())
		{
			// update related task
			if($taskId)
			{
				$task = new Task($taskId);
				if($task->canUpdate())
				{
					$task['REPLICATE'] = $way ? 'Y' : 'N';
					$saveResult = $task->save(); // todo: DO NOT remove template in case of REPLICATE falls to N
					$this->errorCollection->load($saveResult->getErrors()->transform(array(
						'CODE' => 'TASK.#CODE#',
						'TYPE' => Error::TYPE_WARNING
					)));
				}
			}
		}

		return $result;
	}

	protected function checkParameters()
	{
		$this->arParams['DATA'] = ReplicateParams::createValueStructure($this->arParams['DATA'])->get();
		$this->arResult['TASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter($this->arParams['TASK_LIMIT_EXCEEDED']);

		static::tryParseIntegerParameter($this->arParams['USER_ID'], 0, true);
		static::tryParseArrayParameter($this->arParams['COMPANY_WORKTIME'], static::getCompanyWorkTime());

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		// week day order
		$weekStart = $this->arParams['COMPANY_WORKTIME']['WEEK_START'];

		$wdMap = array(
			0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6
		);

		// wee need mapping because of different week start
		if((string) $weekStart != '')
		{
			$wdStrMap = array(
				'MO' => 0,
				'TU' => 1,
				'WE' => 2,
				'TH' => 3,
				'FR' => 4,
				'SA' => 5,
				'SU' => 6,
			);

			$offs = $wdStrMap[$weekStart];

			$wdMap = array();
			for($k = 0; $k < 7; $k++)
			{
				$wdMap[$k] = ($k + $offs) % 7;
			}
		}

		$this->arResult['AUX_DATA']['WEEKDAY_MAP'] = $wdMap;

		$currentTimeZoneOffset = \Bitrix\Tasks\Util\User::getTimeZoneOffset($this->arParams['USER_ID']);
		$serverTimeZoneOffset = \Bitrix\Tasks\Util::getServerTimeZoneOffset();
		$resultTimeZoneOffset = $currentTimeZoneOffset + $serverTimeZoneOffset;

		$this->arResult['CURRENT_TIMEZONE_OFFSET'] = 0;
		$this->arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET'] = $resultTimeZoneOffset;

		$data = $this->arParams['DATA'];

		$time = strtotime($data['TIME']);
		$timeZoneOffset = ($data['TIMEZONE_OFFSET'] ?? null);
		$creator = $this->arParams['TEMPLATE_CREATED_BY'];

		$creatorTimeZoneOffset = (isset($timeZoneOffset)? $timeZoneOffset : \Bitrix\Tasks\Util\User::getTimeZoneOffset($creator));

		$serverTime = date('H:i', $time - $creatorTimeZoneOffset);
		$serverStartDate = MakeTimeStamp($data['START_DATE'] ?? null);
		$serverEndDate = MakeTimeStamp($data['END_DATE'] ?? null);

		$this->arParams['DATA']['TIME'] = ReplicateParamsCorrector::correctTime($serverTime, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['START_DATE'] = ReplicateParamsCorrector::correctStartDate($serverTime, $serverStartDate, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['END_DATE'] = ReplicateParamsCorrector::correctEndDate($serverTime, $serverEndDate, $currentTimeZoneOffset, 'user');
	}
}