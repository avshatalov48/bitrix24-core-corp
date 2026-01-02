<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Main\SystemException;

// feature handler for custom configurable user jobs
class ConfigurableHandler extends BaseHandler
{
	public function __construct(private readonly ?Context $context = null)
	{

	}

	public static function getType(): HandlerType
	{
		return HandlerType::ConfigurableHandler;
	}

	public function execute(): Result
	{
		throw new SystemException('Not implemented');
	}
}
