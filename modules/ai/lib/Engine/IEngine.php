<?php

namespace Bitrix\AI\Engine;

use Bitrix\AI\Agreement;
use Bitrix\AI\Context;
use Bitrix\AI\Payload\IPayload;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Error;

interface IEngine
{
	public function __construct(Context $context, mixed $data = null);

	/**
	 * Set id consumption from bass
	 *
	 * @param string $consumptionId
	 * @return void
	 */
	public function setConsumptionId(string $consumptionId): void;

	/**
	 * Returns id consumption from bass
	 *
	 * @return string
	 */
	public function getConsumptionId(): string;

	/**
	 * Returns true, if this Engine is available for use.
	 *
	 * @return bool
	 */
	public function isAvailable(): bool;

	/**
	 * Returns Engine's category.
	 *
	 * @return string
	 */
	public function getCategory(): string;

	/**
	 * Returns Engine's name.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Returns Engine's code.
	 *
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * Stores payload for future request.
	 *
	 * @param IPayload $payload Payload.
	 * @return void
	 */
	public function setPayload(IPayload $payload): void;

	/**
	 * Returns stored payload.
	 *
	 * @return IPayload|null
	 */
	public function getPayload(): ?IPayload;

	/**
	 * Returns current context.
	 *
	 * @return Context
	 */
	public function getContext(): Context;

	/**
	 * Sets additional parameters for future request to Engine.
	 * Method replace old parameters, if they were set.
	 *
	 * @param array $params Array of parameters (linear array key to value).
	 * @return void
	 */
	public function setParameters(array $params): void;

	/**
	 * Extension of setParameters(), forbids setting by user important engine params,
	 * such as allowed query cost (for example).
	 *
	 * @see setParameters()
	 * @param array $params
	 * @return void
	 */
	public function setUserParameters(array $params): void;

	/**
	 * Returns all current default and custom parameters.
	 *
	 * @return array
	 */
	public function getParameters(): array;

	/**
	 * Write or not history, in depend on $state.
	 *
	 * @param bool $state True to write, false otherwise.
	 * @return void
	 */
	public function setHistoryState(bool $state): void;

	/**
	 * Returns true, if history will be written in current request.
	 *
	 * @return bool
	 */
	public function shouldWriteHistory(): bool;

	/**
	 * Sets ID of history group (-1 - no grouped, 0 - first item of group).
	 *
	 * @param int $groupId
	 * @return void
	 */
	public function setHistoryGroupId(int $groupId): void;

	/**
	 * Returns ID of history group.
	 * -1 - no grouped, 0 - first item of group
	 * @return int
	 */
	public function getHistoryGroupId(): int;

	/**
	 * Writes history, must depend on setHistoryState.
	 *
	 * @param Result $result Engine's work result.
	 * @return void
	 */
	public function writeHistory(Result $result): void;

	/**
	 * Writes error response in history, must depend on isWriteHistory.
	 *
	 * @param Error $error Error instance.
	 * @return void
	 */
	public function writeErrorInHistory(Error $error): void;

	/**
	 * Set analytic data.
	 *
	 * @param string[] $analyticData same data for analytic.
	 * @return void
	 */
	public function setAnalyticData(array $analyticData): void;

	/**
	 * Get analytic data.
	 *
	 * @return array|string[]
	 */
	public function getAnalyticData(): array;

	/**
	 * Prepares raw data and returns Result instance.
	 *
	 * @param mixed $rawResult Raw result.
	 * @return Result
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached): Result;

	/**
	 * Sets callback, that will be called on successful request.
	 * This callback will receive Result object as input parameter.
	 *
	 * @see Result
	 * @param callable $callback Function will be called.
	 * @return void
	 */
	public function onSuccess(callable $callback): void;

	/**
	 * Sets callback, that will be called on any error occurred.
	 * This callback will receive Error object as input parameter.
	 *
	 * @see \Bitrix\Main\Error
	 * @param callable $callback Function will be called.
	 * @return void
	 */
	public function onError(callable $callback): void;

	/**
	 * Makes request to AI Engine. The model will return one or more predicted completions.
	 * After success completions you must execute writeHistory method.
	 *
	 * @return void
	 */
	public function completions(): void;

	/**
	 * Returns Agreement if current Engine has it, null otherwise.
	 *
	 * @return Agreement|null
	 */
	public function getAgreement(): ?Agreement;

	/**
	 * Set skip agreement mode.
	 *
	 * @return void
	 */
	public function skipAgreement():void;

	/**
	 * Returns current skip agreement mode.
	 *
	 * @return bool
	 */
	public function shouldSkipAgreement(): bool;

	/**
	 * Checks that current Engine is available in the current tariff.
	 *
	 * @return bool
	 */
	public function inTariff(): bool;

	/**
	 * Returns true if current Engine is third party application.
	 *
	 * @return bool
	 */
	public function isThirdParty(): bool;

	/**
	 * Returns true if Engine is expired (in REST Application case for example).
	 *
	 * @return bool
	 */
	public function isExpired(): bool;

	/**
	 * Returns true if current Engine must use limits.
	 *
	 * @return bool
	 */
	public function checkLimits(): bool;

	/**
	 * Returns true, if Engine has required Quality.
	 *
	 * @param Quality $quality
	 * @return bool
	 */
	public function hasQuality(Quality $quality): bool;

	/**
	 * Check if Engine recommended to use for Quality
	 *
	 * @param Quality|null $quality
	 * @return bool
	 */
	public function isPreferredForQuality(?Quality $quality = null): bool;

	/**
	 * Return true, if Engine cache is allow.
	 *
	 * @return bool
	 */
	public function isCache(): bool;

	/**
	 * Set cache for Engine result.
	 *
	 * @param bool $cache
	 *
	 * @return void
	 */
	public function setCache(bool $cache): void;


	/**
	 * Get response json mode.
	 *
	 * @return bool
	 */
	public function getResponseJsonMode(): bool;

	/**
	 * Set response json mode.
	 *
	 * @param bool $enable
	 *
	 * @return void
	 */
	public function setResponseJsonMode(bool $enable): void;

}
