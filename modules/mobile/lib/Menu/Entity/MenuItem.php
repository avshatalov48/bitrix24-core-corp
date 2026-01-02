<?php

namespace Bitrix\Mobile\Menu\Entity;

class MenuItem extends BaseMenuItem
{
	public function __construct(
		string $id,
		string $title,
		private readonly string $imageName,
		private readonly ?int $sort = 100,
		private readonly ?string $path = null,
		private readonly ?string $tag = null,
		private readonly ?array $params = null,
	)
	{
		parent::__construct($id, $title);
	}

	public function getSort(): ?int
	{
		return $this->sort;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'imageName' => $this->imageName,
			'sort' => $this->sort,
			'path' => $this->path,
			'tag' => $this->tag,
			'params' => $this->params,
		];
	}
}
