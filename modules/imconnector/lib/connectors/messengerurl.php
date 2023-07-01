<?php

namespace Bitrix\Imconnector\Connectors;

interface MessengerUrl
{
	/**
	 * Generate url to redirect into messenger app.
	 *
	 * @param int $lineId
	 * @param array|string|null $additional
	 * @return array{web: string, mob: string}
	 */
	public function getMessengerUrl(int $lineId, $additional = null): array;
}