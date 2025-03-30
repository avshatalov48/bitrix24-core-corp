<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\Enum\AhaMoment;
use Bitrix\Booking\Internals\Service\OptionDictionary;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;

class AhaMomentProvider
{
	private OptionRepositoryInterface $optionRepository;

	public function __construct()
	{
		$this->optionRepository = Container::getOptionRepository();
	}

	public function get(int $userId): array
	{
		$ahaMoments = [];
		foreach (AhaMoment::cases() as $ahaMoment)
		{
			$ahaMoments[$ahaMoment->value] = !$this->isShown($userId, $ahaMoment);
		}

		return $ahaMoments;
	}

	private function isShown(int $userId, AhaMoment $ahaMoment): bool
	{
		$value = $this->optionRepository->get(
			userId: $userId,
			option: $this->getOptionName($ahaMoment),
			default: 'false',
		);

		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	private function getOptionName(AhaMoment $ahaMoment): OptionDictionary
	{
		return match ($ahaMoment)
		{
			AhaMoment::Banner => OptionDictionary::AhaBanner,
			AhaMoment::TrialBanner => OptionDictionary::AhaTrialBanner,
			AhaMoment::AddResource => OptionDictionary::AhaAddResource,
			AhaMoment::MessageTemplate => OptionDictionary::AhaMessageTemplate,
			AhaMoment::AddClient => OptionDictionary::AhaAddClient,
			AhaMoment::ResourceWorkload => OptionDictionary::AhaResourceWorkload,
			AhaMoment::ResourceIntersection => OptionDictionary::AhaResourceIntersection,
			AhaMoment::ExpandGrid => OptionDictionary::AhaExpandGrid,
			AhaMoment::SelectResources => OptionDictionary::AhaSelectResources,
		};
	}
}
