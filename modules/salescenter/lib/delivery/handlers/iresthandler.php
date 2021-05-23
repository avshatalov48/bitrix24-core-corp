<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

/**
 * Interface IResthandler
 * @package Bitrix\SalesCenter\Delivery\Handlers
 * @internal
 */
interface IRestHandler
{
	/**
	 * @return string|null
	 */
	public function getRestHandlerCode(): ?string;
}
