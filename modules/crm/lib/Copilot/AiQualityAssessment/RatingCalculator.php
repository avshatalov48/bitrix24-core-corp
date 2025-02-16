<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment;

use Bitrix\Crm\Copilot\AiQualityAssessment\Controller\AiQualityAssessmentController;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentTable;

class RatingCalculator
{
	public const METHOD_SIMPLE_AVG = 1;
	public const METHOD_FIBONACCI = 2;

	private const ASSESSMENT_ITEMS_LIMIT = 1000;

	final public function calculateRating(int $userId, int $newAssessment, int $method = self::METHOD_FIBONACCI): int
	{
		if ($userId <= 0)
		{
			return 0;
		}

		$assessmentList = $this->getAssessmentList($userId, $newAssessment);

		return $this->calculateRatingByAssessmentList($assessmentList, $method);
	}

	/**
	 * @param int[] $assessmentIds
	 * @param int $method
	 * @return int[]
	 */
	final public function calculateRatingByAssessmentIds(array $assessmentIds, int $method = self::METHOD_FIBONACCI): array
	{
		if (empty($assessmentIds))
		{
			return [];
		}

		$result = [];
		foreach ($assessmentIds as $assessmentId)
		{
			$assessments = $this->getAssessmentListBySettingsId($assessmentId);
			$result[$assessmentId] = $this->calculateRatingByAssessmentList($assessments, $method);
		}

		return $result;
	}

	private function calculateRatingByAssessmentList(array $assessmentList, int $method): int
	{
		if ($method === self::METHOD_SIMPLE_AVG)
		{
			return round(array_sum($assessmentList)/count($assessmentList));
		}

		if ($method === self::METHOD_FIBONACCI)
		{
			return $this->calculateFibonacciRating($assessmentList);
		}

		return 0;
	}

	final public function getPrevRating(int $userId): int
	{
		if ($userId <= 0)
		{
			return 0;
		}

		$current = AiQualityAssessmentController::getInstance()->getList([
			'select' => ['ASSESSMENT_AVG'],
			'filter' => [
				'=RATED_USER_ID' => $userId,
				'=USE_IN_RATING' => true,
				'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
			],
			'order' => [
				'CREATED_AT' => 'DESC',
			],
			'limit' => 1,
			'offset' => 1,
		])->current();

		return $current ? $current->getAssessmentAvg() : 0;
	}

	protected function getAssessmentList(int $userId, int $newAssessment): array
	{
		$items = AiQualityAssessmentController::getInstance()->getList([
			'select' => ['ASSESSMENT'],
			'filter' => [
				'=RATED_USER_ID' => $userId,
				'=USE_IN_RATING' => true,
				'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => self::ASSESSMENT_ITEMS_LIMIT,
		])->collectValues();

		$assessmentList = array_column($items, 'ASSESSMENT');
		$assessmentList[] = $newAssessment;

		return $assessmentList;
	}

	protected function getAssessmentListBySettingsId(int $assessmentSettingsId): array
	{
		return AiQualityAssessmentController::getInstance()->getList([
			'select' => ['ASSESSMENT'],
			'filter' => [
				'=ASSESSMENT_SETTING_ID' => $assessmentSettingsId,
				'=USE_IN_RATING' => true,
				'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => self::ASSESSMENT_ITEMS_LIMIT,
			'cache' => [
				'ttl' => 3600,
			],
		])->getAssessmentList();
	}

	private function calculateFibonacciRating(array $ratings): int
	{
		$fibWeights = $this->generateFibonacciWeights(count($ratings));

		// calculate the weighted average
		$weightedSum = 0;
		$weightSum = 0;
		foreach ($ratings as $index => $rating)
		{
			$weight = $fibWeights[$index];
			$weightedSum += $rating * $weight;
			$weightSum += $weight;
		}

		if ($weightSum === 0)
		{
			return 0;
		}

		return (int)round($weightedSum / $weightSum);
	}

	private function generateFibonacciWeights(int $count): array
	{
		$weights = [1, 1]; // first two Fibonacci numbers
		for ($i = 2; $i < $count; $i++)
		{
			$weights[] = $weights[$i - 1] + $weights[$i - 2];
		}

		return array_reverse(array_slice($weights, 0, $count));
	}
}
