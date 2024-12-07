<?php

namespace Bitrix\Intranet\Site\FirstPage;

use Bitrix\Intranet\CurrentUser;

class FirstPageProvider
{
	/**
	 * @return FirstPage[]
	 */
	private function getPages(): array
	{
		return [
			new MainFirstPage(),
		];
	}

	/**
	 * @return FirstPage
	 */
	public function getAvailablePage(): FirstPage
	{
		foreach ($this->getPages() as $page)
		{
			if ($page->isEnabled())
			{
				return $page;
			}
		}

		return new IntranetFirstPage(CurrentUser::get()->getId());
	}
}