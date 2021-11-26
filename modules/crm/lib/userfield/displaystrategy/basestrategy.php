<?php

namespace Bitrix\Crm\UserField\DisplayStrategy;

abstract class BaseStrategy
{
	protected $userFields = [];
	protected $processedValues = [];
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function setUserFields(array $userFields)
	{
		$this->userFields = $userFields;
	}

	protected function getUserFields(): array
	{
		return $this->userFields;
	}

	abstract public function processValues(array $items): array;
}
