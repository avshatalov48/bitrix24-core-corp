<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Integration\Calendar\EventConnectorHelperHandler;
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CalendarEventConnector extends StubConnector
{
	private $canRead = null;
	private string $xmlId = '';

	public function setXmlId(string $xmlId): self
	{
		$this->xmlId = $xmlId;

		return $this;
	}

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}
		$eventConnectorHelper = EventConnectorHelperHandler::getHandler($userId, $this->xmlId);

		$actualEventId = $eventConnectorHelper?->findActualEventId();
		if ($actualEventId > 0)
		{
			$this->entityId = $actualEventId;
		}

		$this->canRead = $eventConnectorHelper?->canView($this->entityId, $userId) ?? false;

		return $this->canRead;
	}

	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

	public function getDataToShow()
	{
		return $this->getDataToShowByUser($this->getUser()->getId());
	}

	public function getDataToShowByUser(int $userId)
	{
		$event = \CCalendarEvent::getById($this->entityId);
		if(empty($event))
		{
			return array();
		}
		$members = array();
		if ($event['IS_MEETING'])
		{
			if(is_array($event['ATTENDEE_LIST']))
			{
				$userIndex = \CCalendarEvent::getUserIndex();
				foreach($event['ATTENDEE_LIST'] as $attendee)
				{
					if (isset($userIndex[$attendee["id"]]))
					{
						$members[] = array(
							"NAME" => $userIndex[$attendee["id"]]['DISPLAY_NAME'],
							"LINK" => $userIndex[$attendee["id"]]['URL'],
							'AVATAR_SRC' => $userIndex[$attendee["id"]]['AVATAR'],
							"IS_EXTRANET" => "N",
						);
					}
				}
			}
			if(is_array($event['~ATTENDEES']))
			{
				foreach($event['~ATTENDEES'] as $user)
				{
					$members[] = array(
						"NAME" => $user['DISPLAY_NAME'],
						"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $user['USER_ID'])),
						'AVATAR_SRC' => $user['AVATAR'],
						"IS_EXTRANET" => "N",
					);
				}
			}
		}
		else
		{
			$userRow = \CUser::getList(
				'ID',
				'ASC',
				array("ID_EQUAL_EXACT" => $event['CREATED_BY'], "ACTIVE" => "Y"),
				array("SELECT" => array(
					'ID', 'NAME', 'LAST_NAME', 'LOGIN', 'PERSONAL_PHOTO',
				))
			)->fetch();

			if($userRow)
			{
				$name = trim($userRow['NAME'].' '.$userRow['LAST_NAME']);
				if ($name == '')
				{
					$name = trim($userRow['LOGIN']);
				}
				$members[] = array(
					"NAME" => $name,
					"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $event['CREATED_BY'])),
					'AVATAR_SRC' => Ui\Avatar::getPerson($userRow['PERSONAL_PHOTO']),
					"IS_EXTRANET" => "N",
				);
			}
		}

		return array(
			'TITLE' => Loc::getMessage('DISK_UF_CAL_EVENT_CONNECTOR_TITLE').": ".$event['NAME'],
			'DETAIL_URL' => \CHTTP::urlAddParams(\CCalendar::GetPath($event['CAL_TYPE'], $event['OWNER_ID'], true), array('EVENT_ID' => $event['ID'])),
			'DESCRIPTION' => Ui\Text::killTags($event['DESCRIPTION']),
			'MEMBERS' => $members
		);
	}
}
