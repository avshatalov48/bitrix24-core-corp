<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Main\Error;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Integration\CrmManager;

class Manager extends Base
{
	/**
	 * @return array
	 */
	public function getConfigAction()
	{
		return Driver::getInstance()->getManagerParams();
	}

	public function addAnalyticAction()
	{

	}

	public function getFieldsMapAction(): ?array
	{
		$fields = Driver::getInstance()->getFieldsManager()->getFieldsMap();

		return [
			'fields' => $fields,
		];
	}

	public function getPageUrlAction(int $pageId, array $entities): ?array
	{
		$page = \Bitrix\SalesCenter\Model\PageTable::getById($pageId)->fetchObject();
		if(!$page)
		{
			$this->addError(new Error('Page with id '.$pageId.' not found'));
			return null;
		}

		$pageUrl = $page->getUrl();

		if(CrmManager::getInstance()->isEnabled())
		{
			$fieldsManager = Driver::getInstance()->getFieldsManager();
			$ids = [];

			foreach($entities as $entityTypeId => $entityId)
			{
				$ids[\CCrmOwnerType::ResolveName($entityTypeId)] = $entityId;
			}

			if(!empty($ids))
			{
				$pageUrl = $fieldsManager->setIds($ids)->getUrlWithParameters($page);
			}
		}

		return [
			'pageUrl' => $pageUrl,
		];
	}
}