<?
CModule::IncludeModule('tasks');

use Bitrix\Tasks\Item;

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/tasks/dev/util/testcase.php");
$beforeClasses = get_declared_classes();
$beforeClassesCount = count($beforeClasses);

class AccessTests extends \Bitrix\Tasks\Dev\Util\TestCase
{
	public function testAccess()
	{
		//return;

		//static::disableDataCleanUp();

		set_time_limit(0);

		// i am
		$userId = static::makeDemoUser(false, array('override' => array('NAME' => 'Me', 'LAST_NAME' => 'Me')));

//		_print_r('User id: '.$userId);

		// pre-create departments and project, to avoid to be cached to death
		$testOverStructure = $this->canUseCompanyStructure();
		$employeeIdStructure = 0;
		if($testOverStructure)
		{
			$myDep = static::makeDemoDepartment(-1, array('override' => array(
				'CODE' => 'TEST_PRIMARY_DEPARTMENT',
				'UF_HEAD' => $userId, 'NAME' => 'I am director',
			)));
			$this->assertTrue($myDep > 0, 'Was not able to create demo department');
			//_print_r('Primary dep: '.$myDep);

			$middleDep1 = static::makeDemoDepartment($myDep, array('override' => array(
				'NAME' => 'Middle 1',
				'CODE' => 'TEST_SUB_DEPARTMENT_1',
			)));
			$this->assertTrue($middleDep1 > 0, 'Was not able to create demo department');
			//_print_r('Secondary dep 1: '.$middleDep1);

			$middleDep2 = static::makeDemoDepartment($myDep, array('override' => array(
				'NAME' => 'Middle 2',
				'CODE' => 'TEST_SUB_DEPARTMENT_2',
			)));
			$this->assertTrue($middleDep2 > 0, 'Was not able to create demo department');
			//_print_r('Secondary dep 2: '.$middleDep2);

			$employeeIdStructure = static::makeDemoUser(false, array('override' => array(
				'NAME' => 'Employee',
				'UF_DEPARTMENT' => array($middleDep2),
			)));

			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag("intranet_department_structure"); // just in case of intranet fails its job

			$this->assertTrue(\Bitrix\Tasks\Integration\Intranet\User::isDirector($userId), 'User '.$userId.' is not a director of the primary department '.$myDep.'. Wtf?');
		}

		$testOverProject = $this->canUseProjects();
		$employeeIdProject = 0;
		$projectId = 0;
		if($testOverProject)
		{
			$employeeIdProject = static::makeDemoUser(false, array('override' => array('NAME' => 'Project', 'LAST_NAME' => 'Member')));
			$projectId = static::makeDemoProject(array('users' => array($userId, $employeeIdProject)));
		}

		$mustSee = array();

		// i am in one of the roles
		if(true)
		{
			$mustSee[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('CREATED_BY' => $userId)));
			$mustSee[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('RESPONSIBLE_ID' => $userId)));
			$mustSee[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('ACCOMPLICES' => array($userId))));
			$mustSee[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('AUDITORS' => array($userId))));

			$mustSee = array_filter($mustSee, 'intval');

			$this->assertEquals(4, count($mustSee), 'Not all tasks were created');

//			_print_r('Direct tasks:');
//			_print_r($mustSee);
		}

		// i am a director for employee in one of the roles
		if($testOverStructure)
		{
			$mustSeeStruct = array();

			$mustSeeStruct[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('CREATED_BY' => $employeeIdStructure)));
			$mustSeeStruct[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('RESPONSIBLE_ID' => $employeeIdStructure)));
			$mustSeeStruct[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('ACCOMPLICES' => array($employeeIdStructure))));
			$mustSeeStruct[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('AUDITORS' => array($employeeIdStructure))));

			$mustSeeStruct = array_filter($mustSeeStruct, 'intval');

			$this->assertEquals(4, count($mustSeeStruct), 'Not all tasks were created');

//			_print_r('Tasks over subordination:');
//			_print_r($mustSeeStruct);

			$mustSee = array_merge($mustSee, $mustSeeStruct);
		}

		// i can read task in the open group
		if($testOverProject)
		{
			$mustSeeProject[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('GROUP_ID' => $projectId, 'CREATED_BY' => $employeeIdProject)));
			$mustSeeProject[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('GROUP_ID' => $projectId, 'RESPONSIBLE_ID' => $employeeIdProject)));
			$mustSeeProject[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('GROUP_ID' => $projectId, 'ACCOMPLICES' => array($employeeIdProject))));
			$mustSeeProject[] = static::makeDemoTask(array('mode' => static::MODE_SIMPLE, 'override' => array('GROUP_ID' => $projectId, 'AUDITORS' => array($employeeIdProject))));

			$mustSeeProject = array_filter($mustSeeProject, 'intval');

			$this->assertEquals(4, count($mustSeeProject), 'Not all tasks were created');

//			_print_r('Tasks over accessible groups:');
//			_print_r($mustSeeProject);

			$mustSee = array_merge($mustSee, $mustSeeProject);
		}

//		_print_r('Must see:');
//		_print_r($mustSee);

		// check

//		$GLOBALS['D'] = true;

		// first gen :)
		$res = CTasks::getList(array(), array('ID' => $mustSee), array('ID'), $arParamsOut = array(
			'USER_ID' => $userId,
			'bIgnoreErrors' => true,		// don't die on SQL errors
		));
		$cnt = 0;
		$saw = array();
		while($item = $res->fetch())
		{
			$cnt++;
			$saw[] = $item['ID'];
		}

//		_print_r('Saw through CTasks::getList():');
//		_print_r($saw);
//
//		_print_r('diff:');
//		_print_r(array_diff($mustSee, $saw));

		$this->assertEquals(count($mustSee), $cnt, 'Mismatch in CTasks::getList()');

		// first gen, optimization off :)
		$res = CTasks::getList(array(), array('ID' => $mustSee), array('ID'), $arParamsOut = array(
			'USER_ID' => $userId,
			'bIgnoreErrors' => true,		// don't die on SQL errors
			'DISABLE_ACCESS_OPTIMIZATION' => true,
		));
		$cnt = 0;
		$saw = array();
		while($item = $res->fetch())
		{
			$cnt++;
			$saw[] = $item['ID'];
		}

		//		_print_r('Saw through CTasks::getList(), NO OPTIMIZATION:');
		//		_print_r($saw);
		//
		//		_print_r('diff:');
		//		_print_r(array_diff($mustSee, $saw));

		$this->assertEquals(count($mustSee), $cnt, 'Mismatch in CTasks::getList(), NO OPT');

		// second gen :)
		list($items, $res) = CTaskItem::fetchList($userId, array(), array('ID' => $mustSee));
		$saw = array();
		foreach($items as $item)
		{
			$saw[] = $item->getId();
		}

		//		_print_r('Saw 2 through CTaskItem::fetchList()');
		//		_print_r($saw);
		//
		//		_print_r('diff:');
		//		_print_r(array_diff($mustSee, $saw));

		$this->assertEquals(count($mustSee), count($items), 'Mismatch in CTaskItem::fetchList()');

		$parameters = Bitrix\Tasks\Internals\RunTime::apply(array(
			'filter' => array('ID' => $mustSee),
			'select' => array('ID'),
		), array(
			Bitrix\Tasks\Internals\RunTime\Task::getAccessCheck(array('USER_ID' => $userId))
		));

		// orm low level
		$res = \Bitrix\Tasks\Internals\TaskTable::getList($parameters);
		$cnt = 0;
		$saw = array();
		while($item = $res->fetch())
		{
			$saw[] = $item['ID'];
			$cnt++;
		}

		//		_print_r('Saw 3 through \Bitrix\Tasks\Internals\TaskTable::getList()');
		//		_print_r($saw);
		//
		//		_print_r('diff:');
		//		_print_r(array_diff($mustSee, $saw));

		$this->assertEquals(count($mustSee), $cnt, 'Mismatch in \Bitrix\Tasks\Internals\TaskTable::getList()');

		static::disableDataCleanUp();
	}

	public function testGetCount()
	{
		//return;
		$userId = 156; // todo: there should be some non-admin with non-zero task count

//		$res = \CTasks::GetCount(array('!ACCOMPLICE' => array($userId, 333)), array('bNeedJoinMembersTable' => true, 'USER_ID' => 156))->fetch();
//		_print_r($res);
//		return;

		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => array($userId, 333)));
		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => array($userId, 333)), true);

		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => array($userId, 333)));
		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => array($userId, 333)), true);

		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => $userId));
		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => $userId), true);

		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => array($userId)));
		$this->subTestGetCountForFilter($userId, array('ACCOMPLICE' => array($userId)), true);

		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => $userId));
		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => $userId), true);

		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => array($userId)));
		$this->subTestGetCountForFilter($userId, array('!ACCOMPLICE' => array($userId)), true);

		///////////////////////////////////////

		$this->subTestGetCountForFilter($userId, array('AUDITOR' => $userId));
		$this->subTestGetCountForFilter($userId, array('AUDITOR' => $userId), true);

		$this->subTestGetCountForFilter($userId, array('AUDITOR' => array($userId)));
		$this->subTestGetCountForFilter($userId, array('AUDITOR' => array($userId)), true);

		$this->subTestGetCountForFilter($userId, array('!AUDITOR' => $userId));
		$this->subTestGetCountForFilter($userId, array('!AUDITOR' => $userId), true);

		$this->subTestGetCountForFilter($userId, array('!AUDITOR' => array($userId)));
		$this->subTestGetCountForFilter($userId, array('!AUDITOR' => array($userId)), true);

		///////////////////////////////////////

		$this->subTestGetCountForFilter($userId, array('DOER' => $userId));
		$this->subTestGetCountForFilter($userId, array('MEMBER' => $userId));

		$this->subTestGetCountForFilter($userId, array('!DOER' => $userId));
		$this->subTestGetCountForFilter($userId, array('!MEMBER' => $userId));

		$this->subTestGetCountForFilter($userId, array('DOER' => $userId), true);
		$this->subTestGetCountForFilter($userId, array('MEMBER' => $userId), true);

		$this->subTestGetCountForFilter($userId, array('!DOER' => $userId), true);
		$this->subTestGetCountForFilter($userId, array('!MEMBER' => $userId), true);
	}

	public function testGetFilterSql()
	{
		//return;

		$this->assertTrue($this->subTestGetFilterSql(array('USER_ID' => 156)), 'Legacy access check was not found in GetFilter() result');
		$this->assertTrue(!$this->subTestGetFilterSql(array('USER_ID' => 156, 'ENABLE_LEGACY_ACCESS' => false)), 'Legacy access check WAS found in GetFilter() result, but should not be');
	}

	protected function subTestGetFilterSql($params)
	{
		$filter = CTasks::GetFilter(array('MEMBER' => 156), 'T', $params);

		$found = false;
		foreach($filter as $condition)
		{
			if(mb_strpos($condition, '*access LEGACY BEGIN*') !== false)
			{
				$found = true;
				break;
			}
		}

		return $found;
	}

	protected function subTestGetCountForFilter($userId, $filter, $disableAccessOpt = false)
	{
		$params = array(
			'USER_ID' => $userId,
			'DISABLE_ACCESS_OPTIMIZATION' => $disableAccessOpt,
		);
		$paramsNoOpt = array_merge(array('DISABLE_OPTIMIZATION' => true), $params);
		$paramsMember = array(
			'USER_ID' => $userId,
			'bNeedJoinMembersTable' => true,
			'DISABLE_ACCESS_OPTIMIZATION' => $disableAccessOpt,
		);
		$paramsMemberNoOpt = array_merge(array('DISABLE_OPTIMIZATION' => true), $paramsMember);

		$printCounts = false;

		//_print_r('## SIMPLE COUNT ###############################################');
		$count1 = CTasks::GetCount($filter, $params)->fetch();
		$printCounts && _print_r($count1);

		//_print_r('## SIMPLE COUNT NO OPT ###############################################');
		$count2 = CTasks::GetCount($filter, $paramsNoOpt)->fetch();
		$printCounts && _print_r($count2);

		$this->assertEquals($count1['CNT'], $count2['CNT'], 'Mismatch in simple count');

		// count with members
		//_print_r('## MEMBER COUNT ###############################################');
		$count1 = CTasks::GetCount($filter, $paramsMember)->fetch();
		$printCounts && _print_r($count1);

		//_print_r('## MEMBER COUNT NO OPT ###############################################');
		$count2 = CTasks::GetCount($filter, $paramsMemberNoOpt)->fetch();
		$printCounts && _print_r($count2);

		$this->assertEquals($count1['CNT'], $count2['CNT'], 'Mismatch in count with member');

		// count with group by
		//_print_r('## GROUP COUNT ###############################################');
		$res = CTasks::GetCount($filter, $params, array('RESPONSIBLE_ID'));
		$cntRes1 = array();
		while($item = $res->fetch())
		{
			$cntRes1[$item['RESPONSIBLE_ID']] = $item['CNT'];
		}
		$printCounts && _print_r($cntRes1);

		//_print_r('## GROUP COUNT NO OPT ###############################################');
		$res = CTasks::GetCount($filter, $paramsNoOpt, array('RESPONSIBLE_ID'));
		$cntRes2 = array();
		while($item = $res->fetch())
		{
			$cntRes2[$item['RESPONSIBLE_ID']] = $item['CNT'];
		}
		$printCounts && _print_r($cntRes2);

		$this->assertEquals($cntRes1, $cntRes2);
	}
}