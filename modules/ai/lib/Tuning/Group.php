<?php

namespace Bitrix\AI\Tuning;

/**
 * Group of setting items
 */
class Group
{
	private Collection $items;
	private ?ItemRelations $itemRelations = null;

	private function __construct(
		private string $code,
		private string $title,
		private ?string $description,
		private int|string|null $helpdesk,
		private ?array $icon,
		private ?int $sort,
	) {}

	public static function create(string $code, array $data): ?self
	{
		if (!is_string($data['title'] ?? null))
		{
			return null;
		}

		return new self(
			$code,
			$data['title'],
			$data['description'] ?? null,
			$data['helpdesk'] ?? null,
			$data['icon'] ?? null,
			isset($data['sort']) ? (int)$data['sort'] : null,
		);
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * @return int|null|string
	 */
	public function getHelpdesk(): int|string|null
	{
		return $this->helpdesk;
	}

	/**
	 * @return ?array
	 */
	public function getIcon(): ?array
	{
		return $this->icon;
	}

	/**
	 * @return int|null
	 */
	public function getSort(): ?int
	{
		return $this->sort;
	}

	/**
	 * @param int $sort - value of sorting, less sort will be higher
	 */
	public function setSort(int $sort): void
	{
		$this->sort = $sort;
	}

	/**
	 * @return Collection
	 */
	public function getItems(): Collection
	{
		if (!isset($this->items))
		{
			$this->items = new Collection();
		}

		return $this->items;
	}

	public function addItem(Item $item): void
	{
		if (!isset($this->items))
		{
			$this->items = new Collection();
		}

		$this->items->set($item->getCode(), $item);
	}

	public function sortItems(): void
	{
		$this->getItems()->sort();
	}

	public function addItemRelations(string $parentCode, array $childCodes): void
	{
		if (!$this->itemRelations)
		{
			$this->itemRelations = new ItemRelations($this->getItems());
		}

		$this->itemRelations->addRelation($parentCode, $childCodes);
	}

	/**
	 * Return scalar data of group and its items
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$group = [
			'code' => $this->code,
			'title' => $this->title,
			'description' => $this->description ?? '',
			'helpdesk' => $this->helpdesk ?? null,
			'icon' => $this->icon ?? '',
			'sort' => $this->sort ?? null,
		];

		$group['items'] = [];
		foreach ($this->getItems() as $item)
		{
			$group['items'][$item->getCode()] = $item->toArray();
		}

		if ($this->itemRelations)
		{
			$group['relations'] = $this->itemRelations->toArray();
		}

		return $group;
	}
}
