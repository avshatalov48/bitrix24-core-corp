<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Event,
	\Bitrix\Main\Type\DateTime;
use \Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);
/**
 * The class receiving the delivery status.
 *
 * Class ReceivingStatusDelivery
 * @package Bitrix\ImConnector\Input
 */
class ReceivingStatusDelivery
{
	private $connector;
	private $line;
	private $data;

	/**
	 * ReceivingStatusDelivery constructor.
	 * @param string $connector ID connector.
	 * @param string $line ID line.
	 * @param array $data Array of input data.
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
			if(!Library::isEmpty($status['message']['date']))
				$status['message']['date'] = DateTime::createFromTimestamp($status['message']['date']);

			$event = $this->sendEvent($status);
			if(!$event->isSuccess())
				$result->addErrors($event->getErrors());
		}

		return $result;
	}

	/**
	 * Generation of the event delivery.
	 *
	 * @param array $data The data array
	 * @return Result
	 */
	private function sendEvent($data)
	{
		$result = new Result();
		$data["connector"] = $this->connector;
		$data["line"] = $this->line;
		$event = new Event(Library::MODULE_ID, Library::EVENT_RECEIVED_STATUS_DELIVERY, $data);
		$event->send();

		return $result;
	}
}