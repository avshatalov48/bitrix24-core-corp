<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Result;
use Bitrix\Main\Application;

final class YandexGPT extends CloudEngine implements IContext, IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'YandexGPT 2';
	public const ENGINE_CODE = 'YandexGPT';

	protected const URL_COMPLETIONS = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';

	protected const MODEL = 'gpt://<folder>/yandexgpt-lite/rc';
	protected const TEMPERATURE = 0.75;
	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';
	protected const HTTP_STATUS_OK = 200;

	protected int $modelContextLimit = 3000;

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return 'yandexgpt-lite';
	}

	public function setUserParameters(array $params): void
	{
		$toSet = [];

		if (isset($params['temperature']))
		{
			$toSet['temperature'] = (float)$params['temperature'];
		}

		if ($params['model'] ?? null)
		{
			$toSet['model'] = (string)$params['model'];
		}

		$this->setParameters($toSet);
	}

	protected function getSystemParameters(): array
	{
		return [
			'model' => self::MODEL,
			'temperature' => self::TEMPERATURE,
		];
	}

	protected function getMessageLength(Context\Message $message): int
	{
		return (int)(mb_strlen($message->getContent()) / 3);
	}

	protected function getPostParams(): array
	{
		return [
			'modelUri' => self::MODEL,
			'messages' => $this->getPreparedMessages()
		];
	}

	/**
	 * Builds and returns messages for completions.
	 *
	 * @return array
	 */
	private function getPreparedMessages(): array
	{
		$data = [];
		$text = $this->payload->getData();// oddly place

		// system role (instruction)
		if ($role = $this->payload->getRole())
		{
			$data[] = [
				'role' => self::SYSTEM_ROLE,
				'text' => $role->getInstruction(),
			];
		}

		// context messages
		if ($this->params['collect_context'] ?? false)
		{
			foreach ($this->getMessages() as $message)
			{
				$data[] = [
					'role' => $message->getRole(self::DEFAULT_ROLE),
					'text' => $message->getContent(),
				];
			}
			unset($this->params['collect_context']);
		}

		// user message (payload)
		$data[] = [
			'role' => self::DEFAULT_ROLE,
			'text' => $text,
		];

		return $data;
	}

	protected function preparePostParams(array $additionalParams = []): array
	{
		$postParams = $this->getPostParams();
		$postParams['completionOptions'] = $this->getParameters();

		return $postParams;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		if (isset($rawResult['result']['message']['text']))
		{
			$text = $rawResult['result']['message']['text'];
			$text = $this->restoreReplacements($text);
			$rawResult['result']['message']['text'] = $text;
		}
		else
		{
			$text = $rawResult['result']['alternatives'][0]['message']['text'];
			$text = $this->restoreReplacements($text);
			$rawResult['result']['alternatives'][0]['message']['text'] = $text;
		}

		return new Result($rawResult, $text, $cached);
	}

	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
	}

	/**
	 * @inheritDoc
	 */
	protected function makeRequestParams(array $postParams = []): array
	{
		$postParams = $this->getPostParams();
		$postParams = array_merge($this->getParameters(), $postParams);

		$result = [
			'model' => $postParams['model'] ?? self::MODEL,
			'temperature' => $postParams['temperature'] ?? self::TEMPERATURE,
			'modelUri' => $postParams['modelUri'] ?? self::MODEL,
			'messages' => $postParams['messages'] ?? $this->getPreparedMessages()
		];

		if (isset($postParams['max_tokens']))
		{
			$result['max_tokens'] = $postParams['max_tokens'];
		}

		return $result;
	}
}
