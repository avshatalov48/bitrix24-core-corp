<?php
namespace Bitrix\Crm\Integration\Channel;
class EmailChannelOrigin
{
	const UNDEFINED = '';
	const PERSONAL = 'PERS';
	const COMPANY = 'CO';

	/**
	 * Check if specified sing is defined.
	 * @param string $sing Channel Sing.
	 * @return bool
	 */
	public static function isDefined($sing)
	{
		return ($sing ===  self::PERSONAL || $sing ===  self::COMPANY);
	}
}