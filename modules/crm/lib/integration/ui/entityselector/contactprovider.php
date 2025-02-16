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

		return array_merge($filter, $this->getFilterIds(), $this->getEmailFilters());
	}

	public function getRecentItemIds(string $context): array
	{
		if($this->isFilterByIds())
		{
			$ids = ContactTable::getList([
				'select' => ['ID'],
				'order' => [
					'ID' => 'ASC',
				],
				'filter' => $this->getFilterIds(),
			])->fetchCollection()->getIdList();
		}
		else
		{
			$ids = parent::getRecentItemIds($context);
		}

		return $ids;
	}

	protected function getTabIcon(): string
	{
		return 'data:image/svg+xml,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22'
			. ' xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath'
			. ' d=%22M18.6102 18.7826C19.0562 18.6205 19.3037 18.1517 19.2122 17.686L18.8963 16.0768C18.8963'
			. ' 15.4053 18.0019 14.6382 16.2409 14.1912C15.6442 14.0278 15.077 13.7745 14.5596 13.4403C14.4464'
			. ' 13.3768 14.4636 12.7904 14.4636 12.7904L13.8964 12.7056C13.8964 12.658 13.8479 11.9547'
			. ' 13.8479 11.9547C14.5265 11.7309 14.4567 10.4105 14.4567 10.4105C14.8877 10.6451 15.1684'
			. ' 9.60016 15.1684 9.60016C15.6781 8.14839 14.9145 8.23617 14.9145 8.23617C15.0481 7.34989'
			. ' 15.0481 6.44915 14.9145 5.56287C14.575 2.62285 9.46373 3.42099 10.0698 4.38119C8.57596'
			. ' 4.11109 8.91682 7.44748 8.91682 7.44748L9.24084 8.31146C8.79173 8.5974 8.87993 8.92555'
			. ' 8.97844 9.29212C9.01952 9.44494 9.06238 9.60443 9.06886 9.77033C9.10016 10.6029 9.6192'
			. ' 10.4304 9.6192 10.4304C9.65119 11.8045 10.3415 11.9834 10.3415 11.9834C10.4712 12.8464'
			. ' 10.3904 12.6995 10.3904 12.6995L9.77605 12.7725C9.78436 12.9687 9.76807 13.1652 9.72755'
			. ' 13.3576C9.37062 13.5137 9.1521 13.638 8.93575 13.761C8.71426 13.8869 8.49503 14.0116'
			. ' 8.1319 14.1679C6.74504 14.7644 5.23779 15.5403 4.96984 16.5849C4.8915 16.8903 4.81478'
			. ' 17.3009 4.74543 17.729C4.67275 18.1776 4.92217 18.6168 5.34907 18.7728C7.21183'
			. ' 19.4533 9.31409 19.8566 11.5441 19.9044H12.442C14.6614 19.8568 16.7541 19.4572'
			. ' 18.6102 18.7826Z%22 fill=%22%23ABB1B8%22/%3E%3C/svg%3E%0A'
		;
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