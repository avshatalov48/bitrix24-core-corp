<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\ContactTable;
use Bitrix\Crm\Integration\UI\EntitySelector\Traits\FilterByIds;
use Bitrix\Crm\Integration\UI\EntitySelector\Traits\FilterByEmails;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;

class ContactProvider extends EntityProvider
{
	/** @var ContactTable */
	protected static $dataClass = ContactTable::class;

	protected int $categoryId;
	protected bool $showPhones = false;
	protected bool $showMails = false;
	protected bool $hideReadMoreLink = false;

	use FilterByIds;
	use FilterByEmails;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->categoryId = (int)($options['categoryId'] ?? 0);
		$this->options['categoryId'] = $this->categoryId;

		$this->showPhones = (bool)($options['showPhones'] ?? $this->showPhones);
		$this->showMails = (bool)($options['showMails'] ?? $this->showMails);
		$this->hideReadMoreLink = (bool)($options['hideReadMoreLink'] ?? $this->hideReadMoreLink);
		$this->setIdsForFilter($options['idsForFilterContact'] ?? []);
		$this->setEmailOnlyMode($options['onlyWithEmail'] ?? false);

		$this->options['showPhones'] = $this->showPhones;
		$this->options['showMails'] = $this->showMails;
		$this->options['hideReadMoreLink'] = $this->hideReadMoreLink;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Contact;
	}

	protected function fetchEntryIds(array $filter): array
	{
		$filter['=CATEGORY_ID'] = $this->categoryId;

		$collection = static::$dataClass::getList([
			'select' => ['ID'],
			'filter' => array_merge($filter, $this->getAdditionalFilter()),
		])->fetchCollection();

		return $collection->getIdList();
	}

	protected function getAdditionalFilter(): array
	{
		$filter = [
			'=CATEGORY_ID' =>  $this->categoryId,
		];

		$filter = array_merge($filter, $this->getFilterIds(), $this->getEmailFilters());

		if ($this->notLinkedOnly)
		{
			$filter = $this->getNotLinkedFilter();
		}

		return $filter;
	}

	public function getRecentItemIds(string $context): array
	{
		if ($this->isFilterByIds())
		{
			$ids = ContactTable::getList([
				'select' => ['ID'],
				'order' => [
					'ID' => 'ASC',
				],
				'filter' => $this->getFilterIds(),
			])->fetchCollection()->getIdList();
		}
		elseif ($this->notLinkedOnly)
		{
			$ids = $this->getNotLinkedEntityIds();
		}
		else
		{
			$ids = parent::getRecentItemIds($context);
		}

		return $ids;
	}

	protected function getTabIcon(): string
	{
		return 'o-group';
	}

	protected function getEntityInfo(int $entityId, bool $canReadItem): array
	{
		$entityInfo = parent::getEntityInfo($entityId, $canReadItem);

		if ($this->hideReadMoreLink)
		{
			unset($entityInfo['url']);
		}

		if (!$this->showPhones && !$this->showMails)
		{
			return $entityInfo;
		}

		$entityInfo['desc'] = '';

		if (isset($entityInfo['advancedInfo']['multiFields']))
		{
			$phones = [];
			$mails = [];

			foreach ($entityInfo['advancedInfo']['multiFields'] as $field)
			{
				if ($field['TYPE_ID'] === Phone::ID)
				{
					$phones[] = $field;
				}
				elseif ($field['TYPE_ID'] === Email::ID)
				{
					$mails[] = $field;
				}
			}

			$items = [];
			if ($this->showPhones)
			{
				$items = array_merge($items, array_column($phones, 'VALUE_FORMATTED'));
				$entityInfo['advancedInfo']['phones'] = $phones;
			}

			if ($this->showMails)
			{
				$items = array_merge($items, array_column($mails, 'VALUE_FORMATTED'));
				$entityInfo['advancedInfo']['mails'] = $mails;
			}

			$entityInfo['desc'] = implode(', ', $items);
		}

		return $entityInfo;
	}

	protected function getDefaultItemAvatar(): ?string
	{
		return '/bitrix/images/crm/entity_provider_icons/contact.svg';
	}
}
