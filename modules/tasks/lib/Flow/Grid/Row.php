<?php

namespace Bitrix\Tasks\Flow\Grid;

use Bitrix\Main\Type\Contract\Arrayable;

final class Row implements Arrayable
{
	public function __construct(
		private int $id,
		private array $data,
		private array $columns,
		private bool $editable,
		private array $actions,
		private bool $active,
		private bool $isPinned,
		private array $counters = [],
	)
	{}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'data' => $this->data,
			'columns' => $this->columns,
			'editable' => $this->editable,
			'actions' => $this->actions,
			'active' => $this->active,
			'counters' => $this->counters,
			'isPinned' => $this->isPinned,
		];
	}
}
