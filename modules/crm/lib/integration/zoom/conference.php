<?php

namespace Bitrix\Crm\Integration\Zoom;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Timeline\ZoomController;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\SocialServices\Integration\Zoom;
use Bitrix\Socialservices\ZoomMeetingTable;
use CAllCrmActivity;
use CCrmActivity;

class Conference
{
	/**
	 * @param int $userId
	 * @param array $data See possible values here https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingcreate
	 *
	 * @return Result
	 */
	public static function createZoom(int $userId, $data): Result
	{
		$result = new Result();

		if (!self::isAvailable())
		{
			return $result->addError(new Error('Socialservices module is not installed'));
		}

		$createResult = Zoom\Conference::create($userId, $data);
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

	public static function updateJoinedStatus(int $conferenceId): Result
	{
		$result = new Result();

		if (!self::isAvailable())
		{
			return $result->addError(new Error('Module socialservices is not installed.'));
		}

		$updateResult = Zoom\Conference::setJoin($conferenceId);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Error('Error while update join status.'));
		}
		$conferenceData = $updateResult->getData();

		$params['SETTINGS'] = ['ZOOM_EVENT_TYPE' => Provider\Zoom::TYPE_ZOOM_CONF_JOINED];
		$params['BINDINGS'] = CAllCrmActivity::GetBindings($conferenceData['ENTITY_ID']);

		ZoomController::getInstance()->onCreate($conferenceData['ENTITY_ID'], $params);

		return $result;
	}

	public static function updateEndStatus(int $conferenceId): Result
	{
		$result = new Result();

		if (!self::isAvailable())
		{
			return $result->addError(new Error('Module socialservices is not installed.'));
		}

		$updateResult = Zoom\Conference::setEnd($conferenceId);
		if ($updateResult->isSuccess())
		{
			$conferenceData = $updateResult->getData();

			CCrmActivity::Complete($conferenceData['ENTITY_ID']);
		}
		else
		{
			$result->addError(new Error('Error while update end status.'));
		}

		return $result;
	}

	public static function getConferenceData(int $conferenceId): array
	{
		if (!self::isAvailable())
		{
			return [];
		}

		$conference = ZoomMeetingTable::getRowById($conferenceId);
		if (is_array($conference))
		{
			return [
				'CONF_START_TIME' => $conference['CONFERENCE_STARTED'],
				'CONF_URL' => $conference['SHORT_LINK'] ?? '',
				'DURATION' => $conference['DURATION'] ?? '',
				'TOPIC' => $conference['TITLE'] ?? '',
			];
		}

		return [];
	}

	private static function isAvailable(): bool
	{
		return Loader::includeModule('socialservices');
	}
}
