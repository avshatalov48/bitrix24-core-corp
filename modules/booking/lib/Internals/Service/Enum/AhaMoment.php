<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Enum;

enum AhaMoment: string
{
	case Banner = 'banner';
	case TrialBanner = 'trial_banner';
	case AddResource = 'add_resource';
	case MessageTemplate = 'message_template';
	case AddClient = 'add_client';
	case ResourceWorkload = 'resource_workload';
	case ResourceIntersection = 'resource_intersection';
	case ExpandGrid = 'expand_grid';
	case SelectResources = 'select_resources';
}
