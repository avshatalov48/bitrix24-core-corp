<?php

namespace Bitrix\AI\Prompt;

use Bitrix\Rest\RestException;

/**
 * Proxy class for REST purpose.
 */
class Rest
{
	/**
	 * Adds or updates Prompt.
	 *
	 * @param array $data Array contains fields: ['category', 'section', 'sort', 'code', 'parent_code', 'icon', 'prompt', 'translate', 'work_with_result'].
	 * @param mixed $service During REST executes.
	 * @param mixed $server During REST executes.
	 * @return int
	 */
	public static function register(array $data, mixed $service = null, mixed $server = null): int
	{
		throw new RestException(
			'To register the prompt, use the web interface.',
			'PROMPT_NOT_REGISTER_BY_REST'
		);
	}

	/**
	 * Removes existing Prompt.
	 *
	 * @param array $data Array contains fields: ['code'].
	 * @param mixed $service During REST executes.
	 * @param mixed $server During REST executes.
	 * @return bool
	 */
	public static function unRegister(array $data, mixed $service = null, mixed $server = null): bool
	{
		throw new RestException(
			'To cancel the prompt registration, use the web interface.',
			'PROMPT_NOT_UNREGISTER_BY_REST'
		);
	}
}
