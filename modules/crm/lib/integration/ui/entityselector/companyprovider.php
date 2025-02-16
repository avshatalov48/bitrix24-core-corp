<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Integration\UI\EntitySelector\Traits\FilterByIds;
use Bitrix\Crm\Integration\UI\EntitySelector\Traits\FilterByEmails;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;

class CompanyProvider extends EntityProvider
{
	/** @var CompanyTable */
	protected static $dataClass = CompanyTable::class;

	protected bool $enableMyCompanyOnly = false;
	protected bool $excludeMyCompany = false;
	protected bool $showPhones = false;
	protected bool $showMails = false;
	protected bool $hideReadMoreLink = false;
	protected $categoryId;

	use FilterByIds;
	use FilterByEmails;

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->categoryId = (int)($options['categoryId'] ?? 0);
		$this->options['categoryId'] = $this->categoryId;

		$this->enableMyCompanyOnly = (bool)($options['enableMyCompanyOnly'] ?? $this->enableMyCompanyOnly);
		$this->excludeMyCompany = (bool)($options['excludeMyCompany'] ?? $this->excludeMyCompany);
		$this->showPhones = (bool)($options['showPhones'] ?? $this->showPhones);
		$this->showMails = (bool)($options['showMails'] ?? $this->showMails);
		$this->hideReadMoreLink = (bool)($options['hideReadMoreLink'] ?? $this->hideReadMoreLink);
		$this->setIdsForFilter($options['idsForFilterCompany'] ?? []);
		$this->setEmailOnlyMode($options['onlyWithEmail'] ?? false);
		$this->options['enableMyCompanyOnly'] = $this->enableMyCompanyOnly;
		$this->options['excludeMyCompany'] = $this->excludeMyCompany;
		$this->options['showPhones'] = $this->showPhones;
		$this->options['showMails'] = $this->showMails;
		$this->options['hideReadMoreLink'] = $this->hideReadMoreLink;
	}

	public function getRecentItemIds(string $context): array
	{
		if($this->enableMyCompanyOnly || $this->excludeMyCompany || $this->isFilterByIds())
		{
			$ids = CompanyTable::getList([
				'select' => ['ID'],
				'order' => [
					'ID' => 'ASC',
				],
				'filter' => $this->getCompanyFilter(),
			])->fetchCollection()->getIdList();
		}
		else
		{
			$ids = parent::getRecentItemIds($context);
		}

		return $ids;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function getCategoryId(): int
	{
		return $this->options['categoryId'];
	}

	protected function fetchEntryIds(array $filter): array
	{
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

		return array_merge($filter, $this->getCompanyFilter(), $this->getEmailFilters());
	}

	private function getCompanyFilter(): array
	{
		$filter = [];

		if($this->enableMyCompanyOnly)
		{
			$filter = [
				'=IS_MY_COMPANY' => 'Y',
			];
		}
		elseif ($this->excludeMyCompany)
		{
			$filter = [
				'=IS_MY_COMPANY' => 'N',
			];
		}

		return array_merge($filter, $this->getFilterIds());
	}

	protected function getTabIcon(): string
	{
		return 'data:image/svg+xml,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22'
			. ' xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath'
			. ' d=%22M15.1207 10.1118C15.1207 10.0586 15.1272 10.0055 15.1397 9.95384C15.2252 9.60484'
			. ' 15.5715 9.39264 15.9133 9.4799L18.8899 10.2398C19.1739 10.3123 19.3731 10.5729 19.3731'
			. ' 10.8718V16.877H20.0361C20.257 16.877 20.4361 17.0561 20.4361 17.277V18.6483C20.4361'
			. ' 18.8692 20.257 19.0483 20.0361 19.0483H3.82676C3.60584 19.0483 3.42676 18.8692'
			. ' 3.42676 18.6483V17.277C3.42676 17.0561 3.60584 16.877 3.82676 16.877H4.48985V6.36074C4.48985'
			. ' 5.82839 4.86785 5.37456 5.3824 5.28917L12.824 4.05411C12.8805'
			. ' 4.04474 12.9374 4.04004 12.9945 4.04004C13.5816 4.04004 14.0577 4.5261 14.0577'
			. ' 5.12568V16.877H15.1207V10.1118ZM9.0725 16.7701V13.4346H7.1665V16.7701H9.0725ZM11.9785'
			. ' 15.3406V13.4346H10.0725V15.3406H11.9785ZM18.1259 13.3399H16.2199V15.2459H18.1259V13.3399ZM11.9785'
			. ' 7.62207H10.0725V9.52807H11.9785V7.62207ZM9.07242 7.62186H7.16642V9.52786H9.07242V7.62186ZM11.9785'
			. ' 10.5283H10.0725V12.4343H11.9785V10.5283ZM9.0725 10.5283H7.1665V12.4343H9.0725V10.5283Z%22'
			. ' fill=%22%23ABB1B8%22/%3E%3C/svg%3E%0A'
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
		return '/bitrix/images/crm/entity_provider_icons/company.svg';
	}
}
