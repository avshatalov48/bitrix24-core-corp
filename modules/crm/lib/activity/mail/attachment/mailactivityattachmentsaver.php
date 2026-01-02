<?php

namespace Bitrix\Crm\Activity\Mail\Attachment;

use Bitrix\Crm\Activity\Mail\Attachment\Dto\AttachmentFile;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\AttachmentFileCollection;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\AttachmentFilesResult;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\AttachmentSaveResult;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\BannedAttachment;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\BannedAttachmentCollection;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\StorageAttachmentId;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\StorageAttachmentIdCollection;
use Bitrix\Crm\Integration\StorageManager;

class MailActivityAttachmentSaver
{
	public function getAttachmentFilesForSave(int $messageId, bool $denyNewContact, int $userId): AttachmentFilesResult
	{
		$attachmentMaxSize = $this->getAttachmentMaxSizeInBytes();

		$filesData = new AttachmentFileCollection();
		$bannedAttachments = new BannedAttachmentCollection();
		$res = \CMailAttachment::getList([], ['MESSAGE_ID' => $messageId]);
		while ($attachment = $res->fetch())
		{
			$attachment['FILE_NAME'] = str_replace("\0", '', $attachment['FILE_NAME']);
			if (getFileExtension(mb_strtolower($attachment['FILE_NAME'])) == 'vcf' && !$denyNewContact)
			{
				if ($attachment['FILE_ID'])
					$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);
				\CCrmEMail::tryImportVCard($attachment['FILE_DATA'], $userId);
			}

			$fileSize = isset($attachment['FILE_SIZE']) ? intval($attachment['FILE_SIZE']) : 0;
			if ($fileSize <= 0)
			{
				continue;
			}

			if ($attachmentMaxSize > 0 && $fileSize > $attachmentMaxSize)
			{
				$bannedAttachments->append(
					new BannedAttachment(
						name: $attachment['FILE_NAME'],
						size: $fileSize,
					)
				);

				continue;
			}

			if ($attachment['FILE_ID'] && empty($attachment['FILE_DATA']))
				$attachment['FILE_DATA'] = \CMailAttachment::getContents($attachment);

			$filesData->append(
				new AttachmentFile(
					name: $attachment['FILE_NAME'],
					type: $attachment['CONTENT_TYPE'] ?? '',
					content: $attachment['FILE_DATA'] ?? '',
					attachmentId: $attachment['ID'],
				)
			);
		}

		return new AttachmentFilesResult(
			files: $filesData,
			bannedAttachments: $bannedAttachments,
		);
	}

	public function saveAttachmentFiles(int $messageId, bool $denyNewContact, int $userId): AttachmentSaveResult
	{
		$filesToSave = $this->getAttachmentFilesForSave($messageId, $denyNewContact, $userId);

		return $this->saveAttachmentFilesFromResult($filesToSave, $userId);
	}

	public function saveAttachmentFilesFromResult(
		AttachmentFilesResult $filesToSave,
		int $userId,
	): AttachmentSaveResult
	{
		$diskAttachmentIds = new StorageAttachmentIdCollection();

		foreach ($filesToSave->files as $file)
		{
			$fileId = \CFile::saveFile($file->toArray(), 'crm', true);
			if (!($fileId > 0))
			{
				continue;
			}

			$fileFromDb = \CFile::getFileArray($fileId);
			if (empty($fileFromDb))
			{
				continue;
			}

			if (trim($fileFromDb['ORIGINAL_NAME']) == '')
			{
				$fileFromDb['ORIGINAL_NAME'] = $fileFromDb['FILE_NAME'];
			}

			$elementId = StorageManager::saveEmailAttachment(
				$fileFromDb,
				$this->getStorageTypeId(),
				'',
				['USER_ID' => $userId]
			);

			if ($elementId <= 0)
			{
				continue;
			}

			$diskAttachmentIds->append(
				new StorageAttachmentId(
					attachmentId: $file->attachmentId,
					storageElementId: (int)$elementId,
				)
			);
		}

		return new AttachmentSaveResult(
			bannedAttachments: $filesToSave->bannedAttachments,
			storageAttachmentIds: $diskAttachmentIds,
		);
	}

	public function getAttachmentMaxSizeInMb(): int
	{
		return (int)\COption::getOptionString('crm', 'email_attachment_max_size', 24);
	}

	private function getAttachmentMaxSizeInBytes(): int
	{
		$attachmentMaxSizeMb = $this->getAttachmentMaxSizeInMb();

		return $attachmentMaxSizeMb > 0 ? $attachmentMaxSizeMb * 1024 * 1024 : 0;
	}

	public function getStorageTypeId(): ?int
	{
		return \CCrmActivity::getDefaultStorageTypeID();
	}
}