<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

final class Task
{
	public readonly int $id;
	public readonly string $name;
	private array $data;
	private ?array $fullData;

	private function __construct(array $task)
	{
		$this->id = $task['ID'];
		$this->name = $task['NAME'];

		$this->data = $task['DATA'];
		$this->fullData = $task['FULL_DATA'];
	}

	private static function bizprocEnabled(): bool
	{
		return Loader::includeModule('bizproc');
	}

	public static function initFromArray(array $task): ?self
	{
		$taskId = (int)($task['TASK_ID'] ?? null);
		if ($taskId <= 0)
		{
			return null;
		}

		$fullData = null;

		$name = (string)($task['NAME'] ?? '');
		if (empty($name))
		{
			$fullData = self::loadFullTaskData($taskId);
			if ($fullData)
			{
				$name = $fullData['NAME'];
			}
		}

		return new self([
			'ID' => $taskId,
			'NAME' => $name,
			'DATA' => $task,
			'FULL_DATA' => $fullData,
		]);
	}

	private static function loadFullTaskData(int $taskId): ?array
	{
		if (self::bizprocEnabled())
		{
			$task = \CBPTaskService::getList(
				[],
				['ID' => $taskId],
				false,
				false,
				['ID', 'NAME', 'IS_INLINE', 'OVERDUE_DATE', 'PARAMETERS', 'ACTIVITY'],
			)->fetch();

			return $task ?: [];
		}

		return null;
	}

	private function initFullData(): void
	{
		if (!isset($this->fullData))
		{
			$this->fullData = self::loadFullTaskData($this->id) ?: [];
		}
	}

	public function isInline(): bool
	{
		if (array_key_exists('IS_INLINE', $this->data))
		{
			return $this->data['IS_INLINE'] === 'Y';
		}

		$this->initFullData();

		return ($this->fullData['IS_INLINE'] ?? 'N') === 'Y';
	}

	/**
	 * returns overdue date as string with the date and time according to the server time
	 * @return string|null
	 */
	public function getOverdueDate(): ?string
	{
		if (array_key_exists('OVERDUE_DATE', $this->data))
		{
			$overdueDate = $this->data['OVERDUE_DATE'];
		}
		else
		{
			$this->initFullData();
			$overdueDate = $this->fullData['OVERDUE_DATE'] ?? null;
		}

		$overdueDate =
			is_string($overdueDate) && DateTime::isCorrect($overdueDate)
				? DateTime::createFromUserTime($overdueDate)
				: $overdueDate
		;

		if ($overdueDate instanceof DateTime)
		{
			$stringOverdueDate = $overdueDate->toString();
			if ($overdueDate->isUserTimeEnabled())
			{
				$overdueDate->disableUserTime();
				$stringOverdueDate = $overdueDate->toString();
				$overdueDate->enableUserTime();
			}

			return $stringOverdueDate;
		}

		return null;
	}

	public function getParameters(): mixed
	{
		if (array_key_exists('PARAMETERS', $this->data))
		{
			return $this->data['PARAMETERS'];
		}

		$this->initFullData();

		return $this->fullData['PARAMETERS'] ?? null;
	}

	public function getUsers(): array
	{
		if (self::bizprocEnabled())
		{
			return \CBPTaskService::getTaskUsers($this->id)[$this->id] ?? [];
		}

		return [];
	}

	public function getAccessControl(): ?bool
	{
		if (!self::bizprocEnabled())
		{
			return null;
		}

		$parameters = $this->getParameters();
		if (is_string($parameters))
		{
			$task = isset($this->fullData) ? array_merge($this->data, $this->fullData) : $this->data;
			$taskRows = new \CBPTaskResult();
			$taskRows->InitFromArray([$task]);
			$task = $taskRows->fetch();
			$parameters = $task['PARAMETERS'];
		}

		return $parameters['AccessControl'] === 'Y' ?? null;
	}

	public function getButtons(): ?array
	{
		if (!self::bizprocEnabled())
		{
			return null;
		}

		if (!$this->isInline())
		{
			return [];
		}

		$parameters = $this->getParameters();
		$task = isset($this->fullData) ? array_merge($this->data, $this->fullData) : $this->data;

		if (is_string($parameters))
		{
			$taskRows = new \CBPTaskResult();
			$taskRows->InitFromArray([$task]);
			$task = $taskRows->fetch();
		}

		if (!$task)
		{
			return null;
		}

		$controls = \CBPDocument::getTaskControls($task);
		if (!is_array($controls['BUTTONS'] ?? null))
		{
			return null;
		}

		$buttons = [];
		foreach ($controls['BUTTONS'] as $button)
		{
			if (!isset($button['TARGET_USER_STATUS'], $button['TEXT'], $button['NAME']))
			{
				continue;
			}

			$buttons[] = [
				'TARGET_USER_STATUS' => $button['TARGET_USER_STATUS'],
				'TEXT' => $button['TEXT'],
				'NAME' => $button['NAME'],
				'VALUE' => $button['VALUE'],
			];
		}

		return $buttons;
	}
}
