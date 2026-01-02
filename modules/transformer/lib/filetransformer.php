<?php

namespace Bitrix\Transformer;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Transformer\Entity\CommandTable;

abstract class FileTransformer implements InterfaceCallback
{
	/**
	 * @deprecated
	 *
	 * Transformer module handles timeouts on its own. You don't need to check max execution time.
	 */
	public const MAX_EXECUTION_TIME = 14400;

	public const MAX_FILESIZE = 104857600; // 100 Mb

	public const IMAGE = 'jpg';
	public const MD5 = 'md5';
	public const SHA1 = 'sha1';
	public const CRC32 = 'crc32';

	public const CACHE_PATH = '/bx/transformer/command/';

	private const MAX_FAILED_TRANSFORMATIONS = 5;

	/**
	 * Make transformation of a file
	 * @param int|string $file ID from b_file or path to the file.
	 * @param array $formats What to do with the file.
	 * @param string|array $module Module name (one or several). These modules will be included before callback.
	 * @param string|array $callback Callback(s) to call with results.
	 * @param array $params Extra params.
	 * @return \Bitrix\Main\Result
	 */
	public function transform($file, $formats, $module, $callback, $params = [])
	{
		$result = new Result();
		if (empty($formats))
		{
			$result->addError(new Error('Formats is empty'));
		}

		$foundFile = new File($file);
		$publicPath = $foundFile->getPublicPath();
		if (empty($publicPath))
		{
			$result->addError(new Error('File '.$file.' not found'));
		}

		$fileSize = $foundFile->getSize();
		if (!empty($fileSize) && $fileSize > static::MAX_FILESIZE)
		{
			$result->addError(new Error($this->getFileTypeName().' is too big'));

		}

		if (!empty($publicPath) && $this->isFileProbablyCorrupted($publicPath))
		{
			$result->addError(new Error(
				Loc::getMessage('TRANSFORMER_FILE_TRANSFORMER_FILE_CORRUPTED'),
				Command::ERROR_CORRUPTED_FILE,
				[
					'originalMessage' => 'File has been converted with errors too many times, probably corrupted',
					'jsonCode' => 'corruptedFile',
				],
			));
		}

		if (!$result->isSuccess())
		{
			Log::logger()->error(
				'{errors}',
				[
					'errors' => $result->getErrorMessages(),
					'file' => $file,
					'formats' => $formats,
					'fileTypeName' => $this->getFileTypeName(),
				]
			);

			return $result;
		}

		$params['file'] = $publicPath;
		$params['fileSize'] = $fileSize;
		$params['formats'] = $formats;

		return $this->sendCommand($publicPath, $params, (array)$module, (array)$callback);
	}

	private function isFileProbablyCorrupted(string $file): bool
	{
		// it would be better to check formats as well - may be another format will not return an error.
		// but formats are serialized in PARAMS
		// without DB normalization, it's impossible

		$count = CommandTable::getCount([
			'=COMMAND' => $this->getCommandName(),
			'=FILE' => $file,
			'=ERROR_CODE' => Command::ERROR_CONTROLLER_TRANSFORMATION_COMMAND,
		]);

		return $count >= self::MAX_FAILED_TRANSFORMATIONS;
	}

	private function sendCommand(string $file, array $params, array $module, array $callback): Result
	{
		$existingCommandArray = $this->findCommandInProgress($file, $params);

		if (!$existingCommandArray)
		{
			return $this->sendNewCommand($file, $params, $module, $callback);
		}

		$command = Command::initFromArray($existingCommandArray);

		$updateResult = $command->appendCallback($module, $callback);
		if (!$updateResult->isSuccess())
		{
			return $updateResult;
		}

		return (new Result())->setData(['commandId' => $command->getId()]);
	}

	private function findCommandInProgress(string $file, array $params): array | false
	{
		return CommandTable::query()
			->setSelect(['*'])
			->whereNotIn('STATUS', [Command::STATUS_SUCCESS, Command::STATUS_ERROR])
			->where('COMMAND', $this->getCommandName())
			->where('FILE', $file)
			->where('PARAMS', CommandTable::encode($params))
			->addOrder('ID', 'DESC')
			->setLimit(1)
			->fetch()
		;
	}

	private function sendNewCommand(string $file, array $params, array $module, array $callback): Result
	{
		$command = new Command($this->getCommandName(), $params, $module, $callback);

		$result = $command->save();
		if (!$result->isSuccess())
		{
			return $result;
		}

		self::clearInfoCache($file);

		return $command->send($this->getHttp());
	}

	/**
	 * @internal Don't override or use this method. Can be removed anytime.
	 */
	protected function getHttp(): Http
	{
		return new Http();
	}

	/**
	 * @return string
	 */
	abstract protected function getFileTypeName();

	/**
	 * @return string
	 */
	abstract protected function getCommandName();

	final public static function getTransformerCommandName(): string
	{
		return (new static())->getCommandName();
	}

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
		if (empty($publicPath))
		{
			return false;
		}

		$cacheName = md5($publicPath);
		$cachePath = self::CACHE_PATH;
		$cacheExpire = 604800;
		$cacheInstance = Cache::createInstance();

		if ($cacheInstance->initCache($cacheExpire, $cacheName, $cachePath))
		{
			return $cacheInstance->getVars();
		}

		$cacheInstance->startDataCache($cacheExpire);

		$command = Command::getByFile($publicPath);
		if (!$command)
		{
			$cacheInstance->endDataCache(false);

			return false;
		}

		$result = [
			'status' => $command->getStatus(),
			'time' => $command->getTime(),
			'id' => $command->getId(),
			'params' => $command->getParams(),
		];

		$error = $command->getError();
		if (!is_null($error))
		{
			$result['error'] = $error->jsonSerialize();
		}

		$cacheInstance->endDataCache($result);

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

		$foundFile = new File($file);
		$publicPath = $foundFile->getPublicPath();
		if(empty($publicPath))
		{
			return;
		}

		$cacheInstance = Cache::createInstance();
		$cacheInstance->clean(md5($publicPath), self::CACHE_PATH);
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
	public static function call($status, $command, $params, $result = [])
	{
		Log::logger()->debug(
			'callback {class} called with status {status} ({statusText})',
			[
				'class' => static::class,
				'status' => $status,
				'statusText' => Command::getStatusText($status),
				'result' => $result,
			]
		);
		return true;
	}
}
