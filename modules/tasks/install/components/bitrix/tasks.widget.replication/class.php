<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Action\Filter\BooleanFilter;
use Bitrix\Tasks\Item\Task\Template\Field\ReplicateParams;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetReplicationComponent extends TasksBaseComponent implements Errorable, Controllerable
{
	protected Collection $errorCollection;

	public function configureActions(): array
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'startReplication' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
			'stopReplication' => [
				'+prefilters' => [
					new BooleanFilter(),
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
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new Collection();
	}

	protected function setUserId()
	{
		$this->userId = CurrentUser::get()->getId();
	}

	public function getErrorByCode($code)
	{
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function startReplicationAction(int $templateId): ?array
	{
		if ($templateId <= 0)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$templateModel = TemplateModel::createFromId($templateId);
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

	public function stopReplicationAction(int $templateId): ?array
	{
		if ($templateId <= 0)
		{
			return null;
		}

		if (!Loader::includeModule('tasks'))
		{
			return null;
		}

		$templateModel = TemplateModel::createFromId($templateId);
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

	private function toggleReplication(int $templateId, bool $isReplication): void
	{
		$template = new Template($templateId);
		$template['REPLICATE'] = $isReplication ? 'Y' : 'N';

		if($isReplication)
		{
			$template['TPARAM_REPLICATION_COUNT'] = 0;
		}

		$taskId = (int)$template['TASK_ID'];

		$saveResult = $template->save();
		$this->errorCollection->load($saveResult->getErrors());

		if($saveResult->isSuccess())
		{
			$result = $this->update($taskId, ['REPLICATE' => $isReplication ? 'Y' : 'N']);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors()[0]);
			}
		}
	}

	protected function checkParameters(): bool
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

		// wee, need mapping because of different week start
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

		$currentTimeZoneOffset = User::getTimeZoneOffset($this->arParams['USER_ID']);
		$serverTimeZoneOffset = Util::getServerTimeZoneOffset();
		$resultTimeZoneOffset = $currentTimeZoneOffset + $serverTimeZoneOffset;

		$this->arResult['CURRENT_TIMEZONE_OFFSET'] = 0;
		$this->arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET'] = $resultTimeZoneOffset;

		$data = $this->arParams['DATA'];

		$time = strtotime($data['TIME']);
		$timeZoneOffset = ($data['TIMEZONE_OFFSET'] ?? null);
		$creator = $this->arParams['TEMPLATE_CREATED_BY'];

		$creatorTimeZoneOffset = ($timeZoneOffset ?? User::getTimeZoneOffset($creator));

		$serverTime = date('H:i', $time - $creatorTimeZoneOffset);
		$serverStartDate = MakeTimeStamp($data['START_DATE'] ?? null);
		$serverEndDate = MakeTimeStamp($data['END_DATE'] ?? null);

		$this->arParams['DATA']['TIME'] = ReplicateParamsCorrector::correctTime($serverTime, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['START_DATE'] = ReplicateParamsCorrector::correctStartDate($serverTime, $serverStartDate, $currentTimeZoneOffset, 'user');
		$this->arParams['DATA']['END_DATE'] = ReplicateParamsCorrector::correctEndDate($serverTime, $serverEndDate, $currentTimeZoneOffset, 'user');
	}

	private function update(int $taskId, array $fields): Result
	{
		$result = new Result();
		if ($taskId <= 0 || empty($fields))
		{
			return $result;
		}

		$model = TaskModel::createFromId($taskId);
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_SAVE, $taskId, $model))
		{
			$result->addError(new \Bitrix\Main\Error("Unable to update task {$taskId}"));
		}

		$handler = new \Bitrix\Tasks\Control\Task($this->userId);
		try
		{
			$handler->update($taskId, $fields);
		}
		catch (Exception $exception)
		{
			$result->addError(\Bitrix\Main\Error::createFromThrowable($exception));
		}

		return $result;
	}
}