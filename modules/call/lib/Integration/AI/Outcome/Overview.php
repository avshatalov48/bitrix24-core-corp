<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration;
use Bitrix\Call\Integration\AI\Task\TranscriptionOverview;

/*
{
	"topic": "string or null",
	"agenda": {
		"is_mentioned": "bool",
		"explanation": "string or null",
		"quote": "string or null"
	},
	"meeting_details": {
		"type": "string or null",
		"is_exception_meeting": "bool",
		"explanation": "string or null"
	},
	"agreements": [
		{
			"agreement": "string or null",
			"quote": "string or null"
		}
	],
	"tasks": [
		{
			"task": "string or null",
			"quote": "string or null"
		}
	],
	"meetings": [
		{
			"meeting": "string or null",
			"quote": "string or null"
		}
	],
	"efficiency":
	{
		"agenda_clearly_stated": {
			"value": "bool",
			"explanation": "string or null"
		},
		"agenda_items_covered": {
			"value": "bool",
			"explanation": "string or null"
		},
		"conclusions_and_actions_outlined": {
			"value": "bool",
			"explanation": "string or null"
		},
		"time_exceed_penalty": {
			"value": "bool",
			"explanation": "string or null"
		}
	}
}
*/

class Overview
{
	public string $topic = '';
	public ?\stdClass $agenda = null;
	public ?\stdClass $efficiency = null;
	public int $efficiencyValue = -1;
	public ?\stdClass $calendar = null;/** @see TranscriptionOverview::buildOutcome */
	public array $tasks = [];
	public array $meetings = [];
	public array $agreements = [];
	public ?\stdClass $meetingDetails = null;
	public bool $isExceptionMeeting = false;


	public function __construct(?Integration\AI\Outcome $outcome = null)
	{
		if ($outcome)
		{
			$convertObj = static function ($input) use (&$convertObj)
			{
				$output = new \stdClass();
				foreach ($input as $key => $val)
				{
					if (is_array($val) && !empty($val))
					{
						$val = $convertObj($val);
					}
					if (!is_null($val))
					{
						$output->{$key} = $val;
					}
				}
				return $output;
			};

			$value = $outcome->getProperty('topic');
			if ($value)
			{
				$this->topic = $value->getContent();
			}

			$fieldsMap = [
				'agenda' => 'agenda',
				'meetingDetails' => 'meeting_details',
				'efficiency' => 'efficiency',
				'calendar' => 'calendar',
			];
			foreach ($fieldsMap as $field => $prop)
			{
				$value = $outcome->getProperty($prop)?->getStructure();
				if (is_array($value))
				{
					$this->{$field} = $convertObj($value);
				}
			}

			$fieldsMap = [
				'tasks' => 'tasks',
				'meetings' => 'meetings',
				'agreements' => 'agreements',
			];
			foreach ($fieldsMap as $field => $prop)
			{
				$values = $outcome->getProperty($prop)?->getStructure();
				if (is_array($values))
				{
					$this->{$field} = [];
					foreach ($values as $row)
					{
						$obj = $convertObj($row);
						if (!empty($obj))
						{
							$this->{$field}[] = $obj;
						}
					}
				}
			}
		}

		if ($this->meetingDetails)
		{
			$this->isExceptionMeeting = (bool)($this->meetingDetails?->is_exception_meeting);
		}

		$this->calcEfficiency();

		return $this;
	}

	public function calcEfficiency(): int
	{
		if (!empty($this->efficiency))
		{
			$this->efficiencyValue = 0;

			$isPersist = function ($field): bool
			{
				if (!empty($this->efficiency->{$field}))
				{
					if (
						isset($this->efficiency->{$field}->value)
						&& (bool)$this->efficiency->{$field}->value
					)
					{
						return true;
					}
					elseif (
						is_bool($this->efficiency->{$field})
						&& $this->efficiency->{$field}
					)
					{
						return true;
					}
				}
				return false;
			};

			if ($this->isExceptionMeeting)
			{
				$this->efficiencyValue += 25; // #1
				$this->efficiencyValue += 25; // #3
				if ($isPersist('agenda_items_covered'))
				{
					$this->efficiencyValue += 25;
				}
			}
			else
			{
				$efficiencyWeights = [
					'agenda_clearly_stated' => 25, // #1
					'agenda_items_covered' => 25, // #2
					'conclusions_and_actions_outlined' => 25,// #3
				];
				foreach ($efficiencyWeights as $field => $weight)
				{
					if ($isPersist($field))
					{
						$this->efficiencyValue += $weight;
					}
				}
			}

			// #4
			if ($this->calendar)
			{
				$this->efficiencyValue += $this->calendar->overhead ? 0 : 25;
			}
			else
			{
				$this->efficiencyValue += 25;
			}

			return $this->efficiencyValue;
		}

		return -1;
	}
}