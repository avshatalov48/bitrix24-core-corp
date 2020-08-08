<?
CModule::IncludeModule('tasks');

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/tasks/dev/util/testcase.php");
$beforeClasses = get_declared_classes();
$beforeClassesCount = count($beforeClasses);

class BaseMechanics extends \Bitrix\Tasks\Dev\Util\TestCase
{
	public function testTrigger()
	{
		$this->assertTrue(true);
	}

	public function testJamORMExceptionsOnFind()
	{
		$res = \Bitrix\Tasks\Item\Task\Template::find(array('filter' => array('SHIT' => '1')));
		$this->assertTrue(!$res->isSuccess());

		$this->assertEquals(1, $res->getErrors()->count());
	}

	/**
	 * @depends testTrigger
	 */
	public function testGetUser()
	{
		$t = new \Bitrix\Tasks\Item\Task\Template();
		$this->assertEquals(\Bitrix\Tasks\Item\Context::getDefault()->getUserId(), $t->getUserId());

		$t = new \Bitrix\Tasks\Item\Task\Template(0, 100);
		$this->assertEquals(100, $t->getUserId());

		$t['SE_CHECKLIST'] = array(
			array('TITLE' => 'LALA')
		);
		$this->assertEquals(100, $t->seCheckList->first()->getUserId());
	}

	/**
	 * @depends testTrigger
	 */
	public function testGetMap()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);
		$map = $t->getMap();

		$this->assertTrue(is_a($map, '\\Bitrix\\Tasks\\Item\\Field\\Map'));
		$this->assertTrue(!$map->isEmpty());
		$this->assertTrue($map->containsKey('TITLE'));
		$this->assertTrue($map->containsKey('RESPONSIBLE_ID'));
		$this->assertTrue($map->containsKey('SE_CHECKLIST'));

		$this->assertTrue(!$map->containsKey('ZOMBIE'));
		$this->assertTrue($map->containsKey('DESCRIPTION_IN_BBCODE'));
	}

	/**
	 * @depends testTrigger
	 */
	public function testGetDataNewItem()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);
		$data = $t->getData();

		// test default
		$this->assertEquals(2, $data['STATUS']);

		// test virtual
		$this->assertArrayHasKey('CREATED_BY', $data);
		$this->assertArrayHasKey('RESPONSIBLE_ID', $data);
		$this->assertLegalCollection($data['ACCOMPLICES'], 0);
		$this->assertLegalCollection($data['AUDITORS'], 0);

		// test collections
		$this->assertLegalCollection($data['SE_CHECKLIST'], 0);
		$this->assertLegalCollection($data['SE_MEMBER'], 0);
	}

	/**
	 * @depends testTrigger
	 */
	public function testGetDataExistingItem()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);
		$data = $t->getData();

		// test virtual
		$this->assertEquals($user1, $data['CREATED_BY']);
		$this->assertEquals($user2, $data['RESPONSIBLE_ID']);

		$this->assertLegalCollection($data['ACCOMPLICES'], 2);
		$this->assertLegalCollection($data['AUDITORS'], 2);

		// test collections
		$this->assertLegalCollection($data['SE_CHECKLIST'], 3);
		$this->assertLegalCollection($data['SE_MEMBER'], 6);
		$this->assertLegalCollection($data['SE_PROJECTDEPENDENCE'], 0);
	}

	/**
	 * @depends testTrigger
	 */
	public function testArrayAccessNewItem()
	{
		//$user1 = static::$users[0];
		$user2 = static::$users[1];

		$t = new \Bitrix\Tasks\Item\Task(0, $user2);

		// test default
		$this->assertEquals(2, $t['STATUS']);

		// test simple field update
		$t['STATUS'] = 10;
		$this->assertEquals(10, $t['STATUS']);

		// test virtual field default
		$this->assertLegalCollection($t['ACCOMPLICES'], 0);
		$this->assertLegalCollection($t['AUDITORS'], 0);
		$this->assertEquals(null, $t['CREATED_BY']);
		$this->assertEquals(null, $t['RESPONSIBLE_ID']);

		// test virtual field update
		$t['ACCOMPLICES'] = array(10, 20);
		$this->assertEquals(2, $t['ACCOMPLICES']->count());
		$this->assertEquals(10, $t['ACCOMPLICES']->get(0));
		$this->assertEquals(20, $t['ACCOMPLICES'][1]);
		$this->assertLegalCollection($t['SE_MEMBER'], 2);

		$t['CREATED_BY'] = 5;
		$this->assertEquals(5, $t['CREATED_BY']);
		$this->assertLegalCollection($t['SE_MEMBER'], 3);
		$this->assertEquals(5, $t['SE_MEMBER']->findOne(array('=TYPE' => 'O'))['USER_ID']);

		$t['RESPONSIBLE_ID'] = 7;
		$this->assertEquals(7, $t['RESPONSIBLE_ID']);
		$this->assertLegalCollection($t['SE_MEMBER'], 4);
		$this->assertEquals(7, $t['SE_MEMBER']->findOne(array('=TYPE' => 'R'))['USER_ID']);

		$t['CREATED_BY'] = null;
		$this->assertEquals(null, $t['CREATED_BY']);
		$this->assertLegalCollection($t['SE_MEMBER'], 3);
	}

	/**
	 * @depends testTrigger
	 */
	public function testArrayAccessExistingItem()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);

		// virtual read
		$accomplices = $t['ACCOMPLICES']->toArray();
		$this->assertEquals(2, $t['ACCOMPLICES']->count());
		$this->assertTrue(in_array($user1, $accomplices));
		$this->assertTrue(in_array($user2, $accomplices));

		// real read
		$member = $t['SE_MEMBER'];
		$this->assertEquals(6, $member->count());

		$this->assertEquals($user1, $t['CREATED_BY']);
		$this->assertEquals($user2, $t['RESPONSIBLE_ID']);

		// set data

		// simple
		$newTitle = 'Just test';
		$t['TITLE'] = $newTitle;
		$this->assertEquals($newTitle, $t['TITLE'], 'Simple data update failure');

		// associated
		$newUser1 = 10;
		$newUser2 = 20;
		$newUser3 = 30;
		$t['ACCOMPLICES'] = array($newUser1, $newUser2, $newUser3);

		$accomplices = $t['ACCOMPLICES']->toArray();
		$this->assertEquals(3, $t['ACCOMPLICES']->count());
		$this->assertTrue(in_array($newUser1, $accomplices));
		$this->assertTrue(in_array($newUser2, $accomplices));
		$this->assertTrue(in_array($newUser3, $accomplices));

		$this->assertEquals(7, $t['SE_MEMBER']->count());

		$t['CREATED_BY'] = 666;
		$t['RESPONSIBLE_ID'] = 777;

		$this->assertEquals(666, $t['CREATED_BY']);
		$this->assertEquals(777, $t['RESPONSIBLE_ID']);
		$this->assertEquals(7, $t['SE_MEMBER']->count());
		$this->assertEquals(666, $t['SE_MEMBER']->findOne(array('=TYPE' => 'O'))['USER_ID']);
	}

	/**
	 * @depends testTrigger
	 */
	public function testIterateNewItem()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);

		$this->assertTrue(Bitrix\Tasks\Util\Type::isIterable($t));

		$status = null;
		$priority = null;
		$tags = null;
		$responsible = null;
		$checklist = null;
		$accomplices = null;

		foreach($t as $k => $v)
		{
			if($k == 'STATUS')
			{
				$status = $v;
			}
			if($k == 'PRIORITY')
			{
				$priority = $v;
			}
			if($k == 'SE_CHECKLIST')
			{
				$checklist = $v;
			}
			if($k == 'ACCOMPLICES')
			{
				$accomplices = $v;
			}
			if($k == 'RESPONSIBLE_ID')
			{
				$responsible = $v;
			}
			if($k == 'TAGS')
			{
				$tags = $v;
			}
		}

		$this->assertEquals(1, $priority);
		$this->assertEquals(2, $status);
		$this->assertLegalCollection($checklist, 0);
		$this->assertLegalCollection($accomplices, 0);
		$this->assertLegalCollection($tags, 0);
		$this->assertEquals(null, $responsible);
	}

	/**
	 * @depends testTrigger
	 */
	public function testIterateExistingItem()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);

		$this->assertTrue(Bitrix\Tasks\Util\Type::isIterable($t));

		$id = null;
		$title = null;
		$tags = null;
		$responsible = null;
		$checklist = null;
		$accomplices = null;
		$status = null;
		$priority = null;
		$creator = null;
		$seTag = null;

		foreach($t as $k => $v)
		{
			if($k == 'ID')
			{
				$id = $v;
			}
			if($k == 'TITLE')
			{
				$title = $v;
			}
			if($k == 'TAGS')
			{
				$tags = $v;
			}
			if($k == 'SE_TAG')
			{
				$seTag = $v;
			}
			if($k == 'CREATED_BY')
			{
				$creator = $v;
			}
			if($k == 'RESPONSIBLE_ID')
			{
				$responsible = $v;
			}
			if($k == 'SE_CHECKLIST')
			{
				$checklist = $v;
			}
			if($k == 'ACCOMPLICES')
			{
				$accomplices = $v;
			}
			if($k == 'STATUS')
			{
				$status = $v;
			}
			if($k == 'PRIORITY')
			{
				$priority = $v;
			}
		}

		$this->assertGreaterThan(0, $id);
		$this->assertTrue($title <> '');

		$this->assertEquals(2, $priority);
		$this->assertEquals(3, $status);
		$this->assertLegalCollection($checklist, 3);
		$this->assertLegalCollection($accomplices, 2);
		$this->assertLegalCollection($seTag, 2);
		$this->assertEquals($user1, $creator);
		$this->assertEquals($user2, $responsible);
	}

	/**
	 * @depends testTrigger
	 */
	public function testIllegalOffset()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);

		// illegal offset also could be legally stored inside object until clearData() happens
		$t['ILLEGAL'] = 2;
		$this->assertEquals(2, $t['ILLEGAL']);
	}

	/**
	 * @depends testTrigger
	 */
	public function testDoubleIterateItem()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);

		$count1 = 0;
		foreach($t as $k => $v)
		{
			$count1++;
		}

		$count2 = 0;
		foreach($t as $k => $v)
		{
			$count2++;
		}

		$this->assertEquals($count1, $count2);
	}

	/**
	 * @depends testTrigger
	 */
	public function testNestedIterate()
	{
		$t = new \Bitrix\Tasks\Item\Task\Template(0, static::$userId);

		$count1 = 0;
		$count2 = 0;
		foreach($t as $k => $v1)
		{
			$count1++;

			if($k == 'DESCRIPTION')
			{
				$count2 = 0;
				foreach($t as $v2)
				{
					$count2++;
				}
			}
		}

		$this->assertEquals($count1, $count2);
	}

	/**
	 * @depends testTrigger
	 */
	public function testFieldValueRace()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$taskData = array('override' => array(
			'TITLE' => 'Newest task',
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		));

		$crmUf = '';
		if(\Bitrix\Tasks\Integration\CRM::includeModule())
		{
			$lead = \Bitrix\Crm\LeadTable::getList(array('limit' => 1, 'select' => array('ID')))->fetch();
			$contact = \Bitrix\Crm\ContactTable::getList(array('limit' => 1, 'select' => array('ID')))->fetch();

			$crmUf = \Bitrix\Tasks\Integration\CRM\UserField::getMainSysUFCode();

			$taskData['override'][$crmUf] = array('L_'.$lead['ID'], 'C_'.$contact['ID']);
		}

		$id = static::makeDemoTaskLegacy($taskData);

		// update some field before fetchBaseData()

		$newTitle = 'Brand new task';

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);
		$t['TITLE'] = $newTitle;
		$t['PRIORITY'] = 2;

		if(\Bitrix\Tasks\Integration\CRM::includeModule())
		{
			$this->assertEquals($taskData['override'][$crmUf], $t[$crmUf]->toArray());
		}

		$this->assertEquals($newTitle, $t['TITLE']);
		$this->assertEquals(2, $t['PRIORITY']);

		// another race

		$t2 = new \Bitrix\Tasks\Item\Task($id, $user2);

		$dataA = $t2->getData();
		$t2['TITLE'] = $newTitle;
		$dataB = $t2->getData();

		$this->assertNotEquals($dataA['TITLE'], $dataB['TITLE']);
		$this->assertEquals($newTitle, $dataB['TITLE']);
	}

	/**
	 * @depends testTrigger
	 */
	public function testSetDataNewItem()
	{
		$t = new \Bitrix\Tasks\Item\Task(0, static::$userId);

		$newTitle = 'New title';
		$t['ACCOMPLICES'] = array(10, 20);
		$t['TITLE'] = 'Old title';

		$t->setData(array(
			'TITLE' => 'New title'
		));

		$t->getData();

		$this->assertEquals($newTitle, $t['TITLE']);
		$this->assertLegalCollection($t['SE_MEMBER'], 2);
	}

	/**
	 * @depends testTrigger
	 */
	public function testSetDataExistingItem()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);

		$newTitle = 'New title';
		$t['ACCOMPLICES'] = array(10, 20);
		$t['TITLE'] = 'Old title';

		$t->setData(array(
			'TITLE' => 'New title',
			'ACCOMPLICES' => array(500, 600, 700)
		));

		$t->getData();

		$this->assertEquals($newTitle, $t['TITLE']);
		$this->assertLegalCollection($t['SE_MEMBER'], 7);

		$acc = $t['SE_MEMBER']->find(array('=TYPE' => 'A'))->getUserIds();

		$this->assertDictItemsEqual($acc, array(500, 600, 700));
	}

	/**
	 * @depends testTrigger
	 */
	public function testExport()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);

		$data = $t->export();

		$this->assertTrue(is_array($data));
		$this->assertTrue(is_array($data['ACCOMPLICES']));
		$this->assertDictItemsEqual($data['ACCOMPLICES'], array($user1, $user2));

		$this->assertEquals($user1, $data['CREATED_BY']);
		$this->assertTrue(is_array($data['SE_MEMBER']));
		$this->assertEquals(6, count($data['SE_MEMBER']));

		$this->assertTrue(is_array($data['SE_CHECKLIST']));
		$this->assertTrue(is_array($data['SE_CHECKLIST'][0]));
	}

	/**
	 * @depends testTrigger
	 */
	public function testProperties()
	{
		$title = 'TITLE!';

		$t = new \Bitrix\Tasks\Item\Task();
		$t->title = $title;

		$this->assertEquals($title, $t['TITLE']);

		$t->responsibleId = 100;
		$this->assertEquals(100, $t['RESPONSIBLE_ID']);

		$t['CREATED_BY'] = 200;
		$this->assertEquals(200, $t->createdBy);
	}

	/**
	 * @depends testTrigger
	 */
	public function testGetDataCached()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, static::$userId);

		$t['TITLE'];
		$t['DESCRIPTION'];

		$data = $t->getData('~');

		$this->assertEquals(null, $data['SE_CHECKLIST']); // it wont be lazy-loaded with ~
		$this->assertEquals(null, $data['SE_MEMBER']); // this too
	}

	/**
	 * @depends testTrigger
	 */
	public function testAdd()
	{
		$t = new \Bitrix\Tasks\Item\Task();
		$t['TITLE'] = 'OLALA';
		$t['UF_CRM_TASK'] = array('L_1', 'L_2');
		$t['UF_TASK_WEBDAV_FILES'] = array(
			static::makeDemoAttachment(),
			static::makeDemoAttachment(),
		);

		$this->assertTrue($t->save()->isSuccess());

		$this->assertGreaterThan(0, $t->getId());

		$this->assertEquals('OLALA', $t['TITLE']);
		$this->assertItemsEqual($t['UF_CRM_TASK']->toArray(), array('L_1', 'L_2'));
		$this->assertEquals(2, $t['UF_TASK_WEBDAV_FILES']->count());
	}

	public function testGetData()
	{
		$t = new \Bitrix\Tasks\Item\Task\Template();

		// get tablet

		$tablet = $t->getData('#');
		$tablet = array_keys($tablet);

		$tabletAll = array(
			'0' => 'ID',
			'1' => 'TITLE',
			'2' => 'DESCRIPTION',
			'3' => 'DESCRIPTION_IN_BBCODE',
			'4' => 'PRIORITY',
			'5' => 'TIME_ESTIMATE',
			'6' => 'REPLICATE',
			'7' => 'CREATED_BY',
			'8' => 'XML_ID',
			'9' => 'ALLOW_CHANGE_DEADLINE',
			'10' => 'ALLOW_TIME_TRACKING',
			'11' => 'TASK_CONTROL',
			'12' => 'ADD_IN_REPORT',
			'13' => 'MATCH_WORK_TIME',
			'14' => 'GROUP_ID',
			'15' => 'PARENT_ID',
			'16' => 'MULTITASK',
			'17' => 'SITE_ID',
			'18' => 'DEADLINE_AFTER',
			'19' => 'START_DATE_PLAN_AFTER',
			'20' => 'END_DATE_PLAN_AFTER',
			'21' => 'TASK_ID',
			'22' => 'TPARAM_TYPE',
			'23' => 'TPARAM_REPLICATION_COUNT',
			'24' => 'RESPONSIBLE_ID',
			'25' => 'ACCOMPLICES',
			'26' => 'AUDITORS',
			'27' => 'RESPONSIBLES',
			'28' => 'DEPENDS_ON',
			'29' => 'REPLICATE_PARAMS',
			'30' => 'SE_TAG',
		);
		$tabletDiff = array_diff($tabletAll, $tablet);
		$this->assertEquals(0, count($tabletDiff));

		// get specified
		$specList = array('TITLE', 'RESPONSIBLE_ID', 'GROUP_ID');
		$specified = $t->getData($specList);

		$specified = array_keys($specified);
		$specifiedDiff = array_diff($specified, $specList);
		$this->assertEquals(0, count($specifiedDiff));

		// get cached

		$t = static::makeDemoTemplate();
		$this->assertTrue($t > 0);

		$t = new \Bitrix\Tasks\Item\Task\Template($t);

		$t['TITLE'] = 'LALA';
		$t['GROUP_ID'] = 100;

		$cached = array_keys($t->getData('~'));
		$cachedDiff = array_diff($cached, array('ID', 'TITLE', 'GROUP_ID'));
		$this->assertEquals(0, count($cachedDiff));

		// get all
		$all = $t->getData();
		// todo: check
	}

	/*
	public function testSaveExistingItem()
	{

	}

	public function testDeleteItem()
	{

	}

	public function testTransformExistingItem()
	{

	}

	public function testTransformNewItem()
	{

	}
	*/

	/*
	public function testTricks()
	{
		$user1 = static::$users[0];
		$user2 = static::$users[1];

		$id = static::makeDemoTaskLegacy(array('override' => array(
			'CREATED_BY' => $user1,
			'RESPONSIBLE_ID' => $user2,
			'ACCOMPLICES' => array($user1, $user2),
			'AUDITORS' => array($user1, $user2),
			'STATUS' => 3,
			'PRIORITY' => 2,
		)));

		$t = new \Bitrix\Tasks\Item\Task($id, $user2);

		//

		// add array to collection
		$t['SE_MEMBER']->push(array(
			'USER_ID' => $user1,
			'TYPE' => 'O',
		));

		_print_r($t);
	}
	*/
}