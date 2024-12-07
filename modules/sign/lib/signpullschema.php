<?php

namespace Bitrix\Sign;

final class SignPullSchema
{
	public static function OnGetDependentModule(): array
	{
		return [
			'MODULE_ID' => 'sign',
			'USE' => [
				'PUBLIC_SECTION',
			],
		];
	}
}
