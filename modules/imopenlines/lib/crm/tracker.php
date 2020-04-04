<?php
namespace Bitrix\ImOpenLines\Crm;

class Tracker
{
	public static function getTraceData($data = '')
	{
		$result = '';

		if (
			isset($_SESSION['LIVECHAT']['TRACE_DATA'])
			&& strlen($_SESSION['LIVECHAT']['TRACE_DATA']) > 0
			&& !$data
		)
		{
			$result = $_SESSION['LIVECHAT']['TRACE_DATA'];
		}
		else if($data)
		{
			$result = $data;
		}

		return $result;
	}
}