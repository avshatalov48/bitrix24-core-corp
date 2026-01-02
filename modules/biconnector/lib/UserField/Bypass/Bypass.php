<?php

namespace Bitrix\BIConnector\UserField\Bypass;

interface Bypass
{
	/**
	 * Returns value of UserField without permissions check
	 *
	 * @return string
	 */
	public function getText(array $userField): string;
}
