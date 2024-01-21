<?php

namespace Bitrix\ListsMobile\Controller\Action\Process;

use Bitrix\Lists\Api\Service\ServiceFactory\ProcessService;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Lists\Service\Param;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

Loader::requireModule('lists');

class LoadCatalogAction extends \Bitrix\Main\Engine\Action
{
	public function run()
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		$iBlockTypeId = Option::get('lists', 'livefeed_iblock_type_id', 'bitrix_processes');

		 /** @var ProcessService $service */
		$service = ServiceFactory::getServiceByIBlockTypeId($iBlockTypeId, $currentUserId);

		$result = $service->getCatalog();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return [
				'items' => [],
			];
		}

		$items = [];
		foreach ($result->getCatalog() as $process)
		{
			$items[] = [
				'id' => $process['ID'],
				'name' => $process['NAME'],
				'pictureSrc' => $this->getResizedPicture((int)$process['PICTURE'])['src'],
				'iBlockTypeId' => $iBlockTypeId,
			];
		}

		return [
			'items' => $items,
		];
	}

	private function getResizedPicture(int $pictureId)
	{
		if ($pictureId > 0)
		{
			return \CFile::ResizeImageGet($pictureId, ['width' => 50, 'height' => 50], BX_RESIZE_IMAGE_EXACT, false);
		}

		return false;
	}
}
