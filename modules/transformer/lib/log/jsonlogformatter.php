<?php

namespace Bitrix\Transformer\Log;

use Bitrix\Main\Diag\LogFormatter;
use Bitrix\Main\Web\Json;

final class JsonLogFormatter extends LogFormatter
{
	private bool $lineBreakAfterEachMessage;
	private readonly array $globalContext;

	public function __construct(
		$showArguments = false,
		$argMaxChars = 30,
		bool $lineBreakAfterEachMessage = false,
		array $globalContext = [],
	)
	{
		parent::__construct($showArguments, $argMaxChars);

		$this->lineBreakAfterEachMessage = $lineBreakAfterEachMessage;
		$this->globalContext = $globalContext;
	}

	/**
	 * @inheritDoc
	 */
	public function format($message, array $context = []): string
	{
		$localContext = $context + $this->globalContext;

		$message = parent::format($message, $localContext);

		$jsonifiedContext = [];
		foreach ($localContext as $key => $value)
		{
			$jsonifiedContext[$key] = $this->jsonify($value);
		}

		$result = Json::encode(['message' => $message] + $jsonifiedContext);

		if ($this->lineBreakAfterEachMessage)
		{
			$result .= PHP_EOL;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function formatMixed($value): string
	{
		return Json::encode($this->jsonify($value));
	}

	private function jsonify(mixed $value): mixed
	{
		if (is_object($value) && !($value instanceof \JsonSerializable) && $value instanceof \Stringable)
		{
			return (string)$value;
		}

		return $value;
	}
}
