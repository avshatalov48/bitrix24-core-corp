<?php

namespace Bitrix\ListsMobile\Command;

use Bitrix\Lists\Api\Request\ServiceFactory\GetElementDetailInfoRequest;
use Bitrix\Lists\Api\Service\ServiceFactory\ServiceFactory;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Mobile\Command;

Loader::requireModule('lists');

final class LoadEntityCommand extends Command
{
	private ServiceFactory $service;
	private int $entityId;
	private int $sectionId;
	private int $iBlockId;

	public function __construct(ServiceFactory $service, int $entityId, int $sectionId, int $iBlockId)
	{
		$this->service = $service;
		$this->entityId = $entityId;
		$this->sectionId = $sectionId;
		$this->iBlockId = $iBlockId;
	}

	public function execute(): Result
	{
		$result = new Result();

		$entity = ['ID' => $this->entityId, 'IBLOCK_ID' => $this->iBlockId, 'IBLOCK_SECTION_ID' => $this->sectionId];
		if ($this->entityId > 0)
		{
			$elementInfoResult = $this->service->getElementDetailInfo(
				new GetElementDetailInfoRequest(
					$this->iBlockId,
					$this->entityId,
					$this->sectionId,
					['FIELDS', 'PROPS', 'IBLOCK_ID', 'IBLOCK_SECTION_ID'],
					true,
				)
			);
			$result->addErrors($elementInfoResult->getErrors());
			if ($elementInfoResult->hasInfo())
			{
				$entity = $elementInfoResult->getInfo();
			}
		}

		$result->setData([
			'entity' => $entity,
		]);

		return $result;
	}
}
