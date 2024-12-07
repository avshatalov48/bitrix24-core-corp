<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Engine\IEngine;

interface IFormatter
{
	/**
	 * Expects text for replacement.
	 *
	 * @param string $text Text.
	 * @param IEngine $engine Engine instance.
	 */
	public function __construct(string $text, IEngine $engine);

	/**
	 * Return formatted (if needed) text.
	 *
	 * @param array $additionalMarkers Optional additional markers.
	 * @return string
	 */
	public function format(array $additionalMarkers = []): string;
}
