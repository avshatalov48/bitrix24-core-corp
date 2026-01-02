<?php

namespace Bitrix\Baas\Service;

class LocalService
{
	protected function __construct()
	{
		\Bitrix\Baas\Baas::getInstance()->sync(false);
	}
}
