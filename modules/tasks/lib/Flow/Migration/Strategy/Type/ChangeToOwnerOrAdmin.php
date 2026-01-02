<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Type;

use Bitrix\Main\Error;
use Bitrix\Tasks\Flow\Control\Trait\ActiveUserOrAdminTrait;
use Bitrix\Tasks\Flow\Migration\Strategy\MigrationStrategyInterface;
use Bitrix\Tasks\Flow\Migration\Strategy\Trait\SaveFlowMembersTrait;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;

class ChangeToOwnerOrAdmin implements MigrationStrategyInterface
{
	use ActiveUserOrAdminTrait;
	use SaveFlowMembersTrait;

	public function migrate(int $flowId, ?Role $excludedRole = null, ?string $excludedAccessCode = null): StrategyResult
	{
		$result = new StrategyResult();

		if (null === $excludedRole)
		{
			$result->addError(new Error("Role is not specified."));

			return $result;
		}

		$flow = FlowRegistry::getInstance()->get($flowId);
		if (!$flow)
		{
			$result->addError(new Error("Flow {$flowId} not found."));

			return $result;
		}

		$replacementMemberId = $this->getActiveUserOrAdminId($flow->getOwnerId());

		return $this->saveFlowMembers($flowId, ["U{$replacementMemberId}"], $excludedRole);
	}
}
