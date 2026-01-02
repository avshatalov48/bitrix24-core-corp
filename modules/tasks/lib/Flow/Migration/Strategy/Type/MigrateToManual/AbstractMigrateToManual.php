<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Type\MigrateToManual;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Control\Trait\ActiveUserOrAdminTrait;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Migration\Strategy\MigrationStrategyInterface;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;
use Throwable;

abstract class AbstractMigrateToManual implements MigrationStrategyInterface
{
	use ActiveUserOrAdminTrait;

	protected FlowService $flowService;

	public function __construct()
	{
		$this->flowService = ServiceLocator::getInstance()->get('tasks.flow.service');
	}

	abstract protected function notify(int $flowId): void;

	public function migrate(int $flowId, ?Role $excludedRole = null, ?string $excludedAccessCode = null): StrategyResult
	{
		$result = new StrategyResult();

		$flow = FlowRegistry::getInstance()->get($flowId);
		if (!$flow)
		{
			$result->addError(new Error("Flow {$flowId} not found."));

			return $result;
		}

		$newDistributorId = $this->getActiveUserOrAdminId($flow->getOwnerId());

		$command = (new UpdateCommand())
			->setId($flowId)
			->setDistributionType(FlowDistributionType::MANUALLY->value)
			->setResponsibleList(["U{$newDistributorId}"])
		;

		try
		{
			$this->flowService->update($command);
		}
		catch (Throwable $t)
		{
			$result->addError(Error::createFromThrowable($t));
		}

		if (!FlowFeature::isOn())
		{
			$this->notify($flowId);
		}

		return (new StrategyResult())->setFlowChanged();
	}
}
