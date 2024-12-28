<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\AvatarsStackSteps;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class AvatarsStackSteps extends ContentBlock
{
	protected array $steps = [];
	protected array $styles = [];

	public function getRendererName(): string
	{
		return 'AvatarsStackSteps';
	}

	/**
	 * @param Step[] $steps
	 *
	 * @return $this
	 */
	public function setSteps(array $steps): static
	{
		$this->steps = $steps;

		return $this;
	}

	public function addStep(Step $step): static
	{
		$this->steps[] = $step;

		return $this;
	}

	/**
	 * @return Step[]
	 */
	public function getSteps(): array
	{
		return $this->steps;
	}

	public function setStyles(array $styles): static
	{
		$availableStyles = ['minWidth'];
		foreach ($styles as $key => $value)
		{
			if (in_array($key, $availableStyles, true))
			{
				$this->styles[$key] = $value;
			}
		}

		return $this;
	}

	public function getStyles(): array
	{
		return $this->styles;
	}

	protected function getProperties(): ?array
	{
		return [
			'steps' => $this->getSteps(),
			'styles' => $this->getStyles(),
		];
	}
}
