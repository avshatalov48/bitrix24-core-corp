<?php
namespace Bitrix\ImConnector\Input;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Result;
use Bitrix\Main\Event;

class ReceivingStatusBlock
{
	private $connector;
	private $line;
	private $data;
	private $connectorOutput;

	/**
	 * ReceivingStatusBlock constructor.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 * @param array $data Array of input data.
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function __construct($connector, $line = null, $data = [])
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->data = $data;
		$this->connectorOutput = new Output($this->connector, $this->line);
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function receiving(): Result
	{
		$result = new Result();
		$statusDelivered = [];

		foreach ($this->data as $status)
		{
			$statusDelivered[] = array(
				'chat' => array(
					'id' => $status['chat']['id']
				),
				'message' => array(
					'id' => $status['message']['id']
				)
			);

			$user = Connector::getUserByUserCode($status['user'], $this->connector);

			if ($user->isSuccess())
			{
				$userData = $user->getResult();
				$status['user'] = $userData['ID'];
			}

			$event = $this->sendEvent($status);
			if (!$event->isSuccess())
			{
				$result->addErrors($event->getErrors());
			}
		}
		if (!empty($statusDelivered))
		{
			$this->connectorOutput->setStatusDelivered($statusDelivered);
		}

		return $result;
	}

	/**
	 * @param $data array An array of data.
	 * @return Result
	 */
	private function sendEvent($data): Result
	{
		$result = new Result();
		$data['connector'] = $this->connector;
		$data['line'] = $this->line;
		$event = new Event(Library::MODULE_ID, Library::EVENT_RECEIVED_STATUS_BLOCK, $data);
		$event->send();

		return $result;
	}
}