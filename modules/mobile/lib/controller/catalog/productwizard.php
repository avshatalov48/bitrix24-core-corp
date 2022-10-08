<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Main\Error;
use Bitrix\Mobile\Integration\Catalog\ProductWizard\ConfigQuery;
use Bitrix\Mobile\Integration\Catalog\ProductWizard\SaveProductCommand;

class ProductWizard extends \Bitrix\Main\Engine\Controller
{
	use CatalogPermissions;

	/**
	 * Get config data for catalog product wizard.
	 *
	 * @param string $wizardType Wizard type
	 * @return array|\array[][]
	 */
	public function configAction(string $wizardType): array
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));
			return [];
		}

		return (new ConfigQuery($wizardType))->execute();
	}

	/**
	 * Calls on every wizard step.
	 *
	 * @param array $fields
	 * @param int|null $id
	 * @return array|null
	 */
	public function saveProductAction(array $fields, int $id = null): ?array
	{
		if (!$this->hasWritePermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));
			return null;
		}

		$result = (new SaveProductCommand($fields, $id))->execute();

		if ($result->isSuccess())
		{
			return $result->getData();
		}

		$this->errorCollection->add($result->getErrors());
		return null;
	}
}
