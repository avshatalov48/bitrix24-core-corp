<?php

namespace Bitrix\Disk\Document\OnlyOffice\Templates;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\User;
use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\Integration\AbstractEntity;
use Bitrix\Im\Dialog;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\DocumentGenerator;
use Bitrix\Main\Type\DateTime;
use CFile;

final class CreateDocumentByCallTemplateScenario
{
	public const CODE_RESUME = 'resume';

	/**
	 * @var int
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $language;
	/**
	 * @var Call
	 */
	private $call;
	/** @var string */
	private $chatTitle;

	public function __construct(int $userId, Call $call, string $language)
	{
		$this->userId = $userId;
		$this->language = $language;
		$this->call = $call;
	}

	protected function getDefaultFolderForUser(): Folder
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->userId);

		return $userStorage->getFolderForCreatedFiles();
	}

	public function create(int $templateId): Result
	{
		$result = new Result();

		$targetFolder = $this->getDefaultFolderForUser();
		$storage = $targetFolder->getStorage();
		if (!$targetFolder->canAdd($storage->getSecurityContext($this->userId)))
		{
			$result->addError(new Error('Bad rights. Could not add file to the folder.'));

			return $result;
		}

		if (!Loader::includeModule('documentgenerator') || !DocumentGenerator\Driver::getInstance()->isEnabled())
		{
			$result->addError(new Error('documentgenerator is not available'));

			return $result;
		}

		$fileResult = $this->buildFileByTemplate($templateId);
		if (!$fileResult->isSuccess())
		{
			return $fileResult;
		}

		$fileArray = $fileResult->getData()['bfile'];
		$chatTitle = $this->getChatTitle($this->call->getAssociatedEntity());

		$newFile = $targetFolder->uploadFile($fileArray, [
			'NAME' => Loc::getMessage("DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_FILENAME_1", ['#NAME#' => $chatTitle]) . '.docx',
			'CREATED_BY' => $this->userId,
			'CODE' => self::CODE_RESUME,
		], [], true);

		if (!$newFile)
		{
			$result->addErrors($targetFolder->getErrors());

			return $result;
		}

		$result->setData([
			'file' => $newFile,
		]);

		return $result;
	}

	protected function buildFileByTemplate(int $templateId): Result
	{
		$result = new Result();

		$templateManager = new TemplateManager();
		$template = $templateManager->getById($templateId);
		if (!$template)
		{
			$result->addError(new Error("Could find template {$templateId}"));

			return $result;
		}

		$templateFile = new File($template['path']);
		if (!$templateFile->isReadable())
		{
			$result->addError(new Error('Could not read file ' . $template['path']));

			return $result;
		}

		$values = $this->getValuesForTemplate();
		$langMessages = $this->getLangMessages();
		$body = new DocumentGenerator\Body\Docx($templateFile->getContents());
		$body->normalizeContent();

		$result = $body
			->setValues($values)
			->setValues($langMessages)
			->process()
		;
		if (!$result->isSuccess())
		{
			return $result;
		}

		$storage = new DocumentGenerator\Storage\BFile();
		$result = $storage->write(
			$body->getContent(),
			[
				'fileName' => "resume_{$this->call->getId()}.docx",
				'contentType' => $body->getFileMimeType(),
			]
		);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$result->setData([
			'bfile' => CFile::makeFileArray($result->getId()),
		]);

		return $result;
	}

	protected function getValuesForTemplate(): array
	{
		$associatedEntity = $this->call->getAssociatedEntity();
		$userIds = $associatedEntity->getUsers();
		$users = [];
		if ($userIds)
		{
			$users = User::getModelList([
				'filter' => [
					'@ID' => $userIds,
				]
			]);
		}

		$usersName = [];
		foreach ($users as $user)
		{
			$usersName[] = $user->getFormattedName();
		}

		$chatTitle = $associatedEntity ? $this->getChatTitle($associatedEntity) : '';

		$culture = Context::getCurrent()->getCulture();
		$now = (new DateTime())->toUserTime()->format($culture->getShortDateFormat() . ' ' . $culture->getShortTimeFormat());

		return array_map('strip_tags', [
			'TITLE_VALUE' => $chatTitle,
			'TIME_VALUE' => $now,
			'MEMBERS_VALUE' => implode(', ', $usersName),
		]);
	}

	protected function getChatTitle(AbstractEntity $associatedEntity): string
	{
		if (!$this->chatTitle)
		{
			$dialogId = $associatedEntity->getEntityId($this->userId);
			$title = Dialog::getTitle($dialogId, $this->userId);
			if (\Bitrix\Im\Common::isChatId($dialogId))
			{
				$this->chatTitle = $title;
			}
			else
			{
				$this->chatTitle = Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DIALOG_NAME', ['#NAME#' => $title]);
			}
		}

		return $this->chatTitle;
	}

	protected function getLangMessages(): array
	{
		return [
			'TITLE' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_TITLE'),
			'DATETIME' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_DATETIME'),
			'MEMBERS' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_MEMBERS'),
			'RESUME_SUBTITLE' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_RESUME_SUBTITLE'),
			'RESUME_NOTICE' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_RESUME_NOTICE'),
			'RESUME_LIST_1' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_RESUME_LIST_1'),
			'RESUME_LIST_2' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_RESUME_LIST_2'),
			'RESUME_LIST_3' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_RESUME_LIST_3'),
			'CONCLUSION' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_CONCLUSION'),
			'CONCLUSION_NOTICE' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_CONCLUSION_NOTICE_PLACEHOLDER'),
			'CONCLUSION_LIST_1' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_CONCLUSION_LIST_1'),
			'CONCLUSION_LIST_2' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_CONCLUSION_LIST_2'),
			'CONCLUSION_LIST_3' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_CONCLUSION_LIST_3'),
			'COMMENTS_TITLE' => Loc::getMessage('DISK_DOCUMENT_OO_DOC_BY_CALL_TEMPLATE_DOC_COMMENTS_TITLE'),
		];
	}
}