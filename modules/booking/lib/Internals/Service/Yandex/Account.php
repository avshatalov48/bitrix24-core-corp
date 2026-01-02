<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Account
{
	private const OPTION_ACCOUNT_ID = 'yandex_account_id';

	private ApiClient $apiClient;

	public function __construct(ApiClient $apiClient)
	{
		$this->apiClient = $apiClient;
	}

	public function register(): Result
	{
		$result = $this->apiClient->register();

		if ($result->isSuccess())
		{
			$accountId = isset($result->getData()['accountId']) ? (int)$result->getData()['accountId'] : 0;
			if (!$accountId)
			{
				$result->addError(new Error('Account id is not specified'));

				return $result;
			}

			$this->installCompanyFeedSenderAgent();
			$this->saveAccountId($accountId);
		}

		return $result;
	}

	public function unregister(): Result
	{
		$result = $this->apiClient->unregister();

		if ($result->isSuccess())
		{
			$this->uninstallCompanyFeedSenderAgent();
			$this->removeAccountId();
		}

		return $result;
	}

	public function isRegistered(): bool
	{
		return $this->getAccountId() !== null;
	}

	private function getAccountId(): int|null
	{
		$accountId = Option::get('booking', self::OPTION_ACCOUNT_ID, null);

		return $accountId === null ? null : (int)$accountId;
	}

	private function saveAccountId(int $accountId): void
	{
		Option::set('booking', self::OPTION_ACCOUNT_ID, $accountId);
	}

	private function removeAccountId(): void
	{
		Option::delete('booking', ['name' => self::OPTION_ACCOUNT_ID]);
	}

	private function installCompanyFeedSenderAgent(): void
	{
		\CAgent::AddAgent(
			name: CompanyFeedSenderAgent::getName(),
			module: 'booking',
			interval: 60 * 60 * 12, // 12 hours
			next_exec: ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 6 * 60 * 60, 'FULL'),
			existError: false
		);
	}

	private function uninstallCompanyFeedSenderAgent(): void
	{
		\CAgent::RemoveAgent(name: CompanyFeedSenderAgent::getName(), module: 'booking');
	}
}
