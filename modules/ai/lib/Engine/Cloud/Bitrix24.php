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

final class Bitrix24 extends CloudEngine implements IContext, IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'BitrixGPT 4x';

	public const ENGINE_CODE = 'b24ai';

	protected const URL_COMPLETIONS = 'https://b24ai.bitrix.info/v1/chat/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MODEL = 'default';
	protected const TEMPERATURE = 0.12;

	protected int $modelContextLimit = 15745;

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

	protected function getMessageLength(Message $message): int
	{
		return (new GPT($message->getContent()))->count();
	}

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
		$postParams = ['messages' => $this->getPreparedMessages()];
		if ($this->isModeResponseJson)
		{
			$postParams['response_format'] = ['type' => 'json_object'];
		}

		return $postParams;
	}

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$text = $rawResult['choices'][0]['message']['content'] ?? null;
		$dataJson = null;

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
		if (array_intersect($quality->getRequired(), [Quality::QUALITIES['give_advice']]))
		{
			return false;
		}

		if (array_intersect($quality->getRequired(), [Quality::QUALITIES['meeting_processing']]))
		{
			return false;
		}

		return true;
	}

	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		$prefer = [
			Quality::QUALITIES['translate'],
			Quality::QUALITIES['summarize'],
			Quality::QUALITIES['fields_highlight'],
			Quality::QUALITIES['chat_talk'],
		];

		return $quality === null || !empty(array_intersect($quality->getRequired(), $prefer));
	}

	/**
	 * Check if engine is available for current region.
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
	}
}
