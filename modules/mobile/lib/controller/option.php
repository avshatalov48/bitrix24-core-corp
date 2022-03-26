<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Error;
use Bitrix\Mobile\Project\Helper;

class Option extends \Bitrix\Main\Engine\Controller
{
	public function getAction(array $params = []): array
	{
		$result = [];

		$nameList = ($params['name'] ?? []);
		$siteId = ($params['siteId'] ?? SITE_ID);
		$siteDir = ($params['siteDir'] ?? SITE_DIR);

		if (!is_array($nameList))
		{
			$nameList = [ $nameList ];
		}

		if (empty($nameList))
		{
			return $result;
		}

		foreach ($nameList as $key)
		{
			switch ($key)
			{
				case 'projectNewsPathTemplate':
					$value = Helper::getProjectNewsPathTemplate([
						'siteDir' => $siteDir,
					]);
					break;
				case 'projectCalendarWebPathTemplate':
					$value = Helper::getProjectCalendarWebPathTemplate([
						'siteDir' => $siteDir,
					]);
					break;
				default:
					$value = null;
			}

			if ($value !== null)
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}
}
