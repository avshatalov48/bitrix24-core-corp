<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Context\Message;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\AI\Tokenizer\GPT;
use Bitrix\Main\Application;

final class ItSolution extends CloudEngine implements IContext, IQueueOptional
{
	private const CONTEXT_MESSAGES_LIMIT = 16;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'IT-Solution';
	public const ENGINE_CODE = 'ItSolution';

	protected const URL_COMPLETIONS = 'https://it-solution.ru/llm/v1/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MODEL = 'default';
	protected const TEMPERATURE = 0.12;

	protected int $modelContextLimit = 15666;

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL;
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
		];
	}

	public function setResponseJsonMode(bool $enable): void
	{
		$this->isModeResponseJson = $enable;
	}

	public function getMessages(): array
	{
		return \array_slice(parent::getMessages(), -self::CONTEXT_MESSAGES_LIMIT);
	}

	/**
	 * @inheritDoc
	 */
	protected function getMessageLength(Message $message): int
	{
		return (new GPT($message->getContent()))->count();
	}

	/**
	 * Builds and returns messages for completions.
	 *
	 * @return array
	 */
	private function getPreparedMessages(): array
	{
		$data = [];

		if ($role = $this->payload->getRole())
		{
			$data[] = [
				'role' => self::SYSTEM_ROLE,
				'content' => $role->getInstruction(),
			];
		}

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
		$postParams = ['messages' => $this->getPreparedMessages()];
		if ($this->isModeResponseJson)
		{
			$postParams['response_format'] = ['type' => 'json_object'];
		}

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
		$text = $rawResult['choices'][0]['message']['content'] ?? null;
		$dataJson =  null;

		$text = $this->restoreReplacements($text);
		$rawResult['choices'][0]['message']['content'] = $text;

		if ($text && $this->isModeResponseJson)
		{
			$dataJson = json_decode($text, true) ?? null;
		}

		return new Result($rawResult, $text, $cached, $dataJson);
	}

	public function hasQuality(Quality $quality): bool
	{
		return true;
	}

	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
	}
}
