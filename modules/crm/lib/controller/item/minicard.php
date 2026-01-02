<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemMiniCard\Factory\ProviderFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Engine\Response\Component;
use CCrmOwnerType;

final class MiniCard extends Base
{
	private UserPermissions $permissions;
	private ProviderFactory $providerFactory;

	protected function init(): void
	{
		parent::init();

		$this->providerFactory = new ProviderFactory();
		$this->permissions = Container::getInstance()->getUserPermissions();
	}

	public function getAction(int $entityTypeId, int $entityId): ?\Bitrix\Crm\ItemMiniCard\MiniCard
	{
		if (!$this->permissions->item()->canRead($entityTypeId, $entityId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$provider = $this->providerFactory->create($entityTypeId, $entityId);
		if ($provider === null)
		{
			$this->addError(ErrorCode::getNotFoundError($entityTypeId));

			return null;
		}

		return \Bitrix\Crm\ItemMiniCard\MiniCard::builder()
			->useProvider($provider)
			->build();
	}

	public function getActivityEditorAction(int $ownerTypeId, int $ownerId, string $editorId): ?Component
	{
		if (!$this->permissions->item()->canRead($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		return new Component(
			componentName: 'bitrix:crm.activity.editor',
			componentParams: [
				'CONTAINER_ID' => '',
				'EDITOR_ID' => $editorId,
				'PREFIX' => '',
				'OWNER_TYPE' => CCrmOwnerType::ResolveName($ownerTypeId),
				'OWNER_TYPE_ID' => $ownerTypeId,
				'OWNER_ID' => $ownerId,
				'ENABLE_UI' => false,
				'ENABLE_TOOLBAR' => false,
				'ENABLE_EMAIL_ADD' => true,
				'ENABLE_TASK_ADD' => false,
				'MARK_AS_COMPLETED_ON_VIEW' => false,
				'SKIP_VISUAL_COMPONENTS' => 'Y'
			],
		);
	}
}
