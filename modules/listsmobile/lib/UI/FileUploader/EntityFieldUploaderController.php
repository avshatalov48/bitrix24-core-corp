<?php

namespace Bitrix\Listsmobile\UI\FileUploader;

use Bitrix\Lists\Api\Service\AccessService;
use Bitrix\Lists\Security\ElementRight;
use Bitrix\Lists\Service\Param;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\UploaderController;

Loader::requireModule('ui');

final class EntityFieldUploaderController extends UploaderController
{
	private string $iBlockTypeId = '';
	private int $socNetGroupId = 0;

	public function __construct(array $options)
	{
		$options['elementId'] = isset($options['elementId']) ? (int)$options['elementId'] : -1;
		$options['iBlockId'] = isset($options['iBlockId']) ? (int)$options['iBlockId'] : 0;

		if ($options['iBlockId'] > 0 && Loader::includeModule('iblock'))
		{
			$iBlockInfo = \CIBlock::GetArrayByID($options['iBlockId']);
			if (is_array($iBlockInfo))
			{
				if (array_key_exists('SOCNET_GROUP_ID', $iBlockInfo) && array_key_exists('IBLOCK_TYPE_ID', $iBlockInfo))
				{
					$this->iBlockTypeId = (string)$iBlockInfo['IBLOCK_TYPE_ID'];
					$this->socNetGroupId = (int)$iBlockInfo['SOCNET_GROUP_ID'];
				}
			}
		}

		parent::__construct($options);
	}

	public function isAvailable(): bool
	{
		return $this->checkElementReadRights();
	}

	public function getConfiguration(): Configuration
	{
		return new Configuration();
	}

	public function canUpload()
	{
		return $this->checkElementEditRights();
	}

	public function canView(): bool
	{
		return $this->checkElementReadRights();
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{}

	public function canRemove(): bool
	{
		return $this->checkElementEditRights();
	}

	private function checkElementReadRights(): bool
	{
		$elementId = $this->options['elementId'];
		$sectionId = 0;

		$accessService = $this->getAccessService();
		if ($accessService && $elementId >= 0)
		{
			$checkPermissionResult = $accessService->checkElementPermission($elementId, $sectionId);
			if ($checkPermissionResult->isSuccess())
			{
				$elementRight = $checkPermissionResult->getElementRight();

				if (
					$elementRight
					&& (
						($elementId !== 0 && $elementRight->canRead())
						|| ($elementId === 0 && $elementRight->canAdd())
					)
				)
				{
					return true;
				}
			}
		}

		return false;
	}

	private function checkElementEditRights(): bool
	{
		$elementId = $this->options['elementId'];
		$sectionId = 0;

		$accessService = $this->getAccessService();
		if ($accessService && $elementId >= 0)
		{
			$checkPermissionResult = $accessService->checkElementPermission($elementId, $sectionId, ElementRight::EDIT);
			if ($checkPermissionResult->isSuccess())
			{
				return true;
			}
		}

		return false;
	}

	private function getAccessService(): ?AccessService
	{
		$currentUser = CurrentUser::get();
		$iBlockId = $this->options['iBlockId'];
		if (
			!empty($this->iBlockTypeId)
			&& $this->socNetGroupId >= 0
			&& $iBlockId >= 0
			&& $currentUser
			&& $currentUser->getId() > 0
			&& Loader::includeModule('lists')
		)
		{
			return new AccessService(
				$currentUser->getId(),
				new Param([
					'IBLOCK_TYPE_ID' => $this->iBlockTypeId,
					'IBLOCK_ID' => $iBlockId,
					'SOCNET_GROUP_ID' => $this->socNetGroupId,
				])
			);
		}

		return null;
	}
}
