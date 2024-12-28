<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\ReplaceCollectedDataCommand;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Registry;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Exception\CreateNodeException;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeFactory;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\AbstractFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Filler\Type\Once\FlowFiller;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\Trait\CollectorTrait;
use Bitrix\Tasks\Internals\Log\Logger;
use Throwable;

class FlowCollector extends Stepper
{
	use CollectorTrait;

	protected static $moduleId = 'tasks';

	private int $flowId;
	private array $option = [];

	private CollectorResult $result;
	private Registry $registry;

	public function execute(array &$option): bool
	{
		$this->init($option);

		try
		{
			$this->fillData();
			$this->summarize();
			$this->finalize();
			$this->fireEvent();
		}
		catch (Throwable $t)
		{
			Logger::logThrowable($t);
		}
		finally
		{
			return static::FINISH_EXECUTION;
		}
	}

	private function fillData(): void
	{
		/**
		 * @var AbstractFiller[] $fillers
		 */
		$fillers = [
			new FlowFiller($this->registry),
		];

		foreach ($fillers as $filler)
		{
			$filler->fill($this->result);
		}
	}

	private function getFlowId(): int
	{
		return $this->flowId;
	}

	private function fireEvent(): void
	{
		$event = new Event('tasks', 'onFlowDataCollected', [
			'flowId' => $this->flowId,
		]);

		$event->send();
	}

	private function finalize(): void
	{
		$result = new CollectorResult();

		$collectedDataProvider = new CollectedDataProvider();
		$collectedData = $collectedDataProvider->get($this->getFlowId());
		$data = $collectedData->getData();

		foreach ($data as $path => $nodeData)
		{
			try
			{
				$node = NodeFactory::create($path, $nodeData);
			}
			catch (CreateNodeException)
			{
				continue;
			}

			$result->addNode($node);
		}

		$replaceCommand =
			(new ReplaceCollectedDataCommand())
				->setFlowId($this->getFlowId())
				->setData($result->toArrayFinalize())
		;

		/** @var CollectedDataService $collectedDataService */
		$collectedDataService = ServiceLocator::getInstance()->get('tasks.flow.copilot.collected.data.service');
		$collectedDataService->replace($replaceCommand);
	}

	private function init(array &$option): void
	{
		$this->result = new CollectorResult();

		$this->flowId = $this->outerParams[0];

		$this->option = &$option;

		$this->registry = Registry::getInstance($this->flowId);
	}
}
