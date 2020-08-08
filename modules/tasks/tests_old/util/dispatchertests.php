<?
CModule::IncludeModule('tasks');

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/tasks/dev/util/testcase.php");
$beforeClasses = get_declared_classes();
$beforeClassesCount = count($beforeClasses);

class DispatcherTests extends \Bitrix\Tasks\Dev\Util\TestCase
{
	public function testDispatcherActionRegularExecution()
	{
		//static::disableDataCleanUp();

		$tt = static::makeDemoTemplate();
		$this->assertTrue($tt > 0);

		$todo = array(
			array(
				'OPERATION' => 'task.template.update',
				'ARGUMENTS' => array(
					'id' => $tt,
					'data' => array(
						'PRIORITY' => 2,
					),
				),
				'PARAMETERS' => array(
					'code' => 'op_1',
				)
			)
		);

		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$plan->import($todo);

		$dispatcher = new \Bitrix\Tasks\Dispatcher();
		$result = $dispatcher->run($plan);

		$this->assertTrue($result->isSuccess());
		$this->assertEquals(0, $result->getErrors()->find(array('TYPE' => \Bitrix\Tasks\Util\Error::TYPE_WARNING))->count());

		$t = new \Bitrix\Tasks\Item\Task\Template($tt);
		$this->assertEquals(2, $t['PRIORITY']);
	}

	public function testDispatcherActionComponentExecution()
	{
		//static::disableDataCleanUp();

		$this->checkForComponent('tasks.base'); // works with dispatcher result as array
		$this->checkForComponent('tasks.task.template'); // works with dispatcher result as object
	}

	public function testDispatcherActionBatch()
	{
		//static::disableDataCleanUp();

		$t1 = static::makeDemoTask();
		$this->assertTrue($t1 > 0);

		$t2 = static::makeDemoTask();
		$this->assertTrue($t2 > 0);

		$t3 = static::makeDemoTask();
		$this->assertTrue($t3 > 0);

		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$plan[] = new \Bitrix\Tasks\Dispatcher\ToDo('task.get', array('id' => $t1), array('lala' => 'lolo'));
		$plan->push(new \Bitrix\Tasks\Dispatcher\ToDo('task.get', array('id' => $t2), array('lala' => 'lolo')));
		$plan->addToDo('task.get', array('id' => $t3), array('lala' => 'lolo'));
		$plan->addToDo('task.get', array('id' => $t3), array('lala' => 'lolo', 'code' => 'test'));

		$this->assertEquals('op_0', $plan->nth(0)->getCode());
		$this->assertEquals('op_1', $plan->nth(1)->getCode());
		$this->assertEquals('op_2', $plan->nth(2)->getCode());
		$this->assertEquals('test', $plan->nth(3)->getCode());

		$dispatcher = new \Bitrix\Tasks\Dispatcher();

		$result = $dispatcher->run($plan);

		$this->assertTrue($result->isSuccess());
		foreach($plan as $todo)
		{
			$todoResult = $todo->getResult();
			$this->assertTrue($todoResult->isSuccess());
			$this->assertTrue($todoResult->getData()['DATA']['ID'] > 0);
		}
	}

	public function testDispatcherActionIllegal()
	{
		$dispatcher = new \Bitrix\Tasks\Dispatcher();

		// illegal operation
		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$plan->addToDo('no.such.operation');

		$result = $dispatcher->run($plan);
		$this->assertTrue(!$result->isSuccess());
		$this->assertEquals(1, $result->getErrorCount());
		$this->assertEquals('PARSE_ERROR', $result->getErrors()->first()->getCode());

		// mandatory argument not passed
		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$plan->addToDo('task.get');

		$result = $dispatcher->run($plan);
		$this->assertTrue(!$result->isSuccess());
		$this->assertEquals(1, $result->getErrorCount());
		$this->assertEquals('PARSE_ERROR', $result->getErrors()->first()->getCode());

		// array argument is not an array
		$plan = new \Bitrix\Tasks\Dispatcher\ToDo\Plan();
		$plan->addToDo('task.update', array('id' => 100, 'data' => 200));

		$result = $dispatcher->run($plan);
		$this->assertTrue(!$result->isSuccess());
		$this->assertEquals(1, $result->getErrorCount());
		$this->assertEquals('PARSE_ERROR', $result->getErrors()->first()->getCode());
	}

	private function checkForComponent($componentName)
	{
		$tt = static::makeDemoTemplate();
		$this->assertTrue($tt > 0);

		// request forgery

		$server = \Bitrix\Main\Context::getCurrent()->getServer();
		$serverData = $server->toArray();
		$serverData['REQUEST_METHOD'] = 'POST';
		$server->set($serverData); // and they call it "immutable" ...

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$requestData = $request->getPostList()->toArray();
		$requestData['SITE_ID'] = 's1';
		$requestData['sessid'] = \bitrix_sessid();
		$requestData['ACTION'] = array(
			array(
				'OPERATION' => 'task.template.update',
				'ARGUMENTS' => array(
					'id' => $tt,
					'data' => array(
						'PRIORITY' => 2,
					),
				),
				'PARAMETERS' => array(
					'code' => 'op_1',
				)
			)
		);
		$request->getPostList()->set($requestData);

		require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/'.$componentName.'/class.php');

		ob_start();
		\TasksBaseComponent::executeComponentAjax();
		$json = ob_get_clean();

		$this->assertTrue($json <> '');
		$json = json_decode($json);

		$this->assertEquals(1, $json->SUCCESS);
		$this->assertEquals(array(), $json->ERROR);

		$op = $json->DATA->op_1;

		$this->assertEquals('task.template.update', $op->OPERATION);
		$this->assertEquals(1, $op->SUCCESS);
		$this->assertTrue($op->ERROR == null || $op->ERROR == array());
		$this->assertEquals($tt, $op->RESULT->ID);
	}
}