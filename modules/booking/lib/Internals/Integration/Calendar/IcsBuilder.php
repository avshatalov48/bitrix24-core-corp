<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Calendar;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Internals\Integration\Crm\MyCompany;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmContext;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmLink;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Calendar\Core\Event\Properties\Remind;
use Bitrix\Calendar\ICal\Basic\Dictionary;
use Bitrix\Calendar\ICal\Basic\RecurrenceRuleProperty;
use Bitrix\Calendar\ICal\Builder\Alarm;
use Bitrix\Calendar\ICal\Builder\Calendar;
use Bitrix\Calendar\ICal\Builder\Event as IcalEvent;
use Bitrix\Calendar\ICal\Builder\StandardObservances;
use Bitrix\Calendar\ICal\Builder\Timezone;
use Bitrix\Calendar\ICal\Helper\ReminderHelper;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;

class IcsBuilder
{
	public const REMINDER_UNIT_MINUTES = 'minutes'; /** @see Remind::UNIT_MINUTES */
	private const FIRST_DEFAULT_REMINDER_BEFORE_MIN = 3 * 60; // 3 hours
	private const SECOND_DEFAULT_REMINDER_BEFORE_MIN = 24 * 60; // 24 hours
	private const DEFAULT_REMINDERS_MIN = [
		self::FIRST_DEFAULT_REMINDER_BEFORE_MIN,
		self::SECOND_DEFAULT_REMINDER_BEFORE_MIN,
	];

	private BookingConfirmLink $bookingConfirmLink;
	private IcsBuilderParams $params;

	public function __construct(BookingConfirmLink|null $bookingConfirmLink = null)
	{
		$this->bookingConfirmLink = $bookingConfirmLink ?? new BookingConfirmLink();
	}

	public function buildFromBooking(Booking $booking): string|null
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		$this->params = new IcsBuilderParams(
			datePeriod: $booking->getDatePeriod(),
			name: $this->getNameForBooking($booking),
			description: $this->getDescriptionForBooking($booking),
			currentDate: $this->getCurrentDate(),
			uid: $this->getUidForBooking(),
			reminders: $this->getReminders(),
			rrule: $this->getRrule($booking),
		);

		return $this->build();
	}

	private function build(): string|null
	{
		$icalEvent = $this->buildIcalEvent();
		$icalCalendar = $this->buildIcalCalendar();
		$icalCalendar->addEvent($icalEvent);

		return $icalCalendar->get();
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('calendar');
	}

	private function buildIcalEvent(): IcalEvent
	{
		$rrule = $this->prepareRrule();
		$excludeDates = $this->prepareExDates();

		// Google Calendar not support both fields, so delete UNTIL if COUNT presented
		// From documentation: You can use either COUNT or UNTIL to specify the end of the event recurrence. Don't use both in the same rule.
		// @see: https://developers.google.com/calendar/api/concepts/events-calendars#recurrence_rule
		if (($rrule['COUNT'] ?? null) && ($rrule['UNTIL'] ?? null))
		{
			unset($rrule['UNTIL']);
		}

		$rrule = new RecurrenceRuleProperty($rrule);

		$priority = Dictionary::PRIORITY_MAP['default'];

		$icalEvent = (new IcalEvent($this->params->uid))
			->setName($this->params->name)
			->setDescription($this->params->description)
			->setStartsAt(DateTime::createFromPhp(\DateTime::createFromImmutable($this->params->datePeriod->getDateFrom())))
			->setEndsAt(DateTime::createFromPhp(\DateTime::createFromImmutable($this->params->datePeriod->getDateTo())))
			->setCreatedAt($this->params->currentDate)
			->setDtStamp($this->params->currentDate)
			->setModified($this->params->currentDate)
			->setWithTimezone(true)
			->setWithTime(true)
			->setTransparent(Dictionary::TRANSPARENT['busy'])
			->setRRule($rrule)
			->setExdates($excludeDates)
			->setStatus(Dictionary::INVITATION_STATUS['confirmed'])
			->setPriority($priority)
		;

		if ($alarms = $this->prepareReminders($this->params->reminders))
		{
			$icalEvent->setAlerts($alarms);
		}

		return $icalEvent;
	}

	private function buildIcalCalendar(): Calendar
	{
		$datePeriod = $this->params->datePeriod;

		return Calendar::createInstance()
			->setMethod('REQUEST')
			->setTimezones(Timezone::createInstance()
				->setTimezoneId($datePeriod->getDateFrom()->getTimezone())
				->setObservance(StandardObservances::createInstance()
					->setOffsetFrom($datePeriod->getDateFrom()->getTimezone())
					->setOffsetTo($datePeriod->getDateTo()->getTimezone())
					->setDTStart()
				)
			);
	}

	/**
	 * @return Alarm[]
	 */
	private function prepareReminders(array $reminderParams): array
	{
		$reminders = array_map(static fn (array $params) => (
			(new Remind())->setTimeBeforeEvent(time: $params['time'], units: $params['units'])
		), $reminderParams);

		return array_filter(
			array_map(
				static function (Remind $r) {
					[$value, $valueType] = ReminderHelper::prepareReminderValue($r, false);
					if (!$value || !$valueType)
					{
						return null;
					}

					return new Alarm(type: $valueType, value: $value);
				},
				$reminders
			)
		);
	}

	private function prepareRrule(): array
	{
		$bookingRrule = $this->params->rrule;
		if (!$bookingRrule)
		{
			return [];
		}

		return array_filter([
			'FREQ' => $bookingRrule->getFrequencyAsText(),
			'COUNT' => $bookingRrule->getCount() ?: null,
			'UNTIL' => $bookingRrule->getUntil() ? $bookingRrule->getUntil()->format('Ymd\THis\Z') : null,
			'INTERVAL' => $bookingRrule->getInterval() ?: null,
			'BYDAY' => $bookingRrule->getByDay() ?: null,
		]);
	}

	private function prepareExDates(): array
	{
		if (!($rrule = $this->params->rrule))
		{
			return [];
		}

		return array_map(static fn (\DateTimeInterface $dateExclusion): DateTime => (
			(DateTime::createFromPhp(\DateTime::createFromInterface($dateExclusion)))
		), $rrule->getExcludedDates());
	}

	private function getCurrentDate(): DateTime
	{
		return new DateTime();
	}

	private function getUidForBooking(): string
	{
		return bin2hex(Random::getBytes(16)); // TODO: where is no unique uid for booking
	}

	private function getNameForBooking(Booking $booking): string
	{
		$primaryResource = $booking->getResourceCollection()->getFirstCollectionItem();
		$resourceType = $primaryResource->getType()?->getName();
		$resourceName = $primaryResource->getName();
		$companyName = MyCompany::getName() ?? '';

		return Loc::getMessage('BOOKING_INTEGRATION_CALENDAR_ICS_NAME_TPL', [
			'#RESOURCE_TYPE#' => $resourceType,
			'#RESOURCE_NAME#' => $resourceName,
			'#COMPANY_NAME#' => $companyName,
		]) ?? '';
	}

	private function getRrule(Booking $booking): Rrule|null
	{
		// TODO: activate and check rrule support, when it will be widely implemented in booking
		return null;

//		return $booking->getRrule()
//			? new Rrule($booking->getRrule(), $booking->getDatePeriod())
//			: null
//		;
	}

	private function getReminders(): array
	{
		return array_map(static fn (int $hourReminder) => [
			'time' => $hourReminder,
			'units' => IcsBuilder::REMINDER_UNIT_MINUTES,
		], self::DEFAULT_REMINDERS_MIN);
	}

	private function getDescriptionForBooking(Booking $booking): string
	{
		$url = $this->bookingConfirmLink->getLink($booking, BookingConfirmContext::Info);
		$description = Loc::getMessage('BOOKING_INTEGRATION_CALENDAR_ICS_DESCRIPTION_TPL', [
			'#URL#' => $url,
		]);

		return $this->prepareDescription($description ?? '');
	}

	private function prepareDescription(string $description = null): ?string
	{
		$description = $this->parseText($description);

		return str_replace("\r\n", "\n", $description);
	}

	private function parseText(string $description): string
	{
		return \CTextParser::clearAllTags($description);
	}
}
