<?php
namespace Bitrix\ImOpenLines\Log;

use \Bitrix\ImOpenLines\Model\LogTable;

class Finish
{
	/**
	 * @param $params
	 * @param $data
	 * @throws \Exception
	 */
	public static function add($params, $data)
	{
		if(defined('IMOPENLINES_LOG_FINISH'))
		{
			$lineId = $params['CONFIG_ID'];
			$connectorId = $params['SOURCE'];
			$sessionId = $params['SESSION_ID'];
			$type = 'FINISH';

			ob_start();
			print_r($data);
			$data = ob_get_clean();

			LogTable::add([
				'LINE_ID' => $lineId,
				'CONNECTOR_ID' => $connectorId,
				'SESSION_ID' => $sessionId,
				'TYPE' => $type,
				'DATA' => $data,
			]);
		}
	}
}