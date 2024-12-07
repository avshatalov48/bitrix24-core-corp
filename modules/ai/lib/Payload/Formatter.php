<?php

namespace Bitrix\AI\Payload;

use Bitrix\AI\Engine\IEngine;

class Formatter
{
	/**
	 * Note the order is important. Formatter would apply from top one to the bottom.
	 */
	private const FORMATTERS = [
		Formatter\CaseStatement::class,
		Formatter\IfStatement::class,
		Formatter\Setters::class,
		Formatter\Markers::class,
		Formatter\UserMarkers::class,
		Formatter\AuthorMessage::class,
		Formatter\ContextMessages::class,
		Formatter\Language::class,
		Formatter\Fields::class,
		Formatter\Role::class,
		Formatter\Clean::class,
	];

	public function __construct(
		private string $text,
		private IEngine $engine,
	) {}

	/**
	 * Applies all registered formatters.
	 *
	 * @param array $additionalMarkers Optional additional markers.
	 * @return void
	 */
	private function applyFormatters(array $additionalMarkers = []): void
	{
		foreach (self::FORMATTERS as $formatter)
		{
			/** @var Formatter\IFormatter $formatter */
			$this->text = (new $formatter($this->text, $this->engine))->format($additionalMarkers);
		}
	}

	/**
	 * Return formatted (if needed) text.
	 *
	 * @param array $additionalMarkers Optional additional markers.
	 * @return string
	 */
	public function format(array $additionalMarkers = []): string
	{
		$this->applyFormatters($additionalMarkers);
		return $this->text;
	}
}
