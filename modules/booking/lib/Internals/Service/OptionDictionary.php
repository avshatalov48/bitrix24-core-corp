<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

enum OptionDictionary: string
{
	use DictionaryTrait;

	case BookingEnabled = 'booking_enabled';
	case IntersectionForAll = 'IntersectionForAll';

	/** AhaMoments */
	case AhaBanner = 'aha_banner';
	case AhaTrialBanner = 'aha_trial_banner';
	case AhaAddResource = 'aha_add_resource';
	case AhaMessageTemplate = 'aha_message_template';
	case AhaAddClient = 'aha_add_client';
	case AhaResourceWorkload = 'aha_resource_workload';
	case AhaResourceIntersection = 'aha_resource_intersection';
	case AhaExpandGrid = 'aha_expand_grid';
	case AhaSelectResources = 'aha_select_resources';
}
