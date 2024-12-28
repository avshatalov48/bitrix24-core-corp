<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\JWT;
use Bitrix\Socialservices\Network;
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
		if (
			Loader::includeModule('bitrix24')
			&& Loader::includeModule('socialservices')
		)
		{
			$secret = Network::getRegisterSettings()['INVITE_TOKEN_SECRET'] ?? null;
			if (empty($secret))
			{
				$secret = $this->reCreateSecret();
			}

			return $secret;
		}

		return null;
	}

	private function reCreateSecret(): string
	{
		$secret = Random::getString(12);

		Network::setRegisterSettings([
			'INVITE_TOKEN_SECRET' => $secret,
		]);

		return $secret;
	}
}
