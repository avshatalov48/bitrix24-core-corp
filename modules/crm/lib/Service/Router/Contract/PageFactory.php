<?php

namespace Bitrix\Crm\Service\Router\Contract;

use Bitrix\Crm\Service\Router\Contract\Page\StaticPage;
use Bitrix\Crm\Service\Router\Contract\Page\UserDefinedPage;

interface PageFactory
{
	/**
	 * @return StaticPage[]|string[]
	 */
	public function getStaticPages(): array;

	/**
	 * @return UserDefinedPage[]
	 */
	public function getUserDefinedPages(): array;

	public function getStaticPagesComponentUrlMap(): array;
}
