<?php

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;

trait ConfigTrait
{
	public function __construct(
		private readonly AddConfig $config,
	)
	{

	}
}