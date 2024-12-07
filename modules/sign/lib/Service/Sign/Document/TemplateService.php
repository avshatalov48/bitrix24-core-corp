<?php

namespace Bitrix\Sign\Service\Sign\Document;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;

final class TemplateService
{
	private readonly TemplateRepository $templateRepository;

	public function __construct(
		?TemplateRepository $templateRepository = null,
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
	}

	public function getB2eEmployeeTemplateList(ConditionTree $filter, int $limit = 10, int $offset = 0): TemplateCollection
	{
		return $this->templateRepository->getB2eEmployeeTemplateList($filter, $limit, $offset);
	}

	public function getB2eEmployeeTemplateListCount(ConditionTree $filter): int
	{
		return $this->templateRepository->getB2eEmployeeTemplateListCount($filter);
	}

	public function updateTitle(int $templateId, string $title): Result
	{
		return $this->templateRepository->updateTitle($templateId, $title);
	}
}
