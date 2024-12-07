<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Disk;
use Bitrix\Forum\MessageTable;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Emoji;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Tasks\FileUploader\TaskResultController;
use Bitrix\Tasks\Integration\Disk as TaskDisk;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Rest\Controllers\Task\Result as TaskResult;
use Bitrix\TasksMobile\Dto\TaskResultDto;
use Bitrix\TasksMobile\Provider\DiskFileProvider;
use Bitrix\UI\FileUploader\Uploader;

Loader::requireModule('forum');

class Result extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'list',
		];
	}

	public function addAction(int $taskId, array $commentData): ?TaskResultDto
	{
		$commentData = $this->processUploadedFiles($taskId, 0, $commentData);
		$commentData = $this->replaceUploadedFilesInText($commentData);

		$commentAddResult = Comment::add($taskId, $commentData);
		if (!$commentAddResult->isSuccess())
		{
			return null;
		}

		$commentId = $commentAddResult->getData()['ID'];

		$result = $this->forward(new TaskResult(), 'addFromComment', ['commentId' => $commentId]);
		$result = $this->fillWithCommentText([$result])[0];
		$result = $this->fillWithCommentFiles([$result])[0];

		return $this->prepareItems([$result])[0];
	}

	public function updateAction(int $taskId, int $commentId, array $commentData): ?TaskResultDto
	{
		$commentData = $this->processUploadedFiles($taskId, $commentId, $commentData);
		$commentData = $this->replaceUploadedFilesInText($commentData);

		$commentUpdateAction = Comment::update($commentId, $commentData, $taskId);
		if (!$commentUpdateAction->isSuccess())
		{
			return null;
		}

		$result = $this->forward(
			new TaskResult(),
			'getByCommentId',
			[
				'taskId' => $taskId,
				'commentId' => $commentId,
			],
		);
		$result = $this->fillWithCommentText([$result])[0];
		$result = $this->fillWithCommentFiles([$result])[0];

		return $this->prepareItems([$result])[0];
	}

	private function processUploadedFiles(int $taskId, int $commentId, array $commentData): array
	{
		$existingFiles = ($commentData['EXISTING_FILES'] ?? []);
		$uploadedFiles = ($commentData['UPLOADED_FILES'] ?? []);
		unset(
			$commentData['EXISTING_FILES'],
			$commentData['UPLOADED_FILES'],
		);

		$commentData['UF_FORUM_MESSAGE_DOC'] = $existingFiles;

		if (empty($uploadedFiles))
		{
			return $commentData;
		}

		$controller = new TaskResultController([
			'taskId' => $taskId,
			'commentId' => $commentId,
		]);
		$uploader = new Uploader($controller);
		$pendingFiles = $uploader->getPendingFiles($uploadedFiles);
		$uploadedFiles = array_flip($uploadedFiles);

		foreach ($pendingFiles as $pendingFile)
		{
			$fileId = $pendingFile->getFileId();
			$fileToken = $pendingFile->getId();

			if (!$fileId)
			{
				continue;
			}

			$addingResult = TaskDisk::addFile($fileId);
			if ($addingResult->isSuccess())
			{
				$attachmentId = $addingResult->getData()['ATTACHMENT_ID'];

				$commentData['UF_FORUM_MESSAGE_DOC'][] = $attachmentId;
				$commentData['FILES_REPLACE'][$uploadedFiles[$fileToken]] = $attachmentId;
			}
		}
		$pendingFiles->makePersistent();

		return $commentData;
	}

	private function replaceUploadedFilesInText(array $commentData): array
	{
		$replacements = $commentData['FILES_REPLACE'];
		$commentData['POST_MESSAGE'] = preg_replace_callback(
			'/\[disk file id=([^]]+)]/',
			function ($matches) use ($replacements) {
				if (isset($replacements[$matches[1]]))
				{
					return "[disk file id={$replacements[$matches[1]]}]";
				}

				return $matches[0];
			},
			$commentData['POST_MESSAGE'],
		);
		unset($commentData['FILES_REPLACE']);

		return $commentData;
	}

	public function deleteAction(int $commentId): void
	{
		$this->forward(new TaskResult(), 'deleteFromComment', ['commentId' => $commentId]);
	}

	public function listAction(int $taskId): ?array
	{
		$results = $this->forward(new TaskResult(), 'list', ['taskId' => $taskId]);

		if (is_null($results))
		{
			return null;
		}

		$results = $this->fillWithCommentText($results);
		$results = $this->fillWithCommentFiles($results);

		return [
			'results' => $this->prepareItems($results),
			'users' => $this->getUsersData($results),
		];
	}

	private function fillWithCommentText(array $results): array
	{
		foreach ($results as $key => $result)
		{
			$message = MessageTable::getById($result['commentId'])->fetchObject();
			if (!$message)
			{
				unset($results[$key]);
				continue;
			}

			$text = $message->getPostMessage();
			$text = htmlspecialchars_decode($text, ENT_QUOTES);

			$results[$key]['text'] = $text;
		}

		return $results;
	}

	private function fillWithCommentFiles(array $results): array
	{
		$userFieldManager = Disk\Driver::getInstance()->getUserFieldManager();

		foreach ($results as $key => $result)
		{
			$results[$key]['files'] = [];

			$attachedObjects = $userFieldManager->getAttachedObjectByEntity(
				'FORUM_MESSAGE',
				$result['commentId'],
				'UF_FORUM_MESSAGE_DOC',
			);
			foreach ($attachedObjects as $object)
			{
				$objectId = $object->getId();
				$results[$key]['files'][$objectId] = $objectId;
			}
		}

		$fileIds = array_column($results, 'files');
		$fileIds = array_merge(...$fileIds);
		$fileIds = array_unique($fileIds);

		$attachments = (new DiskFileProvider())->getDiskFileAttachments($fileIds);

		foreach ($results as $key => $result)
		{
			foreach ($result['files'] as $fileId)
			{
				if ($attachments[$fileId])
				{
					$results[$key]['files'][$fileId] = $this->convertKeysToCamelCase($attachments[$fileId]);
				}
			}
		}

		return $results;
	}

	/**
	 * @param array $results
	 * @return TaskResultDto[]
	 */
	private function prepareItems(array $results): array
	{
		$converter = new Converter(
			Converter::KEYS
			| Converter::TO_SNAKE
			| Converter::TO_UPPER
			| Converter::RECURSIVE
		);
		$preparedResults = array_map(
			static fn (array $result) => (
				TaskResultDto::make([
					'id' => $result['id'],
					'taskId' => $result['taskId'],
					'commentId' => $result['commentId'],
					'createdBy' => $result['createdBy'],
					'createdAt' => $result['createdAt']->getTimeStamp(),
					'status' => $result['status'],
					'text' => Emoji::decode($result['text']),
					'files' => $converter->process($result['files']),
				])
			),
			$results,
		);

		return array_values($preparedResults);
	}

	private function getUsersData(array $results): array
	{
		$currentUserId = (int)$this->getCurrentUser()->getId();
		$userIds = [];

		foreach ($results as $result)
		{
			$userIds[] = (int)$result['createdBy'];
		}
		$userIds = array_filter($userIds, fn ($userId) => ($userId !== $currentUserId));

		return UserRepository::getByIds($userIds);
	}
}
