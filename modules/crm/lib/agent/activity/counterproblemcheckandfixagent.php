<?php

namespace Bitrix\Crm\Agent\Activity;


use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Counter\ProblemDetector\Detector;

class CounterProblemCheckAndFixAgent extends AgentBase
{
	public static function doRun()
	{
		$detector = new Detector();
		$detector->execute();

		return true;
	}

}