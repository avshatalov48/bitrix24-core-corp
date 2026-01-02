<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\IO;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Loader::includeModule('biconnector'))
{
	return;
}

class SupersetDashboardDetailAjax extends \Bitrix\Main\Engine\Controller
{
	public function sendDashboardToChatAction(CurrentUser $user, $dialogId, string $content, string $dashboardName, string $fileExtension): ?bool
	{
		if ($fileExtension !== 'jpeg' && $fileExtension !== 'pdf')
		{
			$this->addError(new Error('Wrong file extension.'));

			return null;
		}

		if (
			!Loader::includeModule('disk')
			|| !Loader::includeModule('im')
		)
		{
			$this->addError(new Error('Modules disk and im are required.'));

			return null;
		}

		$chatId = \Bitrix\Im\Dialog::getChatId($dialogId);
		if (!$chatId)
		{
			$this->addError(new Error(Loc::getMessage('BI_SEND_DASHBOARD_TO_CHAT_ERROR_ACCESS'), 'NOT_IN_CHAT'));

			return null;
		}

		$folder = CIMDisk::getFolderModel($chatId);
		if (!$folder)
		{
			$this->addError(new Error(Loc::getMessage('BI_SEND_DASHBOARD_TO_CHAT_ERROR_ACCESS'), 'NO_DISK_FOLDER'));

			return null;
		}

		if ($fileExtension === 'jpeg')
		{
			$content = str_replace('data:image/jpeg;base64,', '', $content);
		}
		$fileContent = base64_decode($content);
		$ds = DIRECTORY_SEPARATOR;
		$targetDir = CTempFile::getDirectoryName(1, 'biconnector' . $ds . md5(uniqid('chat_dashboard_', true)));
		checkDirPath($targetDir);
		$date = (new DateTime())->format('Y-m-d H-i-s');
		$fileName = "$dashboardName $date.$fileExtension";
		$tmpFilePath = IO\Path::normalize($targetDir) . $ds . $fileName;
		IO\File::putFileContents($tmpFilePath, $fileContent);

		$fileArray = CFile::makeFileArray($tmpFilePath);
		$file = $folder->uploadFile(
			$fileArray,
			[
				'NAME' => $fileName,
				'CREATED_BY' => $user->getId(),
			],
			[],
			true
		);

		if (!$file)
		{
			$this->addError(new Error(Loc::getMessage('BI_SEND_DASHBOARD_TO_CHAT_ERROR'), 'SAVE_FILE_ERROR'));

			return null;
		}

		$file->increaseGlobalContentVersion();

		$result = CIMDisk::uploadFileFromDisk(
			$chatId,
			['upload' . $file->getId()],
		);

		if (!is_array($result))
		{
			$this->addError(new Error(Loc::getMessage('BI_SEND_DASHBOARD_TO_CHAT_ERROR_ACCESS'), 'SEND_MESSAGE_ERROR'));

			return null;
		}

		return true;
	}
}
