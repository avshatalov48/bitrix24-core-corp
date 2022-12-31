<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Integration\Catalog\ProductWizard\ConfigQuery;
use Bitrix\Mobile\Integration\Catalog\ProductWizard\SaveProductCommand;

class ProductWizard extends Controller
{
	/** @var AccessController */
	private $accessController;

	/**
	 * @inheritDoc
	 */
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		$this->accessController = AccessController::getCurrent();
	}

	/**
	 * Get config data for catalog product wizard.
	 *
	 * @param string $wizardType Wizard type
	 * @return array|\array[][]
	 */
	public function configAction(string $wizardType): array
	{
		if (!$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_PRODUCT_WIZARD_ACCESS_DENIED')));
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
		$result = (new SaveProductCommand($fields, $id))->execute();

		if ($result->isSuccess())
		{
			return $result->getData();
		}

		$this->errorCollection->add($result->getErrors());
		return null;
	}
}
