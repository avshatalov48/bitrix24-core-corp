<?php

namespace Bitrix\Crm\Activity\Mail\Attachment;

use Bitrix\Crm\Activity\Mail\Attachment\Dto\AttachmentFilesResult;
use Bitrix\Crm\Activity\Mail\Attachment\Dto\BannedAttachmentCollection;
use Bitrix\Crm\Activity\Mail\Attachment\Result\MailActivityUpdatedResult;
use Bitrix\Crm\Integration\Mail\Client;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class MailActivityAttachmentLoader
{
	private readonly MailActivityAttachmentSaver $saver;
	private readonly MailActivityDescriptionFactory $descriptionFactory;

	public function __construct()
	{
		$this->saver = new MailActivityAttachmentSaver();
		$this->descriptionFactory = new MailActivityDescriptionFactory();
	}

	public function loadByActivityArray(array $activity): Result|MailActivityUpdatedResult
	{
		if (!empty($activity['STORAGE_ELEMENT_IDS']))
		{
			return (new Result())->addError(new Error('Attachments already loaded'));
		}

		$notLoadedAttachments = $activity['SETTINGS'][\CCrmEMail::ACTIVITY_SETTINGS_NOT_LOADED_ATTACHMENTS_FIELD] ?? null;
		if ($notLoadedAttachments === 0 || $notLoadedAttachments < 0)
		{
			return (new Result())->addError(new Error('Nothing to load'));
		}

		if (!Client::isReadyToUse())
		{
			return (new Result())->addError(new Error('Module mail not ready to use'));
		}

		$messageId = (int)($activity['UF_MAIL_MESSAGE'] ?? 0);
		if (!$messageId)
		{
			return (new Result())->addError(new Error('Message id not found'));
		}

		$activityId = $activity['ID'] ?? null;
		if (!$activityId)
		{
			return (new Result())->addError(new Error('Activity id not found'));
		}

		$message = \CMailMessage::getById($messageId)->fetch();
		if (empty($message))
		{
			return (new Result())->addError(new Error('Message not found'));
		}

		if (empty($message['OPTIONS']['attachments']))
		{
			if ($notLoadedAttachments === null)
			{
				$this->markAsNothingToLoad($activity);
			}

			return (new Result())->addError(new Error('Nothing to load from message'));
		}

		$lock = new MailActivityAttachmentLock($activityId);
		if (!$lock->lock())
		{
			return (new Result())->addError(new Error('Attachment loading in progress'));
		}

		$result = $this->loadByActivityId($activityId, $messageId, $message);
		$lock->release();

		return $result;
	}

	private function getActivity(int $activityId): array|false
	{
		$activity = \CCrmActivity::getList(
				[],
				['ID' => $activityId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				[
					'ID',
				 	'UF_MAIL_MESSAGE',
				 	'RESPONSIBLE_ID',
				 	'STORAGE_TYPE_ID',
				 	'STORAGE_ELEMENT_IDS',
				 	'SETTINGS',
				],
				['QUERY_OPTIONS' => ['LIMIT' => 1]],
			)
			->fetch()
		;
		\CCrmActivity::PrepareStorageElementIDs($activity);

		return $activity;
	}

	private function loadByActivityId(
		int $activityId,
		int $messageId,
		array $message,
	): Result|MailActivityUpdatedResult
	{
		$activity = $this->getActivity($activityId);
		$notLoadedAttachments = $activity['SETTINGS'][\CCrmEMail::ACTIVITY_SETTINGS_NOT_LOADED_ATTACHMENTS_FIELD] ?? null;
		if ($notLoadedAttachments === 0 || $notLoadedAttachments < 0 || !empty($activity['STORAGE_ELEMENT_IDS']))
		{
			return (new Result())->addError(new Error('Attachments already loaded'));
		}

		$this->downloadAttachmentsIfNeed($message);

		$filesToSave = $this->saver->getAttachmentFilesForSave(
			$messageId,
			$this->getDenyNewContactFromMessage($message),
			$this->getResponsibleUserId($activity),
		);

		if ($filesToSave->files->isEmpty())
		{
			if ($filesToSave->bannedAttachments->count() === $message['OPTIONS']['attachments'])
			{
				$this->markAsNothingToLoad($activity);
			}

			$errorMessage = $this->getBannedAttachmentsMessage($filesToSave->bannedAttachments);

			return (new Result())->addError(new Error($errorMessage ?? 'Attachments to save not found'));
		}

		$saveResult = $this->saver->saveAttachmentFilesFromResult($filesToSave, $this->getResponsibleUserId($activity));
		// refresh message to get correct inline body attachment links
		$message = \CMailMessage::getById($messageId)->fetch();
		$description = $this->descriptionFactory->makeFromMessageFieldsArray($message);
		$activityUpdateFields = [
			'STORAGE_TYPE_ID' => $this->saver->getStorageTypeId(),
			'STORAGE_ELEMENT_IDS' => $saveResult->storageAttachmentIds->getStorageElementIds(),
			'SETTINGS' => array_merge($activity['SETTINGS'], [
				'SANITIZE_ON_VIEW' => (int)($message['SANITIZE_ON_VIEW'] ?? 0),
				\CCrmEMail::ACTIVITY_SETTINGS_NOT_LOADED_ATTACHMENTS_FIELD => 0,
			]),
		];

		if ($description->mayContainInlineFiles)
		{
			$newDescription = (new \Bitrix\Crm\Activity\Mail\Attachment\MailActivityDiskLinkReplacer())
				->replaceLinks(
					$activityId,
					$description->description,
					$saveResult->storageAttachmentIds,
				)
			;
			if ($newDescription !== $description->description)
			{
				$activityUpdateFields['DESCRIPTION'] = $newDescription;
			}
		}

		$updated = \CCrmActivity::update($activityId, $activityUpdateFields,false,false);
		if (!$updated)
		{
			return (new Result())->addError(new Error('Activity update error'));
		}

		return new MailActivityUpdatedResult($activityUpdateFields);
	}

	private function getDenyNewContactFromMessage(array $message): bool
	{
		$mailboxId = (int)($message['MAILBOX_ID'] ?? 0);
		if (!$mailboxId)
		{
			return true;
		}

		$mailbox = \CMailBox::getById($mailboxId)->fetch();

		return !empty($mailbox['OPTIONS']['flags'])
			&& is_array($mailbox['OPTIONS']['flags'])
			&& in_array('crm_deny_new_contact', $mailbox['OPTIONS']['flags'], true)
		;
	}

	private function getBannedAttachmentsMessage(BannedAttachmentCollection $bannedAttachments): ?string
	{
		return \CCrmEMail::getBannedAttachmentErrorMessage($bannedAttachments, $this->saver->getAttachmentMaxSizeInMb());
	}

	private function getResponsibleUserId(array $activity): int
	{
		return (int)($activity['RESPONSIBLE_ID'] ?? 0);
	}

	private function markAsNothingToLoad(array $activity): void
	{
		\CCrmActivity::update(
			$activity['ID'],
			[
				'SETTINGS' => array_merge($activity['SETTINGS'], [
					\CCrmEMail::ACTIVITY_SETTINGS_NOT_LOADED_ATTACHMENTS_FIELD => 0,
				]),
			],
			false,
			false
		);
	}

	private function downloadAttachmentsIfNeed(array $message): void
	{
		if (!empty($message['ATTACHMENT']) || empty($message['ID']))
		{
			return;
		}

		if (method_exists('Bitrix\Mail\Integration\Attachment','downloadAttachmentsByMessageId'))
		{
			\Bitrix\Mail\Integration\Attachment::downloadAttachmentsByMessageId($message['ID']);
		}
	}

}