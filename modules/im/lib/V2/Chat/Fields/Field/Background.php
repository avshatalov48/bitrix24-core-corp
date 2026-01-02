<?php

namespace Bitrix\Im\V2\Chat\Fields\Field;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Chat\Fields\BaseField;
use Bitrix\Im\V2\Chat\Param\Params;
use Bitrix\Im\V2\Pull\Event\ChatFieldsUpdate;

class Background extends BaseField
{
	public const BACKGROUND_IDS = [
		'marta' => 'martaAI',
		'copilot' => 'copilot',
		'collab' => 'collab',
	];

	protected Params $params;

	public function __construct(int $chatId)
	{
		parent::__construct($chatId);
		$this->params = Params::getInstance($chatId);
	}

	/**
	 * @return string|null
	 */
	public function get(): ?string
	{
		return $this->params->get(Params::BACKGROUND_ID)?->getValue();
	}

	/**
	 * @param string $value
	 * @return self
	 */
	public function set(mixed $value): self
	{
		if ($value === null || $value === '')
		{
			$this->params->deleteParam(Params::BACKGROUND_ID);
			$this->sendPush();
		}

		if (!in_array($value, self::BACKGROUND_IDS, true))
		{
			return $this;
		}

		$this->params->addParamByName(Params::BACKGROUND_ID, $value);
		$this->sendPush();

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
		return 'backgroundId';
	}

	public function toRestFormat(array $option = []): array
	{
		$backgroundId = $this->get();

		if (!isset($backgroundId))
		{
			return [];
		}

		return [$this->getFieldName() => $backgroundId];
	}

	public static function validateBackgroundId(mixed $backgroundId): ?string
	{
		if (!isset($backgroundId) || $backgroundId === '' || !is_string($backgroundId))
		{
			return null;
		}

		if (!in_array($backgroundId, self::BACKGROUND_IDS, true))
		{
			return null;
		}

		return $backgroundId;
	}
}
