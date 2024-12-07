<?php

namespace Bitrix\AI\Payload\Formatter;

use Bitrix\AI\Engine\IContext;

class ContextMessages extends Formatter implements IFormatter
{
	private const MARKER = '{context_messages}';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (!str_contains($this->text, self::MARKER))
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

		// author message
		array_shift($messages);

		$context = '';
		foreach ($messages as $message)
		{
			$context .= "\n- {$message->getContent()}";
		}

		return str_replace(self::MARKER, $context, $this->text);
	}
}
