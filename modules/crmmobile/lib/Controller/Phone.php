<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Engine\ActionFilter\CheckWritePermission;
use Bitrix\Crm\Item;
use Bitrix\Crm\Multifield\Type;
use Bitrix\Crm\Multifield\Value;
use Bitrix\Crm\Multifield\ValueExtra;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\ActionFilter;

Loader::requireModule('crm');

class Phone extends JsonController
{
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new CheckReadPermission(),
		];
	}

	public function configureActions(): array
	{
		return [
			'addToContact' => [
				'+prefilters' => [
					new CheckWritePermission(),
				],
			],
		];
	}

	public function addToContactAction(Item $entity, string $phone, string $countryCode): bool
	{
		if (empty($phone))
		{
			$errors = $this->markErrorsAsPublic([
				new Error(Loc::getMessage('M_CRM_PHONE_EMPTY')),
			]);

			$this->addErrors($errors);

			return false;
		}

		$phoneValue = (new Value())
			->setTypeId(Type\Phone::ID)
			->setValueType(Type\Phone::VALUE_TYPE_WORK)
			->setValueExtra((new ValueExtra())->setCountryCode($countryCode))
			->setValue($phone)
		;

		$entity->setFm($entity->getFm()->add($phoneValue));

		$operation = Container::getInstance()
			->getFactory($entity->getEntityTypeId())
			->getUpdateOperation($entity)
			->launch()
		;

		if (!$operation->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($operation->getErrors());
			$this->addErrors($errors);
			return false;
		}

		return true;
	}
}
