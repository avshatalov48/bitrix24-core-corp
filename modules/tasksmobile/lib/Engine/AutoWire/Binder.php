<?php

namespace Bitrix\TasksMobile\Engine\AutoWire;

use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\TasksMobile\Dto\FlowRequestFilter;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\Settings;

final class Binder
{
	public static function registerDefaultAutoWirings(): void
	{
		\Bitrix\Main\Engine\AutoWire\Binder::registerGlobalAutoWiredParameter(new ExactParameter(
				TaskRequestFilter::class,
				'searchParams',
				static function ($className, array $searchParams = []) {
					return TaskRequestFilter::make($searchParams);
				}
			)
		);

		\Bitrix\Main\Engine\AutoWire\Binder::registerGlobalAutoWiredParameter(new ExactParameter(
				FlowRequestFilter::class,
				'flowSearchParams',
				static function ($className, array $flowSearchParams = []) {
					return FlowRequestFilter::make($flowSearchParams);
				}
			)
		);

		\Bitrix\Main\Engine\AutoWire\Binder::registerGlobalAutoWiredParameter(new Parameter(
				Settings::class,
				static fn ($className) => Settings::getInstance()
			)
		);
	}
}
