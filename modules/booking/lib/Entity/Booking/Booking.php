<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\EventInterface;
use Bitrix\Booking\Entity\EventTrait;
use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Exception\Booking\ConfirmBookingException;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Feature\BookingConfirmReminder;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Booking\Internals\Service\Time;
use DateTimeImmutable;
use DateTimeZone;

class Booking implements EntityInterface, EventInterface
{
	use EventTrait;

	private int|null $id = null;
	private string|null $name = null;
	private string|null $description = null;
	private bool|null $isConfirmed = null;
	private bool|null $isDeleted = null;
	private int $counter = 0;
	private array $counters = [];
	/** @var NotificationType[] */
	private array $notificationTypes = [];

	private DatePeriod|null $datePeriod = null;

	private ResourceCollection $resourceCollection;
	private ClientCollection $clientCollection;
	private ExternalDataCollection $externalDataCollection;

	private string|null $rrule = null;
	private Booking|null $parent = null;

	private BookingVisitStatus|null $visitStatus = null;

	private int|null $createdBy = null;
	private int|null $createdAt = null;
	private int|null $updatedAt = null;

	private string|null $note = null;

	public function __construct()
	{
		$this->resourceCollection = new ResourceCollection(...[]);
		$this->clientCollection = new ClientCollection(...[]);
		$this->externalDataCollection = new ExternalDataCollection(...[]);
	}

	public function getId(): int|null
	{
		return $this->id;
	}

	public function setId(int|null $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): string|null
	{
		return $this->description;
	}

	public function setDescription(string|null $description): self
	{
		$this->description = $description;

		return $this;
	}

	public function isConfirmed(): bool|null
	{
		return $this->isConfirmed;
	}

	public function setConfirmed(bool $confirmed): self
	{
		$this->isConfirmed = $confirmed;

		return $this;
	}

	public function isDeleted(): bool|null
	{
		return $this->isDeleted;
	}

	public function setDeleted(bool $deleted): self
	{
		$this->isDeleted = $deleted;
		return $this;
	}

	public function getPrimaryClient(): Client|null
	{
		return $this->getClientCollection()->getPrimaryClient();
	}

	public function getPrimaryClientUrl(): string
	{
		$url = Container::getProviderManager()::getProviderByBooking($this)
			?->getClientProvider()
			?->getClientUrl($this->getPrimaryClient())
		;

		return $url ?? '#';
	}

	public function setClientCollection(ClientCollection $clientCollection): Booking
	{
		$this->clientCollection = $clientCollection;

		return $this;
	}

	public function getClientCollection(): ClientCollection
	{
		return $this->clientCollection;
	}

	public function getDatePeriod(): DatePeriod|null
	{
		return $this->datePeriod;
	}

	public function setDatePeriod(DatePeriod|null $datePeriod): self
	{
		$this->datePeriod = $datePeriod;

		return $this;
	}

	public function getRrule(): string|null
	{
		return $this->rrule;
	}

	public function setRrule(string|null $rrule): self
	{
		$this->rrule = $rrule;

		return $this;
	}

	public function getParent(): self|null
	{
		return $this->parent;
	}

	public function setParent(self|null $parent): self
	{
		$this->parent = $parent;

		return $this;
	}

	public function getNote(): string|null
	{
		return $this->note;
	}

	public function setNote(string|null $note): self
	{
		$this->note = $note;

		return $this;
	}

	/**
	 * Returns primary resource from resource collection
	 * Primary resource is used for obtaining notification settings for a certain booking
	 * We are currently expecting that the first item from resource collection is primary
	 *
	 * @return Resource|null
	 */
	public function getPrimaryResource(): Resource|null
	{
		return $this->resourceCollection->getFirstCollectionItem();
	}

	public function getResourceCollection(): ResourceCollection
	{
		return $this->resourceCollection;
	}

	public function setResourceCollection(ResourceCollection $resourceCollection): self
	{
		$this->resourceCollection = $resourceCollection;

		return $this;
	}

	public function getExternalDataCollection(): ExternalDataCollection
	{
		return $this->externalDataCollection;
	}

	public function setExternalDataCollection(ExternalDataCollection $externalDataCollection): self
	{
		$this->externalDataCollection = $externalDataCollection;

		return $this;
	}

	public function getMaxDate(): DateTimeImmutable|null
	{
		if ($this->isEventRecurring())
		{
			return $this->getEventRrule()->getUntil();
		}

		return $this->getEventDatePeriod()?->getDateTo();
	}

	public function getCreatedBy(): int|null
	{
		return $this->createdBy;
	}

	public function setCreatedBy(int|null $createdBy): self
	{
		$this->createdBy = $createdBy;

		return $this;
	}

	public function getCreatedAt(): int|null
	{
		return $this->createdAt;
	}

	public function setCreatedAt(int|null $createdAt): self
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): int|null
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(int|null $updatedAt): self
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getCounter(): int
	{
		return $this->counter;
	}

	public function setCounter(int $value): self
	{
		$this->counter = $value;

		return $this;
	}

	public function getCounters(): array
	{
		return $this->counters;
	}

	public function setCounters(array $counters): self
	{
		$this->counters = $counters;

		return $this;
	}

	public function isAutoConfirmed(): bool
	{
		$startFrom = $this->getDatePeriod()?->getDateFrom();

		if (!$startFrom)
		{
			throw new ConfirmBookingException('DatePeriod is not specified');
		}

		$now = new DateTimeImmutable();
		$startFromWithConfirmedPeriod = $startFrom->sub((new BookingConfirmReminder())->bookingAutoConfirmedPeriod());

		return $now->getTimestamp() > $startFromWithConfirmedPeriod->getTimestamp();
	}

	public function getVisitStatus(): BookingVisitStatus
	{
		return $this->visitStatus ?? BookingVisitStatus::Unknown;
	}

	public function isVisitStatusKnown(): bool
	{
		return $this->getVisitStatus() !== BookingVisitStatus::Unknown;
	}

	public function setVisitStatus(BookingVisitStatus $visitStatus): self
	{
		$this->visitStatus = $visitStatus;

		return $this;
	}

	public function isDelayed(): bool
	{
		$now = time();

		if (
			$this->getDatePeriod()->getDateFrom()->getTimestamp() < ($now - Time::SECONDS_IN_MINUTE * 5)
			&& $this->getDatePeriod()->getDateTo()->getTimestamp() > $now
			&& $this->visitStatus->value !== BookingVisitStatus::Visited->value
		)
		{
			return true;
		}

		return false;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'description' => $this->description,
			'isConfirmed' => $this->isConfirmed,
			'isDeleted' => $this->isDeleted,
			'datePeriod' =>
				$this->datePeriod
					? [
					'from' => [
						'timestamp' => $this->datePeriod->getDateFrom()->getTimestamp(),
						'timezone' => $this->datePeriod->getDateFrom()->getTimezone()->getName(),
					],
					'to' => [
						'timestamp' => $this->datePeriod->getDateTo()->getTimestamp(),
						'timezone' => $this->datePeriod->getDateTo()->getTimezone()->getName(),
					],
				]
					: null
			,
			'resources' => $this->resourceCollection->toArray(),
			'clients' => $this->clientCollection->toArray(),
			'externalData' => $this->externalDataCollection->toArray(),
			'primaryClient' => $this->getPrimaryClient(),
			'rrule' => $this->rrule,
			'parent' => $this->parent?->toArray(),
			'createdBy' => $this->createdBy,
			'createdAt' => $this->createdAt,
			'updatedAt' => $this->updatedAt,
			'counter' => $this->counter,
			'counters' => $this->counters,
			'note' => $this->note,
			'visitStatus' => $this->getVisitStatus()->value,
		];
	}

	public static function mapFromArray(array $props): self
	{
		$result = new Booking();

		if (isset($props['id']))
		{
			$result->setId((int)$props['id']);
		}

		if (isset($props['name']))
		{
			$result->setName((string)$props['name']);
		}

		if (isset($props['description']))
		{
			$result->setDescription((string)$props['description']);
		}

		if (isset($props['isConfirmed']))
		{
			$result->setConfirmed((bool)$props['isConfirmed']);
		}

		if (isset($props['isDeleted']))
		{
			$result->setDeleted((bool)$props['isDeleted']);
		}

		if (
			isset($props['datePeriod']['from']['timestamp'])
			&& !empty($props['datePeriod']['from']['timezone'])
			&& isset($props['datePeriod']['to']['timestamp'])
			&& !empty($props['datePeriod']['to']['timezone'])
		)
		{
			$result->setDatePeriod(
				new DatePeriod(
					(new DateTimeImmutable('@' . (int)$props['datePeriod']['from']['timestamp']))
						->setTimezone(new DateTimeZone((string)$props['datePeriod']['from']['timezone'])),
					(new DateTimeImmutable('@' . (int)$props['datePeriod']['to']['timestamp']))
						->setTimezone(new DateTimeZone((string)$props['datePeriod']['to']['timezone']))
				)
			);
		}

		if (isset($props['resources']))
		{
			$result->setResourceCollection(
				ResourceCollection::mapFromArray((array)$props['resources'])
			);
		}

		if (isset($props['clients']))
		{
			$result->setClientCollection(
				ClientCollection::mapFromArray((array)$props['clients'])
			);
		}

		if (isset($props['externalData']))
		{
			$result->setExternalDataCollection(
				ExternalDataCollection::mapFromArray((array)$props['externalData'])
			);
		}

		if (isset($props['rrule']))
		{
			$result->setRrule((string)$props['rrule']);
		}

		if (isset($props['parent']))
		{
			$result->setParent(self::mapFromArray($props['parent']));
		}

		if (isset($props['createdBy']))
		{
			$result->setCreatedBy($props['createdBy']);
		}

		if (isset($props['createdAt']))
		{
			$result->setCreatedAt($props['createdAt']);
		}

		if (isset($props['updatedAt']))
		{
			$result->setUpdatedAt($props['updatedAt']);
		}

		if (isset($props['note']))
		{
			$result->setNote((string)$props['note']);
		}

		if (isset($props['visitStatus']))
		{
			$result->setVisitStatus(
				BookingVisitStatus::tryFrom(
					(string)$props['visitStatus']
				)
			);
		}

		return $result;
	}

	public function isEventRecurring(): bool
	{
		return $this->getRrule() !== null;
	}

	public function getEventDatePeriod(): DatePeriod
	{
		return $this->getDatePeriod();
	}

	public function getEventRrule(): Rrule|null
	{
		if ($this->getRrule() === null)
		{
			return null;
		}

		return new Rrule(
			$this->getRrule(),
			$this->getDatePeriod()
		);
	}
}
