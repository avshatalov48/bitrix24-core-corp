<?
use Bitrix\Main\Web\Json;
use Bitrix\Transformer\Command;
use Bitrix\Transformer\FileUploader;
use Bitrix\Transformer\File;

global $APPLICATION;

if(is_object($APPLICATION))
	$APPLICATION->RestartBuffer();

if(!\Bitrix\Main\Loader::includeModule('transformer'))
{
	echo Json::encode(array(
		'error' => array(
			'code' => 'MODULE_NOT_INSTALLED',
			'msg' => 'Module transformer isn`t installed'
		)
	));
	return;
}

$httpRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
$id = $httpRequest->getQuery('id');
if(!empty($id))
{
	$command = Command::getByGuid($id);
	if(!$command)
	{
		$message = 'Command '.$id.' not found';
		\Bitrix\Transformer\Log::write($message);
		echo Json::encode(array(
			'error' => $message,
		));
		return;
	}
	if($command->getStatus() != Command::STATUS_SEND)
	{
		$message = 'Error: Wrong command status '.$command->getStatus();
		\Bitrix\Transformer\Log::write($message);
		echo Json::encode(array(
			'error' => $message,
		));
		return;
	}
	$fileSize = $httpRequest->getPost('file_size');
	if($httpRequest->getPost('upload') == 'where')
	{
		$fileId = $httpRequest->getPost('file_id');
		$uploadInfo = FileUploader::getUploadInfo($id, $fileId, $fileSize);
		echo Json::encode($uploadInfo);
		return;
	}
	$fileName = $httpRequest->getPost('file_name');
	$uploadedFile = $httpRequest->getFile('file');
	if($uploadedFile)
	{
		if(isset($uploadedFile['error']) && $uploadedFile['error'] > 0)
		{
			$message = 'client web-server error uploading file part';
			echo Json::encode(array(
				'error' => $message,
			));
			return;
		}
		$file = fopen($uploadedFile['tmp_name'], 'rb');
		if($file)
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
	if($fileName && $filePart)
	{
		$saveResult = FileUploader::saveUploadedPart($fileName, $filePart, $fileSize, $isLastPart, $bucket);
		if($saveResult->isSuccess())
		{
			$saveData = $saveResult->getData();
			$message = 'file saved to '.$saveData['result'];
			echo Json::encode(array(
				'success' => $message,
			));
		}
		else
		{
			$message = $saveResult->getErrorMessages();
			\Bitrix\Transformer\Log::write($message);
			echo Json::encode(array(
				'error' => $message,
				)
			);
		}
		return;
	}
	$error = $httpRequest->getPost('error');
	$errorCode = intval($httpRequest->getPost('errorCode'));
	if($error || $errorCode)
	{
		if($errorCode && !$error)
		{
			$error = $errorCode;
		}
		if(!$errorCode)
		{
			$errorCode = Command::ERROR_CONTROLLER_UNKNOWN_ERROR;
		}
		\Bitrix\Transformer\Log::write('Error on server: '.$error);
		$result = $httpRequest->getPost('result');
		if(is_array($result['files']))
		{
			foreach($result['files'] as $key => $fileName)
			{
				$file = new File($fileName);
				$file->delete();
			}
		}
		$command->updateStatus(Command::STATUS_ERROR, $error, $errorCode);
		$command->callback(array('error' => $error));
		echo Json::encode(array(
			'success' => 'error received'
		));
		return;
	}
	$finish = ($httpRequest->getPost('finish') == 'y');
	if($finish)
	{
		/** @var File[] $files */
		$files = array();
		$command->updateStatus(Command::STATUS_UPLOAD);
		$result = $httpRequest->getPost('result');
		if(!is_array($result['files']))
		{
			$result['files'] = [];
		}
		foreach($result['files'] as $key => $fileName)
		{
			$files[$key] = new File($fileName);
			$result['files'][$key] = $files[$key]->getAbsolutePath();
		}
		try
		{
			if($command->callback($result))
			{
				$command->updateStatus(Command::STATUS_SUCCESS);
				$command->push();
				echo Json::encode(array(
					'success' => 'OK'
				));
			}
			else
			{
				$command->updateStatus(Command::STATUS_ERROR, 'Callback error', Command::ERROR_CALLBACK);
				echo Json::encode(array(
					'error' => 'Error of the callback',
				));
			}
		}
		finally
		{
			foreach($result['files'] as $key => $file)
			{
				$files[$key]->delete();
			}
		}
		return;
	}
}

echo Json::encode(array(
	'error' => 'Wrong request',
));