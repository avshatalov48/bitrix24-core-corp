<?php

namespace Bitrix\Tasks\Flow\Grid;

use Bitrix\Main\Grid\Options;
use Bitrix\Tasks\Flow\List;

final class Grid
{
	private static array $instances = [];

	private string $gridId;
	private Options $options;
	private array $columns;

	public static function getInstance(string $gridId): static
	{
		if (!isset(self::$instances[$gridId]))
		{
			self::registerInstance(new self($gridId));
		}

		return self::$instances[$gridId];
	}

	public function __construct(string $gridId)
	{
		$this->gridId = $gridId;
		$this->options = new Options($gridId);
		$this->columns = $this->options->getVisibleColumns();

		self::registerInstance($this);
	}

	public function getPageSize(): int
	{
		$navParams = $this->options->getNavParams(['nPageSize' => 10]);

		return (int) $navParams['nPageSize'];
	}

	public function isColumnVisible(Column\Column $column): bool
	{
		if (in_array($column->getId(), $this->columns, true))
		{
			$isVisible = true;
		}
		else
		{
			$isVisible = $column->isVisible();
		}

		return $isVisible;
	}

	private static function registerInstance(self $instance): void
	{
		self::$instances[$instance->gridId] = $instance;
	}
}
