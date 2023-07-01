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

class MultiField extends JsonController
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
			new CheckWritePermission(),
		];
	}

	public function saveAction(Item $entity, array $values): ?array
	{
		$values = $this->prepareFmData($values);
		if (empty($values))
		{
			$errors = $this->markErrorsAsPublic([new Error(Loc::getMessage('MCRM_MULTIFIELD_EMPTY_VALUES'))]);
			$this->addErrors($errors);

			return null;
		}

		foreach ($values as $fmType => $fmData)
		{
			$value = $fmData['value'] ?? '';
			$type = $fmData['type'] ?? \CCrmFieldMulti::GetDefaultValueType($fmType);
			$countryCode = null;

			if ($fmType === Type\Phone::ID)
			{
				$countryCode = $value['countryCode'] ?? null;
				$value = $value['phoneNumber'] ?? '';
			}

			$fmValue = (new Value())
				->setTypeId($fmType)
				->setValueType($type)
				->setValue($value)
			;

			if ($countryCode !== null)
			{
				$fmValue->setValueExtra(
					(new ValueExtra())->setCountryCode($countryCode)
				);
			}

			$entity->setFm($entity->getFm()->add($fmValue));
		}

		$operation = Container::getInstance()
			->getFactory($entity->getEntityTypeId())
			->getUpdateOperation($entity)
			->launch()
		;

		if (!$operation->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($operation->getErrors());
			$this->addErrors($errors);

			return null;
		}

		return $entity->getFm()->toArray();
	}

	private function prepareFmData(array $values): array
	{
		$fmData = [];

		foreach ($values as $fmType => $fmValue)
		{
			if (\CCrmFieldMulti::IsSupportedType($fmType))
			{
				$value = $fmValue['value'] ?? null;

				if ($value !== null && $value !== '')
				{
					$fmData[$fmType] = $fmValue;
				}
			}
		}

		return $fmData;
	}
}
