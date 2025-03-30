<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Disk;
use Bitrix\Disk\Controller\Integration\Flipchart;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Main\Loader::requireModule('disk');

final class DiskDocumentsController extends Disk\Internals\Engine\Controller
{
	public function showFileHistoryAction(Disk\Document\TrackedObject $trackedObject): Main\Engine\Response\AjaxJson
	{
		if (!$trackedObject->canRead($this->getCurrentUser()->getId()))
		{
			if ($trackedObject->getUserId() == $this->getCurrentUser()->getId())
			{
				$trackedObject->delete();
			}

			return Main\Engine\Response\AjaxJson::createDenied()->setStatus('403 Forbidden');
		}

		return new Main\Engine\Response\Component(
			'bitrix:disk.file.history',
			'',
			[
				'STORAGE' => $trackedObject->getFile()->getStorage(),
				'FILE' => $trackedObject->getFile(),
			]
		);
	}

	public function getMenuActionsAction(Disk\Document\TrackedObject $trackedObject)
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		if (!$trackedObject->canRead($this->getCurrentUser()->getId()))
		{
			if ($trackedObject->getUserId() == $this->getCurrentUser()->getId())
			{
				$trackedObject->delete();
			}

			return Main\Engine\Response\AjaxJson::createDenied()->setStatus('403 Forbidden');
		}

		$file = $trackedObject->getFile();
		/** @see \Bitrix\Disk\Controller\TrackedObject::downloadAction */
		$downloadUri = (new Disk\Controller\TrackedObject())->getActionUri('download', ['id' => $trackedObject->getId()]);

		$actions = [
			[
				'id' => 'download',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_download.svg',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_DOWNLOAD'),
				'href' => $downloadUri,
			]
		];

		$actionToShare = [];
		if (Disk\Configuration::isPossibleToShowExternalLinkControl())
		{
			$featureBlocker = Bitrix24Manager::filterJsAction($this->getExternalLinkFeature($trackedObject), '');
			$actionToShare[] = [
				'id' => 'externalLink',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_GET_EXT_LINK'),
				'dataset' => [
					'shouldBlockFeature' => (bool)$featureBlocker,
					'blocker' => $featureBlocker ?: null,
				]
			];
		}

		$belongsToDiskStorages = $this->belongsToDiskStorages($file);
		if ($belongsToDiskStorages)
		{
			$internalLink = $urlManager->getUrlFocusController('showObjectInGrid', [
					'objectId' => $file->getId(),
					'cmd' => 'show',
			], true);

			$actionToShare[] = [
				'id' => 'internalLink',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_COPY_INTERNAL_LINK'),
				'dataset' => [
					'internalLink' => $internalLink,
					'textCopied' => Loc::getMessage('DISK_DOCUMENTS_ACT_COPIED_INTERNAL_LINK')
				]
			];
		}

		$actionToShare[] = [
			'id' => 'sharing',
			'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_SHARING_2'),
			'dataset' => [
				'objectId' => $trackedObject->getFileId(),
				'objectName' => $trackedObject->getFile()->getName(),
				'type' => $this->getSharingControlType($trackedObject),
			]
		];

		if ($actionToShare)
		{
			$actions[] = [
				'id' => 'share-section',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_SHARE_COMPLEX'),
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_share.svg',
				'items' => $actionToShare,
			];
		}

		if ($trackedObject->canRename($this->getCurrentUser()->getId()))
		{
			$actions[] = [
				'id' => 'rename',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_RENAME'),
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_rename.svg'
			];
		}

		if ($belongsToDiskStorages)
		{
			$actions[] = [
				'id' => 'history',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_SHOW_HISTORY'),
				'dataset' => [
					'objectId' => $trackedObject->getFileId(),
					'objectName' => $trackedObject->getFile()->getName(),
					'fileHistoryUrl' => $urlManager->getPathFileHistory($file),
					'blockedByFeature' => !Bitrix24Manager::isFeatureEnabled('disk_file_history')
				],
			];
		}

		if ($trackedObject->canMarkDeleted($this->getCurrentUser()->getId()))
		{
			$actions[] = [
				'id' => 'delete',
				'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_DELETE'),
				'dataset' => [
					'objectId' => $trackedObject->getFileId(),
					'objectName' => $trackedObject->getFile()->getName(),
				],
			];
		}

		if ($trackedObject->getFile()->getTypeFile() == Disk\TypeFile::FLIPCHART)
		{
			$openUrl = Driver::getInstance()->getUrlManager()->getUrlForViewBoard($trackedObject->getFileId());
			array_unshift(
				$actions,
				[
					'id' => 'open',
					'text' => Loc::getMessage('DISK_DOCUMENTS_ACT_OPEN'),
					'href' => $openUrl,
					'target' => '_blank',
				]
			);
		}

		return $actions;
	}

	protected function getSharingControlType(Disk\Document\TrackedObject $trackedObject): ?string
	{
		$currentUserId = $this->getCurrentUser()->getId();
		if (!$trackedObject->canChangeRights($currentUserId) && !$trackedObject->canShare($currentUserId))
		{
			return 'without-edit';
		}
		if ($trackedObject->canChangeRights($currentUserId))
		{
			return 'with-change-rights';
		}
		if ($trackedObject->canShare($currentUserId))
		{
			return 'with-sharing';
		}

		return 'without-edit';
	}

	protected function belongsToDiskStorages(File $file): bool
	{
		$storage = $file->getStorage();
		if (!$storage)
		{
			return false;
		}

		return $storage->getProxyType() instanceof Disk\ProxyType\Common
			|| $storage->getProxyType() instanceof Disk\ProxyType\Group
			|| $storage->getProxyType() instanceof Disk\ProxyType\User
		;
	}

	public function getInfoAction(array $trackedObjectIds): array
	{
		$result = [];
		$fileController = Main\Engine\ControllerBuilder::build(Disk\Controller\File::class, []);
		$userId = $this->getCurrentUser()?->getId();
		if (!$userId)
		{
			return $result;
		}

		/** @var Disk\Document\TrackedObject[] $batchById */
		$batchById = Disk\Document\TrackedObject::loadBatchById(
			array_keys($trackedObjectIds),
			[Disk\Document\TrackedObject::REF_OBJECT],
		);
		foreach ($batchById as $trackedObject)
		{
			$trackedObjectId = $trackedObject->getId();
			$actions = $trackedObjectIds[$trackedObjectId] ?? [];

			$result[$trackedObjectId] = [
				'shared' => null,
				'externalLink' => null,
			];

			if (!$trackedObject->canRead($userId))
			{
				continue;
			}

			if (\in_array('shared', $actions, true))
			{
				if (!$trackedObject->canShare($userId) && !$trackedObject->canChangeRights($userId))
				{
					$user = Disk\User::getById($userId);
					$result[$trackedObjectId]['shared'] = [[
						'entityId' => Disk\Sharing::CODE_USER . $userId,
						'name' => $user->getFormattedName(),
						'url' => $user->getDetailUrl(),
						'avatar' => $user->getAvatarSrc(),
						'type' => 'users',
					]];
				}
				else
				{
					$result[$trackedObjectId]['shared'] = $trackedObject->getFile()->getMembersOfSharing();
				}
			}

			if (\in_array('externalLink', $actions, true))
			{
				$externalLinkData = $fileController->getExternalLinkAction($trackedObject->getFile());
				$result[$trackedObjectId]['externalLink'] = $externalLinkData['externalLink'] ?? null;
			}
		}

		return $result;
	}

	public function getMenuOpenAction($trackedObjectId)
	{
		return 'someUrl';
	}

	public function formattedRowAction(int $id): mixed
	{
		$grid = new \Bitrix\Main\Engine\Response\Component(
			'bitrix:disk.documents',
			'',
			array(
				'SEF_MODE' => 'N',
				'USER_ID' => (int)$this->getCurrentUser()->getId(),
				'VARIANT' => \Bitrix\Disk\Type\DocumentGridVariant::DocumentsList,
			),
			[],
			array("HIDE_ICONS" => "Y")
		);

		[$items, $nextPage] = $grid->getItems(
			[
				'TRACKED_OBJECT.OBJECT_ID' => $id,
			],
			null,
			['ACTIVITY_TIME' => 'desc'],
			$grid->getGridHeaders()
		);

		$preparedRows = $grid->formatRows($items);

		return $preparedRows[0];
	}

	private function getExternalLinkFeature(Disk\Document\TrackedObject $trackedObject): string
	{
		$isBoardType = (int)$trackedObject->getFile()->getTypeFile() === Disk\TypeFile::FLIPCHART;

		return $isBoardType ? 'disk_board_external_link' : 'disk_manual_external_link';
	}

}
