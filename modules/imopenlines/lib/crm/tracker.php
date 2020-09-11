<?php
namespace Bitrix\ImOpenLines\Crm;

class Tracker
{
	public static function getTraceData($userId, $data = '')
	{
		$result = '';

		if ($userId && !$data)
		{
			$result = \Bitrix\ImOpenLines\Widget\Cache::get($userId, 'TRACE_DATA')?: '';
		}
		elseif ($data)
		{
			$result = $data;
		}

		return $result;
	}
}