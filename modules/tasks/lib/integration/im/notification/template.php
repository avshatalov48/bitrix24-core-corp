<?php

namespace Bitrix\Tasks\Integration\IM\Notification;

class Template
{
	private string $search;
	private string $replace;

	public function __construct(string $search, string $replace)
	{
		$this->search = $search;
		$this->replace = $replace;
	}

	public function getSearch(): string
	{
		return $this->search;
	}

	public function getReplace(): string
	{
		return $this->replace;
	}
}