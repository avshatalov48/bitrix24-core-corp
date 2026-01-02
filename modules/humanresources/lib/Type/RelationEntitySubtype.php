<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

/**
 * Enum for ENTITY_SUBTYPE field of b_hr_structure_node_relation table
 * Unlike ENTITY_TYPE it doesn't represent other table row but rather additional information
 * which can help display relation it properly
 * e.g.: CHAT and CHANNEL both related to b_im_chat but displayed in different fields
 */
enum RelationEntitySubtype: string
{
	case Chat = 'CHAT';
	case Channel = 'CHANNEL';

	use ValuesTrait;

	public static function fromChatType(string $type): ?self
	{
		return match ($type) {
			ImChatType::ImTypeChat->value, ImChatType::ImTypeOpen->value => self::Chat,
			ImChatType::ImTypeChannel->value, ImChatType::ImTypeOpenChannel->value => self::Channel,
			default => null,
		};
	}
}
