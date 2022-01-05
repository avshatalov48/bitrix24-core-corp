<?php
namespace Bitrix\ImConnector\Input;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Result;
use Bitrix\Main\Event;

class ReceivingStatusBlock
{
	private $connector;
	private $line;
	private $data;

	/**
	 * ReceivingStatusBlock constructor.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 * @param array $data Array of input data.
	 */
	public function __construct($connector, $line = null, $data = [])
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->data = $data;
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

		foreach ($this->data as $status)
		{
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

		return $result;
	}

	/**
	 * @param array $data An array of data.
	 * @return Result
	 */
	private function sendEvent(array $data): Result
	{
		$result = new Result();
		$data['connector'] = $this->connector;
		$data['line'] = $this->line;
		$event = new Event(Library::MODULE_ID, Library::EVENT_RECEIVED_STATUS_BLOCK, $data);
		$event->send();

		return $result;
	}
}