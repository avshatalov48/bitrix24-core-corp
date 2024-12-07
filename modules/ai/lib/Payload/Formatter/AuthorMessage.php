<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Engine\IContext;

class AuthorMessage extends Formatter implements IFormatter
{
	private const MARKER = '{author_message}';
	private const MARKER_AUTHOR = '{author.';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (!str_contains($this->text, self::MARKER) && !str_contains($this->text, self::MARKER_AUTHOR))
		{
			return $this->text;
		}

		if (!($this->engine instanceof IContext))
		{
			return $this->text;
		}

		$messages = $this->engine->getMessages();
		if (empty($messages))
		{
			return $this->text;
		}

		$authorMessage = $messages[0];
		$authorMeta = $authorMessage->getMeta('author');

		if (is_array($authorMeta))
		{
			$this->replaceAuthorMeta($authorMeta);
		}

		return str_replace(self::MARKER, $authorMessage->getContent(), $this->text);
	}

	/**
	 * Replaces in text author's markers.
	 *
	 * @param array $authorData Author data array.
	 * @return void
	 */
	private function replaceAuthorMeta(array $authorData): void
	{
		$replace = [];

		foreach ($authorData as $key => $value)
		{
			if (!is_array($value))
			{
				$key = mb_strtolower($key);
				$replace["{author.$key}"] = $value;
			}
		}

		$this->text = str_replace(array_keys($replace), array_values($replace), $this->text);
	}
}
