<?php

namespace Bitrix\Mobile\Tab;

use Bitrix\Mobile\Context;

interface FactoryTabable
{
	public function getTabsList(): array;
	public function setContext(Context $context);
}