<?php

namespace Bitrix\Crm\Service\EditorAdapter;

interface SchemeDecorator
{
	public function decorate(array $scheme): array;
}
