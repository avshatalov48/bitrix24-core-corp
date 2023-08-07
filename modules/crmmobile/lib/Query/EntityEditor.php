<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Query;

use Bitrix\Crm\Integration\UI\EntityEditor\Provider;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\CrmMobile\UI\EntityEditor\ProviderDecorator;
use Bitrix\Mobile\Query;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

final class EntityEditor extends Query
{
	private Factory $factory;
	private Item $entity;
	private array $params;

	public function __construct(Factory $factory, Item $entity, array $params = [])
	{
		$this->factory = $factory;
		$this->entity = $entity;

		$this->params = $params;
	}

	public function execute(array $requiredFields = null): array
	{
		$provider = new Provider($this->entity, $this->params);
		$mobileDecoratedProvider = new ProviderDecorator($provider, $this->factory, $this->entity);
		$formWrapper = new FormWrapper($mobileDecoratedProvider, 'bitrix:crm.entity.editor');

		if ($requiredFields !== null)
		{
			return $formWrapper->getRequiredFields($requiredFields);
		}

		return $formWrapper->getResult();
	}
}
