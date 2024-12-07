<?php

namespace Bitrix\Crm\Controller\Autorun\Dto;

use Bitrix\Crm\Dto\Dto;
use Bitrix\Main\Error;

final class Progress extends Dto
{
	public int $lastId = 0;
	public int $processedCount = 0;
	public ?int $totalCount = null;
	private array $errors = [];
	private array $successIds = [];
	private array $errorIds = [];

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function hasErrors(): bool
	{
		return !empty($this->errors);
	}

	public function addError(Error $error): void
	{
		$this->errors[] = $error;
	}

	public function getSuccessIds(): array
	{
		return $this->successIds;
	}

	public function hasSuccessIds(): bool
	{
		return !empty($this->successIds);
	}

	public function addSuccessId(int $id): void
	{
		$this->successIds[] = $id;
	}

	public function getErrorIds(): array
	{
		return $this->errorIds;
	}

	public function hasErrorIds(): bool
	{
		return !empty($this->errorIds);
	}

	public function addErrorId(int $id): void
	{
		$this->errorIds[] = $id;
	}
}
