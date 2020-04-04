<?php

namespace Bitrix\Disk\Ui;

final class LazyLoad
{
	/**
	 * Returns base64 stub for lazy load.
	 * @return string
	 */
	public static function getBase64Stub()
	{
		return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIW2N88f7jfwAJWAPJBTw90AAAAABJRU5ErkJggg==";
	}
}