<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Exclusion\Agent;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Tasks\Flow\Migration\Exclusion\ExclusionServiceFactory;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Update\AgentInterface;
use CAgent;

class ExclusionFromFlowAgent implements AgentInterface
{
	private const STEP_SIZE = 80;

	private string $accessCode;
	private int $stepsWithErrors = 0;

	private FlowMemberFacade $memberFacade;

	public function __construct(string $accessCode)
	{
		$this->accessCode = $accessCode;
		$this->memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
	}

	public static function bindAgent(string $accessCode): void
	{
		if (!AccessCode::isValid($accessCode))
		{
			return;
		}

		CAgent::AddAgent(self::getAgentName($accessCode), 'tasks', 'N', 5, existError: false);
	}

	public static function getAgentName(string $accessCode): string
	{
		return self::class . '::execute(\'' . $accessCode . '\');';
	}

	public static function execute(): string
	{
		$accessCode = func_get_args()[0] ?? '';

		if (!AccessCode::isValid($accessCode))
		{
			return '';
		}

		$doNeedToContinue = (new self($accessCode))->run();

		if (!$doNeedToContinue)
		{
			return '';
		}

		return self::getAgentName($accessCode);
	}

	public function run(): bool
	{
		$relationList = $this->memberFacade->getRelationsByAccessCode($this->accessCode, self::STEP_SIZE);

		foreach ($relationList as $relation)
		{
			$memberRole = Role::tryFrom($relation->getRole());
			if (!$memberRole)
			{
				continue;
			}

			$service = ExclusionServiceFactory::getByExcludedRole($memberRole);
			if (!$service)
			{
				continue;
			}

			$result = $service->excludeByAccessCode($relation->getFlowId(), $memberRole, $this->accessCode);

			if (!empty($result->getErrors()))
			{
				$this->stepsWithErrors++;

				foreach ($result->getErrors() as $error)
				{
					Logger::handle($error);
				}
			}
		}

		$countRelations = $relationList->count();

		if ($this->stepsWithErrors === $countRelations)
		{
			return false;
		}

		return $countRelations >= self::STEP_SIZE;
	}
}
