<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Control\Parameter;

class AddParameters
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		$parameter = new Parameter($this->config->getUserId(), $fields['ID']);
		$parameter->add($fields);
	}
}