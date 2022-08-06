<?php

namespace Bitrix\Tasks\Scrum\Form;

class ItemInfo
{
	private $color = '';
	private $borderColor = '';
	private $visibilitySubtasks = 'Y';

	public function getInfoData(): array
	{
		return [
			$this->getColorKey() => $this->getColor(),
			$this->getBorderColorKey() => $this->getBorderColor(),
			$this->getVisibilitySubtasksKey() => $this->getVisibilitySubtasks(),
		];
	}

	public function setInfoData(array $infoData): void
	{
		if (
			isset($infoData[$this->getBorderColorKey()])
			&& is_string($infoData[$this->getBorderColorKey()])
		)
		{
			$this->setBorderColor($infoData[$this->getBorderColorKey()]);
		}

		if (
			isset($infoData[$this->getColorKey()])
			&& is_string($infoData[$this->getColorKey()])
		)
		{
			$this->setColor($infoData[$this->getColorKey()]);
		}

		if (
			isset($infoData[$this->getVisibilitySubtasksKey()])
			&& is_string($infoData[$this->getVisibilitySubtasksKey()])
		)
		{
			$this->setVisibilitySubtasks($infoData[$this->getVisibilitySubtasksKey()]);
		}
	}

	public function getColorKey(): string
	{
		return 'color';
	}

	public function getBorderColorKey(): string
	{
		return 'borderColor';
	}

	public function getVisibilitySubtasksKey(): string
	{
		return 'visibilitySubtasks';
	}

	public function getColor(): string
	{
		return $this->color;
	}

	public function setColor(string $color): void
	{
		$this->color = $color;
	}

	public function getBorderColor(): string
	{
		return $this->borderColor;
	}

	public function setBorderColor(string $borderColor): void
	{
		$this->borderColor = $borderColor;
	}

	public function getVisibilitySubtasks(): string
	{
		return $this->visibilitySubtasks;
	}

	public function setVisibilitySubtasks(string $visibility): void
	{
		$availableValues = ['Y', 'N'];
		if (!in_array($visibility, $availableValues))
		{
			$visibility = 'Y';
		}

		$this->visibilitySubtasks = $visibility;
	}

	public function isVisibilitySubtasks(): bool
	{
		return $this->visibilitySubtasks === 'Y';
	}
}