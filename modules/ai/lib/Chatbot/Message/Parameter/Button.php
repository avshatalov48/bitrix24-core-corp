<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message\Parameter;

use Bitrix\Main\Type\Contract\Arrayable;

class Button implements Arrayable
{
	protected int $id;
	protected string $title;
	protected string $text;
	protected bool $selected = false;
	protected string $command;
	protected array $commandData;

	public function __construct(string $title, string $text, string $command, array $commandData)
	{
		$this->title = $title;
		$this->text = $text;
		$this->command = $command;
		$this->commandData = $commandData;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'text' => $this->text,
			'command' => $this->command,
			'commandData' => $this->commandData,
		];
	}
}