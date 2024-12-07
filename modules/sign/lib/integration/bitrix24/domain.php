<?php

namespace Bitrix\Sign\Integration\Bitrix24;

use Bitrix\Sign\Item\Api\Client\DomainRequest;
use Bitrix\Sign\Service\Container;

class Domain
{
	/**
	 * @param array $domains {new_domain: string, old_domain: string}
	 * @return void
	 */
	public static function onChangeDomain(array $domains): void
	{
		Container::instance()->getApiClientDomainService()->change(
			new DomainRequest($domains['new_domain'])
		);
	}
}
