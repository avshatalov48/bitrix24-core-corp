<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");
CBitrixComponent::includeComponentClass("bitrix:tasks.report.effective.detail");

class TasksReportEffectiveInprogressComponent extends TasksReportEffectiveDetailComponent
{
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
	{
		$isAccessible = (array_key_exists('USER_ID', $arParams) && (int)$arParams['USER_ID']);

		if (!$isAccessible)
		{
			$errors->add('TASKS_MODULE_ACCESS_DENIED', Loc::getMessage('TASKS_COMMON_ACCESS_DENIED'));
		}

		return $errors->checkNoFatals();
	}

	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = [])
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

	protected function getData()
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
		$this->arResult['TASK_LIMIT_EXCEEDED'] = $taskLimitExceeded;
	}

	private function getTasksList()
	{
		$filterData = $this->getFilterData();

		$userId = $this->arParams['USER_ID'];
		$groupId = array_key_exists('GROUP_ID', $filterData) ? $filterData['GROUP_ID'] : 0;

		$nav = new PageNavigation("nav");
		$nav->allowAllRecords(true)->setPageSize($this->getPageSize())->initFromUri();

		$sql = "
            SELECT 
                #select#
            FROM 
                b_tasks as t
                JOIN b_tasks_member as tm ON tm.TASK_ID = t.ID  AND tm.TYPE IN ('R', 'A')
            WHERE
                (
                    (tm.USER_ID = {$userId} AND tm.TYPE='R' AND t.CREATED_BY != t.RESPONSIBLE_ID)
                    OR 
                    (tm.USER_ID = {$userId} AND tm.TYPE='A' AND (t.CREATED_BY != {$userId} AND t.RESPONSIBLE_ID != {$userId}))
                )
                
                ".($groupId > 0 ? "AND t.GROUP_ID = {$groupId}" : '')."
                
                AND t.CREATED_DATE <= '".(new Datetime($filterData['DATETIME_to']))->format('Y-m-d H:i:s')."'
				AND 
				(
					t.CLOSED_DATE >= '".(new Datetime($filterData['DATETIME_from']))->format('Y-m-d H:i:s')."'
					OR
					CLOSED_DATE is null
				)
				
                AND t.ZOMBIE = 'N'
                AND t.STATUS != 6
            
            ";

		$res = \Bitrix\Main\Application::getConnection()->query(str_replace('#select#', 'COUNT(t.ID) as COUNT', $sql))
									   ->fetch();
		$count = $res['COUNT'];
		$nav->setRecordCount($count);

		$sql.= "LIMIT
            	".$nav->getOffset().",".$nav->getLimit();

		$data = $GLOBALS['DB']->Query(
			str_replace('#select#', 'ID, TITLE, DEADLINE, CREATED_BY, CREATED_DATE, CLOSED_DATE, STATUS', $sql)
		);

		$list = [];
		while ($t = $data->Fetch())
		{
			$list[] = $t;
		}

		//region NAV
		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult['PAGE_SIZES'] = $this->pageSizes;

		$this->arResult['TOTAL_RECORD_COUNT'] = $count;
		//endregion

		$this->arResult['LIST'] = $list;
	}

	private function getGridHeaders()
	{
		return array(
			'TASK'         => array(
				'id'       => 'TASK',
				'name'     => GetMessage('TASKS_COLUMN_TASK'),
				'editable' => false,
				'default'  => true
			),
			'STATUS'       => array(
				'id'       => 'STATUS',
				'name'     => GetMessage('TASKS_COLUMN_STATUS'),
				'editable' => false,
				'default'  => true
			),
			'DEADLINE'     => array(
				'id'       => 'DEADLINE',
				'name'     => GetMessage('TASKS_COLUMN_DEADLINE'),
				'editable' => false,
				'default'  => false
			),
			'CREATED_DATE' => array(
				'id'       => 'CREATED_DATE',
				'name'     => GetMessage('TASKS_COLUMN_CREATED_DATE'),
				'editable' => false,
				'default'  => true
			),
			'CLOSED_DATE'  => array(
				'id'       => 'CLOSED_DATE',
				'name'     => GetMessage('TASKS_COLUMN_CLOSED_DATE'),
				'editable' => false,
				'default'  => true
			),
			'ORIGINATOR'   => array(
				'id'       => 'ORIGINATOR',
				'name'     => GetMessage('TASKS_COLUMN_ORIGINATOR'),
				'editable' => false,
				'default'  => true
			),
			'GROUP'        => array(
				'id'       => 'GROUP',
				'name'     => GetMessage('TASKS_COLUMN_GROUP'),
				'editable' => false,
				'default'  => false
			),
		);
	}

}
