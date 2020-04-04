<?php
namespace Bitrix\FaceId;

class Controller
{
	public static function receiveCommand($command, $params)
	{
		$result = null;

		foreach ($params as $key => $value)
		{
			$params[$key] = $value == '#EMPTY#'? '': $value;
		}

		$result = "";

		Log::write(Array('command' => $command, 'params' => $params), 'Controller/ReceiveMessage');

		return $result;
	}
}