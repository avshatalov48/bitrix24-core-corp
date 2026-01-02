<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message\Parameter;

class ButtonsParameter extends Parameter
{
	/* @var Button[] $value */
	protected mixed $value = [];

	public function addButton(Button $button): self
	{
		$this->value[] = $button;
		$button->setId(array_key_last($this->value));

		return $this;
	}

	public function getValue(): array
	{
		$buttons = [];
		foreach ($this->value as $button)
		{
			$buttons[] = $button->toArray();
		}

		return $buttons;
	}

}