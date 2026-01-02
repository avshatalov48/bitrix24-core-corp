<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Config;
use Bitrix\AI\Context\Message;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Payload\Text;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\AI\Tokenizer\GPT;
use Bitrix\Main\Application;

trait BitrixGPTCommonTrait
{
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

		if ($this->payload::class === Text::class)
		{
			$this->payload->setPayload(str_replace(['/think', '/no_think'], '', $this->payload->getData()));
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

	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		return true;
	}

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$text = $rawResult['choices'][0]['message']['content'] ?? null;
		$dataJson = null;

		$text = $this->restoreReplacements($text);
		$rawResult['choices'][0]['message']['content'] = $text;

		if ($text && $this->isModeResponseJson)
		{
			$text = trim($text, " \n\r\t\v\0`");
			$dataJson = json_decode($text, true) ?? null;
		}

		return new Result($rawResult, $text, $cached, $dataJson);
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

	/**
	 * Check if engine is available for current region.
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		$shouldUseB24 = Bitrix24::shouldUseB24();

		$availableByRegion = $region === 'ru' || $region === 'by';
		if (!$shouldUseB24)
		{
			return $availableByRegion;
		}

		$isBitrixGptEnabled = Config::getValue('bitrixgpt_enabled') === 'Y';

		$moduleId = $this->getContext()->getModuleId();
		$isAvailableByModuleId = $moduleId === 'fake' || in_array($moduleId, $this->availableForModules(), true);

		return $isBitrixGptEnabled
			&& $availableByRegion
			&& $isAvailableByModuleId;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeRequestParams(array $postParams = []): array
	{
		$params = parent::makeRequestParams($postParams);
		$reasoningEffort = $params['reasoning_effort'] ?? null;
		unset($params['reasoning_effort']);

		$params['chat_template_kwargs'] = [
			'enable_thinking' => !empty($reasoningEffort),
		];

		return $params;
	}
}
