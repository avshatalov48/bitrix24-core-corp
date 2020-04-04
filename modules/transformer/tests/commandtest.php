<?php

namespace Bitrix\Transformer\Tests;

use Bitrix\Transformer\Command;
use Bitrix\Transformer\Entity\CommandTable;

class CommandTest extends \CBitrixTestCase
{
	private $command;
	private $statuses = array(
		'create' => 100,
		'send' => 200,
		'upload' => 300,
		'success' => 400,
		'error' => 1000
	);
	private $demo = array(
		'command' => 'UnitTestCommand',
		'params' => array('test' => 'params'),
		'module' => array('bxtest'),
		'callback' => array('TestCallback'),
		'status' => '100',
	);
	private $id;

	public static function setUpBeforeClass()
	{
		\Bitrix\Main\Loader::includeModule('transformer');
	}

	function setUp()
	{
		$this->command = new Command($this->demo['command'], $this->demo['params'], $this->demo['module'], $this->demo['callback'], $this->demo['status']);
	}

	private function getMockHttpObject($response = array('success' => true))
	{
		$http = $this->getMock('Bitrix\\Transformer\\Http', array('query'));
		$http->expects($this->any())->method('query')->will(
			$this->returnValue(
				$response
			)
		);
		return $http;
	}

	private function saveCommand()
	{
		$saveResult = $this->command->save();
		$this->id = $saveResult->getId();
		return $saveResult;
	}

	function constructProvider()
	{
		return array(
			'empty command' => array('', $this->demo['params'], $this->demo['module'], $this->demo['callback']),
			'empty module' => array($this->demo['command'], $this->demo['params'], '', $this->demo['callback']),
			'empty callback' => array($this->demo['command'], $this->demo['params'], $this->demo['module'], array()),
		);
	}

	/**
	 * @dataProvider constructProvider
	 */
	function testConstruct($command, $params, $module, $callback)
	{
		$this->setExpectedException('Bitrix\Main\ArgumentNullException');
		$command = new Command($command, $params, $module, $callback);
	}

	function testSave()
	{
		$saveResult = $this->saveCommand();
		$this->assertEquals(true, $saveResult->isSuccess());
		$this->assertGreaterThan(1, $this->id);
		return $this->id;
	}

	function testGetStatus()
	{
		$this->assertEquals($this->demo['status'], $this->command->getStatus());
	}

	function testUpdateStatusOnUnsavedCommand()
	{
		$this->setExpectedException('Bitrix\Main\InvalidOperationException');
		$this->command->updateStatus($this->statuses['send']);
	}

	function statusProvider()
	{
		return array(
			'send' => array(200),
			'upload' => array(300),
			'success' => array(400),
			'error' => array(1000)
		);
	}

	/**
	 * @depends testSave
	 * @dataProvider statusProvider
	 */
	function testUpdateStatus($status, $id)
	{
		$this->command = new Command($this->demo['command'], $this->demo['params'], $this->demo['module'], $this->demo['callback'], $this->demo['status'], $id);
		$this->command->updateStatus($status);
		$this->assertEquals($status, $this->command->getStatus());
	}

	function testSendSuccess()
	{
		$http = $this->getMockHttpObject();
		$this->saveCommand();
		$sendResult = $this->command->send($http);
		$this->assertTrue($sendResult->isSuccess());
		$this->assertEquals($this->statuses['send'], $this->command->getStatus());
	}

	function testSendNotSaved()
	{
		$this->setExpectedException('Bitrix\Main\InvalidOperationException');
		$http = $this->getMockHttpObject();
		$this->command->send($http);
	}

	function testSendWrongStatus()
	{
		$this->setExpectedException('Bitrix\Main\InvalidOperationException');
		$this->saveCommand();
		$this->command->updateStatus($this->statuses['success']);
		$http = $this->getMockHttpObject();
		$this->command->send($http);
	}

	function testSendWrongCommand()
	{
		$this->setExpectedException('Bitrix\Main\NotSupportedException');
		$this->saveCommand();
		$http = $this->getMockHttpObject(array('result' => array('code' => 'WRONG_COMMAND'), 'success' => false));
		$this->command->send($http);
	}

	function testSendWrongAnswer()
	{
		$this->saveCommand();
		$http = $this->getMockHttpObject(array('result' => array('code' => 'SOME_ERROR'), 'success' => false));
		$this->command->send($http);
		$this->assertEquals($this->statuses['error'], $this->command->getStatus());
	}

	function testGetSuccess()
	{
		$this->saveCommand();
		$commandItem = CommandTable::getRowById($this->id);
		$this->assertEquals($this->id, $commandItem['ID']);
		$guid = $commandItem['GUID'];
		$commandGet = Command::getByGuid($guid);
		$this->assertEquals($this->command, $commandGet);
		$this->assertEquals($this->statuses['create'], $commandGet->getStatus());
	}

	function testGetEmptyArgument()
	{
		$this->setExpectedException('Bitrix\Main\ArgumentNullException');
		Command::getByGuid('');
	}

	function testGetNotFound()
	{
		$command = Command::getByGuid('1');
		$this->assertFalse($command);
	}

	function testCallbackSuccess()
	{
		$result = array('result' => 'test');
		$className = 'TestCommandCallback';
		$callback = $this->getMockBuilder('\Bitrix\Transformer\InterfaceCallback')->setMockClassName($className)->setMethods(array('call'))->getMock();
		$callback::staticExpects($this->once())->method('call')->will($this->returnValue(true));
		$command = new Command($this->demo['command'], $this->demo['params'], $this->demo['module'], $className);
		$callResult = $command->callback($result);
		$this->assertTrue($callResult);
	}

	function testCallbackWrongModule()
	{
		$result = array('result' => 'test');
		$command = new Command($this->demo['command'], $this->demo['params'], 'bad_module_name', '\Bitrix\Transformer\DocumentTransformer');
		$callResult = $command->callback($result);
		$this->assertFalse($callResult);
	}

	function testCallbackWrongCallback()
	{
		$result = array('result' => 'test');
		$command = new Command($this->demo['command'], $this->demo['params'], $this->demo['module'], 'SomeFoulClass');
		$callResult = $command->callback($result);
		$this->assertFalse($callResult);
	}

	protected function tearDown()
	{
		if($this->id > 0)
			CommandTable::delete($this->id);
		parent::tearDown();
	}
}