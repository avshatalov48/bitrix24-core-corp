<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message;

use Bitrix\AI\Chatbot\Enum\MessageType;
use Bitrix\AI\Chatbot\Message\Parameter\DefaultParameter;
use Bitrix\AI\Chatbot\Message\Parameter\Parameter;

class ButtonClickedMessage extends Message
{
	protected MessageType $type = MessageType::ButtonClicked;

	public function __construct(string $content, int $messageId, int $buttonId, array $command)
	{
		parent::__construct($content);
		$this->params->add(new DefaultParameter('messageId', $messageId));
		$this->params->add(new DefaultParameter('buttonId', $buttonId));
		$this->params->add(new DefaultParameter('command', $command));
	}

	public function getCommand(): ?Parameter
	{
		return $this->params->get('command');
	}

}