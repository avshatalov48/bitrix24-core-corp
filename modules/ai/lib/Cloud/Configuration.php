<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\AI\Cloud\Dto\RegistrationDto;
use Bitrix\Main\Config;
use Bitrix\Main\Config\Option;

final class Configuration
{
	private const AI_PROXY_TEMP_SECRET = 'ai_proxy_tempSecret';
	private const AI_PROXY_CLIENT_ID = 'ai_proxy_clientId';
	private const AI_PROXY_SECRET_KEY = 'ai_proxy_secretKey';
	private const AI_PROXY_SERVER_HOST = 'ai_proxy_serverHost';

	public function storeTempSecretForDomainVerification(string $tempSecret): void
	{
		Option::set('ai', self::AI_PROXY_TEMP_SECRET, $tempSecret);
	}

	public function getTempSecretForDomainVerification(): ?string
	{
		return Option::get('ai', self::AI_PROXY_TEMP_SECRET, null);
	}

	public function resetTempSecretForDomainVerification(): void
	{
		Option::set('ai', self::AI_PROXY_TEMP_SECRET, null);
	}

	public function resetCloudRegistration(): void
	{
		Option::delete('ai', [
			'name' => self::AI_PROXY_CLIENT_ID,
		]);
		Option::delete('ai', [
			'name' => self::AI_PROXY_SECRET_KEY,
		]);
		Option::delete('ai', [
			'name' => self::AI_PROXY_SERVER_HOST,
		]);
	}

	/**
	 * Checks if cloud registration data is stored.
	 * @return bool
	 */
	public function hasCloudRegistration(): bool
	{
		return $this->getCloudRegistrationData() !== null;
	}

	public function getCloudRegistrationData(): ?RegistrationDto
	{
		$data = array_filter([
			'clientId' => Option::get('ai', self::AI_PROXY_CLIENT_ID),
			'secretKey' => Option::get('ai', self::AI_PROXY_SECRET_KEY),
			'serverHost' => Option::get('ai', self::AI_PROXY_SERVER_HOST),
		]);

		if (\count($data) !== 3)
		{
			return null;
		}

		return new RegistrationDto(
			$data['clientId'],
			$data['secretKey'],
			$data['serverHost']
		);
	}

	public function storeCloudRegistration(RegistrationDto $registrationDto): void
	{
		Option::set('ai', self::AI_PROXY_CLIENT_ID, $registrationDto->clientId);
		Option::set('ai', self::AI_PROXY_SECRET_KEY, $registrationDto->secretKey);
		Option::set('ai', self::AI_PROXY_SERVER_HOST, $registrationDto->serverHost);
	}

	public function getServerHost(): ?string
	{
		return $this->getCloudRegistrationData()?->serverHost;
	}

	public function getServerListEndpoint(): ?string
	{
		$b24aiPrimary = Config\Configuration::getInstance()->get('aiproxy');
		$b24ai = Config\Configuration::getInstance('ai')->get('aiproxy');

		return $b24aiPrimary['serverListEndpoint'] ?? $b24ai['serverListEndpoint'];
	}
}