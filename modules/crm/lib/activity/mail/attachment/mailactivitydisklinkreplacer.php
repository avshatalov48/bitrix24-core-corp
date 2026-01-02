<?php

namespace Bitrix\Crm\Activity\Mail\Attachment;

use Bitrix\Crm\Activity\Mail\Attachment\Dto\StorageAttachmentIdCollection;
use Bitrix\Crm\Integration\DiskManager;

class MailActivityDiskLinkReplacer
{
	public function replaceLinksAndUpdateDescription(
		int $activityId,
		string $description,
		StorageAttachmentIdCollection $storageAttachmentIds,
	): bool
	{
		$newDescription = $this->replaceLinks($activityId, $description, $storageAttachmentIds);
		$descriptionUpdated = $description !== $newDescription;
		if ($descriptionUpdated)
		{
			\CCrmActivity::update(
				$activityId,
				[
					'DESCRIPTION' => $newDescription,
				],
				false,
				false,
			);
		}

		return $descriptionUpdated;
	}

	public function replaceLinks(
		int $activityId,
		string $description,
		StorageAttachmentIdCollection $storageAttachmentIds,
	): string
	{
		\Bitrix\Main\Config\Ini::adjustPcreBacktrackLimit(strlen($description) * 2);
		$updatedDescription = null;
		foreach ($storageAttachmentIds as $diskAttachmentId)
		{
			$diskUrl = $this->getDiskFileUrl($diskAttachmentId->storageElementId, $activityId);
			if ($diskUrl === null)
			{
				continue;
			}

			$updatedDescription = preg_replace(
				sprintf('/<img([^>]+)src\s*=\s*(\'|\")?\s*(aid:%u)\s*\2([^>]*)>/is', $diskAttachmentId->attachmentId),
				sprintf('<img\1src="%s"\4>', $diskUrl),
				$updatedDescription ?? $description,
			);
		}

		if ($updatedDescription === null && $storageAttachmentIds->count() && isModuleInstalled('bitrix24'))
		{
			(new \Bitrix\Crm\Service\Logger\Message2LogLogger('MailActivityDiskLinkReplacer'))
				->error("null preg_replace result in activity $activityId")
			;
		}

		return $updatedDescription ?? $description;
	}

	private function getDiskFileUrl(int $id, int $activityId): ?string
	{
		$info = DiskManager::getFileInfo(
			$id,
			false,
			['OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $activityId],
		);

		return $info['VIEW_URL'] ?? null;
	}
}