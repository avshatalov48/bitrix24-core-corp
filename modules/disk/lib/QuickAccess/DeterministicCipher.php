<?php

declare(strict_types=1);

namespace Bitrix\Disk\QuickAccess;

use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Security\SecurityException;

/**
 * Should be used for deterministic encryption of data.
 * @todo Refactor this class to extend from the \Bitrix\Main\Security\Cipher class.
 * @see \Bitrix\Main\Security\Cipher
 */
final class DeterministicCipher extends Cipher
{
	private string $ivSalt;

	public function setIvSalt(string $ivSalt): void
	{
		$this->ivSalt = $ivSalt;
	}

	private function generateIv($data, $key): string
	{
		return substr(openssl_digest($data . $this->ivSalt . $key, $this->hashAlgorithm, true), 0, $this->ivLength);
	}

	/**
	 * Encrypts the data using the given key.
	 * Uses a deterministic IV based on the data and key.
	 */
	public function encrypt($data, $key): string
	{
		$iv = $this->generateIv($data, $key);

		// Hash the key: we shouldn't use the password itself, it can be weak
		$keyHash = openssl_digest($iv . $key, $this->hashAlgorithm, true);

		if ($this->calculateHash)
		{
			//store the hash to check on reading
			$dataHash = openssl_digest($data, $this->hashAlgorithm, true);
			$data = $dataHash . $data;
		}

		// Encrypt the data
		$encrypted = openssl_encrypt($data, $this->cipherAlgorithm, $keyHash, OPENSSL_RAW_DATA, $iv);
		if ($encrypted === false)
		{
			throw new SecurityException("Encryption failed: " . openssl_error_string());
		}

		// Store IV with encrypted data to use it for decryption
		return $iv . $encrypted;
	}
}