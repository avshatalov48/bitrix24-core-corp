<?php

namespace Bitrix\Mobile\Menu\Entity;

class MenuSection extends BaseMenuItem
{
	/** @var MenuItem[] */
	private array $children = [];

	public function __construct(
		string $id,
		string $title,
		private readonly ?int $sort = 100,
	)
	{
		parent::__construct($id, $title);
	}

	public function addChild(MenuItem $child): void
	{
		$this->children[] = $child;
	}

	private function isHidden(): bool
	{
		return empty($this->children);
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function toArray(): array
	{
		$this->sortChildren();

		return [
			'id' => $this->id,
			'code' => $this->id,
			'title' => $this->title,
			'sort' => $this->sort,
			'hidden' => $this->isHidden(),
			'items' => array_map(fn($child) => $child->toArray(), $this->children),
		];
	}

	private function sortChildren(): void
	{
		usort($this->children, function (MenuItem $a, MenuItem $b) {
			return ($a->getSort() ?? PHP_INT_MAX) <=> ($b->getSort() ?? PHP_INT_MAX);
		});
	}
}
