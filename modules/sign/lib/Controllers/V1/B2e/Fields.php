<?php

namespace Bitrix\Sign\Controllers\V1\B2e;

use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute\Access\LogicOr;
use Bitrix\Sign\Attribute\ActionAccess;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\Field\FrontFieldCategory;

class Fields extends Controller
{
	#[LogicOr(
		new ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_READ),
		new ActionAccess(ActionDictionary::ACTION_B2E_TEMPLATE_READ),
	)]
	public function loadAction(array $options = []): array
	{
		if (!\CModule::IncludeModule('crm'))
		{
			$this->addError(new Main\Error('Module `crm` is not installed'));
			return [];
		}

		$crmFieldsData = (new Crm\Controller\Form\Fields\Selector())->getDataAction($options);
		$crmFieldsData['options']['permissions']['userField']['addByCategory'] =
			$this->getAddByCategoryPermissions($crmFieldsData)
		;
		$crmFieldsData['fields'] = array_merge(
			$crmFieldsData['fields'],
			Service\Container::instance()->getServiceProfileProvider()->getFieldsForSelector(),
			Service\Container::instance()->getMemberDynamicFieldProvider()->getFieldsForSelector(),
		);

		return $crmFieldsData;
	}

	/**
	 * @param array $crmFieldsData
	 *
	 * @return array<string, bool>
	 */
	private function getAddByCategoryPermissions(array $crmFieldsData): array
	{
		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();
		$accessController = new AccessController($currentUserId);

		return [
			FrontFieldCategory::PROFILE->value =>
				$accessController->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_ADD)
			,
			FrontFieldCategory::DYNAMIC_MEMBER->value =>
				$accessController->check(ActionDictionary::ACTION_B2E_TEMPLATE_ADD)
			,
		] + $this->getOtherCategoriesAddPermissions($crmFieldsData);
	}

	/**
	 * @param array{fields: array, options: array} $crmFieldsData
	 *
	 * @return array<string, bool>
	 */
	private function getOtherCategoriesAddPermissions(array $crmFieldsData): array
	{
		$otherCategories = array_keys((array)($crmFieldsData['fields'] ?? []));
		$otherCategoriesAddPermission = (bool)($crmFieldsData['options']['permissions']['userField']['add'] ?? false);

		$arrayPermission = array_fill(0, count($otherCategories), $otherCategoriesAddPermission);

		return array_combine($otherCategories, $arrayPermission);
	}
}
