<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main;

class SignDocument extends Controller
{

	/**
	 * Updating deadline of the SignDocument Activity
	 * @param int $activityId
	 * @param string $activityDeadline
	 * @return null|array
	 */
	public function updateActivityDeadlineAction(int $activityId, string $activityDeadline): ?array
	{
		$activity = \CCrmActivity::GetByID($activityId);

		if (empty($activity))
		{
			$this->addError(new Main\Error('Activity not found.'));
			return null;
		}

		if (!Provider\SignDocument::checkUpdatePermission($activity))
		{
			$this->addError(new Main\Error('Access denied'));
			return null;
		}

		$result = \CCrmActivity::Update($activityId, [
			'END_TIME' => DateTime::createFromText($activityDeadline),
		]);

		if (!$result)
		{
			$this->addError(new Main\Error('Can not save Activity'));
			return null;
		}

		return [
			'document' => [
				'activityDeadline' => $activityDeadline,
			]
		];
	}
}