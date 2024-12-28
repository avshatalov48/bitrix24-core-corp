<?php

namespace Bitrix\HumanResources\Controller\HcmLink\Company;

use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Engine\HcmLinkController;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Intranet;

class Config extends HcmLinkController
{
	public function getDefaultPreFilters(): array
	{
		return [
			new Main\Engine\ActionFilter\ContentType([Main\Engine\ActionFilter\ContentType::JSON]),
			new Main\Engine\ActionFilter\Authentication(),
			new Main\Engine\ActionFilter\HttpMethod(
				[Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
			),
			new Intranet\ActionFilter\IntranetUser()
		];
	}

	public function loadAction(int $companyId): array
	{
		if (!$this->checkAccess())
		{
			return [];
		}

		$company = Container::getHcmLinkCompanyRepository()->getById($companyId);
		if ($company === null)
		{
			$this->addError(new Main\Error(
				Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_COMPANY_CONFIG_COMPANY_NOT_FOUND')
			));

			return [];
		}

		$config = [];
		$data = $company->data['config'];

		try
		{
			$data = json_decode($data);
		}
		catch (\Exception $e)
		{

			$this->addError(new Main\Error(
				Main\Localization\Loc::getMessage('HUMANRESOURCES_HCMLINK_COMPANY_CONFIG_COMPANY_NOT_FOUND')
			));

			return [];
		}

		if (is_array($data))
		{
			$config = array_map(
				fn($item) => ['title' => $item->title, 'value' => $item->value,],
				$data
			);
		}

		return compact('config');
	}

	private function checkAccess(): bool
	{
		if (!Container::getHcmLinkAccessService()->canRead())
		{
			$this->addError($this->makeAccessDeniedError());
			return false;
		}

		return true;
	}
}
