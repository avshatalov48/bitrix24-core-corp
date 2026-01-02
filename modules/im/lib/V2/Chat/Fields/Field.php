<?php

namespace Bitrix\Im\V2\Chat\Fields;

enum Field: string
{
	case TextFieldEnabled = 'textFieldEnabled';
	case BackgroundId = 'backgroundId';

	public static function getOptionalParams(): array
	{
		return [
			self::TextFieldEnabled,
			self::BackgroundId,
		];
	}
}
