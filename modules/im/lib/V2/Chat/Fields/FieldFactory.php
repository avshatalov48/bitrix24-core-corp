<?php

namespace Bitrix\Im\V2\Chat\Fields;

use Bitrix\Im\V2\Chat\Fields\Field\Background;
use Bitrix\Im\V2\Chat\Fields\Field\TextFieldEnabled;

class FieldFactory
{
	private static ?self $instance = null;

	private function __construct()
	{}

	public static function getInstance(): self
	{
		return self::$instance ?? new self();
	}

	public function getField(Field $field, int $chatId): BaseField
	{
		return match ($field)
		{
			Field::TextFieldEnabled => (new TextFieldEnabled($chatId)),
			Field::BackgroundId => (new Background($chatId))
		};
	}
}
