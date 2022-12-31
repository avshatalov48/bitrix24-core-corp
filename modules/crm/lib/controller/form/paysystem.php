<?php

namespace Bitrix\Crm\Controller\Form;

use Bitrix\Crm\WebForm;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use CFile;

class PaySystem extends JsonController
{
	private const MODULE_NOT_INCLUDED_ERROR_CODE = 'MODULE_NOT_INCLUDED';

	public function listAction(): array
	{
		if (!Loader::includeModule('sale'))
		{
			$this->addError(
				new Error('The sale module must be installed', self::MODULE_NOT_INCLUDED_ERROR_CODE)
			);
			return [];
		}
		if (!Loader::includeModule('salescenter'))
		{
			$this->addError(
				new Error('The salecenter module must be installed', self::MODULE_NOT_INCLUDED_ERROR_CODE)
			);
			return [];
		}
		if (!$this->checkReadFormAccess())
		{
			return [];
		}

		$serviceLocator = ServiceLocator::getInstance();

		$paySystemManager = $serviceLocator->get('sale.paysystem.manager');
		$paySystems =
			$paySystemManager
				::getList([
					'select' => ['ID', 'NAME', 'LOGOTIP'],
					'filter' => [
						'!=ACTION_FILE' => ['inner', 'cash'],
						'=ACTIVE' => 'Y',
					],
				])
				->fetchAll()
		;
		$salehubPaySystemItems =
			$serviceLocator
				->get('salecenter.integration.salemanager')
				::getSaleshubPaySystemItems()
		;

		$recommendedPaySystemsResult =
			$serviceLocator
				->get('salecenter.component.paysystem')
				->getRecommendedItems(
					$salehubPaySystemItems,
					true,
				)
		;

		$recommendedPaySystems = $recommendedPaySystemsResult->isSuccess()
			? $recommendedPaySystemsResult->getData()
			: []
		;

		$this->addErrors($recommendedPaySystemsResult->getErrors());

		$result = [
			'active' => [],
			'recommended' => [],
		];
		foreach ($paySystems as $paySystem)
		{
			$result['active'][] = $this->mapPaySystemFormList(
				(int)$paySystem['ID'],
				$paySystem['NAME'],
				CFile::GetPath($paySystem['LOGOTIP']),
			);
		}
		foreach ($recommendedPaySystems as $recommendedPaySystem)
		{
			if (!$recommendedPaySystem['itemSelected'])
			{
				$result['recommended'][] = $this->mapRecommendedPaySystems(
					$recommendedPaySystem['id'],
					$recommendedPaySystem['title'],
					$recommendedPaySystem['image'],
					$recommendedPaySystem['data']['connectPath'],
				);
			}
		}

		return $result;
	}

	private function mapRecommendedPaySystems(
		string $id,
		string $title,
		string $image,
		string $editPath
	): array
	{
		return [
			'id' => $id,
			'title' => $title,
			'image' => $image,
			'editPath' => $editPath,
		];
	}

	private function mapPaySystemFormList(
		?int $id = null,
		?string $title = null,
		?string $image = null
	): array
	{
		return [
			'id' => $id,
			'title' => $title,
			'image' => $image,
		];
	}

	private function checkReadFormAccess(): bool
	{
		if (!$this->getReadFormAccess())
		{
			$this->addError(new Main\Error('Access denied.', 510));

			return false;
		}

		return true;
	}

	private function getReadFormAccess()
	{
		return WebForm\Manager::checkReadPermission();
	}
}