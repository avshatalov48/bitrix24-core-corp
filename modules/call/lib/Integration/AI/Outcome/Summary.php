<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration;

/*
{
	"call_summary": [
		{
			"timestamp": "string or null",
			"title": "string or null",
			"summary": "string or null"
		}
	]
}
*/

class Summary
{
	/** @var array<array{start: string, end: string, title: string, summary: string}> */
	public array $summary = [];

	public bool $isEmpty = true;

	public function __construct(?Integration\AI\Outcome $outcome = null)
	{
		if ($outcome)
		{
			$summary = $outcome->getProperty('call_summary')?->getStructure();
			if (!$summary)
			{
				$summary = $outcome->getProperty('summary')?->getStructure();
			}

			if (is_array($summary))
			{
				foreach ($summary as $row)
				{
					if (!empty($row['summary']) || !empty($row['topic']) || !empty($row['title']))
					{
						$obj = new \stdClass;
						$time = explode('–', $row['timestamp']);
						$obj->start = $time[0];
						$obj->end = $time[1];
						$obj->title = $row['title'] ?? ($row['topic'] ?? '');
						$obj->summary = $row['summary'] ?? '';
						$this->summary[] = $obj;
					}
				}
				$this->isEmpty = empty($this->summary);
			}
		}

		return $this;
	}
}