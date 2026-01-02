<?php

declare(strict_types=1);

namespace Bitrix\Baas;

/**
 * deprecated
 */
class ServiceManager
{
	public static function getInstance(): Service\ServiceService
	{
		return Baas::getInstance()->getServiceManager();
	}
}
