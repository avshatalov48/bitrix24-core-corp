<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Integration;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\Task\TranscriptionOverview;

/*
{
	"insights": [
		{
			"speaker": "string or null",
			"detailed_insight": "string or null"
		}
	],
	"meeting_strengths": [
		{
			"strength_title": "string",
			"strength_explanation": "string"
		}
	],
	"meeting_weaknesses": [
		{
			"weakness_title": "string",
			"weakness_explanation": "string"
		}
	],
	"speech_style_influence": "string or null",
	"engagement_level": "string or null",
	"areas_of_responsibility": "string or null",
	"final_recommendations": "string"
}
*/

class Insights
{
	public array $insights = [];
	public array $meetingStrengths = [];
	public array $meetingWeaknesses = [];
	public string $speechStyleInfluence = '';
	public string $engagementLevel = '';
	public string $areasOfResponsibility = '';
	public string $finalRecommendations = '';
	public bool $isEmpty = true;

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
						$key = lcfirst(str_replace('_', '', ucwords($key, '_')));
						$output->{$key} = $val;
					}
				}
				return $output;
			};

			$fieldsMap = [
				'insights' => 'insights',
				'meetingStrengths' => 'meeting_strengths',
				'meetingWeaknesses' => 'meeting_weaknesses',
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

			$fieldsMap = [
				'speechStyleInfluence' => 'speech_style_influence',
				'engagementLevel' => 'engagement_level',
				'areasOfResponsibility' => 'areas_of_responsibility',
				'finalRecommendations' => 'final_recommendations',
			];
			foreach ($fieldsMap as $field => $prop)
			{
				$value = $outcome->getProperty($prop);
				if ($value)
				{
					$this->{$field} = $value->getContent();
				}
			}
		}
	}


	public function toRestFormat(): array
	{
		$mentionService = MentionService::getInstance();

		$result = [];

		$fields = [
			'insights' => ['detailedInsight'],
			'meetingStrengths' => ['strengthExplanation', 'strengthTitle'],
			'meetingWeaknesses' => ['weaknessTitle', 'weaknessExplanation'],
		];
		foreach ($fields as $field => $subFields)
		{
			if ($this?->{$field})
			{
				$result[$field] = [];
				foreach ($this->{$field} as $i => $row)
				{
					$result[$field][$i] = [];
					foreach ($subFields as $subField)
					{
						if ($row?->{$subField})
						{
							$result[$field][$i][$subField] = $mentionService->replaceBbMentions($row->{$subField});
						}
					}
				}
			}
		}
		foreach (['speechStyleInfluence', 'engagementLevel', 'areasOfResponsibility', 'finalRecommendations'] as $field)
		{
			if ($this?->{$field})
			{
				$result[$field] = $mentionService->replaceBbMentions($this->{$field});
			}
		}

		return $result;
	}
}