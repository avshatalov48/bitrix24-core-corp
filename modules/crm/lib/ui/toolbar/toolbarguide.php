<?php

namespace Bitrix\Crm\UI\Toolbar;

final class ToolbarGuide
{
	public function __construct(
		private readonly string $title,
		private readonly string $logoPath,
		private readonly string $manualCode,
	)
	{

	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getNormalizedLogoPath(): string
	{
		return \Bitrix\Main\IO\Path::normalize($this->logoPath);
	}

	public function getManualCode(): string
	{
		return $this->manualCode;
	}
}
