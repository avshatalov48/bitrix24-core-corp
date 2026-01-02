<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

use Bitrix\Main\SystemException;

class Exception extends SystemException
{
	public const CODE_PERMISSION_DENIED = 403;
	public const CODE_INVALID_ARGUMENT = 422;
	public const CODE_JOURNAL_APPEND = 1001;
	public const CODE_RESOURCE_CREATE = 1006;
	public const CODE_RESOURCE_UPDATE = 1007;
	public const CODE_RESOURCE_REMOVE = 1008;
	public const CODE_RESOURCE_NOT_FOUND = 1009;
	public const CODE_RESOURCE_TYPE_CREATE = 1010;
	public const CODE_RESOURCE_TYPE_UPDATE = 1011;
	public const CODE_RESOURCE_TYPE_REMOVE = 1012;
	public const CODE_RESOURCE_TYPE_NOT_FOUND = 1013;
	public const CODE_RESOURCE_SLOT = 1017;
	public const CODE_BOOKING_CREATE = 1018;
	public const CODE_BOOKING_UPDATE = 1019;
	public const CODE_BOOKING_REMOVE = 1020;
	public const CODE_BOOKING_NOT_FOUND = 1021;
	public const CODE_FAVORITE_CREATE = 1022;
	public const CODE_FAVORITE_REMOVE = 1023;
	public const CODE_BOOKING_CONFIRMATION_FAILED = 1024;
	public const CODE_BOOKING_CLIENT_CREATE = 1025;
	public const CODE_BOOKING_INTERSECTION = 1026;
	public const CODE_COUNTER_UPDATE_FAILED = 1027;
	public const CODE_INVALID_SIGNATURE = 1028;
	public const CODE_NOTE_REMOVE = 1029;
	public const CODE_NOTE_CREATE = 1030;
	public const CODE_ADD_RESOURCE_TO_FAVORITES_LIST = 1031;
	public const CODE_BOOKING_CANCEL_FAILED = 1032;
	public const CODE_BOOKING_OPTION_SET_FAILED = 1033;
	public const CODE_WAIT_LIST_ITEM_CREATE = 1034;
	public const CODE_WAIT_LIST_ITEM_UPDATE = 1035;
	public const CODE_WAIT_LIST_ITEM_REMOVE = 1036;
	public const CODE_BOOKING_FROM_WAIT_LIST_ITEM_CREATE = 1038;
	public const CODE_EXTERNAL_RESOURCE_PROCESSING = 1039;
	public const CODE_WAIT_LIST_ITEM_CREATE_FROM_BOOKING = 1037;
	public const CODE_WAIT_LIST_ITEM_NOT_FOUND = 1040;
	public const CODE_BOOKING_CONFIRMATION_ALREADY_CONFIRMED = 1041;

	public const CODE_YANDEX_SERVICE_NOT_FOUND = 2001;
	public const CODE_YANDEX_RESOURCE_NOT_FOUND = 2002;
	public const CODE_YANDEX_BOOKING_CANCEL_FORBIDDEN = 2003;
	public const CODE_YANDEX_BOOKING_CREATE_FORBIDDEN = 2004;
	public const CODE_YANDEX_SLOT_UNAVAILABLE = 2005;
	public const CODE_YANDEX_BOOKING_NOT_FOUND = 2006;
	public const CODE_YANDEX_INTERNAL_ERROR = 2100;

	private bool $isPublic = false;

	public function setIsPublic(bool $isPublic): self
	{
		$this->isPublic = $isPublic;

		return $this;
	}

	public function isPublic(): bool
	{
		return $this->isPublic;
	}
}
