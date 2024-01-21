<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Collector;

use Bitrix\Crm\Counter\ProblemDetector\Problem;

interface Collector
{
	public const COLLECT_LIMIT = 40;
	public function collect(): Problem;
}
