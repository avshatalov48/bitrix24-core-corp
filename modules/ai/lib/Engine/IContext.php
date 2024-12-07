<?php

namespace Bitrix\AI\Engine;

use Bitrix\AI\Context;

interface IContext
{
	/**
	 * Returns array of messages, that represents as Context of current request.
	 * Each item must contain at least one key `content`.
	 *
	 * @return Context\Message[]
	 */
	public function getMessages(): array;
}
