<?php

namespace Bitrix\Crm\Agent\Activity;


use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Settings\CounterSettings;

class CounterProblemCheckAndFixAgent extends AgentBase
{
	public static function doRun()
	{
		if (CounterSettings::getInstance()->isEnabled())
		{
			$detector = new Detector();
			$detector->execute();
		}

		return true;
	}

}
