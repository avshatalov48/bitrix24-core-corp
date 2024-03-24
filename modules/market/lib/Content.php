<?php

namespace Bitrix\Market;

class Content
{
	public static function showAdditional($info): void
	{
		if (is_array($info) && !empty($info['ADDITIONAL_CONTENT'])) {
			echo $info['ADDITIONAL_CONTENT'];
		}
	}
}