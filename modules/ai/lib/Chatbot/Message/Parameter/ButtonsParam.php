<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message\Params;

class ButtonsParam extends Param
{
	/* @var Button[] $value */
	protected mixed $value = [];

	public function addButton(Button $button): self
	{
		$id = count($this->value) + 1;
		$this->value[$id] = $button;
		$this->value[$id]->setId($id);

		return $this;
	}

	public function getValue(): array
	{
		$buttons = [];
		foreach ($this->value as $button)
		{
			$buttons[] = $button->getAsArray();
		}

		return $buttons;
	}

}