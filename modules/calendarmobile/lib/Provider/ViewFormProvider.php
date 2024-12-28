<?php

namespace Bitrix\CalendarMobile\Provider;

use Bitrix\CalendarMobile\Integration\Disk\Attachment;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Result;
use Bitrix\Mobile\Provider\UserRepository;

final class ViewFormProvider
{
	private Result $result;
	private ?BaseInfoProvider $baseInfoProvider = null;

	public function __construct(
		private readonly int $userId,
		private readonly int $eventId,
		private readonly string $eventDate,
		private readonly int $timezoneOffset,
		private readonly array $userIdsToRequest,
		private readonly bool $requestUsers,
		private readonly bool $requestCollabs,
		private readonly bool $getEventById,
	)
	{
		$this->result = new Result();
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getViewFormConfig(): Result
	{
		if (!$this->checkToolAvailability())
		{
			return $this->result;
		}

		$eventArray = $this->getEvent();
		if (empty($eventArray))
		{
			return $this->result;
		}

		$sections = $this->getSections($eventArray);
		if (empty($sections))
		{
			return $this->result;
		}

		$config = [
			'event' => $eventArray,
			'sections' => $this->getSections($eventArray),
			'permissions' => \CCalendarEvent::getEventPermissions($eventArray),
			'files' => $this->getFiles($eventArray),
			'users' => $this->getUsers($eventArray),
			'settings' => $this->getSettings($eventArray),
		];

		if (!empty($eventArray['COLLAB_ID']) && $this->requestCollabs)
		{
			$config['collabs'] = array_values($this->getCollabs($eventArray));
		}

		if (!$this->getEventById)
		{
			$config = [
				...$config,
				'locations' => $this->getBaseInfoProvider($eventArray)->getLocationInfo(),
				'categories' => $this->getBaseInfoProvider($eventArray)->getCategoriesInfo(),
			];
		}

		$this->result->setData($config);

		return $this->result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function checkToolAvailability(): bool
	{
		if (
			Loader::includeModule('intranet')
			&& !ToolsManager::getInstance()->checkAvailabilityByToolId('calendar')
		)
		{
			$this->result->addError(new Error('Tool not available'));

			return false;
		}

		return true;
	}

	/**
	 * @return array|null
	 */
	private function getEvent(): ?array
	{
		if (empty($this->eventId))
		{
			$this->result->addError(new Error('Event id not specified'));

			return null;
		}

		if ($this->getEventById)
		{
			$eventArray = \CCalendarEvent::GetById($this->eventId);
		}
		else
		{
			$eventArray = \CCalendarEvent::getEventForViewInterface($this->eventId, [
				'eventDate' => $this->eventDate,
				'timezoneOffset' => $this->timezoneOffset,
				'userId' => $this->userId,
			]);
		}

		if (!$eventArray || !$eventArray['ID'])
		{
			$this->result->addError(new Error('Event not found'));

			return null;
		}

		return $eventArray;
	}

	private function getSections(array $eventArray): array
	{
		$sections = \CCalendarSect::GetList([
			'arFilter' => [
				'ID' => $eventArray['SECTION_ID'],
				'ACTIVE' => 'Y',
			],
			'checkPermissions' => false,
			'getPermissions' => true,
		]);

		if (empty($sections))
		{
			$this->result->addError(new Error('Section not found'));
		}

		return $sections;
	}

	/**
	 * @param $eventArray
	 *
	 * @return array
	 * @throws LoaderException
	 */
	private function getCollabs($eventArray): array
	{
		return $this->getBaseInfoProvider($eventArray)->getCollabs();
	}

	/**
	 * @param $eventArray
	 *
	 * @return array
	 */
	private function getUsers($eventArray): array
	{
		$users = [];

		if ($this->requestUsers && !empty($this->userIdsToRequest))
		{
			$users = UserRepository::getByIds($this->userIdsToRequest);
		}
		else if ($this->requestUsers && !empty($eventArray['ATTENDEE_LIST']))
		{
			$userIds = array_map(static function (array $attendee) {
				return (int)$attendee['id'];
			}, $eventArray['ATTENDEE_LIST']);

			$users = UserRepository::getByIds($userIds);
		}
		else if ($this->requestUsers && !empty($eventArray['MEETING_HOST']))
		{
			$users = UserRepository::getByIds([(int)$eventArray['MEETING_HOST']]);
		}

		return $users;
	}

	/**
	 * @param array $eventArray
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getFiles(array $eventArray): array
	{
		$userFields = \CCalendarEvent::GetEventUserFields($eventArray);
		$files = !empty($userFields['UF_WEBDAV_CAL_EVENT']) ? $userFields['UF_WEBDAV_CAL_EVENT']['VALUE'] : [];
		$fileIds = is_array($files) ? array_map(static fn ($fileId) => (int)$fileId, $files) : [];

		return (new Attachment($this->userId))->getAttachments($fileIds);
	}

	private function getSettings(array $eventArray): array
	{
		$result = [];

		if (!$this->getEventById)
		{
			$baseInfoProvider = $this->getBaseInfoProvider($eventArray);

			$result = [
				...$baseInfoProvider->getBaseSettings(),
				...$baseInfoProvider->getCalendarSettings(),
			];
		}

		return $result;
	}

	private function getBaseInfoProvider(array $eventArray): BaseInfoProvider
	{
		if ($this->baseInfoProvider === null)
		{
			$this->baseInfoProvider = new BaseInfoProvider($this->userId, $eventArray['OWNER_ID'], $eventArray['CAL_TYPE']);
		}

		return $this->baseInfoProvider;
	}
}
