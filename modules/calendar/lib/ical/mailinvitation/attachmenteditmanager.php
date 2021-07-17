<?php


namespace Bitrix\Calendar\ICal\MailInvitation;


use Bitrix\Calendar\ICal\Builder\Calendar;
use Bitrix\Calendar\ICal\Builder\Dictionary;
use Bitrix\Calendar\ICal\Builder\Event;
use Bitrix\Calendar\ICal\Builder\StandardObservances;
use Bitrix\Calendar\ICal\Builder\Timezone;
use Bitrix\Calendar\Util;
use Bitrix\Main\ObjectException;

/**
 * Class AttachmentEditManager
 * @package Bitrix\Calendar\ICal\MailInvitation
 */
class AttachmentEditManager extends AttachmentManager
{
	/**
	 * AttachmentEditManager constructor.
	 * @param array $event
	 */
	public function __construct(array $event)
	{
		parent::__construct($event);
		$this->uid = $event['DAV_XML_ID'];
	}

	/**
	 * @return string
	 * @throws ObjectException
	 */
	public function getContent(): string
	{
		$event = $this->event;
		return Calendar::createInstance()
			->setMethod(mb_strtoupper(SenderRequestInvitation::METHOD))
			->setTimezones(Timezone::createInstance()
				->setTimezoneId(Helper::getTimezoneObject($event['TZ_FROM']))
				->setObservance(StandardObservances::createInstance()
					->setOffsetFrom(Helper::getTimezoneObject($event['TZ_FROM']))
					->setOffsetTo(Helper::getTimezoneObject($event['TZ_TO']))
					->setDTStart()
				)
			)
			->addEvent(Event::createInstance($this->uid)
				->setName($event['NAME'])
				->setAttendees($event['ICAL_ATTENDEES'])
				->setStartsAt(Util::getDateObject($event['DATE_FROM'], $event['SKIP_TIME'], $event['TZ_FROM']))
				->setEndsAt(Util::getDateObject($event['DATE_TO'], $event['SKIP_TIME'], $event['TZ_TO']))
				->setCreatedAt(Util::getDateObject($event['CREATED'], false, $event['TZ_FROM']))
				->setDtStamp(Util::getDateObject($event['CREATED'], false, $event['TZ_FROM']))
				->setModified(Util::getDateObject($event['MODIFIED'], false, $event['TZ_FROM']))
				->setWithTimezone(!$event['SKIP_TIME'])
				->setWithTime(!$event['SKIP_TIME'])
				->setOrganizer($event['ICAL_ORGANIZER'], $this->getOrganizerMailTo())
				->setDescription($this->prepareDescription($event['DESCRIPTION']))
				->setTransparent(Dictionary::TRANSPARENT[$event['ACCESSIBILITY']] ?? Dictionary::TRANSPARENT['busy'])
				->setRRule($this->prepareRecurrenceRule($event['RRULE']))
//				->setExdates($this->prepareExDate($event['EXDATE']))
				->setLocation($event['TEXT_LOCATION'])
				->setSequence((int)$event['VERSION'])
				->setStatus(Dictionary::EVENT_STATUS['confirmed'])
			)
			->get();
	}
}
