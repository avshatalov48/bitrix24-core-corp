<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper\Trait;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\ReplaceCollectedDataCommand;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Integration\AI\Result\CollectorResult;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\Exception\CreateNodeException;
use Bitrix\Tasks\Flow\Integration\AI\Result\Node\NodeFactory;

trait CollectorTrait
{
	abstract private function getFlowId(): int;

	private function summarize(): void
	{
		$result = new CollectorResult();

		$collectedDataProvider = new CollectedDataProvider();
		$collectedData = $collectedDataProvider->get($this->getFlowId());
		$data = $collectedData->getData();

		foreach ($this->result as $path => $currentNode)
		{
			$nodeData = $data[$path] ?? null;
			if (null !== $nodeData)
			{
				try
				{
					$node = NodeFactory::create($path, $nodeData);
				}
				catch (CreateNodeException)
				{
					continue;
				}

				$currentNode->summarize($node);

				unset($data[$path]);
			}

			$result->addNode($currentNode);
		}

		$finalData = array_merge($data, $result->toArraySummarize());

		$replaceCommand =
			(new ReplaceCollectedDataCommand())
				->setFlowId($this->getFlowId())
				->setData($finalData)
		;

		/** @var CollectedDataService $collectedDataService */
		$collectedDataService = ServiceLocator::getInstance()->get('tasks.flow.copilot.collected.data.service');
		$collectedDataService->replace($replaceCommand);
	}
}
