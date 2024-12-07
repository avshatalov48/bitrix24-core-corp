<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Integration\Intranet\Settings;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");
CBitrixComponent::includeComponentClass("bitrix:tasks.report.effective.detail");

/**
 * Class TasksReportEffectiveInprogressComponent
 */
class TasksReportEffectiveInprogressComponent extends TasksReportEffectiveDetailComponent
{
	/**
	 * @param array $arParams
	 * @param array $arResult
	 * @param Collection $errors
	 * @param array $auxParams
	 * @return bool
	 */
	protected static function checkBasicParameters(
		array &$arParams,
		array &$arResult,
		Collection $errors,
		array $auxParams = []
	): bool
	{
		$isAccessible = (array_key_exists('USER_ID', $arParams) && (int)$arParams['USER_ID']);

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'));
		}

		return $errors->checkNoFatals();
	}

	/**
	 * @param array $arParams
	 * @param array $arResult
	 * @param Collection $errors
	 * @param array $auxParams
	 * @return bool
	 */
	protected static function checkPermissions(
		array &$arParams,
		array &$arResult,
		Collection $errors,
		array $auxParams = []
	): bool
	{
		$currentUser = User::getId();
		$viewedUser = (int)$arParams['USER_ID'];

		$isAccessible = (
			$currentUser === $viewedUser
			|| User::isSuper($currentUser)
			|| User::isBossRecursively($currentUser, $viewedUser)
		);

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkIfToolAvailable(array &$arParams, array &$arResult, Collection $errors, array $auxParams): void
	{
		parent::checkIfToolAvailable($arParams, $arResult, $errors, $auxParams);

		if (!$arResult['IS_TOOL_AVAILABLE'])
		{
			return;
		}

		$arResult['IS_TOOL_AVAILABLE'] = (new Settings())->isToolAvailable(Settings::TOOLS['effective']);
	}

	/**
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getData(): void
	{
		$taskLimitExceeded = TaskLimit::isLimitExceeded();

		if (!$taskLimitExceeded)
		{
			$this->getTasksList();
		}
		else
		{
			$nav = new PageNavigation('nav');
			$nav->allowAllRecords(true)->setPageSize($this->getPageSize())->initFromUri();
			$nav->setRecordCount(0);

			$this->arResult['NAV_OBJECT'] = $nav;
			$this->arResult['LIST'] = [];
		}

		$this->arParams['HEADERS'] = $this->getGridHeaders();
	}

	/**
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\ObjectException
	 */
	private function getTasksList(): void
	{
		$filterData = $this->getFilterData();

		$userId = (int)$this->arParams['USER_ID'];
		$groupId = (int)(array_key_exists('GROUP_ID', $filterData) ? $filterData['GROUP_ID'] : 0);
		$groupCondition = ($groupId > 0 ? "AND T.GROUP_ID = {$groupId}" : '');
		$deferredStatus = Status::DEFERRED;

		$dateTo = (new Datetime($filterData['DATETIME_to']))->format('Y-m-d H:i:s');
		$dateFrom = (new Datetime($filterData['DATETIME_from']))->format('Y-m-d H:i:s');

		$sql = "
			SELECT #select#
			FROM b_tasks as T
				INNER JOIN b_tasks_member TM ON TM.TASK_ID = T.ID AND TM.TYPE IN ('R', 'A')
				LEFT JOIN b_sonet_group G ON G.ID = T.GROUP_ID
			WHERE
				(
					(TM.USER_ID = {$userId} AND TM.TYPE = 'R' AND T.CREATED_BY != T.RESPONSIBLE_ID)
					OR (TM.USER_ID = {$userId} AND TM.TYPE = 'A' AND (T.CREATED_BY != {$userId} AND T.RESPONSIBLE_ID != {$userId}))
				)
				{$groupCondition}
				AND T.CREATED_DATE <= '{$dateTo}'
				AND (T.CLOSED_DATE >= '{$dateFrom}' OR T.CLOSED_DATE is null)
				AND T.STATUS != {$deferredStatus}
		";

		$connection = Main\Application::getConnection();
		$countResult = $connection->query(str_replace('#select#', 'COUNT(T.ID) AS COUNT', $sql))->fetch();
		$count = $countResult['COUNT'];

		$nav = new PageNavigation('nav');
		$nav->allowAllRecords(true)->setPageSize($this->getPageSize())->initFromUri();
		$nav->setRecordCount($count);

		$sql .= "LIMIT {$nav->getOffset()},{$nav->getLimit()}";
		$select = [
			'T.ID',
			'T.TITLE',
			'T.DEADLINE',
			'T.CREATED_BY',
			'T.CREATED_DATE',
			'T.CLOSED_DATE',
			'T.STATUS',
			'T.GROUP_ID',
			'G.NAME AS GROUP_NAME',
		];

		$tasksList = [];
		$tasksResult = $connection->query(str_replace('#select#', implode(', ', $select), $sql));
		while ($task = $tasksResult->Fetch())
		{
			$tasksList[] = $task;
		}

		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult['PAGE_SIZES'] = $this->pageSizes;
		$this->arResult['TOTAL_RECORD_COUNT'] = $count;
		$this->arResult['LIST'] = $tasksList;
	}

	/**
	 * @return array[]
	 */
	private function getGridHeaders(): array
	{
		return [
			'TASK' => [
				'id' => 'TASK',
				'name' => Loc::getMessage('TASKS_COLUMN_TASK'),
				'editable' => false,
				'default' => true,
			],
			'STATUS' => [
				'id' => 'STATUS',
				'name' => Loc::getMessage('TASKS_COLUMN_STATUS'),
				'editable' => false,
				'default' => true,
			],
			'DEADLINE' => [
				'id' => 'DEADLINE',
				'name' => Loc::getMessage('TASKS_COLUMN_DEADLINE'),
				'editable' => false,
				'default' => false,
			],
			'CREATED_DATE' => [
				'id' => 'CREATED_DATE',
				'name' => Loc::getMessage('TASKS_COLUMN_CREATED_DATE'),
				'editable' => false,
				'default' => true,
			],
			'CLOSED_DATE' => [
				'id' => 'CLOSED_DATE',
				'name' => Loc::getMessage('TASKS_COLUMN_CLOSED_DATE'),
				'editable' => false,
				'default' => true,
			],
			'ORIGINATOR' => [
				'id' => 'ORIGINATOR',
				'name' => Loc::getMessage('TASKS_COLUMN_ORIGINATOR'),
				'editable' => false,
				'default' => true,
			],
			'GROUP' => [
				'id' => 'GROUP',
				'name' => Loc::getMessage('TASKS_COLUMN_GROUP'),
				'editable' => false,
				'default' => false,
			],
		];
	}

}