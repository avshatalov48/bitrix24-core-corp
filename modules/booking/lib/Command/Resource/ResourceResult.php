<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Resource;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Main\Result;

class ResourceResult extends Result
{
	public function __construct(private Resource $resource)
	{
		parent::__construct();
	}

	public function getResource(): Resource
	{
		return $this->resource;
	}
}
