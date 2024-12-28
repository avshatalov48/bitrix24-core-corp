<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration;
use Bitrix\Call\Integration\AI\Task\TranscriptionOverview;

/*
{
	"insights": [
		{
			"insight_type": "string or null",
			"detailed_insight": "string or null"
		}
	]
}
*/

class Insights
{
	public array $insights = [];
	public bool $isEmpty = true;

	public function __construct(?Integration\AI\Outcome $outcome = null)
	{
		if ($outcome)
		{
			$insights = $outcome->getProperty('insights')?->getStructure();
			if (is_array($insights))
			{
				foreach ($insights as $row)
				{
					if (!empty($row['detailed_insight']))
					{
						$obj = new \stdClass;
						$obj->detailed_insight = $row['detailed_insight'];
						$this->insights[] = $obj;
					}
				}
				$this->isEmpty = empty($this->insights);
			}
		}

		return $this;
	}
}