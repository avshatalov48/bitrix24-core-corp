<?

namespace Bitrix\Transformer;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Result;
use Bitrix\Main\Text\BinaryString;

class FileUploader
{
	const MODULE_PATH = "transformer";
	const MODULE_ID = "transformer";

	/**
	 * Generates filename from command ID and file extension/type.
	 *
	 * @param int $commandId Id of the command to make filename.
	 * @param string $fileKey Extension or name.
	 * @return string
	 */
	private static function getUploadedName($commandId, $fileKey)
	{
		$fileName = '/'.self::MODULE_PATH.'/'.$commandId.'.'.$fileKey;
		return $fileName;
	}

	/**
	 * Get information about uploading of the particular file.
	 * If it can be uploaded to a cloud - get bucket id and chunk.
	 *
	 * @param int $commandId ID of the command.
	 * @param string $fileKey Extension of the file.
	 * @param int $fileSize Full size of the file.
	 * @return array
	 */
	public static function getUploadInfo($commandId, $fileKey, $fileSize)
	{
		$fileName = self::getUploadedName($commandId, $fileKey);
		$bucketId = $maxUploadSizeCloud = $maxUploadSize = 0;
		if(\Bitrix\Main\Loader::includeModule('clouds'))
		{
			$bucket = \CCloudStorage::FindBucketForFile(array('size' => $fileSize, 'MODULE_ID' => self::MODULE_ID), $fileName);
			if($bucket != null)
			{
				if($bucket->init())
				{
					$bucketId = $bucket->ID;
					$maxUploadSizeCloud = $bucket->getService()->getMinUploadPartSize();
					$fileName = Path::combine(\CCloudTempFile::GetDirectoryName($bucket, 1), $fileName);
				}
			}
		}

		if(!$bucketId)
		{
			$fileName = Path::combine(\CTempFile::GetDirectoryName(1), $fileName);
		}

		$maxUploadSizeLocal = min(self::parseSize(ini_get('post_max_size')), self::parseSize(ini_get('upload_max_filesize')));

		if($maxUploadSizeCloud > 0 && $maxUploadSizeCloud < $maxUploadSizeLocal)
		{
			$maxUploadSize = $maxUploadSizeCloud;
		}
		else
		{
			$maxUploadSize = $maxUploadSizeLocal - 1024;
		}

		if($maxUploadSize <= 0)
		{
 			$maxUploadSize = 5*1024*1024;
		}

		return array('name' => $fileName, 'bucket' => $bucketId, 'chunk_size' => $maxUploadSize, 'upload_type' => 'file');
	}

	/**
	 * Try to upload file. If bucket ID is set, try to cloud. Otherwise upload it local.
	 *
	 * @param string $fileName Relative path to the file.
	 * @param string $data Content to save.
	 * @param int $fileSize Full size of the file.
	 * @param bool $isLastPart Is it last part of the file.
	 * @param int $bucket Id of the cloud bucket.
	 * @return Result
	 */
	public static function saveUploadedPart($fileName, $data, $fileSize, $isLastPart, $bucket = 0)
	{
		if($bucket > 0)
		{
			return self::saveUploadedPartToCloud($fileName, $data, $fileSize, $isLastPart, $bucket);
		}
		else
		{
			return self::saveUploadedPartLocal($fileName, $data);
		}
	}

	/**
	 * Try to upload part of the file to the cloud storage with bucket_id = $bucket.
	 *
	 * @param string $fileName Relative path to the file.
	 * @param string $data Content to save.
	 * @param int $fileSize Full size of the file.
	 * @param bool $isLastPart Is it last part of the file.
	 * @param int $bucketId Id of the cloud bucket.
	 * @return Result
	 */
	private static function saveUploadedPartToCloud($fileName, $data, $fileSize, $isLastPart, $bucketId)
	{
		$result = new Result();
		if(\Bitrix\Main\Loader::includeModule('clouds'))
		{
			$bucket = new \CCloudStorageBucket($bucketId);
			if(!$bucket->init())
			{
				$result->addError(new Error('Could not init bucket'));
				return $result;
			}
			$isStarted = true;
			$upload = new \CCloudStorageUpload($fileName);

			if(!$upload->isStarted())
			{
				/** @noinspection PhpParamsInspection */
				$isStarted = $upload->start($bucket, $fileSize);
			}

			if(!$isStarted)
			{
				$result->addError(new Error('Could not start upload'));
			}

			$success = false;
			$fails = 0;
			while($upload->hasRetries())
			{
				if($upload->Next($data, $bucket))
				{
					$success = true;
					break;
				}
				$fails++;
			}
			if(!$success)
			{
				$result->addError(new Error('Could not upload part'));
				return $result;
			}
			elseif($success && $isLastPart)
			{
				if(!$upload->finish())
				{
					$result->addError(new Error('Could not finish upload'));
					return $result;
				}
			}
			$result->setData(array('result' => 'cloud'));
		}
		else
		{
			$result->addError(new Error('Module clouds is not installed'));
		}
		return $result;
	}

	/**
	 * Save part of the file local.
	 *
	 * @param string $fileName Relative path to the file.
	 * @param string $data Content to save.
	 * @return Result
	 */
	private static function saveUploadedPartLocal($fileName, $data)
	{
		$result = new Result();

		$file = new \Bitrix\Main\IO\File($fileName);
		if(!static::isCorrectFile($file))
		{
			$result->addError(new Error('Wrong fileName'));
		}
		if(!$file->putContents($data, \Bitrix\Main\IO\File::APPEND))
		{
			$result->addError(new Error('Cant write local file'));
		}

		return $result;
	}

	public static function isCorrectFile(\Bitrix\Main\IO\File $file): bool
	{
		return (mb_strpos($file->getPath(), \CTempFile::GetAbsoluteRoot()) === 0);
	}

	/**
	 * Finds upload directory and adds to it directory of the module.
	 *
	 * @param string $fileName Basename of the file.
	 * @return string
	 */
	public static function getFullPath($fileName)
	{
		$uploadDirectory = \Bitrix\Main\Config\Option::get("main", "upload_dir", "upload");
		$fileName = $_SERVER['DOCUMENT_ROOT'].'/'.$uploadDirectory.$fileName;
		return $fileName;
	}

	/**
	 * Parse real size in bytes from a string.
	 *
	 * @param string $str Input string, 5M for example.
	 * @return float|int
	 */
	private static function parseSize($str)
	{
		$str = BinaryString::changeCaseToLower($str);
		$res = doubleval($str);
		$suffix = BinaryString::changeCaseToLower(BinaryString::getSubstring($str, -1));
		if($suffix === "k")
		{
			$res *= 1024;
		}
		elseif($suffix === "m")
		{
			$res *= 1048576;
		}
		elseif($suffix === "g")
		{
			$res *= 1048576 * 1024;
		}

		return $res;
	}
}

