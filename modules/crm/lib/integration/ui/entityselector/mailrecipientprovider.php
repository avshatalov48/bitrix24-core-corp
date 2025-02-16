<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Activity\Mail\Message;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\Crm\Multifield\Type;
use Bitrix\UI\EntitySelector\ItemCollection;

class MailRecipientProvider extends BaseProvider
{
	public const PROVIDER_ENTITY_ID = 'mail_recipient';
	public const EMAIL_TYPE_ID = 'email';
	public const EMAIL_TYPE_WORK = 'WORK';
	public const EMAIL_ID_EMPTY = 0;
	private const TAB_ID_FOR_EMAIL = 'recents';

	private const NAMES_OF_ENTITIES_WITH_AVATARS = [
		'company',
		'contact',
	];

	private static function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/mailrecipientprovider.svg';
	}

	private int $ownerId;
	private string $ownerType;
	private array $accessibleItemIds;
	private bool $checkTheWhitelist;

	public function __construct(array $options = [])
	{
		$this->ownerType = $options['ownerType'] ?? '';
		$this->ownerId = (int)($options['ownerId'] ?? 0);
		$this->accessibleItemIds = ($options['selectedItemIds'] ?? []);
		$this->checkTheWhitelist = ($options['checkTheWhitelist'] ?? true);

		parent::__construct();
	}

	private function getAccessibleItemIds(): array
	{
		return $this->accessibleItemIds;
	}

	private function addAccessibleItemIdsByItemList($items): void
	{
		$ids = [];

		foreach ($items as $item)
		{
			$ids[]=$item->getId();
		}

		$this->accessibleItemIds = array_merge($this->accessibleItemIds, $ids);
	}

	public static function buildRecipientProviderId($entityTypeName, $entityId, $emailType, $email): string
	{
		if (is_null($entityTypeName))
		{
			$entityTypeName = self::EMAIL_TYPE_ID;
		}

		global $DB;
		return (
			mb_strtolower(trim($entityTypeName))
			.','.
			(int)$entityId
			.','.
			mb_strtolower(trim($emailType))
			.','.
			$DB->ForSql(mb_strtolower(trim($email)))
		);
	}

	private static function getDataByProviderId(string $id): array
	{
		$splitId =  explode(',', $id);
		$entityTypeId = $splitId[0];

		$data = [
			'ENTITY_ID' => (int)$splitId[1],
			'EMAIL_TYPE' => mb_strtoupper($splitId[2]),
			'EMAIL' => $splitId[3],
		];

		if ($entityTypeId === self::EMAIL_TYPE_ID)
		{
			$data['ENTITY_TYPE_NAME'] = self::EMAIL_TYPE_ID;
		}
		else
		{
			$data['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($splitId[0]);
		}

		return $data;
	}

	private function buildItems(Item $baseItem, Dialog $dialog = null): array
	{
		$newItems = [];

		$customDataValues = $baseItem->getCustomData()->getValues();

		if (isset($customDataValues['entityInfo']['advancedInfo']['multiFields']))
		{
			$fields = $customDataValues['entityInfo']['advancedInfo']['multiFields'];

			if (in_array($baseItem->getEntityId(), self::NAMES_OF_ENTITIES_WITH_AVATARS))
			{
				$avatar = $baseItem->getAvatar();
			}
			else
			{
				$avatar = self::getDefaultItemAvatar();
			}

			$title = $baseItem->getTitle();

			foreach ($fields as $field)
			{
				if ($field['TYPE_ID'] === Type\Email::ID)
				{
					$email = (string)$field['VALUE'];
					$emailType = (string)$field['COMPLEX_NAME'];

					$item = new Item(
						[
							'id' => self::buildRecipientProviderId($baseItem->getEntityId(), $baseItem->getId(), $field['VALUE_TYPE'], $email),
							'entityId' => self::PROVIDER_ENTITY_ID,
							'tabs' => $baseItem->getTabs(),
							'title' => $title,
							'subtitle' => $email . ' ' . $emailType,
							'avatar' => $avatar,
							'tagOptions' => [
								'title' => $title . ' (' . $email . ')',
							],
							'customData' => [
								'name' => $baseItem->getTitle(),
								'entityType' => $baseItem->getEntityId(),
								'entityId' => $baseItem->getId(),
								'email' => $email,
							],
						]
					);

					if (!is_null($dialog))
					{
						$item->setDialog($dialog);
					}

					$newItems[] = $item;
				}
			}
		}

		return $newItems;
	}

	private function checkReadPermissionsById($id): bool
	{
		if (!$this->checkTheWhitelist)
		{
			return true;
		}

		return in_array($id, $this->getAccessibleItemIds());
	}

	public function fillDialog(Dialog $dialog): void
	{
		$recipientsResult = Message::getEntityRecipients($this->ownerId, $this->ownerType, true, false, true);

		if (!$recipientsResult->isSuccess())
		{
			return;
		}

		$itemsToAdd = [];

		$itemsCollection = ($recipientsResult->getData()[0])->getAll();

		foreach ($itemsCollection as $item)
		{
			$buildItems =  self::buildItems($item, $dialog);
			$this->addAccessibleItemIdsByItemList($buildItems);
			$itemsToAdd = array_merge($itemsToAdd, $buildItems);
		}

		$dialog->addRecentItems($itemsToAdd);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	private static function getEmailItems(array $dataItems): ItemCollection
	{
		$itemCollection = new ItemCollection();
		$entityType = self::EMAIL_TYPE_ID;

		foreach ($dataItems as $item)
		{
			if (isset($item['ENTITY_TYPE_NAME']) && $item['ENTITY_TYPE_NAME'] === $entityType)
			{
				$email = $item['EMAIL'];
				$entityId = self::EMAIL_ID_EMPTY;

				$itemCollection->add(
					new Item(
						[
							'id' => self::buildRecipientProviderId($entityType, $entityId, self::EMAIL_TYPE_WORK, $email),
							'entityId' => self::PROVIDER_ENTITY_ID,
							'tabs' => self::TAB_ID_FOR_EMAIL,
							'title' => $email,
							'subtitle' => '',
							'avatar' => self::getDefaultItemAvatar(),
							'customData' => [
								'name' => $email,
								'entityType' => $entityType,
								'entityId' => $entityId,
								'email' => $email,
							],
						]
					)
				);
			}
		}

		return $itemCollection;
	}

	public function getItems(array $ids): array
	{
		$dataItems = [];

		foreach ($ids as $id)
		{
			if ($this->checkReadPermissionsById($id))
			{
				$data = self::getDataByProviderId($id);

				if ($data['ENTITY_TYPE_ID'] !== 0)
				{
					$dataItems[] = $data;
				}
			}
		}

		$itemsToAdd = self::getEmailItems($dataItems)->getAll();

		$itemsCollection = Message::entityRecipientsToCollectionForDialog($dataItems, true);

		foreach ($itemsCollection as $item)
		{
			$itemsToAdd = array_merge($itemsToAdd, self::buildItems($item));
		}

		return $itemsToAdd;
	}
}