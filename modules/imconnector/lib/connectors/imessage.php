<?php
namespace Bitrix\ImConnector\Connectors;

use \Bitrix\Main\IO\File,
	\Bitrix\Main\Security\Cipher,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Input\ReceivingMessage;

class IMessage extends Base
{
	//User
	/**
	 * Preparation of new user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @return array Given the right format array description user.
	 */
	public function preparationNewUserFields($user): array
	{
		$fields = $this->getBasicFieldsNewUser($user);

		$userNumber = $user['id'];
		if(\CGlobalCounter::Increment($this->idConnector))
		{
			$userNumber = \CGlobalCounter::GetValue($this->idConnector);
		}

		$fields['NAME'] = Loc::getMessage('IMCONNECTOR_IMESSAGE_DEFAULT_USER_NAME') . $userNumber;

		return $fields;
	}

	/**
	 * Preparation of user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @return array Given the right format array description user.
	 */
	public function preparationUserFields($user): array
	{
		//The hash of the data
		return [
			'UF_CONNECTOR_MD5' => md5(serialize($user))
		];
	}


	//File
	/**
	 * Save file
	 *
	 * @param $file
	 * @return bool|int|mixed|string
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function saveFile($file)
	{
		$result = false;

		if (!empty($file['key']))
		{
			$key = $file['key'];

			$file = ReceivingMessage::downloadFile($file);

			if($file)
			{
				$file = self::getDecryptedFile($file, $key);

				$result = \CFile::SaveFile(
					$file,
					Library::MODULE_ID
				);
			}
		}

		return $result;
	}

	/**
	 * Returns file array of decrypted file
	 *
	 * @param array $file
	 * @param mixed $key
	 * @return mixed
	 */
	protected static function getDecryptedFile($file, $key)
	{
		$key = self::convertFileKey($key);
		$newFile = $file;
		$content = File::getFileContents($file['tmp_name']);

		$decryptedContent = self::decryptContent($content, $key);
		$newFilePath = self::getNewFilePath($file['tmp_name']);

		File::putFileContents($newFilePath, $decryptedContent);

		$newFile['tmp_name'] = $newFilePath;

		return $newFile;
	}

	/**
	 * Implements decrypting of aes/ctr-encrypted content
	 * decrypt with AES/CTR/NoPadding algorithm
	 *
	 * @param string $content
	 * @param string $key
	 * @return string
	 */
	protected static function decryptContent($content, $key): string
	{
		$decryptedContent = openssl_decrypt($content, 'AES-256-CTR', $key, OPENSSL_RAW_DATA, self::getIv());

		return $decryptedContent;
	}

	/**
	 * Return iv for business chat encryption requirements
	 *
	 * @return string
	 */
	protected static function getIv(): string
	{
		$iv = '0000000000000000';
		$ivLength = mb_strlen($iv);
		$ivResult = '';
		for ($i = 0; $i < $ivLength; $i++)
		{
			$ivResult .= chr($iv[$i]);
		}

		return $ivResult;
	}

	/**
	 * Converts received file key for file decrypt
	 *
	 * @param string $key
	 * @return bool|float|int|string
	 */
	protected static function convertFileKey($key)
	{
		$result = mb_substr($key, 2);
		$result = pack('H*', $result);

		return $result;
	}

	/**
	 * Implements encrypting content with aes/ctr
	 *
	 * @param string $content
	 * @param string $key
	 * @return bool|string
	 * @throws \Bitrix\Main\Security\SecurityException
	 */
	protected static function encryptContent($content, $key)
	{
		$content = base64_encode($content);
		$cipher = new Cipher();
		$decryptedContent = $cipher->encrypt($content, $key);
		//$decryptedContent = base64_decode($decryptedContent);

		return $decryptedContent;
	}

	/**
	 * Makes new file path from encrypted file path
	 *
	 * @param string $filePath
	 * @return string
	 */
	protected static function getNewFilePath($filePath): string
	{
		$pointPosition = mb_strrpos($filePath, '.');
		$fileName = mb_substr($filePath, 0, $pointPosition);
		$fileExtension = mb_substr($filePath, $pointPosition);

		$newFilePath = $fileName . '-decrypted' . $fileExtension;

		return $newFilePath;
	}
}