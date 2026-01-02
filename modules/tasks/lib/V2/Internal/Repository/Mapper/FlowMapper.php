<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\V2\Internal\Entity;

class FlowMapper
{
	public function mapToEntity(FlowEntity $flowEntity): Entity\Flow
	{
		return new Entity\Flow(
			id:         $flowEntity->getId(),
			name:       $flowEntity->getName(),
			efficiency: $flowEntity->getEfficiency(),
			group:      Entity\Group::mapFromId($flowEntity->getGroupId()),
		);
	}
}