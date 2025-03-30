<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\OptionDictionary;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;

class OptionProvider
{
	private OptionRepositoryInterface $optionRepository;

	public function __construct()
	{
		$this->optionRepository = Container::getOptionRepository();
	}

	public function isBookingEnabled(int $userId): bool
	{
		$enabled = $this->optionRepository->get(
			userId: $userId,
			option: OptionDictionary::BookingEnabled,
			default: 'true'
		);

		return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
	}

	public function isIntersectionForAll(int $userId): bool
	{
		$enabled = $this->optionRepository->get(
			userId: $userId,
			option: OptionDictionary::IntersectionForAll,
			default: 'true'
		);

		return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
	}
}
