<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Error,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Library;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Class of distribution of incoming requests from the server.
 * @package Bitrix\ImConnector\Input
 */
class Router
{
	/**
	 * The method accepting the entering data from the server of connectors.
	 *
	 * @param string $command The called method.
	 * @param string $connector ID connector.
	 * @param string|null $line ID of the open line.
	 * @param array $data Data array.
	 * @return Result
	 */
	static public function receiving($command, $connector, $line = null, $data = array())
	{
		$result = new Result();

		if(empty($command))
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND'), Library::ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));

		if(empty($connector) && Connector::isConnector($connector, true))
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR'), Library::ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));

		if($result->isSuccess())
		{
			if(!is_array($data))
				$data = array($data);
			
			switch ($command)
			{
				case 'testConnect'://Test connection
					$result->setResult('OK');
					break;
				case 'receivingMessage'://To receive the message
					$receivingHandlers = new ReceivingMessage($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'receivingStatusDelivery'://To receive a delivery status
					$receivingHandlers = new ReceivingStatusDelivery($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'receivingStatusReading'://To receive the status of reading
					$receivingHandlers = new ReceivingStatusReading($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'deactivateConnector'://The disconnection of the connector due to the connection with the specified data on a different portal or lines
					$receivingHandlers = new DeactivateConnector($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				default:
					$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND'), Library::ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));
			}
		}

		return $result;
	}
}