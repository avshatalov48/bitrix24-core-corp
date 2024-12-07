<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Integration\Intranet\CustomSection\Page;
use Bitrix\Main\Type\Contract\Arrayable;

class CustomSection implements Arrayable
{
	/** @var int|null */
	protected $id;
	/** @var string|null */
	protected $code;
	/** @var string|null */
	protected $title;
	/** @var Page[] */
	protected $pages = [];

	/**
	 * Returns ID of this custom section
	 *
	 * @return int|null
	 */
	public function getId(): ?int
	{
		return $this->id;
	}

	/**
	 * Sets ID of this custom section
	 *
	 * @param int $id
	 *
	 * @return CustomSection
	 */
	public function setId(int $id): CustomSection
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Returns CODE of this custom section
	 *
	 * @return string|null
	 */
	public function getCode(): ?string
	{
		return $this->code;
	}

	/**
	 * Sets CODE of this custom section
	 *
	 * @param string $code
	 *
	 * @return CustomSection
	 */
	public function setCode(string $code): CustomSection
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * Returns TITLE of this custom section
	 *
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return $this->title;
	}

	/**
	 * Sets TITLE of this custom section
	 *
	 * @param string $title
	 *
	 * @return CustomSection
	 */
	public function setTitle(string $title): CustomSection
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * Returns pages of this custom section
	 *
	 * @return Page[]
	 */
	public function getPages(): array
	{
		return $this->pages;
	}

	/**
	 * Returns pages of this custom section
	 *
	 * @param Page[] $pages
	 *
	 * @return CustomSection
	 */
	public function setPages(array $pages): CustomSection
	{
		$this->pages = $pages;

		return $this;
	}

	public function toArray()
	{
		return [
			'ID' => $this->id,
			'CODE' => $this->code,
			'TITLE' => $this->title,
			'PAGES' => array_map(fn(Page $page) => $page->toArray(), $this->pages),
		];
	}
}
