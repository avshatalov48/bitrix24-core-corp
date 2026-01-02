<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Context;
use Bitrix\AI\Engine\Cloud\CloudEngine;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;

trait YandexGPTCommonTrait
{
	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		if (Bitrix24::shouldUseB24())
		{
			$region = Bitrix24::getPortalZone();
		}
		else
		{
			$region = Application::getInstance()->getLicense()->getRegion();
		}

		return ($region === 'ru' || $region === 'by');
	}

	/**
	 * @inheritDoc
	 */
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

	/**
	 * @inheritDoc
	 */
	protected function getSystemParameters(): array
	{
		return [
			'model' => self::MODEL,
			'temperature' => self::TEMPERATURE,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getMessageLength(Context\Message $message): int
	{
		return mb_strlen($message->getContent());
	}

	/**
	 * @inheritDoc
	 */
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

		return Encoding::convertEncoding($postParams, SITE_CHARSET, 'UTF-8');
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

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->preparePostParams();
			$this->addPostParamsCredential($postParams);
		}

		$completionOptions = [
			'temperature' => $postParams['completionOptions']['temperature'] ?? self::TEMPERATURE,
		];

		if (isset($postParams['completionOptions']['max_tokens']))
		{
			$completionOptions['max_tokens'] = $postParams['completionOptions']['max_tokens'];
		}

		return [
			'modelUri' => $postParams['modelUri'] ?? '',
			'messages' => $postParams['messages'] ?? $this->getPreparedMessages(),
			'completionOptions' => $completionOptions,
		];
	}

	abstract private function addPostParamsCredential(array &$postParams): void;
}