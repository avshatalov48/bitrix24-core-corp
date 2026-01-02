<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Type;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Tasks\Flow\Migration\Strategy\MigrationStrategyInterface;
use Bitrix\Tasks\Flow\Migration\Strategy\Trait\SaveFlowMembersTrait;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;

class FilterExcluded implements MigrationStrategyInterface
{
	use SaveFlowMembersTrait;

	private FlowMemberFacade $memberFacade;

	public function __construct()
	{
		$this->memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
	}

	public function migrate(int $flowId, ?Role $excludedRole = null, ?string $excludedAccessCode = null): StrategyResult
	{
		$result = new StrategyResult();

		if (null === $excludedRole)
		{
			$result->addError(new Error("Role is not specified."));

			return $result;
		}

		if (!AccessCode::isValid($excludedAccessCode))
		{
			$result->addError(new Error("Invalid access code: {$excludedAccessCode}."));

			return $result;
		}

		$flow = FlowRegistry::getInstance()->get($flowId);
		if (!$flow)
		{
			$result->addError(new Error("Flow {$flowId} not found."));

			return $result;
		}

		$currentMembersByRole = $this->memberFacade->getMemberAccessCodesByRole($flowId, $excludedRole);
		$withoutExcluded = $this->filterExcluded($currentMembersByRole, $excludedAccessCode);

		if (empty($withoutExcluded))
		{
			return $result;
		}

		return $this->saveFlowMembers($flowId, $withoutExcluded, $excludedRole);
	}

	/**
	 * @param string[] $memberList
	 * @return string[]
	 */
	private function filterExcluded(array $memberList, string $excluded): array
	{
		$filter = static function ($member) use ($excluded) {
			$isDepartmentExcluded = str_starts_with($excluded, 'D');

			if (!$isDepartmentExcluded)
			{
				return $member !== $excluded;
			}

			$departmentId = (new AccessCode($excluded))->getEntityId();

			return ($member !== "D{$departmentId}") && ($member !== "DR{$departmentId}");
		};

		return array_filter($memberList, $filter);
	}
}
