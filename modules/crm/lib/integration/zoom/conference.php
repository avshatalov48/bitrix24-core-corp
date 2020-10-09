<?php

namespace Bitrix\Crm\Integration\Zoom;

use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error,
	Bitrix\Main\Loader,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc,
	Bitrix\Socialservices\ZoomMeetingTable,
	Bitrix\Main\Result;

class Conference
{
	public const MEETING_SCHEDULED_TYPE = 2;
	public const ACTIVITY_ENTITY_TYPE = 'activity';

	/**
	 * @param $userId
	 * @param array $data See possible values here https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingcreate
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function createZoom($userId, $data): Result
	{
		$result = new Result();
		if (!Loader::includeModule('socialservices'))
		{
			return $result->addError(new Error('Socialservices module is not installed'));
		}

		$createResult = \Bitrix\SocialServices\Integration\Zoom\Conference::create($userId, $data);
		if (!$createResult->isSuccess())
		{
			return $result->addErrors($createResult->getErrors());
		}

		$conferenceData = $createResult->getData();
		$zoomController = new Sender();
		$registerResult = $zoomController->registerConference($conferenceData);
		if (!$registerResult->isSuccess())
		{
			return $result->addErrors($registerResult->getErrors());
		}

		return $result->setData($conferenceData);
	}

	/**
	 * @param int $conferenceId
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function updateJoinedStatus(int $conferenceId): Result
	{
		$result = new Result();
		if (!Loader::includeModule('socialservices'))
		{
			return $result->addError(new Error('Module socialservices is not installed.'));
		}

		$updateResult = \Bitrix\SocialServices\Integration\Zoom\Conference::setJoin($conferenceId);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Error('Error while update join status.'));
		}
		$conferenceData = $updateResult->getData();

		$params['SETTINGS'] = [
			'ZOOM_EVENT_TYPE' => \Bitrix\Crm\Activity\Provider\Zoom::TYPE_ZOOM_CONF_JOINED
		];
		$params['BINDINGS'] = \CAllCrmActivity::GetBindings($conferenceData['ENTITY_ID']);

		\Bitrix\Crm\Timeline\ZoomController::getInstance()->onCreate(
			$conferenceData['ENTITY_ID'],
			$params
		);

		return $result;
	}

	/**
	 * @param int $conferenceId
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function updateEndStatus(int $conferenceId): Result
	{
		$result = new Result();
		if (!Loader::includeModule('socialservices'))
		{
			return $result->addError(new Error('Module socialservices is not installed.'));
		}

		$updateResult = \Bitrix\SocialServices\Integration\Zoom\Conference::setEnd($conferenceId);
		if ($updateResult->isSuccess())
		{
			$conferenceData = $updateResult->getData();
			\CCrmActivity::Complete($conferenceData['ENTITY_ID'], true);
		}
		else
		{
			$result->addError(new Error('Error while update end status.'));
		}

		return $result;
	}
}