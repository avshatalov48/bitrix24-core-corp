<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Engine\Models\GigaChatModel;
use Bitrix\AI\Result;
use Bitrix\Main\Application;

final class GigaChat extends CloudEngine implements IContext, IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'GigaChat 3.0+';
	public const ENGINE_CODE = 'GigaChat';

	protected const URL_COMPLETIONS = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MAX_OUTPUT_TOKENS = 1024;
	protected const DEFAULT_MODEL = GigaChatModel::Lite;
	protected const TEMPERATURE = 0.87; //from 0 to 2
	protected const VARIANTS = 1; //from 1 to 4
	protected const HTTP_STATUS_OK = 200;

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL->value;
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
			'model' => $this->getModel(),
			'temperature' => self::TEMPERATURE,
			'n' => self::VARIANTS,
		];
	}

	/**
	 * Returns message's length in tokens.
	 *
	 * @link https://developers.sber.ru/docs/ru/gigachat/limitations
	 * @link https://developers.sber.ru/docs/ru/gigachat/api/reference/rest/post-tokens-count
	 * @param Context\Message $message Message item.
	 * @return int
	 */
	protected function getMessageLength(Context\Message $message): int
	{
		return (int)(mb_strlen($message->getContent()) / 2.7);
	}

	public function getContextLimit(): int
	{
		// Previosly it was 1700 symbols and used \Bitrix\AI\Engine\Engine::$modelContextLimit.
		// Now it's value from GigaChat documentation, but we can setup model by Config for each request
		// or for one request. So, in that cases we should refactor code to get real limit from model from payload.
		$gigaChatModel = GigaChatModel::tryFrom($this->getModel()) ?? GigaChatModel::from($this->getDefaultModel());
		$contextLimit = $gigaChatModel->contextLimit();

		return $contextLimit - $this->getMaxOutputTokens();
	}

	public function getMaxOutputTokens(): int
	{
		return self::DEFAULT_MAX_OUTPUT_TOKENS;
	}

	/**
	 * Builds and returns messages for completions.
	 *
	 * @return array
	 */
	private function getPreparedMessages(): array
	{
		$data = [];

		// system role (instruction)
		if ($role = $this->payload->getRole())
		{
			$data[] = [
				'role' => self::SYSTEM_ROLE,
				'content' => $role->getInstruction(),
			];
		}

		// context messages
		if ($this->params['collect_context'] ?? false)
		{
			foreach ($this->getMessages() as $message)
			{
				$data[] = [
					'role' => $message->getRole(self::DEFAULT_ROLE),
					'content' => $message->getContent(),
				];
			}
			unset($this->params['collect_context']);
		}

		// user message (payload)
		$data[] = [
			'role' => self::DEFAULT_ROLE,
			'content' => $this->payload->getData(),
		];

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	protected function getPostParams(): array
	{
		return ['messages' => $this->getPreparedMessages()];
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
		$text = $rawResult['choices'][0]['message']['content'] ?? null;
		$text = $this->restoreReplacements($text);
		$rawResult['choices'][0]['message']['content'] = $text;

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
		if (empty($postParams))
		{
			$postParams = parent::makeRequestParams();
		}

		$postParams['n'] = $postParams['n'] ?? self::VARIANTS;

		return $postParams;
	}
}
