<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Search\Result;
use Bitrix\Crm\Search\Result\Provider;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Search\Content;
use Bitrix\Main\Type\Date;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use CAllCrmActivity;

class ActivityProvider extends EntityProvider
{
	private ?int $ownerTypeId;

	protected static ActivityTable|string $dataClass = ActivityTable::class;

	private bool $hasAccess;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->ownerTypeId = (int)($options['entityTypeId'] ?? 0);
		$this->ownerId = (int)($options['entityId'] ?? 0);

		$this->hasAccess =
			\CCrmOwnerType::IsDefined($this->ownerTypeId) && $this->ownerId > 0
			&& $this->userPermissions->checkReadPermissions($this->ownerTypeId, $this->ownerId);
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Activity;
	}

	public function isAvailable(): bool
	{
		return $this->hasAccess;
	}

	protected function fetchEntryIds(array $filter): array
	{
		if (!$this->hasAccess)
		{
			return [];
		}

		return static::$dataClass::getList(
			[
				'select' => ['ID'],
				'filter' => array_merge($filter, [
					'@PROVIDER_ID' => ProviderManager::getRelatedFilterProviders(),
					'=BINDINGS.OWNER_ID' => $this->ownerId,
					'=BINDINGS.OWNER_TYPE_ID' => $this->ownerTypeId,
				]),
			]
		)->fetchCollection()
			->getIdList();
	}

	public function getRecentItemIds(string $context): array
	{
		if (!$this->hasAccess)
		{
			return [];
		}

		return static::$dataClass::getList(
			[
				'select' => ['ID'],
				'filter' => [
					'@PROVIDER_ID' => ProviderManager::getRelatedFilterProviders(),
					'=BINDINGS.OWNER_ID' => $this->ownerId,
					'=BINDINGS.OWNER_TYPE_ID' => $this->ownerTypeId,
				],
				'order' => [
					'ID' => 'ASC',
				],
			]
		)->fetchCollection()
			->getIdList();
	}

	protected function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/activity.svg';
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		if (!$this->hasAccess)
		{
			return;
		}

		$filter['FIND'] = trim($searchQuery->getQuery());
		if ($filter['FIND'] === '')
		{
			return;
		}

		SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Activity, $filter);

		$filter['BINDINGS'] = [
			[
				'OWNER_ID' => $this->ownerId,
				'OWNER_TYPE_ID' => $this->ownerTypeId,
			]
		];
		$filter['CHECK_PERMISSIONS'] = 'N';
		$filter['@PROVIDER_ID'] = ProviderManager::getRelatedFilterProviders();

		$activities = \CCrmActivity::GetList(
			[],
			$filter,
			false,
			['nTopCount' => Provider::DEFAULT_LIMIT],
			['ID']
		);

		$result = new Result();
		while ($activity = $activities->Fetch())
		{
			$result->addId($activity['ID']);
		}

		$wasFulltextUsed = Content::canUseFulltextSearch($searchQuery->getQuery());
		$searchQuery->setCacheable($wasFulltextUsed);

		$dialog->addItems($this->makeItemsByIds($result->getIds()));
	}

	protected function makeItem(int $entityId): ?Item
	{
		$entityInfo = $this->getActivityInfo($entityId);
		$this->prepareItemAvatar($entityInfo);

		$itemOptions = [
			'id' => $entityId,
			'entityId' => $this->getItemEntityId(),
			'title' => $this->getEntityTitle($entityInfo),
			'subtitle' => $entityInfo['desc'],
			'link' => $entityInfo['url'],
			'linkTitle' => Loc::getMessage('CRM_COMMON_DETAIL'),
			'avatar' => $entityInfo['image'],
			'searchable' => true,
			'hidden' => !$this->isAvailable(),
			'tabs' => $this->getTabsNames(),
			'customData' => [
				'id' => (string)$entityId,
				'entityInfo' => $entityInfo,
			],
		];

		if ($this->showEntityTypeNameInHeader)
		{
			$itemOptions['supertitle'] = (string)$entityInfo['typeNameTitle'];
			$itemOptions['title'] = $this->getEntityTitle($entityInfo);
			$itemOptions['subtitle'] = null;
		}

		return new Item($itemOptions);
	}

	private function getActivityInfo(int $entityId): array
	{
		$activity = CAllCrmActivity::GetList(
			[],
			['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['SUBJECT', 'DESCRIPTION', 'DESCRIPTION_TYPE', 'CREATED', 'TYPE_ID', 'PROVIDER_ID', 'COMPLETED']
		)->Fetch();

		if ($activity)
		{
			$created = $activity['CREATED'] ?? null;
			if ($created)
			{
				if ($created instanceof Date)
				{
					$created = (string)$created;
				} else
				{
					$created = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($created), 'SHORT', SITE_ID));
				}
			}

			$description = TextHelper::cleanTextByType($activity['DESCRIPTION'], (int)$activity['DESCRIPTION_TYPE']);
			$result['title'] = $activity['SUBJECT'];
			if ($result['title'] === '')
			{
				$provider = \CCrmActivity::GetActivityProvider($activity);
				$result['title'] = $provider ? $provider::getActivityTitle($activity) : "ACTIVITY_{$entityId}";
			}
			$result['desc'] = $description ? html_entity_decode($description, ENT_QUOTES) . ' ' . $created : $created;
		} else
		{
			$result['notFound'] = true;
		}

		return $result;
	}
}