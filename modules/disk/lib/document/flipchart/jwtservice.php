<?php

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\User;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Web\JWT;

class JwtService
{
	private User $user;

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

	public function generateToken(bool $readOnly = false, array $additionalData = []): string
	{
		$secret = Configuration::getJwtSecret();
		$ttl = Configuration::getJwtTtl();
		$oldLeeway = JWT::$leeway;
		JWT::$leeway = $ttl;

		$urlManager = UrlManager::getInstance();
		$avatarUrl = $urlManager->getHostUrl() . $this->user->getAvatarSrc();

		$data = [
			'user_id' => (string)$this->user->getId(),
			'username' => $this->user->getLogin(),
			'avatar_url' => $avatarUrl,
			'access_level' => $readOnly ? 'read' : 'write',
			'can_edit_board' => !$readOnly,
			'webhook_url' => Configuration::getWebhookUrl(),
		];

		if ($additionalData)
		{
			$data = array_merge($data, $additionalData);
		}

		$result = JWT::encode($data, $secret);

		JWT::$leeway = $oldLeeway;

		return $result;
	}
}