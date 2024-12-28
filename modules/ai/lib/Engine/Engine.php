<?php

namespace Bitrix\AI\Engine;

use Bitrix\AI\Agreement;
use Bitrix\AI\Cache\EngineResultCache;
use Bitrix\AI\Context;
use Bitrix\AI\Config;
use Bitrix\AI\Facade\Analytics;
use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\User;
use Bitrix\AI\History;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Payload\Prompt;
use Bitrix\AI\Quality;
use Bitrix\AI\QueueJob;
use Bitrix\AI\Result;
use Bitrix\AI\Role\RoleManager;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

abstract class Engine
{
	protected const CATEGORY_CODE = '';
	protected const ENGINE_NAME = '';
	protected const ENGINE_CODE = '';
	protected const AGREEMENT_CODE = '';
	protected const FEATURE_CODE = '';
	protected const SHARD_PREFIX = '';

	protected const PARAM_CONSUMPTION_ID = 'consumptionId';

	protected const URL_COMPLETIONS_QUEUE_DEFAULT = '';
	protected const URL_COMPLETIONS_QUEUE_PATH = '/api/v1/proxy/send';
	protected const HTTP_STATUS_OK = 200;

	protected int $modelContextLimit = 0;
	protected array $params = [];
	protected bool $historyState = false;
	protected bool $cache = false;
	protected bool $isModeResponseJson = false;
	protected int $historyGroupId = -1;//Group ID for save history. -1 - no grouped, 0 - first item of group
	protected ?IPayload $payload = null;
	protected $onSuccessCallback;
	protected $onErrorCallback;
	protected QueueJob|null $queueJob = null;
	private ?array $contextMessages = null;
	private array $analyticData = [
		'category' => '',
		'type' => '',
		'c_section' => '',
		'c_element' => '',
		'c_sub_section' => '',
	];

	private bool $shouldSkipAgreement = false;

	public function __construct(
		protected Context $context,
		mixed $data = null
	){
	}

	/**
	 * Set id consumption from bass
	 */
	public function setConsumptionId(string $consumptionId): void
	{
		if (!empty($consumptionId))
		{
			$this->setParameters([self::PARAM_CONSUMPTION_ID => $consumptionId]);
		}
	}

	/**
	 * Returns id consumption from bass
	 */
	public function getConsumptionId(): string
	{
		if (empty($this->params[self::PARAM_CONSUMPTION_ID]))
		{
			return '';
		}

		$consumptionId = $this->params[self::PARAM_CONSUMPTION_ID];
		if (!is_string($consumptionId))
		{
			return '';
		}

		return $consumptionId;
	}

	/**
	 * Returns Engine's category.
	 *
	 * @return string
	 */
	public function getCategory(): string
	{
		return static::CATEGORY_CODE;
	}

	/**
	 * Returns Engine's name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return static::ENGINE_NAME;
	}

	/**
	 * Returns Engine's code.
	 *
	 * @return string
	 */
	public function getCode(): string
	{
		return static::ENGINE_CODE;
	}

	/**
	 * Returns context limit in tokens.
	 * @return int
	 */
	public function getContextLimit(): int
	{
		return $this->modelContextLimit;
	}

	/**
	 * Stores payload for future request.
	 *
	 * @param IPayload $payload Payload.
	 * @return void
	 */
	public function setPayload(IPayload $payload): void
	{
		$this->payload = $payload;
	}

	/**
	 * Returns stored payload.
	 *
	 * @return IPayload|null
	 */
	public function getPayload(): ?IPayload
	{
		if (!$this->payload)
		{
			throw new SystemException('Before using Payload you must set it.');
		}

		return $this->payload;
	}

	/**
	 * Returns current context.
	 *
	 * @return Context
	 */
	public function getContext(): Context
	{
		return $this->context;
	}

	/**
	 * Sets additional parameters for future request to Engine.
	 * Method replace old parameters, if they were set.
	 *
	 * @param array $params Array of parameters (linear array key to value).
	 * @return void
	 */
	public function setParameters(array $params): void
	{
		$this->params = array_merge($this->params, $params);
	}

	/**
	 * Extension of setParameters(), forbids setting by user important engine params,
	 * such as allowed query cost (for example).
	 *
	 * @see setParameters()
	 * @param array $params
	 * @return void
	 */
	public function setUserParameters(array $params): void
	{
		$this->setParameters($params);
	}

	/**
	 * In Engine's class must return array of system parameters.
	 *
	 * @return array
	 */
	protected function getSystemParameters(): array
	{
		return [];
	}

	/**
	 * Returns all current default and custom parameters.
	 *
	 * @return array
	 */
	public function getParameters(): array
	{
		return array_merge(
			$this->getSystemParameters(),
			$this->params,
			\Bitrix\AI\Engine::getConfigParameters($this->getCode())
		);
	}

	/**
	 * Write or not history, in depend on $state.
	 *
	 * @param bool $state True to write, false otherwise.
	 * @return void
	 */
	public function setHistoryState(bool $state): void
	{
		$this->historyState = $state;
	}

	/**
	 * Extension of setAnalyticData(), set same data for analytic,
	 *
	 * @see setAnalyticData()
	 * @param string[] $analyticData
	 * @return void
	 */
	public function setAnalyticData(array $analyticData): void
	{
		$this->analyticData = array_merge($this->analyticData, array_intersect_key($analyticData, $this->analyticData));
	}

	/**
	 * Get analytic data
	 *
	 * @return array|string[]
	 */
	public function getAnalyticData(): array
	{
		return $this->analyticData;
	}

	/**
	 * Returns true, if history will be written in current request.
	 *
	 * @return bool
	 */
	final public function shouldWriteHistory(): bool
	{
		$userId = $this->getContext()->getUserId();
		if ($userId && History\Manager::shouldDisableHistoryForUser($userId))
		{
			return false;
		}

		return $this->historyState || History\Manager::shouldAlwaysWrite();
	}

	/**
	 * Set ID of history group
	 * -1 - no grouped, 0 - first item of group
	 * @param int $groupId
	 * @return void
	 */
	public function setHistoryGroupId(int $groupId): void
	{
		$this->historyGroupId = $groupId;
	}

	/**
	 * Get ID of history group
	 * -1 - no grouped, 0 - first item of group
	 * @return int
	 */
	public function getHistoryGroupId(): int
	{
		return $this->historyGroupId;
	}

	/**
	 * Writes history, must depend on isWriteHistory.
	 *
	 * @param Result $result Engine's work result.
	 * @return void
	 */
	public function writeHistory(Result $result): void
	{
		if ($this->shouldWriteHistory())
		{
			(new History\Manager($this))->writeHistory($result);
		}
	}

	/**
	 * Writes error response in history, must depend on isWriteHistory.
	 *
	 * @param Error $error Error instance.
	 * @return void
	 */
	public function writeErrorInHistory(Error $error): void
	{
		if ($this->shouldWriteHistory() && Config::getValue('write_errors') === 'Y')
		{
			$errorMessage = "[ERROR] {$error->getCode()} {$error->getMessage()}";
			$fakeResult = new Result($errorMessage, $errorMessage);

			(new History\Manager($this))->writeHistory($fakeResult);
		}
	}

	/**
	 * Sets callback, that will be called on successful request.
	 * This callback will receive Result object as input parameter.
	 *
	 * @see \Bitrix\AI\Result
	 * @param callable $callback Function will be called.
	 * @return void
	 */
	public function onSuccess(callable $callback): void
	{
		$this->onSuccessCallback = $callback;
	}

	/**
	 * Sets callback, that will be called on any error occurred.
	 * This callback will receive Error object as input parameter.
	 *
	 * @see \Bitrix\Main\Error
	 * @param callable $callback Function will be called.
	 * @return void
	 */
	public function onError(callable $callback): void
	{
		$this->onErrorCallback = $callback;
	}

	/**
	 * Decodes response. If error occurred, returns null and sends Error to onErrorCallback.
	 *
	 * @param string $response String response from Engine.
	 * @return array|null
	 */
	protected function decodeResponse(string $response): ?array
	{
		try
		{
			return Json::decode($response);
		}
		catch (\Exception $e)
		{
			$this->onResponseError($e->getMessage(), $e->getCode());
		}

		return null;
	}

	/**
	 * Returns true if current Engine is third party application.
	 *
	 * @return bool
	 */
	public function isThirdParty(): bool
	{
		return false;
	}

	/**
	 * Returns true if Engine is expired (in REST Application case for example).
	 *
	 * @return bool
	 */
	public function isExpired(): bool
	{
		return false;
	}

	/**
	 * Returns message's length in tokens.
	 *
	 * @param Context\Message $message Message item.
	 * @return int
	 */
	protected function getMessageLength(Context\Message $message): int
	{
		return 0;
	}

	/**
	 * Returns array of messages, that represents as Context of current request.
	 * Each item must contain at least one key `content`.
	 *
	 * @return Context\Message[]
	 */
	public function getMessages(): array
	{
		if ($this->contextMessages !== null)
		{
			return $this->contextMessages;
		}

		$this->contextMessages = [];
		$firstMessage = null;

		$length = 0;
		while ($messages = $this->context->getMessages())
		{
			foreach ($messages as $message)
			{
				$length += $this->getMessageLength($message);
				if ($length < $this->getContextLimit())
				{
					if ($message->getMeta('is_original_message'))
					{
						$firstMessage = $message;
					}
					else
					{
						$this->contextMessages[] = $message;
					}
				}
				else
				{
					break 2;
				}
			}
		}

		$this->contextMessages = array_reverse($this->contextMessages);
		if (!empty($firstMessage))
		{
			array_unshift($this->contextMessages, $firstMessage);
		}

		return $this->contextMessages;
	}

	/**
	 * Returns additional query params.
	 *
	 * @return array
	 */
	protected function getPostParams(): array
	{
		return [];
	}

	/**
	 * Return prepared post.
	 *
	 * @param array $additionalParams
	 * @return array
	 */
	protected function preparePostParams(array $additionalParams = []): array
	{
		$postParams = $this->getPostParams();// !important getPostParams() before merge
		$postParams = array_merge($this->getParameters(), $postParams, $additionalParams);

		return $postParams;
	}

	/**
	 * On response success.
	 *
	 * @param mixed $response
	 * @param EngineResultCache $cacheManager
	 * @param bool $cached
	 * @return void
	 */
	function onResponseSuccess(mixed $response, EngineResultCache $cacheManager, bool $cached = false): void
	{
		if (!$cached && $this->isCache())
		{
			$cacheManager->store($response);
		}

		Analytics::engineGenerateResultEvent(
			($cached) ? 'generate_reusage' : 'generate',
			$this,
			$this->analyticData
		);

		$result = $this->getResultFromRaw($response, $cached);
		$this->writeHistory($result);

		if ($this->getPayload()->getRole() !== null)
		{
			$roleManager = new RoleManager(User::getCurrentUserId(), User::getUserLanguage());
			$roleManager->addRecentRole($this->getPayload()->getRole());
		}

		if (is_callable($this->onSuccessCallback))
		{
			call_user_func($this->onSuccessCallback, $result);
		}
	}

	/**
	 * On post response error.
	 *
	 * @param string $message
	 * @param string $status
	 * @return void
	 */
	function onResponseError(string $message, string $status): void
	{
		$error = new Error(
			$message,
			$status
		);
		$this->writeErrorInHistory($error);

		if (is_callable($this->onErrorCallback))
		{
			$errorCode = (int)$error->getCode();
			if ($errorCode == 100 || $errorCode >= 500)
			{
				$errorForUser = new Error(Loc::getMessage('AI_ENGINE_ERROR_PROVIDER'), 'AI_ENGINE_ERROR_PROVIDER');
			}
			else
			{
				$errorForUser = new Error(Loc::getMessage('AI_ENGINE_ERROR_OTHER'), 'AI_ENGINE_ERROR_OTHER');
			}
			call_user_func($this->onErrorCallback, $errorForUser);
		}
	}

	/**
	 * Returns authorization header.
	 *
	 * @return string
	 */
	protected function getAuthorizationHeader(): string
	{
		return '';
	}

	/**
	 * Returns Completions url.
	 *
	 * @return string
	 */
	protected function getCompletionsUrl(): string
	{
		return '';
	}

	/**
	 * Returns completions url for queue from config or static const.
	 *
	 * @return string
	 */
	protected function getCompletionsQueueUrl(): string
	{
		$url = Config::getValue('queue_url');
		if (!empty($url))
		{
			return rtrim($url, '/');
		}

		return self::URL_COMPLETIONS_QUEUE_DEFAULT;
	}

	/**
	 * Returns completions url path for queue from static const.
	 *
	 * @return string
	 */
	protected function getCompletionsQueueUrlPath(): string
	{
		return self::URL_COMPLETIONS_QUEUE_PATH;
	}

	/**
	 * Makes request to AI Engine throw the queue.
	 *
	 * @return void
	 */
	public function completionsInQueue(): void
	{
		if ($this->payload->shouldUseCache())
		{
			$this->setCache(true);
		}
		$this->queueJob = QueueJob::createWithinFromEngine($this)->register();

		$url = $this->getCompletionsQueueUrl() . $this->getCompletionsQueueUrlPath();

		$cacheManager = new EngineResultCache($this->queueJob->getCacheHash());

		if ($this->isCache() && ($response = $cacheManager->getExists()))
		{
			$this->onResponseSuccess($response, $cacheManager, true);

			return;
		}

		$http = new HttpClient;
		$http->setHeader('Content-Type', 'application/json');

		$http->post($url, json_encode([
			'callbackUrl' => $this->queueJob->getCallbackUrl(),
			'errorCallbackUrl' => $this->queueJob->getErrorCallbackUrl(),
			'url' => $this->getCompletionsUrl(),
			'params' => $this->makeRequestParams(),
			'authorization' => $this->getAuthorizationHeader(),
			'additionalHeaders' => $this->getAdditionalHeaders() ?? null,
		]));

		if ($http->getStatus() === self::HTTP_STATUS_OK)
		{
			if (is_callable($this->onSuccessCallback))
			{
				call_user_func(
					$this->onSuccessCallback,
					new Result(true, ''),
					$this->queueJob->getHash(),
				);
			}
		}
		elseif (is_callable($this->onErrorCallback))
		{
			$error = $http->getError();
			call_user_func(
				$this->onErrorCallback,
				new Error(current($error), key($error)),
			);
		}
	}

	/**
	 * Return additional headers for specific engines(like yandex)
	 *
	 * @return array|null
	 */
	protected function getAdditionalHeaders(): ?array
	{
		return null;
	}

	/**
	 *  Returns only necessary parameters to make request to AI engine
	 *
	 * @param array $postParams
	 * @return array
	 */
	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->getPostParams();
			$postParams = array_merge($this->getParameters(), $postParams);
		}

		$returnArray = [
			'model' => $postParams['model'] ?? $this->getSystemParameters()['model'],
			'temperature' => $postParams['temperature'] ?? static::TEMPERATURE,
			'messages' => $postParams['messages'] ?? $this->getPostParams()['messages'],
		];

		if (isset($postParams['max_tokens']))
		{
			$returnArray['max_tokens'] = $postParams['max_tokens'];
		}
		if (isset($postParams['response_format']))
		{
			$returnArray['response_format']['type'] = $postParams['response_format']['type'];
		}

		return $returnArray;
	}

	/**
	 * Returns true if current Engine must use limits.
	 *
	 * @return bool
	 */
	public function checkLimits(): bool
	{
		return true;
	}

	/**
	 * Checks that current Engine is available in the current tariff.
	 *
	 * @return bool
	 */
	public function inTariff(): bool
	{
		return Bitrix24::isFeatureEnabled(static::FEATURE_CODE);
	}

	/**
	 * Returns Agreement if current Engine has it, null otherwise.
	 *
	 * @return Agreement|null
	 */
	public function getAgreement(): ?Agreement
	{
		if ($this->shouldSkipAgreement)
		{
			return null;
		}

		return Agreement::get(static::AGREEMENT_CODE);
	}

	/**
	 * Skips User Agreement (in fact just accept agreement by current Context's user).
	 *
	 * @return self
	 */
	public function skipAgreement(): void
	{
		$this->shouldSkipAgreement = true;
	}

	public function shouldSkipAgreement(): bool
	{
		return $this->shouldSkipAgreement;
	}

	/**
	 * Returns true, if Engine has required Quality.
	 *
	 * @param Quality $quality
	 * @return bool
	 */
	public function hasQuality(Quality $quality): bool
	{
		return false;
	}

	/**
	 * Check if Engine recommended to use for Quality
	 *
	 * @param Quality|null $quality
	 * @return bool
	 */
	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		return false;
	}

	/**
	 * Return cache state
	 *
	 * @return bool
	 */
	public function isCache(): bool
	{
		return $this->cache;
	}

	/**
	 * Set cache state for request
	 *
	 * @param bool $cache
	 *
	 * @return void
	 */
	public function setCache(bool $cache): void
	{
		$this->cache = $cache;
	}

	/**
	 * Get response json mode.
	 *
	 * @return bool
	 */
	public function getResponseJsonMode(): bool
	{
		return $this->isModeResponseJson;
	}

	/**
	 * Set response json mode.
	 *
	 * @param bool $enable
	 *
	 * @return void
	 */
	public function setResponseJsonMode(bool $enable): void
	{
		$quality = new Quality(['json_response_mode']);
		$this->isModeResponseJson = $this->hasQuality($quality) && $enable;
	}

	protected function restoreReplacements(mixed $value): mixed
	{
		$processedReplacements = $this->getPayload()?->getProcessedReplacements();
		if ($processedReplacements && \is_string($value))
		{
			$value = strtr($value, array_flip($processedReplacements));
		}

		return $value;
	}
}
