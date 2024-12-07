<?php

use Bitrix\Main\Web\Json;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\File;
use Bitrix\Transformer\FileUploader;

global $APPLICATION;

if (is_object($APPLICATION))
{
	$APPLICATION->RestartBuffer();
}

if (!\Bitrix\Main\Loader::includeModule('transformer'))
{
	echo Json::encode([
		'error' => [
			'code' => 'MODULE_NOT_INSTALLED',
			'msg' => 'Module transformer isn`t installed'
		]
	]);

	return;
}

$httpRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
$id = $httpRequest->getQuery('id');
if (empty($id))
{
	echo Json::encode([
		'error' => 'Wrong request',
	]);

	return;
}

$command = Command::getByGuid($id);
if (!$command)
{
	$message = 'Command '.$id.' not found';
	\Bitrix\Transformer\Log::logger()->error($message, ['guid' => $id]);
	echo Json::encode([
		'error' => $message,
	]);

	return;
}

$connection = \Bitrix\Main\Application::getConnection();
$lockName = 'transformer_result_' . $connection->getSqlHelper()->forSql($id);
if (!$connection->lock($lockName))
{
	$message = 'Could not acquire lock for command ' . $id;
	\Bitrix\Transformer\Log::logger()->error($message, ['guid' => $id]);
	echo Json::encode([
		'error' => $message,
	]);

	return;
}

if ($command->getStatus() != Command::STATUS_SEND)
{
	$message = 'Error: Wrong command status '.$command->getStatus();
	\Bitrix\Transformer\Log::logger()->error($message, ['guid' => $id, 'status' => $command->getStatus()]);
	echo Json::encode([
		'error' => $message,
	]);

	$connection->unlock($lockName);
	return;
}

$fileSize = $httpRequest->getPost('file_size');

//region get upload info
if ($httpRequest->getPost('upload') === 'where')
{
	$fileId = $httpRequest->getPost('file_id');
	$uploadInfo = FileUploader::getUploadInfo($id, $fileId, $fileSize);
	echo Json::encode($uploadInfo);

	$connection->unlock($lockName);
	return;
}
//endregion

//region upload file part
$fileName = $httpRequest->getPost('file_name');
$uploadedFile = $httpRequest->getFile('file');
$filePart = null;
if ($uploadedFile)
{
	if (isset($uploadedFile['error']) && $uploadedFile['error'] > 0)
	{
		$message = 'client web-server error uploading file part';
		echo Json::encode([
			'error' => $message,
		]);

		$connection->unlock($lockName);
		return;
	}
	$file = fopen($uploadedFile['tmp_name'], 'rb');
	if ($file)
	{
		$filePart = fread($file, filesize($uploadedFile['tmp_name']));
	}
}
else
{
	$filePart = $httpRequest->getPost('file');
}

$isLastPart = ($httpRequest->getPost('last_part') === 'y');
$bucket = intval($httpRequest->getPost('bucket'));
if ($fileName && $filePart)
{
	$saveResult = FileUploader::saveUploadedPart($fileName, $filePart, $fileSize, $isLastPart, $bucket);
	if ($saveResult->isSuccess())
	{
		$saveData = $saveResult->getData();
		$message = 'file saved to '.$saveData['result'];
		echo Json::encode([
			'success' => $message,
		]);
	}
	else
	{
		$message = $saveResult->getErrorMessages();
		\Bitrix\Transformer\Log::logger()->error(
			'Error uploading file part: {errors}',
			[
				'guid' => $id,
				'errors' => $message,
				'fileName' => $fileName,
				'isLastPart' => $isLastPart,
				'bucket' => $bucket,
				'fileSize' => $fileSize
			]
		);
		echo Json::encode([
			'error' => $message,
			]
		);
	}

	$connection->unlock($lockName);
	return;
}
//endregion

$analyticsRegistrar = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('transformer.integration.analytics.registrar');

//region error
$error = $httpRequest->getPost('error');
$errorCode = intval($httpRequest->getPost('errorCode'));
if ($error || $errorCode)
{
	if ($errorCode && !$error)
	{
		$error = $errorCode;
	}
	if (!$errorCode)
	{
		$errorCode = Command::ERROR_CONTROLLER_UNKNOWN_ERROR;
	}
	\Bitrix\Transformer\Log::logger()->error(
		'Error on server: {error}',
		['error' => $error, 'errorCode' => $errorCode, 'guid' => $id],
	);

	$result = $httpRequest->getPost('result');
	if (is_array($result) && isset($result['files']) && is_array($result['files']))
	{
		foreach ($result['files'] as $key => $fileName)
		{
			$file = new File($fileName);
			$file->delete();
		}
	}
	$command->updateStatus(Command::STATUS_ERROR, $error, $errorCode);

	$analyticsRegistrar->registerCommandFinish($command);

	$command->callback(['error' => $error]);
	echo Json::encode([
		'success' => 'error received'
	]);

	$connection->unlock($lockName);
	return;
}
//endregion

//region finish
$finish = ($httpRequest->getPost('finish') === 'y');
if ($finish)
{
	/** @var File[] $files */
	$files = [];
	$updateStatusResult = $command->updateStatus(Command::STATUS_UPLOAD);
	if (!$updateStatusResult->isSuccess())
	{
		\Bitrix\Transformer\Log::logger()->critical(
			'Cant mark command as uploaded: {errors}',
			['errors' => $updateStatusResult->getErrorMessages(), 'guid' => $id],
		);

		echo Json::encode([
			'error' => 'Could not mark command as uploaded',
		]);

		$connection->unlock($lockName);

		return;
	}

	$analyticsRegistrar->registerCommandFinish($command);

	$result = $httpRequest->getPost('result');
	if (!is_array($result))
	{
		$result = [];
	}
	if (!isset($result['files']) || !is_array($result['files']))
	{
		$result['files'] = [];
	}
	foreach ($result['files'] as $key => $fileName)
	{
		$files[$key] = new File($fileName);
		$result['files'][$key] = $files[$key]->getAbsolutePath();
	}
	try
	{
		if ($command->callback($result))
		{
			$command->updateStatus(Command::STATUS_SUCCESS);
			$command->push();
			echo Json::encode([
				'success' => 'OK'
			]);
		}
		else
		{
			$command->updateStatus(Command::STATUS_ERROR, 'Callback error', Command::ERROR_CALLBACK);
			echo Json::encode([
				'error' => 'Error of the callback',
			]);
		}
	}
	finally
	{
		foreach ($result['files'] as $key => $file)
		{
			$files[$key]->delete();
		}
	}

	$connection->unlock($lockName);
	return;
}
//endregion

$connection->unlock($lockName);
