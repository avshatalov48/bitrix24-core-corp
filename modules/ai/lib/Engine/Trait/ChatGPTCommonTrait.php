<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Config;
use Bitrix\AI\Context\Message;
use Bitrix\AI\Payload\Prompt;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\AI\Tokenizer\GPT;

trait ChatGPTCommonTrait
{
	protected array $noJsonModeSupportModels = [
		'gpt-3.5-turbo-16k',
		'gpt-3.5-turbo-0613',
		'gpt-3.5-turbo-16k-0613',
		'gpt-3.5-turbo-instruct',
	];

	protected array $reasoningModels = [
		'o3-mini-2025-01-31',
		'o1-2024-12-17',
	];

	private function shouldUseReasoning(): bool
	{
		return true;
	}

	private function isGpt4(): bool
	{
		$model = $this->getModel();

		return str_starts_with($model, 'gpt-4');
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
		//dirty hack for GPT-4 & money saving
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

		if ($this->getPayload() instanceof Prompt && $this->getPayload()->getJsonSchema() !== null)
		{
			$postParams['response_format'] = [
				'type' => 'json_schema',
				'json_schema' => $this->getPayload()->getJsonSchema(),
			];
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

}