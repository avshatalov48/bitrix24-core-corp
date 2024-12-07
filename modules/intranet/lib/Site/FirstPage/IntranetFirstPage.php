<?php

namespace Bitrix\Intranet\Site\FirstPage;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

class IntranetFirstPage implements FirstPage
{
	public function __construct(
		private readonly int $userId,
	)
	{}

	public function isEnabled(): bool
	{
		return true;
	}

	public function getUri(): Uri
	{
		$firstPagePath = $this->getUserFirstPage();

		if (empty($firstPagePath))
		{
			$firstPagePath = $this->getPortalFirstPage();
		}

		if (
			preg_match('~^' . SITE_DIR . 'crm~i', $firstPagePath)
			&& Loader::includeModule('crm')
			&& !$this->isCrmAvailable()
		)
		{
			$firstPagePath = SITE_DIR . 'company/personal/user/#USER_ID#/tasks/';
		}

		$firstPagePath = str_replace('#USER_ID#', $this->getUserId(), $firstPagePath);

		if (
			Loader::includeModule('crm')
			&& preg_match('~^' . SITE_DIR . 'crm/lead/~i', $firstPagePath)
			&& !$this->isCrmLeadAvailable()
		)
		{
			if ($this->isCrmKanbanAvailable())
			{
				$firstPagePath = SITE_DIR . 'crm/deal/kanban/';
			}
			else
			{
				$firstPagePath = \CCrmOwnerType::GetListUrl(\CCrmOwnerType::Deal);
			}
		}

		// if empty set 'stream/'
		if (
			empty($firstPagePath)
			|| preg_match('~^(/(\\?.*)?|/index.php(\\?.*)?)$~i', $firstPagePath)
			|| preg_match('~^(http|//|/company/personal/mail/)~i', $firstPagePath)
		)
		{
			$firstPagePath = SITE_DIR . 'stream/';
		}

		return new Uri($firstPagePath);
	}

	protected function getUserId(): string
	{
		return (string)$this->userId;
	}

	protected function getUserFirstPage(): string
	{
		return \CUserOptions::GetOption('intranet', 'left_menu_first_page_' . SITE_ID, '');
	}

	protected function getPortalFirstPage(): string
	{
		return Option::get('intranet', 'left_menu_first_page', '');
	}

	protected function isCrmAvailable(): bool
	{
		return \CCrmPerms::IsAccessEnabled();
	}

	protected function isCrmLeadAvailable(): bool
	{
		return \Bitrix\Crm\Settings\LeadSettings::isEnabled();
	}

	protected function isCrmKanbanAvailable(): bool
	{
		return \Bitrix\Crm\Settings\DealSettings::getCurrent()->getCurrentListViewID() === \Bitrix\Crm\Settings\DealSettings::VIEW_KANBAN;
	}
}
