<?php

namespace Bitrix\StaffTrack\Shift;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Helper\DateHelper;
use Bitrix\StaffTrack\Internals\AbstractDto;
use Bitrix\StaffTrack\Internals\Attribute\Enum;
use Bitrix\StaffTrack\Internals\Attribute\Min;
use Bitrix\StaffTrack\Internals\Attribute\NotEmpty;
use Bitrix\StaffTrack\Internals\Attribute\NotNull;
use Bitrix\StaffTrack\Internals\Attribute\Nullable;
use Bitrix\StaffTrack\Internals\Attribute\Primary;
use Bitrix\StaffTrack\Internals\Attribute\Skip;
use ReflectionProperty;

/**
 * @method self setId(int $id)
 * @method self setUserId(int $userId)
 * @method self setShiftDate(Date $shiftDate)
 * @method self setDateCreate(DateTime $dateCreate)
 * @method self setTimezoneOffset(int $timezoneOffset)
 * @method self setStatus(int $status)
 * @method self setLocation(string $location)
 * @method self setDialogId(int $dialogId)
 * @method self setMessage(string $message)
 * @method self setGeoImageUrl(string $geoImageUrl)
 * @method self setAddress(string $address)
 * @method self setCancelReason(string $cancelReason)
 * @method self setDateCancel(DateTime $dateCancel)
 * @method self setSkipTm(bool $skipTm)
 * @method self setSkipOptions(bool $skipOptions)
 * @method self setSkipCounter(bool $skipCounter)
 */
final class ShiftDto extends AbstractDto
{
	#[Primary]
	#[Min(1)]
	public int $id = 0;

	#[NotEmpty]
	#[Min(1)]
	public int $userId;

	#[NotEmpty]
	public ?Date $shiftDate = null;

	#[NotNull]
	public ?int $timezoneOffset = null;

	#[Enum(Status::class)]
	public ?int $status = null;

	#[Nullable]
	public ?DateTime $dateCreate = null;

	#[Nullable]
	public ?string $location = null;

	#[Nullable]
	public ?string $dialogId = null;

	#[Nullable]
	public ?string $message = null;

	#[Nullable]
	public ?int $imageFileId = null;

	#[Nullable]
	public ?string $geoImageUrl = null;

	#[Nullable]
	public ?string $address = null;

	#[Nullable]
	public ?string $cancelReason = null;

	#[Nullable]
	public ?DateTime $dateCancel = null;

	#[Skip]
	public bool $skipTm = false;

	#[Skip]
	public bool $skipOptions = false;

	#[Skip]
	public bool $skipCounter = false;

	protected function setValue(ReflectionProperty $property, mixed $value): void
	{
		if (is_string($value) && $property->getName() === 'shiftDate')
		{
			$this->shiftDate = DateHelper::getInstance()->getServerDate($value);
		}
		else if (is_string($value) && $property->getName() === 'skipTm')
		{
			$this->skipTm = $value === 'true';
		}
		else
		{
			parent::setValue($property, $value);
		}
	}
}
