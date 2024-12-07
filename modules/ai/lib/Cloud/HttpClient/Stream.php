<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud\HttpClient;

use Bitrix\Main\Web\Http;

final class Stream extends Http\Stream
{
	/**
	 * @var callable
	 */
	private $readPortionCallback;

	public function setReadPortionCallback(callable $callback): void
	{
		$this->readPortionCallback = $callback;
	}

	public function write(string $string): int
	{
		if (!$this->resource)
		{
			throw new \RuntimeException('No resource available, cannot write.');
		}

		$result = fwrite($this->resource, $string);

		if ($this->readPortionCallback)
		{
			$readPortionCallback = $this->readPortionCallback;
			$readPortionCallback($string);
		}

		if ($result === false)
		{
			throw new \RuntimeException('Error writing to stream.');
		}

		return $result;
	}
}
