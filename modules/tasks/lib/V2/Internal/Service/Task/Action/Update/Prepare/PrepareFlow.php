<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Flow\Control\Task\Exception\FlowTaskException;
use Bitrix\Tasks\Flow\Control\Task\Field\FlowFieldHandler;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;

class PrepareFlow implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if ($this->skipModifyByFlow($fields, $fullTaskData))
		{
			return $fields;
		}

		$flowId = (int)($fields['FLOW_ID'] ?? $fullTaskData['FLOW_ID']);
		$handler = new FlowFieldHandler($flowId, $this->config->getUserId());

		try
		{
			$handler->modify($fields, $fullTaskData);
		}
		catch (FlowTaskException|FlowNotFoundException $e)
		{
			throw new TaskFieldValidateException($e->getMessage());
		}

		return $fields;
	}

	private function skipModifyByFlow(array $fields, array $fullTaskData): bool
	{
		if (!FlowFeature::isFeatureEnabled() || !FlowFeature::isOn())
		{
			return true;
		}

		if (isset($fields['FLOW_ID']) && (int)$fields['FLOW_ID'] === 0)
		{
			return true;
		}

		return empty($fullTaskData['FLOW_ID']) && empty($fields['FLOW_ID']);
	}
}