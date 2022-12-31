<?php

namespace Bitrix\CrmMobile\Query;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Loader;
use Bitrix\CrmMobile\UI\EntityEditor\Provider;
use Bitrix\Mobile\Query;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

Loader::requireModule('crm');

final class EntityEditor extends Query
{
	/** @var Factory */
	private $factory;

	/** @var Item */
	private $entity;

	/** @var array */
	private $params;

	public function __construct(Factory $factory, Item $entity, array $params = [])
	{
		$this->factory = $factory;
		$this->entity = $entity;
		$this->params = $params;
	}

	public function execute(): array
	{
		$provider = new Provider($this->factory, $this->entity, $this->params);

		return [
			'editor' => (new FormWrapper($provider, 'bitrix:crm.entity.editor'))->getResult(),
		];
	}
}
