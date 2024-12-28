<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Calendar\Controller\CalendarEntryAjax;
use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Ui\CalendarFilter;
use Bitrix\Calendar\Ui\CountersManager;
use Bitrix\CalendarMobile\AhaMoments\Factory;
use Bitrix\CalendarMobile\Integration;
use Bitrix\CalendarMobile\Provider\BaseInfoProvider;
use Bitrix\CalendarMobile\Provider\EditFormProvider;
use Bitrix\CalendarMobile\Provider\ViewFormProvider;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Mobile\Dto\InvalidDtoException;

class Event extends Controller
{
	private int $userId;

	protected function init(): void
	{
		parent::init();

		$this->userId = \CCalendar::GetUserId();
	}

	public function configureActions(): array
	{
		return [
			'loadMain' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getList' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getSectionList' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getViewFormConfig' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getEditFormConfig' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getIcsLink' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @param int|null $ownerId
	 * @param string|null $calType
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws InvalidDtoException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function loadMainAction(?int $ownerId = null, ?string $calType = null): array
	{
		if ($ownerId === null || $calType === null)
		{
			$ownerId = $this->userId;
			$calType = Dictionary::CALENDAR_TYPE['user'];
		}

		$baseInfoProvider = new BaseInfoProvider($this->userId, $ownerId, $calType);

		$result = $baseInfoProvider->getBaseInfoConfig();
		if (!$result->isSuccess())
		{
			$this->addError($result->getError());
		}

		return $result->getData();
	}

	/**
	 * @return array[]
	 */
	public function getListAction(): array
	{
		$request = $this->getRequest();
		$yearFrom = (int)$request->getPost('yearFrom');
		$yearTo = (int)$request->getPost('yearTo');
		$monthFrom = (int)$request->getPost('monthFrom');
		$monthTo = (int)$request->getPost('monthTo');
		$limits = \CCalendarEvent::getLimitDates($yearFrom, $monthFrom, $yearTo, $monthTo);

		$sectionIdList = is_array($request->getPost('sectionIdList'))
			? array_map(static fn($section) => (int)$section, $request->getPost('sectionIdList'))
			: []
		;

		$events = [];
		if (!empty($sectionIdList))
		{
			$events = $this->getEvents($sectionIdList, $limits);
		}

		return [
			'events' => $events,
		];
	}

	/**
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getFilteredListAction(): array
	{
		$request = $this->getRequest();
		$search = (string)$request->getPost('search');
		$preset = $request->getPost('preset');
		$ownerId = (int)$request->getPost('ownerId');
		$calType = (string)$request->getPost('calType');

		if (empty($ownerId) || empty($calType))
		{
			$ownerId = $this->userId;
			$calType = Dictionary::CALENDAR_TYPE['user'];
		}

		$presetId = null;
		if (!empty($preset) && is_array($preset))
		{
			$presetId = $preset['id'] ?? null;
		}

		$sectionIds = CalendarFilter::getSectionsForFilter($calType, $presetId, $ownerId, $this->userId);

		$fields = [
			'SECTION_ID' => $sectionIds,
		];

		if (!empty($preset['fields']))
		{
			$fields = array_merge($fields, $preset['fields']);
		}

		$searchFields = [
			'search' => $search,
			'presetId' => $presetId,
			'fields' => $fields,
		];

		if ($calType === Dictionary::CALENDAR_TYPE['group'])
		{
			$searchResult = CalendarFilter::getFilterCompanyData(
				$calType,
				$this->userId,
				$ownerId,
				$searchFields
			);
		}
		else
		{
			$searchResult = CalendarFilter::getFilterUserData(
				$calType,
				$this->userId,
				$ownerId,
				$searchFields
			);
		}

		return [
			'events' => $searchResult['entries'],
		];
	}

	/**
	 * @param int $parentId
	 * @param int $ownerId
	 * @param string $calType
	 *
	 * @return array
	 */
	public function getEventForContextAction(int $parentId, int $ownerId, string $calType): array
	{
		 return \CCalendarEvent::GetList([
			'arFilter' => [
				'PARENT_ID' => $parentId,
				'OWNER_ID' => $ownerId,
				'CAL_TYPE' => $calType,
			],
			'parseRecursion' => false,
			'fetchAttendees' => true,
			'userId' => $this->userId,
			'setDefaultLimit' => false,
		]);
	}

	public function setAhaViewedAction(string $name): void
	{
		Factory::getInstance()->getAhaInstance($name)?->setViewed();
	}

	/**
	 * @param int|null $ownerId
	 * @param string|null $calType
	 *
	 * @return array[]
	 * @throws LoaderException
	 */
	public function getSectionListAction(?int $ownerId = null, ?string $calType = null): array
	{
		if ($ownerId === null || $calType === null)
		{
			$ownerId = $this->userId;
			$calType = Dictionary::CALENDAR_TYPE['user'];
		}

		$baseInfoProvider = new BaseInfoProvider($this->userId, $ownerId, $calType);

		if (!$baseInfoProvider->checkPermissions())
		{
			$this->addError(new Error('Access denied'));

			return [
				'sections' => [],
			];
		}

		return [
			'sections' => $baseInfoProvider->getSectionInfo(),
		];
	}

	/**
	 * @return array
	 */
	public function setMeetingStatusAction(): array
	{
		$request = $this->getRequest();
		$eventId = (int)$request->getPost('eventId');
		$parentId = (int)$request->getPost('parentId');
		$status = $request->getPost('status');
		$currentDateFrom = $request->getPost('currentDateFrom');
		$recurrentMode = $request->getPost('recursionMode');

		\CCalendarEvent::SetMeetingStatusEx([
			'attendeeId' => $this->userId,
			'eventId' => $eventId,
			'parentId' => $parentId,
			'status' => $status,
			'reccurentMode' => $recurrentMode,
			'currentDateFrom' => $currentDateFrom,
		]);

		\CCalendar::UpdateCounter([$this->userId]);

		return [
			'counters' => CountersManager::getValues($this->userId),
		];
	}

	/**
	 * @param int $eventId
	 * @param string $recursionMode
	 * @return array|true[]
	 * @throws \Bitrix\Main\Access\Exception\UnknownActionException
	 */
	public function deleteEventAction(int $eventId, string $recursionMode): array
	{
		$result = \CCalendar::deleteEvent(
			$eventId,
			true,
			[
				'recursionMode' => $recursionMode,
			]
		);

		if ($result !== true)
		{
			$this->addError(new Error('Error while delete event'));
		}

		return [
			'result' => $result,
		];
	}

	/**
	 * @param int $eventId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function getIcsLinkAction(int $eventId): array
	{
		$connectionPath = '/bitrix/services/main/ajax.php';
		$siteId = \CSite::GetDefSite();

		$hash = \CUser::GetHitAuthHash($connectionPath, $this->userId, $siteId);
		if (!$hash)
		{
			$hash = \CUser::AddHitAuthHash($connectionPath, $this->userId, $siteId, 7200);
		}

		if (!$hash)
		{
			$this->addError(new Error('Error while trying to receive link', 404));

			return [];
		}

		$link = UrlManager::getInstance()->createByController(
			new CalendarEntryAjax(),
			'getIcsFileMobile',
			[
				'hitHash' => $hash,
				'eventId' => $eventId,
			],
			true
		);

		return [
			'link' => $link,
		];
	}

	/**
	 * @param int $eventId
	 * @param string $eventDate
	 * @param int $timezoneOffset
	 * @param array $userIds
	 * @param string $requestUsers
	 * @param string $requestCollabs
	 * @param string $getEventById
	 *
	 * @return array
	 * @throws LoaderException
	 * @throws SystemException
	 */
	public function getViewFormConfigAction(
		int $eventId,
		string $eventDate = '',
		int $timezoneOffset = 0,
		array $userIds = [],
		string $requestUsers = 'Y',
		string $requestCollabs = 'N',
		string $getEventById = 'N',
	): array
	{
		$viewFormProvider = new ViewFormProvider(
			$this->userId,
			$eventId,
			$eventDate,
			$timezoneOffset,
			$userIds,
			$requestUsers === 'Y',
			$requestCollabs === 'Y',
			$getEventById === 'Y',
		);

		$result = $viewFormProvider->getViewFormConfig();

		if (!$result->isSuccess())
		{
			$this->addError($result->getError());
		}

		return $result->getData();
	}

	/**
	 * @param int $eventId
	 * @param int $parentId
	 *
	 * @return array
	 * @throws LoaderException
	 * @throws SystemException
	 */
	public function getFilesForViewFormAction(int $eventId, int $parentId): array
	{
		$event = [
			'ID' => $eventId,
			'PARENT_ID' => $parentId,
		];

		$userFields = \CCalendarEvent::GetEventUserFields($event);
		$files = !empty($userFields['UF_WEBDAV_CAL_EVENT']) ? $userFields['UF_WEBDAV_CAL_EVENT']['VALUE'] : [];
		$fileIds = is_array($files) ? array_map(static fn ($fileId) => (int)$fileId, $files) : [];

		return [
			'files' => (new Integration\Disk\Attachment($this->userId))->getAttachments($fileIds),
		];
	}

	/**
	 * @param int $ownerId
	 * @param string $calType
	 * @param array $userIds
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getEditFormConfigAction(int $ownerId, string $calType, array $userIds = []): array
	{
		$editFormProvider = new EditFormProvider($this->userId, $ownerId, $calType, $userIds);
		$result = $editFormProvider->getEditFormConfig();

		if (!$result->isSuccess())
		{
			$this->addError($result->getError());
		}

		return $result->getData();
	}

	/**
	 * @param int $eventId
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getEventChatIdAction(int $eventId): array
	{
		$chatService = new Integration\IM\ChatService($this->userId);
		$result = $chatService->getEventChatId($eventId);

		if (!$result->isSuccess())
		{
			$this->addError($result->getError());
		}

		return $result->getData();
	}

	/**
	 * @param array $sections
	 * @param array $limits
	 * @return array
	 */
	private function getEvents(array $sections, array $limits): array
	{
		return \CCalendarEvent::GetList([
			'arFilter' => [
				'SECTION' => $sections,
				'FROM_LIMIT' => $limits['from'],
				'TO_LIMIT' => $limits['to'],
			],
			'parseRecursion' => false,
			'fetchAttendees' => true,
			'userId' => $this->userId,
			'setDefaultLimit' => false,
		]);
	}
}
