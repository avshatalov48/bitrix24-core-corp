<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment\Controller;

use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentItem;
use Bitrix\Crm\Copilot\AiQualityAssessment\Entity\AiQualityAssessmentTable;
use Bitrix\Crm\Copilot\AiQualityAssessment\RatingCalculator;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;

final class AiQualityAssessmentController
{
	use Singleton;

	public function add(AiQualityAssessmentItem $item): AddResult
	{
		return AiQualityAssessmentTable::add($this->getFields($item));
	}

	public function update(int $id, AiQualityAssessmentItem $item): UpdateResult
	{
		return AiQualityAssessmentTable::update($id, $this->getFields($item));
	}

	public function getList(array $params = []): Collection
	{
		$select = $params['select'] ?? ['*'];
		$filter = $params['filter'] ?? null;
		$order = $params['order'] ?? [
			'ID' => 'DESC',
		];

		$offset = $params['offset'] ?? 0;
		$limit = $params['limit'] ?? 10;

		$cacheTtl = $params['cache']['ttl'] ?? 0;

		$query = AiQualityAssessmentTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOrder($order)
			->setOffset($offset)
			->setLimit($limit)
			->setCacheTtl($cacheTtl)
		;

		return $query->exec()->fetchCollection();
	}

	public function getById(int $id): ?EntityObject
	{
		return AiQualityAssessmentTable::getById($id)?->fetchObject();
	}

	public function getByActivityIdAndJobId(int $activityId, ?int $jobId = null): ?array
	{
		if ($activityId <= 0)
		{
			return null;
		}

		$select = [
			'ID',
			'CREATED_AT',
			'ASSESSMENT_SETTING_ID',
			'JOB_ID',
			'PROMPT',
			'ASSESSMENT',
			'ASSESSMENT_AVG',
			'RATED_USER_ID',
			'USE_IN_RATING',
			'LOW_BORDER' => 'SETTINGS.LOW_BORDER',
			'HIGH_BORDER' => 'SETTINGS.HIGH_BORDER',
			'ASSESSMENT_SETTINGS_STATUS' => 'SETTINGS.STATUS',
			'TITLE' => 'SETTINGS.TITLE',
			'IS_ENABLED' => 'SETTINGS.IS_ENABLED',
			'ACTUAL_PROMPT' => 'SETTINGS.PROMPT',
			'PROMPT_UPDATED_AT' => 'SETTINGS.UPDATED_AT',
		];
		$filter = [
			'=ACTIVITY_TYPE' => AiQualityAssessmentTable::ACTIVITY_TYPE_CALL,
			'=ACTIVITY_ID' => $activityId,
		];

		if (isset($jobId))
		{
			$filter['=JOB_ID'] = $jobId;
		}
		else
		{
			$filter['=USE_IN_RATING'] = true;
		}

		$result = AiQualityAssessmentTable::getList([
			'select' => $select,
			'filter' => $filter,
			'runtime' => [
				new ReferenceField(
					'SETTINGS',
					CopilotCallAssessmentTable::class,
					['=ref.ID' => 'this.ASSESSMENT_SETTING_ID'],
					['join_type' => 'LEFT'],
				)
			],
			'limit' => 1,
		])->fetch();

		return is_array($result) ? $result : null;
	}

	public function getCountByFilter(array $filter = []): ?int
	{
		return (int)(AiQualityAssessmentTable::query()
			->setFilter($filter)
			->queryCountTotal())
		;
	}

	public function getNewAvgAssessmentValue(int $userId, int $assessment): int
	{
		return (new RatingCalculator())->calculateRating($userId, $assessment);
	}

	public function getPrevAvgAssessmentValue(int $userId): int
	{
		return (new RatingCalculator())->getPrevRating($userId);
	}

	private function getFields(AiQualityAssessmentItem $item): array
	{
		return [
			'ACTIVITY_TYPE' => $item->getActivityType(),
			'ACTIVITY_ID' => $item->getActivityId(),
			'ASSESSMENT_SETTING_ID' => $item->getAssessmentSettingId(),
			'JOB_ID' => $item->getJobId(),
			'PROMPT' => $item->getPrompt(),
			'ASSESSMENT' => $item->getAssessment(),
			'ASSESSMENT_AVG' => $item->getAssessmentAvg(),
			'USE_IN_RATING' => $item->isUseInRating(),
			'RATED_USER_ID' => $item->getRatedUserId(),
			'MANAGER_USER_ID' => $item->getManagerUserId(),
			'RATED_USER_CHAT_ID' => $item->getRatedUserChatId(),
			'MANAGER_USER_CHAT_ID' => $item->getManagerUserChatId(),
		];
	}
}
