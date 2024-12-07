<?php

namespace Bitrix\Tasks\Flow\Grid\Action;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Flow;

abstract class Action implements Arrayable
{
	protected string $id;
	protected string $text;
	protected array $data;
	protected bool $default = false;
	protected string $href = '';
	protected string $onclick = '';
	protected string $className = '';

	public function getId(): string
	{
		return $this->id;
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public abstract function prepareData(Flow $flow, array $params = []): void;

	public function toArray()
	{
		return [
			'id' => $this->id,
			'text' => $this->text,
			'data' => $this->data,
			'default' => $this->default,
			'href' => $this->href,
			'onclick' => $this->onclick,
			'className' => $this->className,
		];
	}
}
