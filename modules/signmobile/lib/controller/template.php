<?php

namespace Bitrix\SignMobile\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Sign\Type\ProviderCode;
use Bitrix\SignMobile\Serializer\MobileMasterFieldSerializer;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentScenario;

class Template extends Controller
{
	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		return
			[
				new Main\Engine\ActionFilter\ContentType([Main\Engine\ActionFilter\ContentType::JSON]),
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\HttpMethod(
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
				),
				new Intranet\ActionFilter\IntranetUser()
			];
	}

	private function includeRequiredModules(): bool
	{
		$result = (Loader::includeModule('mobile')
			&& Loader::includeModule('sign')
			&& Loader::includeModule('intranet'));

		if (!$result)
		{
			$this->addError(
				new Error(
					'Modules must be installed: mobile, sign, intranet', 'REQUIRED_MODULES_NOT_INSTALLED'
				)
			);
		}

		return $result;
	}

	public function getFieldsAction(
		string $uid,
	): array
	{
		if (!$this->includeRequiredModules())
		{
			return [];
		}

		$template = Container::instance()->getDocumentTemplateRepository()->getByUid($uid);
		if ($template === null)
		{
			$this->addError(new Error('Template not found'));

			return [];
		}

		$document = Container::instance()->getDocumentRepository()->getByTemplateId($template->id);
		if ($document === null)
		{
			$this->addError(new Error('Document not found'));

			return [];
		}

		if (!DocumentScenario::isB2EScenario($document->scenario) || empty($document->companyUid))
		{
			$this->addError(new Error('Incorrect document'));

			return [];
		}

		$providerCodeForAnalytics = ProviderCode::toAnalyticString($document->providerCode);
		$factory = new \Bitrix\Sign\Factory\Field();
		$fields = $factory->createDocumentFutureSignerFields($document, CurrentUser::get()->getId());

		return [
			'fields' => (new MobileMasterFieldSerializer())->serialize($fields),
			'providerCodeForAnalytics' => $providerCodeForAnalytics,
		];
	}
}