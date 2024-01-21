<?php

namespace Bitrix\ListsMobile\Controller\Action\Process;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\UI\StatefulList\BaseAction;
use Bitrix\Lists\Api\Data\ServiceFactory\ListsToGetFilter;
use Bitrix\Lists\Api\Request\ServiceFactory\GetListRequest;
use Bitrix\Lists\Api\Service\ServiceFactory\ProcessService;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Lists\Service\Param;

Loader::requireModule('lists');

class LoadPersonalListAction extends BaseAction
{
	public function run(PageNavigation $pageNavigation, array $extra = [])
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		$iBlockTypeId = Option::get('lists', 'livefeed_iblock_type_id', 'bitrix_processes');

		/** @var ProcessService $service */
		$service = ServiceFactory::getServiceByIBlockTypeId($iBlockTypeId, $currentUserId);

		$filter =
			(new ListsToGetFilter())
				->setIBlockType($iBlockTypeId)
				->setCreatedBy($currentUserId)
				->setWorkflowState('R')
		;
		$result = $service->getElementList(
			new GetListRequest(
				['ID' => 'DESC'],
				$filter,
				$pageNavigation->getOffset(),
				$pageNavigation->getLimit(),
				additionalSelectFields: ['IBLOCK_ID', 'IBLOCK_SECTION_ID', 'IBLOCK_TYPE_ID']
			)
		);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return $this->showErrors();
		}

		$converter = new Converter(Converter::KEYS | Converter::LC_FIRST | Converter::TO_CAMEL);

		$items = array_map(
			static function($process) use ($converter)
			{
				return [
					'id' => (int)$process['ID'],
					'data' => [
						'id' => (int)$process['ID'],
						'name' => (string)$process['NAME'],
						'process' => $converter->process($process),
					],
				];
			},
			$result->getElements()
		);

		return [
			'items' => $items,
			'permissions' => [],
		];
	}
}
