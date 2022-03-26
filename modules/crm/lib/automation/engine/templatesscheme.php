<?php

namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Bizproc\Automation\Engine\TemplateScope;
use Bitrix\Crm\Automation;
use Bitrix\Crm\Order;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\OrderSettings;
use Bitrix\Main\Loader;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class TemplatesScheme extends \Bitrix\Bizproc\Automation\Engine\TemplatesScheme
{
	public function isAutomationAvailable(array $complexDocumentType): bool
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($complexDocumentType[2]);
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$isAvailable = Automation\Factory::isAutomationAvailable($entityTypeId);
		if (isset($factory))
		{
			$isAvailable &= $factory->isAutomationEnabled() && $factory->isStagesEnabled();
		}

		return $isAvailable;
	}

	protected function hasTemplate(TemplateScope $scope): bool
	{
		if ($scope->getCategoryId() === 0)
		{
			$typeId = \CCrmOwnerType::ResolveID($scope->getComplexDocumentType()[2]);
			$factory = Container::getInstance()->getFactory($typeId);

			$isCategoriesSupported =
				isset($factory)
				&& $factory->isCategoriesSupported()
				&& !is_null($factory->getCategory(0))
			;
			if (!$isCategoriesSupported)
			{
				$realScope = new TemplateScope($scope->getComplexDocumentType(), null, $scope->getStatusId());

				return parent::hasTemplate($realScope);
			}
		}

		return parent::hasTemplate($scope);
	}

	public function build(): void
	{
		$types = Container::getInstance()->getTypesMap()->getFactories();
		foreach ($types as $factory)
		{
			$documentType = \CCrmBizProcHelper::ResolveDocumentType($factory->getEntityTypeId());
			if (!is_array($documentType) || !$this->isAutomationAvailable($documentType))
			{
				continue;
			}
			$categories = $factory->isCategoriesEnabled() ? $factory->getCategories() : [null];

			foreach ($categories as $category)
			{
				$categoryId = isset($category) ? $category->getId() : null;
				$categoryName = isset($category) ? $category->getName() : null;

				foreach ($factory->getStages($categoryId) as $stage)
				{
					$scope = new TemplateScope($documentType, $categoryId, $stage->getStatusId());
					$scope->setNames($categoryName, $stage->getName());
					$scope->setStatusColor($stage->getColor());

					$this->addTemplate($scope);
				}
			}
		}

		$this->addOrderTemplates();
	}

	private function addOrderTemplates()
	{
		$statuses = Order\OrderStatus::getListInCrmFormat();

		$documentType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::Order);
		foreach ($statuses as $statusInfo)
		{
			$scope = new TemplateScope($documentType, null, $statusInfo['STATUS_ID']);
			$scope->setNames(null, $statusInfo['NAME']);
			if (isset($statusInfo['COLOR']) && is_string($statusInfo['COLOR']) && $statusInfo['COLOR'])
			{
				$scope->setStatusColor($statusInfo['COLOR']);
			}

			$this->addTemplate($scope);
		}
	}
}