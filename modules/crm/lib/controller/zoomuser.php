<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\Integration\Zoom\Conference;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Crm\Integration\Zoom\Activity;

class ZoomUser extends Controller
{
	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		Loader::includeModule('crm');

		return parent::processBeforeAction($action);
	}

	public function configureActions(): array
	{
		return [
			'registerJoinMeeting' => [
				'-prefilters' => [
					Csrf::class,
					Authentication::class
				],
			]
		];
	}

	public function registerJoinMeetingAction(int $conferenceId)
	{
		if (!Loader::includeModule('socialservices'))
		{
			return $this->addError(new Error('Socialservices module is not installed'));
		}
		$conferenceInfo = \Bitrix\SocialServices\Integration\Zoom\Conference::getInfo($conferenceId);
		if (!$conferenceInfo->isSuccess())
		{
			$this->addErrors($conferenceInfo->getErrors());
			return null;
		}

		$confData = $conferenceInfo->getData();
		if (!\CCrmSecurityHelper::IsAuthorized())
		{
			Conference::updateJoinedStatus($conferenceId);
		}

		return new Response\Redirect($confData['CONFERENCE_URL'], true);
	}

	public function createConferenceAction($conferenceParams, $entityId, $entityType)
	{
		$createResult = Conference::createZoom(\CCrmSecurityHelper::GetCurrentUserID(), $conferenceParams);
		if (!$createResult->isSuccess())
		{
			$this->addErrors($createResult->getErrors());
			return null;
		}
		$conferenceData = $createResult->getData();

		$activity = new Activity($entityId, $entityType);
		$resultAddActivity = $activity->addZoom([
			'id' => $conferenceData['id'],
			'bitrix_internal_id' => $conferenceData['bitrix_internal_id'],
			'start_time' => $conferenceData['start_time'],
			'duration' => $conferenceData['duration'],
		]);
		if (!$resultAddActivity->isSuccess())
		{
			$this->addErrors($resultAddActivity->getErrors());
			return null;
		}
		$activityResult = $resultAddActivity->getData();
		$bindResult = \Bitrix\SocialServices\Integration\Zoom\Conference::bindActivity(
			['id' => $conferenceData['id']],
			$activityResult['ACTIVITY_ID']
		);
		if (!$bindResult->isSuccess())
		{
			$this->addErrors($bindResult->getErrors());
			return null;
		}

		return [];
	}
}