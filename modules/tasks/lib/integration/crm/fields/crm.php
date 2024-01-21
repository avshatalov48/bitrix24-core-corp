<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

class Crm
{
	private int $id;
	private int $typeId;
	private string $caption;
	private string $url;
	private string $xmlId;

	public function __construct(int $id, int $typeId, string $caption, string $url, string $xmlId)
	{
		$this->id = $id;
		$this->typeId = $typeId;
		$this->caption = $caption;
		$this->url = $url;
		$this->xmlId = $xmlId;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getTypeId(): int
	{
		return $this->typeId;
	}

	public function getCaption(): string
	{
		return $this->caption;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getXmlId(): string
	{
		return $this->xmlId;
	}
}
