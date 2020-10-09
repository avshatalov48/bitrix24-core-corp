<?php
namespace Bitrix\Timeman;

class Common
{
	public static function checkOptionNetworkRange($ranges = Array())
	{
		if (!is_array($ranges))
			return false;

		$correctRange = [];
		$errorRange = [];
		foreach ($ranges as $range)
		{
			$range = array_change_key_case($range, CASE_LOWER);
			if (preg_match(
				"/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s?\-?\s?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})?$/",
				$range['ip_range'], $result
			))
			{
				$correctRange[] = [
					'ip_range' => str_replace(" ", "", $result[0]),
					'name' => (string)$range['name']
				];
			}
			else
			{
				$errorRange[] = $range;
			}
		}

		return Array(
			'CORRECT' => $correctRange,
			'ERROR' => $errorRange
		);
	}

	public static function setOptionNetworkRange($ranges = Array())
	{
		if (!is_array($ranges))
			return false;

		$result = \Bitrix\Timeman\Common::checkOptionNetworkRange($ranges);
		\Bitrix\Main\Config\Option::set('timeman', 'intranet_network_range', serialize($result['CORRECT']));

		return true;
	}

	public static function getOptionNetworkRange()
	{
		$ranges = \Bitrix\Main\Config\Option::get('timeman', 'intranet_network_range', 'a:0:{}');

		return unserialize($ranges);
	}

	public static function isNetworkRange($userIp = '')
	{
		$userIp = trim($userIp);

		if ($userIp == '')
		{
			$userIp = $_SERVER['REMOTE_ADDR'];
		}

		if ($userIp == '127.0.0.1')
		{
			return [
				'IP' => $userIp,
				'RANGE' => $userIp,
				'NAME' => 'localhost',
			];
		}

		$userIpLong = ip2long($userIp);

		$networkRanges = \Bitrix\Timeman\Common::getOptionNetworkRange();

		if (!empty($networkRanges))
		{
			foreach($networkRanges as $range)
			{
				if (mb_strpos($range['ip_range'], "-") !== false)
				{
					$ipRange = explode("-", $range['ip_range']);
					$ipMinRange = ip2long($ipRange[0]);
					$ipMaxRange = ip2long($ipRange[1]);
					if ($userIpLong <= $ipMaxRange && $userIpLong >= $ipMinRange)
					{
						return [
							'IP' => $userIp,
							'RANGE' => $range['ip_range'],
							'NAME' => $range['name'],
						];
					}
				}
				else if ($userIp == $range['ip_range'])
				{
					return [
						'IP' => $userIp,
						'RANGE' => $range['ip_range'],
						'NAME' => $range['name'],
					];
				}
			}

			return false;
		}

		return [
			'ip' => $userIp,
			'range' => $userIp,
			'name' => $userIp,
		];
	}

	public static function isAdmin()
	{
		return $GLOBALS['USER']->IsAdmin() || \Bitrix\Main\Loader::includeModule('bitrix24') && \CBitrix24::IsPortalAdmin($GLOBALS['USER']->GetID());
	}
}