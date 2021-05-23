<?php

namespace Bitrix\Tasks\Scrum\Internal;

class ItemInfoColumn
{
	private $color = '';
	private $borderColor = '';

	public function getInfoData(): array
	{
		return [
			$this->getColorKey() => $this->getColor(),
			$this->getBorderColorKey() => $this->getBorderColor(),
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (isset($infoData[$this->getBorderColorKey()]) && is_string($infoData[$this->getBorderColorKey()]))
		{
			$this->setBorderColor($infoData[$this->getBorderColorKey()]);
		}
		if (isset($infoData[$this->getColorKey()]) && is_string($infoData[$this->getColorKey()]))
		{
			$this->setColor($infoData[$this->getColorKey()]);
		}
	}

	public function getColorKey(): string
	{
		return 'color';
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor(string $color): void
	{
		$this->color = $color;
	}

	public function getBorderColorKey(): string
	{
		return 'borderColor';
	}

	public function getBorderColor(): string
	{
		return $this->borderColor;
	}

	public function setBorderColor(string $borderColor): void
	{
		$this->borderColor = $borderColor;
	}
}