<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

enum FlowUserOptionDictionary: string
{
	case FLOW_PINNED_FOR_USER = 'flow_pinned_for_user';

	public const NOTIFIABLE_OPTIONS = [
		self::FLOW_PINNED_FOR_USER,
	];
}