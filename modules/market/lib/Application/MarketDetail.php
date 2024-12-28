<?php

namespace Bitrix\Market\Application;

use Bitrix\Market\Detail\DetailType;
use Bitrix\Market\Rest\Actions;
use Bitrix\Market\Rest\Transport;

class MarketDetail
{
	private string $appCode;
	private DetailType $type;

	private int $version = 0;
	private string $checkHash = '';
	private string $installHash = '';

	private string $additionalContent = '';
	private string $additionalMarketAction = '';

	public function __construct(string $appCode, DetailType $type)
	{
		$this->appCode = $appCode;
		$this->type = $type;
	}

	public function getAppCode(): string
	{
		return $this->appCode;
	}

	public function setVersion(int $version): void
	{
		$this->version = $version;
	}

	public function getVersion(): int
	{
		return $this->version;
	}

	public function setCheckHash(string $checkHash): void
	{
		$this->checkHash = $checkHash;
	}

	public function getCheckHash(): string
	{
		return $this->checkHash;
	}

	public function setInstallHash(string $installHash): void
	{
		$this->installHash = $installHash;
	}

	public function getInstallHash(): string
	{
		return $this->installHash;
	}

	public function getAdditionalContent(): string
	{
		return $this->additionalContent;
	}

	public function getAdditionalMarketAction(): string
	{
		return $this->additionalMarketAction;
	}

	public function getInfo(): array
	{
		global $USER;

		$result = [];

		$detailMethod = $this->type->getRestMethod();

		$detailMethodFields = [
			$detailMethod->getMethodName() => [
				$detailMethod->getMethodName(),
				$detailMethod->getParams($this),
			],
		];

		$ratingMethodFields = [
			Actions::METHOD_GET_REVIEWS => [
				Actions::METHOD_GET_REVIEWS,
				[
					'filter_app' => $this->appCode,
					'filter_user' => $USER->GetID(),
				],
			],
		];

		$batch = array_merge($detailMethodFields, $ratingMethodFields);

		$response = Transport::instance()->batch($batch);
		if (isset($response[$detailMethod->getMethodName()]['ITEMS']) && is_array($response[$detailMethod->getMethodName()]['ITEMS'])) {
			$result = $response[$detailMethod->getMethodName()]['ITEMS'];

			$this->additionalContent = $response[$detailMethod->getMethodName()]['ADDITIONAL_CONTENT'] ?? '';
			$this->additionalMarketAction = $response[$detailMethod->getMethodName()]['ADDITIONAL_MARKET_ACTION'] ?? '';

			if (is_array($response[Actions::METHOD_GET_REVIEWS])) {
				$result['REVIEWS'] = $response[Actions::METHOD_GET_REVIEWS];
			}
		}

		return $result;
	}
}