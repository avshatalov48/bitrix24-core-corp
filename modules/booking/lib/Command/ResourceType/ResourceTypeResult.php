<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\ResourceType;

use Bitrix\Booking\Entity\ResourceType\ResourceType;
use Bitrix\Main\Result;

class ResourceTypeResult extends Result
{
	public function __construct(private ResourceType $resourceType)
	{
		parent::__construct();
	}

	public function getResourceType(): ResourceType
	{
		return $this->resourceType;
	}
}
