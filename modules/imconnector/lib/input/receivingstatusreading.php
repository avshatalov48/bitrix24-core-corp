<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Event;
use \Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);

/**
 * The class obtaining the status of a reading.
 *
 * @package Bitrix\ImConnector\Input
 */
class ReceivingStatusReading
{
	private $connector;
	private $line;
	private $data;

	/**
	 * ReceivingStatusReading constructor.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 * @param array $data An array of data.
	 */
	function __construct($connector, $line = null, $data = array())
	{
		$this->connector = $connector;
		$this->line = $line;
		$this->data = $data;
	}

	/**
	 * Receive data.
	 *
	 * @return Result
	 */
	public function receiving()
	{
		$result = new Result();

		foreach ($this->data as $cell => $status)
		{
			$event = $this->sendEvent($status);
			if(!$event->isSuccess())
				$result->addErrors($event->getErrors());
		}

		return $result;
	}

	/**
	 * Generation of the event of reading.
	 *
	 * @param $data array An array of data.
	 * @return Result
	 */
	private function sendEvent($data)
	{
		$result = new Result();
		$data["connector"] = $this->connector;
		$data["line"] = $this->line;
		$event = new Event(Library::MODULE_ID, Library::EVENT_RECEIVED_STATUS_READING, $data);
		$event->send();

		return $result;
	}
}