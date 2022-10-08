<?php

namespace Bitrix\Crm\Service\Timeline\Layout;


class Footer extends Base
{
	/**
	 * @var Footer\Button[]
	 */
	protected array $buttons = [];

	/**
	 * @var Footer\IconButton[]
	 */
	protected array $additionalButtons = [];

	/**
	 * @var Menu
	 */
	protected ?Menu $menu = null;

	/**
	 * @return Footer\Button[]
	 */
	public function getButtons(): array
	{
		return $this->buttons;
	}

	/**
	 *  @param $buttons Footer\Button[]
	 */
	public function setButtons(array $buttons): self
	{
		$this->buttons = [];
		foreach ($buttons as $id => $button)
		{
			$this->addButton((string)$id, $button);
		}

		return $this;
	}

	public function addButton(string $id, Footer\Button $button): self
	{
		$this->buttons[$id] = $button;

		return $this;
	}

	public function getButtonById(string $id): ? Footer\Button
	{
		return ($this->buttons[$id] ?? null);
	}

	/**
	 * @return Footer\IconButton[]
	 */
	public function getAdditionalButtons(): array
	{
		return $this->additionalButtons;
	}

	/**
	 *  @param $buttons Footer\IconButton[]
	 */
	public function setAdditionalButtons(array $buttons): self
	{
		$this->additionalButtons = [];
		foreach ($buttons as $id => $button)
		{
			$this->addAdditionalButton((string)$id, $button);
		}

		return $this;
	}

	public function addAdditionalButton(string $id, Footer\IconButton $button): self
	{
		$this->additionalButtons[$id] = $button;

		return $this;
	}

	public function getMenu(): ?Menu
	{
		return $this->menu;
	}

	public function setMenu(?Menu $menu): self
	{
		$this->menu = $menu;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'buttons' => $this->getButtons(),
			'additionalButtons' => $this->getAdditionalButtons(),
			'menu' => $this->getMenu(),
		];
	}
}
