<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property;

final class Property
{
	public function __construct(
		readonly private string $code,
		readonly private string $title,
		readonly private string $type,
		readonly private array $params = []
	)
	{

	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code,
			'title' => $this->title,
			'type' => $this->type,
			'params' => $this->params,
		];
	}

	public function isCommon(): bool
	{
		return $this->params['isCommon'] ?? false;
	}
}
