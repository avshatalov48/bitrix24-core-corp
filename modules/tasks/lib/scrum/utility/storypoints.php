<?php
namespace Bitrix\Tasks\Scrum\Utility;

class StoryPoints
{
	/**
	 * The method calculates the sum of story points.
	 *
	 * @param array $listStoryPoints List story points.
	 * @return float
	 */
	public function calculateSumStoryPoints(array $listStoryPoints): float
	{
		$sumStoryPoints = 0;

		foreach ($listStoryPoints as $storyPoints)
		{
			$sumStoryPoints += (float) $storyPoints;
		}

		return $sumStoryPoints;
	}
}