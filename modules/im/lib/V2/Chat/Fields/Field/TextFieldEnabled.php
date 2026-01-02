<?php

namespace Bitrix\Im\V2\Chat\Fields\Field;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\Fields\BaseField;
use Bitrix\Im\V2\Chat\Param\Params;
use Bitrix\Im\V2\Pull\Event\ChatFieldsUpdate;

class TextFieldEnabled extends BaseField
{
	protected Params $params;

	public function __construct(int $chatId)
	{
		parent::__construct($chatId);
		$this->params = Params::getInstance($chatId);
	}

	/**
	 * @return bool
	 */
	public function get(): bool
	{
		return $this->params->get(Params::TEXT_FIELD_ENABLED)?->getValue() ?? true;
	}

	/**
	 * @param bool $value
	 * @return self
	 */
	public function set(mixed $value): self
	{
		$isChanged = false;
		$value = (bool)$value;

		if (!$value && $this->params->get(Params::TEXT_FIELD_ENABLED) === null)
		{
			$this->params->addParamByName(Params::TEXT_FIELD_ENABLED, false);
			$isChanged = true;
		}
		elseif ($value && $this->params->get(Params::TEXT_FIELD_ENABLED) !== null)
		{
			$this->params->deleteParam(Params::TEXT_FIELD_ENABLED);
			$isChanged = true;
		}

		if ($isChanged)
		{
			$this->sendPush();
		}

		return $this;
	}

	protected function sendPush(): void
	{
		$chat = Chat::getInstance($this->chatId);
		$updateField = ['optionalParams' => [$this->getFieldName() => $this->get()]];
		(new ChatFieldsUpdate($chat, $updateField))->send();
	}

	protected function getFieldName(): string
	{
		return 'textFieldEnabled';
	}

	public function toRestFormat(array $option = []): array
	{
		$enableTextField = $this->get();

		if ($enableTextField)
		{
			return [];
		}

		return [$this->getFieldName() => false];
	}
}
