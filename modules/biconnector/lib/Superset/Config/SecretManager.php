<?php

namespace Bitrix\BIConnector\Superset\Config;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;

final class SecretManager
{
	public function __construct(private ConfigContainer $config)
	{}

	public static function getManager(): self
	{
		return new SecretManager(ConfigContainer::getConfigContainer());
	}

	public function encryptMessage(string $message): Result
	{
		$result = new Result();
		$secret = $this->config->getPortalId();

		if (!$secret)
		{
			$result->addError(new Error('Empty secret.', 'EMPTY_SECRET'));

			return $result;
		}

		try
		{
			$cipher = new Cipher();
			$encryptMessage = base64_encode($cipher->encrypt($message, $secret));
		}
		catch (SecurityException $securityException)
		{
			$result->addError(new Error("Cipher doesn't happy.", 'CIPHER_EXCEPTION'));

			return $result;
		}

		return $result->setData([
			'message' => $encryptMessage,
		]);
	}
}