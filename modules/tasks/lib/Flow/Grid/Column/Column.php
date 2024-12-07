<?php

namespace Bitrix\Tasks\Flow\Grid\Column;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Flow;

abstract class Column implements Arrayable
{
	protected string $id;
	protected string $name;
	protected string $sort;
	protected bool $default;
	protected bool $editable;
	protected bool $resizeable;
	protected ?string $align;
	protected ?string $class;
	protected ?int $width;

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'sort' => $this->sort,
			'default' => $this->default,
			'editable' => $this->editable,
			'resizeable' => $this->resizeable,
			'width' => $this->width,
			'align' => $this->align ?? null,
			'class' => $this->class ?? null,
		];
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function isVisible(): bool
	{
		return $this->default;
	}

	public function hasCounter(): bool
	{
		return false;
	}

	public function getCounter(Flow $flow, int $userId): array
	{
		return [];
	}

	/**
	 * userId is always in params
	 */
	abstract public function prepareData(Flow $flow, array $params = []): mixed;
}
