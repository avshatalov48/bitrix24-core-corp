<?php

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Document\Flipchart\Cloud\SignBoardConfig;
use Bitrix\Disk\User;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Web\JWT;

class JwtService
{
	private User $user;
	private array $webhookGetParams = [];
	private ?array $dataFromProxy = null;

	public function __construct(?User $user = null)
	{
		if (!$user)
		{
			/** @var \CUser $USER */
			global $USER;

			$user = User::loadById($USER->getId());
		}
		$this->user = $user;
	}

	public function generateToken(bool $readOnly = false, array $additionalData = []): ?string
	{
		$secret = Configuration::getJwtSecret();
		$ttl = Configuration::getJwtTtl();
		$oldLeeway = JWT::$leeway;

		if (!array_key_exists('webhook_url', $additionalData))
		{
			$webhookUrl = Configuration::getWebhookUrl();
		}
		else
		{
			$webhookUrl = $additionalData['webhook_url'];
		}
		if ($this->webhookGetParams)
		{
			$params = http_build_query($this->webhookGetParams);
			$sign = str_contains($webhookUrl, '?') ? '&' : '?';
			$webhookUrl .= $sign . $params;
		}

		$data = [
			'access_level' => $readOnly ? 'read' : 'write',
			'can_edit_board' => !$readOnly,
			'webhook_url' => $webhookUrl,
		];

		if ($additionalData)
		{
			$data = array_merge($data, $additionalData);
		}

		if (empty($data['user_id']))
		{
			$data['user_id'] = (string)$this->user->getId();
		}
		if (empty($data['username']))
		{
			$data['username'] = $this->user->getLogin();
		}
		if (!array_key_exists('avatar_url', $data))
		{
			$urlManager = UrlManager::getInstance();
			$avatarUrl = $urlManager->getHostUrl() . $this->user->getAvatarSrc();
			$data['avatar_url'] = $avatarUrl;
		}

		if (Configuration::isUsingDocumentProxy())
		{
			if (!$this->dataFromProxy && !$this->signDataOnProxy($data) && !$this->signDataOnProxy($data))
			{
				return null;
			}

			return $this->getTokenFromProxy();
		}

		JWT::$leeway = $ttl;
		$result = JWT::encode($data, $secret);

		JWT::$leeway = $oldLeeway;

		return $result;
	}

	public function getTokenFromProxy(): ?string
	{
		return $this->dataFromProxy['token'] ?? null;
	}

	public function getAppUrlFromProxy(): ?string
	{
		return $this->dataFromProxy['app_url'] ?? null;
	}

	public function getApiUrlFromProxy(): ?string
	{
		return $this->dataFromProxy['api_url'] ?? null;
	}

	protected function signDataOnProxy($data)
	{
		$cloudRegistrationData = Configuration::getCloudRegistrationData();
		if ($cloudRegistrationData)
		{
			$configSigner = new SignBoardConfig($cloudRegistrationData['serverHost']);
			$signedData = $configSigner->sign($data);
			if ($signedData->isSuccess())
			{
				$this->dataFromProxy = $signedData->getData();
				return true;
			}
		}
		return false;
	}

	public function decodeWebhookData(string $data): ?array
	{
		$cloudRegistrationData = Configuration::getCloudRegistrationData();
		if ($cloudRegistrationData)
		{
			$configSigner = new SignBoardConfig($cloudRegistrationData['serverHost']);
			$decoded = $configSigner->decode($data);
			if (!$decoded->isSuccess())
			{
				$decoded = $configSigner->decode($data);
			}
			if ($decoded->isSuccess())
			{
				$data = $decoded->getData();
				if ($data['status'] ?? null)
				{
					return $data['data'];
				}
			}
		}
		return null;
	}

	public function addWebhookGetParam(string $key, string $value): self
	{
		$this->webhookGetParams[$key] = $value;
		return $this;
	}

	public function setWebhookGetParams(array $params): self
	{
		$this->webhookGetParams = $params;
		return $this;
	}
}
