<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\IO\File;
use Bitrix\Main\Security\Cipher;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Library;

class IMessage extends Base
{
	//Input

	//END Input

	//Output

	//END Output

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

		$fields['NAME'] = Loc::getMessage('IMCONNECTOR_IMESSAGE_DEFAULT_USER_NAME_NEW') . $userNumber;

		return $fields;
	}

	/**
	 * Preparation of user fields before saving or adding.
	 *
	 * @param array $user An array describing the user.
	 * @return array Given the right format array description user.
	 */
	public function preparationUserFields($user, $userId = 0): array
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
	 * @return false|int|string
	 */
	public function saveFile($file)
	{
		$result = false;

		if (!empty($file['key']))
		{
			$key = $file['key'];

			if(
				!empty($file)
				&& is_array($file)
			)
			{
				$file = Library::downloadFile($file);

				if($file !== false)
				{
					$file = $this->getDecryptedFile($file, $key);

					$result = \CFile::SaveFile(
						$file,
						Library::MODULE_ID
					);
				}
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
	protected function getDecryptedFile($file, $key)
	{
		$key = $this->convertFileKey($key);
		$newFile = $file;
		$content = File::getFileContents($file['tmp_name']);

		$decryptedContent = $this->decryptContent($content, $key);
		$newFilePath = $this->getNewFilePath($file['tmp_name']);

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
	protected function decryptContent($content, $key): string
	{
		return openssl_decrypt($content, 'AES-256-CTR', $key, OPENSSL_RAW_DATA, $this->getIv());
	}

	/**
	 * Return iv for business chat encryption requirements
	 *
	 * @return string
	 */
	protected function getIv(): string
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
	protected function convertFileKey($key)
	{
		return pack('H*', mb_substr($key, 2));
	}

	/**
	 * Implements encrypting content with aes/ctr
	 *
	 * @param string $content
	 * @param string $key
	 * @return bool|string
	 * @throws \Bitrix\Main\Security\SecurityException
	 */
	protected function encryptContent($content, $key)
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
	protected function getNewFilePath($filePath): string
	{
		$pointPosition = mb_strrpos($filePath, '.');
		$fileName = mb_substr($filePath, 0, $pointPosition);
		$fileExtension = mb_substr($filePath, $pointPosition);

		return $fileName . '-decrypted' . $fileExtension;
	}
}
