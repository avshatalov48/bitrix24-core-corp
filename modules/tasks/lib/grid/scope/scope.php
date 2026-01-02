<?php

namespace Bitrix\Tasks\Grid\Scope;

use Bitrix\Socialnetwork\Livefeed\Context\Context;
use Bitrix\Tasks\Internals\Task\Base;

abstract class Scope extends Base
{
	/**
	 * @see Context
	 */
	public const COLLAB = 'collab';
}
