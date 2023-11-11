<?php

namespace Bitrix\Tasks\Integration\IM;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM\Notification\Template;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\TaskObject;

class Notification
{
	private array $templates = [];
	private string $locKey;
	private Message $message;
	private array $params = [];

	public function __construct(
		string $locKey,
		Message $message
	)
	{
		$this->locKey = $locKey;
		$this->message = $message;
	}

	public function addTemplate(Template $template): void
	{
		$this->templates[] = $template;
	}

	/**
	 * @return Template[]
	 */
	public function getTemplates(): array
	{
		return $this->templates;
	}

	public function getSender(): User
	{
		return $this->message->getSender();
	}

	public function getRecepient(): User
	{
		return $this->message->getRecepient();
	}

	public function getTask(): ?TaskObject
	{
		return $this->message->getMetaData()->getTask();
	}

	public function getMessage(): Message
	{
		return $this->message;
	}

	public function setParams(array $params): self
	{
		$this->params = $params;
		return $this;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function getGenderMessage(string $postfix = ''): string
	{
		$message = Loc::getMessage(
			$this->locKey . '_' . $this->getSender()->getGender() . $postfix,
			null,
			$this->getRecepient()->getLang()
		);

		return ((string)$message === '')
			? $this->getNeuturalMessage($this->locKey . $postfix, $this->getRecepient()->getLang())
			: $message;
	}

	private function getNeuturalMessage(string $messageKey, string $lang): string
	{
		$message = Loc::getMessage($messageKey . '_N', null, $lang);
		if((string)$message === '') // no neutral message? fall back to Male gender
		{
			$message = Loc::getMessage($messageKey . '_M', null, $lang);
		}

		return (string)$message;
	}
}