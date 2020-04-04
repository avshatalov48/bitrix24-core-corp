<?php

namespace Bitrix\Transformer;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class FileTransformer implements InterfaceCallback
{
	const MAX_EXECUTION_TIME = 14400;

	const MAX_FILESIZE = 104857600; // 100 Mb

	const IMAGE = 'jpg';
	const MD5 = 'md5';
	const SHA1 = 'sha1';
	const CRC32 = 'crc32';

	const CACHE_PATH = '/bx/transformer/command/';

	/**
	 * Make transformation of a file
	 * @param int|string $file ID from b_file or path to the file.
	 * @param array $formats What to do with the file.
	 * @param string|array $module Module name (one or several). These modules will be included before callback.
	 * @param string|array $callback Callback(s) to call with results.
	 * @param array $params Extra params.
	 * @return \Bitrix\Main\Result
	 */
	public function transform($file, $formats, $module, $callback, $params = array())
	{
		$result = new Result();
		if(empty($formats))
		{
			$result->addError(new Error('Formats is empty'));
		}
		$foundFile = new File($file);
		$publicPath = $foundFile->getPublicPath();
		if(empty($publicPath))
		{
			$result->addError(new Error('File '.$file.' not found'));
		}
		$fileSize = $foundFile->getSize();
		if(!empty($fileSize) && $fileSize > static::MAX_FILESIZE)
		{
			$result->addError(new Error($this->getFileTypeName().' is too big'));
		}
		if(!$result->isSuccess())
		{
			Log::write($result->getErrorMessages());
			return $result;
		}
		$params['file'] = $publicPath;
		$params['fileSize'] = $fileSize;
		$params['formats'] = $formats;
		$command = new Command($this->getCommandName(), $params, $module, $callback);
		$result = $command->save();
		if($result->isSuccess())
		{
			$http = new Http();
			$result = $command->send($http);
			self::clearInfoCache($file);
		}
		return $result;
	}

	/**
	 * @return string
	 */
	abstract protected function getFileTypeName();

	/**
	 * @return string
	 */
	abstract protected function getCommandName();

	/**
	 * Get information of the last transformation command of the file.
	 * array
	 *  status - int
	 *  time - DateTime
	 *  id - int
	 *
	 * @param int|string $file - ID in b_file or path.
	 * @return bool|array
	 */
	public static function getTransformationInfoByFile($file)
	{
		$foundFile = new File($file);
		$publicPath = $foundFile->getPublicPath();
		if(empty($publicPath))
		{
			return false;
		}
		$result = false;
		$cacheName = md5($file);
		$cachePath = self::CACHE_PATH;
		$cacheExpire = 604800;
		$cacheInstance = Cache::createInstance();
		if($cacheInstance->initCache($cacheExpire, $cacheName, $cachePath))
		{
			$result = $cacheInstance->getVars();
		}
		else
		{
			self::clearInfoCache($file);
			$cacheInstance->startDataCache($cacheExpire);
			$command = Command::getByFile($publicPath);
			if($command)
			{
				$result = array(
					'status' => $command->getStatus(),
					'time' => $command->getTime(),
					'id' => $command->getId(),
					'params' => $command->getParams(),
				);
			}
			$cacheInstance->endDataCache($result);
		}

		return $result;
	}

	/**
	 * Clears cache of command info on $file.
	 *
	 * @param int|string $file - ID in b_file or path.
	 */
	public static function clearInfoCache($file)
	{
		if(empty($file))
		{
			return;
		}
		$cacheInstance = Cache::createInstance();
		$cacheInstance->clean(md5($file), self::CACHE_PATH);
	}

	/**
	 * Example callback that will be invoked after transformation to process results.
	 *
	 * @param int $status Status of the command in b_transformer_command.
	 * @param string $command Name of the command.
	 * @param array $params Parameters of the command.
	 * @param array $result Result of the command from controller
	 * array (
	 *      'files' => array (
	 *          'extension' => 'url',
	 *          ...
	 *      )
	 *      'md5' => 'md5 sum',
	 *      'crc32' => 'crc32 sum',
	 *      'sha1' => 'sha1 sum',
	 *      ...
	 * ).
	 * @return mixed
	 */
	public static function call($status, $command, $params, $result = array())
	{
		$str = 'callback '.__CLASS__.' called with status '.Command::getStatusText($status).' and results '.print_r($result, 1);
		Log::write($str);
		return true;
	}
}