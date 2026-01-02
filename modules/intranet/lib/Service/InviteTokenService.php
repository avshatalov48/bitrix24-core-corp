<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Bitrix24\Integration\Network\RegisterSettingsSynchronizer;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\JWT;
use InvalidArgumentException;
use UnexpectedValueException;

final class InviteTokenService
{
	private const JWT_ALGO = 'HS256';

	/**
	 * @param  string $inviteToken
	 *
	 * @return mixed payload or null
	 */
	public function parse(string $inviteToken): mixed
	{
		try
		{
			$secret = $this->getJwtSecret();
			if (empty($secret))
			{
				return null;
			}

			return JWT::decode($inviteToken, $secret, [
				self::JWT_ALGO,
			]);
		}
		catch (InvalidArgumentException|UnexpectedValueException $e)
		{
			return null;
		}
	}

	public function create(mixed $payload): string
	{
		$secret = $this->getJwtSecret();
		if (empty($secret))
		{
			throw new SystemException('Secret is empty');
		}

		return JWT::encode($payload, $secret, self::JWT_ALGO);
	}

	private function getJwtSecret(): ?string
	{
		if (Loader::includeModule('bitrix24'))
		{
			$secret = RegisterSettingsSynchronizer::getRegisterSettings()['INVITE_TOKEN_SECRET'] ?? null;
		}
		else
		{
			$secret = Option::get('socialservices', 'new_user_registration_invite_token_secret', null);
		}

		if (empty($secret))
		{
			$secret = $this->reCreateSecret();
		}

		return $secret;
	}

	private function reCreateSecret(): string
	{
		$secret = Random::getString(12);
		if (Loader::includeModule('bitrix24'))
		{
			RegisterSettingsSynchronizer::setRegisterSettings([
				'INVITE_TOKEN_SECRET' => $secret,
			]);
		}
		else
		{
			Option::set('socialservices', 'new_user_registration_invite_token_secret', $secret);
		}

		return $secret;
	}
}
