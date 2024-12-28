<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Activity\Provider\Bizproc\Task;
use Bitrix\Crm\Timeline\Bizproc\Data\Workflow;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

final class CreateCommand
{
	private int $responsibleId;
	private \Bitrix\Crm\Timeline\Bizproc\Data\Task $task;
	private ?Workflow $workflow = null;
	private ?array $bindings = null;

	public function __construct(\Bitrix\Crm\Timeline\Bizproc\Data\Task $task, int $responsibleId)
	{
		$this->responsibleId = max($responsibleId, 0);
		$this->task = $task;
	}

	public function setWorkflow(Workflow $workflow): self
	{
		$this->workflow = $workflow;

		return $this;
	}

	public function setBindings(array $bindings): self
	{
		$this->bindings = $bindings;

		return $this;
	}

	public function execute(): Result
	{
		$result = new Result();
		if (empty($this->bindings))
		{
			$result->addError(new Error('empty bindings'));

			return $result;
		}

		$settings = $this->getActivitySettings();

		$fields = [
			'IS_INCOMING_CHANNEL' => 'N',
			'BINDINGS' => $this->bindings,
			'RESPONSIBLE_ID' => $this->responsibleId,
			'SUBJECT' => $this->task->name,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_ID' => $this->task->id,
		];

		$overdueDate = $this->task->getOverdueDate();
		if ($overdueDate && DateTime::isCorrect($overdueDate))
		{
			$fields['END_TIME'] = (new DateTime($overdueDate))->toString();
		}

		if ($this->workflow)
		{
			$fields['ORIGIN_ID'] = $this->workflow->id;
		}

		$provider = new Task();

		$createResult = $provider->createActivity($provider::getProviderTypeId(), $fields);

		$result->addErrors($createResult->getErrors());
		$result->setData([
			'settings' => $settings,
			'id' => ($createResult->getData()['id'] ?? 0),
		]);

		return $result;
	}

	private function getActivitySettings(): array
	{
		$settings = [
			'TASK_ID' => $this->task->id,
			'TASK_NAME' => $this->task->name,
			'IS_INLINE' => $this->task->isInline(),
			'STATUS' => \Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc\Task::TASK_STATUS_RUNNING,
		];

		$overdueData = $this->task->getOverdueDate();
		if ($overdueData && DateTime::isCorrect($overdueData))
		{
			$settings['OVERDUE_DATE'] = $overdueData;
		}

		$buttons = $this->task->getButtons();
		if ($buttons !== null)
		{
			$settings['BUTTONS'] = $buttons;
		}

		if ($this->workflow)
		{
			$settings['WORKFLOW_ID'] = $this->workflow->id;
			$settings['WORKFLOW_TEMPLATE_NAME'] = $this->workflow->getTemplateName();
		}

		return $settings;
	}
}
