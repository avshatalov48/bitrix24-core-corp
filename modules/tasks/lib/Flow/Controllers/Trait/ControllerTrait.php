<?php

namespace Bitrix\Tasks\Flow\Controllers\Trait;

use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Rest\Controllers\Trait\ErrorResponseTrait;

trait ControllerTrait
{
	use ErrorResponseTrait;

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				FlowDto::class,
				'flowData',
				function($className, $flowData): ?FlowDto {
					$dto = FlowDto::createFromArray($flowData);
					$dto->validateIfSet();

					return $dto;
				}
			),
		];
	}
}