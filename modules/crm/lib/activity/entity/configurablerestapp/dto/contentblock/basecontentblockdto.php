<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\ContentBlock;

use Bitrix\Crm\Dto\Dto;

abstract class BaseContentBlockDto extends Dto
{
	public ?string $scope = null;
}
