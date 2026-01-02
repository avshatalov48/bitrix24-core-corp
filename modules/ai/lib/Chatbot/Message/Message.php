<?php declare (strict_types=1);

namespace Bitrix\AI\Chatbot\Message;

use Bitrix\AI\Chatbot\Enum\MessageType;
use Bitrix\AI\Chatbot\Message\Parameter\Button;
use Bitrix\AI\Chatbot\Message\Parameter\ButtonsParameter;
use Bitrix\AI\Chatbot\Message\Parameter\Parameter;
use Bitrix\AI\Chatbot\Message\Parameter\Parameters;

abstract class Message
{
	protected string $content;
	protected Parameters $params;
	private ButtonsParameter $buttons;

	protected MessageType $type = MessageType::Default;

	public function __construct(string $content)
	{
		$this->params = new Parameters();
		$this->buttons = new ButtonsParameter('buttons', []);
		$this->content = $content;
	}

	public function getType(): MessageType
	{
		return $this->type;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function setContent(string $content): self
	{
		$this->content = $content;

		return $this;
	}


	protected function addParam(Parameter $param): self
	{
		$this->params->add($param);

		return $this;
	}

	public function getParams(): array
	{
		return $this->params->getParametersArray();
	}

	public function setParams(Parameters $params): self
	{
		$this->params = $params;

		return $this;
	}

	public function addButton(string $title, string $text, string $command, array $commandData = []): self
	{
		$button = new Button(
			$title,
			$text,
			$command,
			$commandData
		);

		$this->buttons->addButton($button);
		$this->params->set($this->buttons);

		return $this;
	}

	public function getButtons(): ButtonsParameter
	{
		return $this->buttons;
	}
}