<?php

namespace Bitrix\Im\Integration\Bizproc\Message;

class NotifyTemplate extends PlainTemplate
{
	protected const DEFAULT_BORDER_COLOR = '#468EE5';

	protected string $entityTypeName = '';
	protected string $entityName = '';
	protected string $entityLink = '';

	public function buildMessage(array $messageFields): array
	{
		$attach = new \CIMMessageParamAttach(0, static::DEFAULT_BORDER_COLOR);

		$attach->SetDescription($this->buildDescriptionText());

		$attach->AddDelimiter();
		$attach->AddMessage('[b]' . $this->entityTypeName . '[/b][br]');
		$attach->AddLink([
			'NAME' => $this->entityName,
			'LINK' => $this->entityLink,
		]);
		$attach->AddDelimiter();
		$attach->AddMessage($this->buildMessageText());

		$messageFields['ATTACH'] = $attach;

		return $messageFields;
	}

	public function setFields(array $fields): self
	{
		parent::setFields($fields);

		if (is_string($fields['EntityTypeName'] ?? null))
		{
			$this->entityTypeName = trim($fields['EntityTypeName']);
		}
		if (is_string($fields['EntityName'] ?? null))
		{
			$this->entityName = trim($fields['EntityName']);
		}
		if (is_string($fields['EntityLink'] ?? null))
		{
			$this->entityLink = trim($fields['EntityLink']);
		}

		return $this;
	}
}
