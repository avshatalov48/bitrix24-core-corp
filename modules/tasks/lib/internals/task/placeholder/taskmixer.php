<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Internals\Task\Placeholder\Exception\PlaceholderException;
use Exception;

class TaskMixer
{
	private array $placeholders;
	private array $taskData;
	private string $description;
	private ErrorCollection $errors;

	public function __construct(int $templateId, array $taskData)
	{
		$this->errors = new ErrorCollection();
		$this->taskData = $taskData;
		$this->setDescription($templateId);
	}

	public function addPlaceholder(string $key, $value): self
	{
		$this->placeholders[$key] = $value;
		return $this;
	}

	public function getReplacedData(): array
	{
		if (
			empty($this->description)
			|| empty($this->placeholders)
		)
		{
			return $this->taskData;
		}

		$placeholderValues = [];
		foreach ($this->placeholders as $placeholderName => $placeholderValue)
		{
			try
			{
				$placeholder = PlaceholderFactory::create($placeholderName, $placeholderValue);
				$placeholderValues[$placeholderName] = $placeholder->toString();
			}
			catch (PlaceholderException $exception)
			{
				$this->errors->setError(new Error($exception->getMessage(), $placeholderName));
				continue;
			}
		}

		$this->description = str_replace(
			array_keys($placeholderValues),
			array_values($placeholderValues),
			$this->description
		);

		$this->taskData['DESCRIPTION'] = $this->description;

		return $this->taskData;
	}

	public function getErrors(): ErrorCollection
	{
		return $this->errors;
	}

	public function isSuccess(): bool
	{
		return $this->errors->isEmpty();
	}

	private function setDescription(int $templateId): void
	{
		/** @var TemplateModel $template */
		$template = TemplateModel::createFromId($templateId);
		try
		{
			$this->description = $template->getDescription();
		}
		catch (Exception $exception)
		{
			$this->description = '';
			$this->errors->setError(new Error($exception->getMessage(), "Template ID: {$templateId}"));
		}
	}
}