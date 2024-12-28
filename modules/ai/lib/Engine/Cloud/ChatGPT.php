<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Context\Message;
use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\AI\Tokenizer\GPT;
use Bitrix\Main\Application;

final class ChatGPT extends CloudEngine implements IContext, IQueueOptional
{
	/**
	 * Limit of context messages.
	 * For GPT-4 it's 15, it's nessessary to reduce messages for chat scenario.
	 */
	private const GTP4_CONTEXT_MESSAGES_LIMIT = 16;

	protected const CATEGORY_CODE = Engine::CATEGORIES['text'];
	protected const ENGINE_NAME = 'gpt-3.5-turbo';
	public const ENGINE_CODE = 'ChatGPT';

	protected const URL_COMPLETIONS = 'https://api.openai.com/v1/chat/completions';

	protected const SYSTEM_ROLE = 'system';
	protected const DEFAULT_ROLE = 'user';

	protected const DEFAULT_MODEL = 'gpt-3.5-turbo-16k';
	protected const TEMPERATURE = 0.12;


	protected int $modelContextLimit = 15666;
	protected array $noJsonModeSupportModels = [
		'gpt-3.5-turbo-16k',
		'gpt-3.5-turbo-0613',
		'gpt-3.5-turbo-16k-0613',
		'gpt-3.5-turbo-instruct',
	];

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL;
	}

	private function isGpt4(): bool
	{
		$model = $this->getModel();

		return str_starts_with($model, 'gpt-4');
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
		$this->isModeResponseJson = ($enable && !in_array($this->getModel(), $this->noJsonModeSupportModels, true));
	}

	private function reduceMessagesByModelLimitaion(array $messages): array
	{
		if (!$this->isGpt4())
		{
			return $messages;
		}

		return \array_slice($messages, -self::GTP4_CONTEXT_MESSAGES_LIMIT);
	}

	public function getMessages(): array
	{
		return $this->reduceMessagesByModelLimitaion(parent::getMessages());
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
		$dataJson = null;

		$text = $this->restoreReplacements($text);
		$rawResult['choices'][0]['message']['content'] = $text;

		if ($text && $this->isModeResponseJson)
		{
			$dataJson = json_decode($text, true) ?? null;
		}

		return new Result($rawResult, $text, $cached, $dataJson);
	}

	/**
	 * @inheritDoc
	 */
	public function hasQuality(Quality $quality): bool
	{
		// GPT is the best, and has any possible quality
		return true;
	}

	/**
	 * Check if engine is available for current region.
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return !in_array($region, ['ru', 'by', 'cn']);
	}

	/**
	 * @inheritDoc
	 */
	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		$zone = Bitrix24::getPortalZone();
		if (\in_array($zone, ['ru', 'by', 'cn'], true))
		{
			return false;
		}

		$prefer = [
			Quality::QUALITIES['translate'],
			Quality::QUALITIES['fields_highlight'],
		];

		return $quality === null || !empty(array_intersect($quality->getRequired(), $prefer));
	}
}
