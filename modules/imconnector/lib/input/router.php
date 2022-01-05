<?php
namespace Bitrix\ImConnector\Input;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Result,
	\Bitrix\ImConnector\Error,
	\Bitrix\ImConnector\Connector,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Status;

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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function receiving($command, $connector, $line = null, $data = array()): Result
	{
		$result = new Result();

		if(empty($command))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND'), Library::ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_COMMAND, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));
		}

		if(empty($connector) && Connector::isConnector($connector, true))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR'), Library::ERROR_IMCONNECTOR_NOT_SPECIFIED_CORRECT_CONNECTOR, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));
		}

		if($result->isSuccess())
		{
			if(!is_array($data))
			{
				$data = [$data];
			}

			switch ($command)
			{
				case 'testConnect'://Test connection
					$result->setResult('OK');
					break;
				case 'receivingMessage'://To receive the message
					$lineStatus = Status::getInstance($connector, $line);
					if ($lineStatus->isStatus())
					{
						$receivingHandlers = new ReceivingMessage($connector, $line, $data);
						$receivingHandlers->receiving();
					}
					else
					{
						$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_ACTIVE_LINE'), Library::ERROR_IMCONNECTOR_NOT_ACTIVE_LINE, __METHOD__, array('$command' => $command, '$connector' => $connector, '$line' => $line, '$data' => $data)));
					}
					break;
				case 'receivingStatusDelivery'://To receive a delivery status
					$receivingHandlers = new ReceivingStatusDelivery($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'receivingStatusReading'://To receive the status of reading
					$receivingHandlers = new ReceivingStatusReading($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'receivingError':
					$receivingHandlers = new ReceivingError($connector, $line, $data);
					$receivingHandlers->receiving();
					break;
				case 'receivingStatusBlock':
					$receivingHandlers = new ReceivingStatusBlock($connector, $line, $data);
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