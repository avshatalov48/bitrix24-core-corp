<?php

namespace Bitrix\AI\Engine;

use Bitrix\AI\Context;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\Rest;
use Bitrix\AI\Prompt;
use Bitrix\AI\Quality;
use Bitrix\AI\QueueJob;
use Bitrix\AI\Result;
use Bitrix\AI\ThirdParty\Item;
use Bitrix\AI\ThirdParty\Manager;
use Bitrix\AI\Tokenizer\GPT;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;

class ThirdParty extends Engine implements IEngine, IQueue, IContext
{
	protected const HTTP_STATUS_OK = 202;
	protected const HTTP_TIMEOUT = 5;

	protected const DEFAULT_ROLE = 'user';

	protected string $modelContextType = 'token';
	protected int $modelContextLimit = 15666;

	private Item|null $item = null;

	public function __construct(
		protected Context $context,
		mixed $data = null
	){
		parent::__construct($context, $data);

		if ($data instanceof Item)
		{
			$this->item = $data;
		}
		else if (is_string($data))
		{
			$this->item = $this->retrieveItem($data);
		}

		if (is_null($this->item))
		{
			throw new SystemException('Incorrect or unknown Engine');
		}

		$this->modelContextType = $this->item->getOption('model_context_type') ?: $this->modelContextType;
		$this->modelContextLimit = (int)$this->item->getOption('model_context_limit') ?: $this->modelContextLimit;
	}

	/**
	 * Tries to retrieve data from Engine's storage.
	 *
	 * @param string $code Engine code.
	 * @return Item|null
	 */
	private function retrieveItem(string $code): ?Item
	{
		return Manager::getByCode($code);
	}

	/**
	 * @inheritDoc
	 */
	public function getCategory(): string
	{
		return $this->item->getCategory();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return $this->item->getName();
	}

	/**
	 * @inheritDoc
	 */
	public function getCode(): string
	{
		return $this->item->getCode();
	}

	/**
	 * Returns code alias from settings. Specific method for StatementTrait.
	 *
	 * @see \Bitrix\AI\Payload\Formatter\StatementTrait::getEngineCode
	 * @return string
	 */
	public function getCodeAlias(): string
	{
		return $this->item->getOption('code_alias') ?: 'ChatGPT';
	}

	/**
	 * Returns application Item for specific purposes.
	 *
	 * @return Item
	 */
	public function getRestItem(): Item
	{
		return $this->item;
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region !== 'cn';
	}

	/**
	 * @inheritDoc
	 */
	public function inTariff(): bool
	{
		return true;
	}

	/**
	 * Returns true if current Engine is third party application.
	 *
	 * @return bool
	 */
	public function isThirdParty(): bool
	{
		return true;
	}

	/**
	 * Returns true if Engine is expired (in REST Application case for example).
	 *
	 * @return bool
	 */
	public function isExpired(): bool
	{
		if ($this->item?->getAppCode())
		{
			return Rest::isApplicationExpired($this->item->getAppCode());
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		if (isset($rawResult['result']))
		{
			$result = is_array($rawResult['result']) ? $rawResult['result'] : [$rawResult['result']];
			$result = $this->getCategory() === 'image'
				? $result
				: $result[0]
			;
		}
		else
		{
			$result = $rawResult;
		}

		return new Result(
			$rawResult,
			is_array($result) ? json_encode($result) : $result,
			$cached
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getMessageLength(Context\Message $message): int
	{
		return match (strtolower($this->modelContextType))
		{
			'symbol' => mb_strlen($message->getContent()),
			'token' => (new GPT($message->getContent()))->count(),
			default => 0,
		};
	}

	/**
	 * Collects Context messages for sending to provider.
	 *
	 * @return array
	 */
	private function packContextMessages(): array
	{
		$data = [];

		foreach ($this->getMessages() as $message)
		{
			$data[] = [
				'role' => $message->getRole(self::DEFAULT_ROLE),
				'content' => $message->getContent(),
			];
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function completions(): void
	{
		$http = new HttpClient;
		$http->setHeader('Content-Type', 'application/json');
		$http->setTimeout(self::HTTP_TIMEOUT);

		$this->getQueueJob()->register();

		$this->params['prompt'] = $this->payload->getData();
		$this->params['payload_raw'] = $this->payload->getRawData();
		$this->params['payload_provider'] = $provider = mb_strtolower((new \ReflectionClass($this->payload))->getShortName());
		$this->params['payload_role'] = $this->payload->getRole()?->getInstruction();

		$this->params['context'] = $this->packContextMessages();

		$this->params['payload_markers'] = $this->payload->getMarkers() + ['language' => Bitrix24::getUserLanguage()];

		$this->params['payload_prompt_text'] = ($provider === 'prompt')
			? Prompt\Manager::getByCode($this->params['payload_raw'])?->getPrompt()
			: null
		;

		$this->params['auth'] = $this->item->getAppCode() ? Rest::getAuthInfo($this->item->getAppCode()) : null;
		$this->params['category'] = $this->getCategory();
		$this->params['ttl'] = $this->getQueueJob()->getTTL();

		$this->params['callbackUrl'] = $this->getQueueJob()->getCallbackUrl();
		$this->params['errorCallbackUrl'] = $this->getQueueJob()->getErrorCallbackUrl();

		$params = $this->getParameters();
		$response = $http->post($this->item->getCompletionsUrl(), json_encode($params));
		$responseDecode = $this->decodeResponse($response);
		if (!$responseDecode)
		{
			return;
		}
		if ($http->getStatus() === self::HTTP_STATUS_OK)
		{
			if (is_callable($this->onSuccessCallback))
			{
				call_user_func(
					$this->onSuccessCallback,
					new Result(true, ''),
					$this->getQueueJob()->getHash()
				);
			}
		}
		else
		{
			$this->onResponseError(
				"Unknown error occurred with status {$http->getStatus()}",
				'unknown_error'
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getQueueJob(): QueueJob
	{
		if (!$this->queueJob)
		{
			$this->queueJob = QueueJob::createWithinFromEngine($this);
		}
		return $this->queueJob;
	}

	/**
	 * @inheritDoc
	 */
	public function checkLimits(): bool
	{
		return $this->item->getCode() === 'itsolutionru.gptconnector';
	}

	/**
	 * @inheritDoc
	 */
	public function hasQuality(Quality $quality): bool
	{
		$categoryAudio = \Bitrix\AI\Engine::CATEGORIES['audio'];
		$categoryText = \Bitrix\AI\Engine::CATEGORIES['text'];

		// for audio category is required to exists any provider in text category
		if ($this->getCategory() === $categoryAudio)
		{
			$engineInText = \Bitrix\AI\Engine::getByCategory($categoryText, $this->context);
			return !empty($engineInText);
		}

		return true;
	}

	/**
	 * Get the supported image formats.
	 *
	 * @return array The supported image formats with their respective dimensions.
	 */
	public function getImageFormats(): array
	{
		return [
			'square' => [
				'code' => 'square',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_THIRDPARTY_FORMAT_SQUARE') ?? 'square (1:1)',
				'width' => 1024,
				'height' => 1024,
			],
			'portrait' => [
				'code' => 'portrait',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_THIRDPARTY_FORMAT_PORTRAIT') ?? 'portrait (9:16)',
				'width' => 1024,
				'height' => 1792,
			],
			'landscape' => [
				'code' => 'landscape',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_THIRDPARTY_FORMAT_LANDSCAPE') ?? 'landscape (16:9)',
				'width' => 1792,
				'height' => 1024,
			],
		];
	}
}
