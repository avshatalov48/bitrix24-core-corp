<?php

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config\DeleteConfig;

trait ConfigTrait
{
	public function __construct(
		private readonly DeleteConfig $config
	)
	{

	}
}