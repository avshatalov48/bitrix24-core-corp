<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Sign\Contract\Operation as OperationContract;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Repository\Document\TemplateFolderRelationRepository;
use Bitrix\Sign\Repository\Document\TemplateFolderRepository;
use Bitrix\Sign\Repository\Document\TemplateRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Template\EntityType;

class DeleteTemplateEntity implements OperationContract
{
	private readonly TemplateRepository $templateRepository;
	private readonly TemplateFolderRepository $templateFolderRepository;
	private readonly TemplateFolderRelationRepository $templateFolderRelationRepository;

	public function __construct(
		/**
		 * @var list<TemplateFolderRelation>
		 */
		private readonly array $templateFolderRelations,
		?TemplateRepository $templateRepository = null,
		?TemplateFolderRepository $templateFolderRepository = null,
		?TemplateFolderRelationRepository $templateFolderRelationRepository = null,
	)
	{
		$container = Container::instance();

		$this->templateRepository = $templateRepository ?? $container->getDocumentTemplateRepository();
		$this->templateFolderRepository = $templateFolderRepository ?? $container->getTemplateFolderRepository();
		$this->templateFolderRelationRepository = $templateFolderRelationRepository ?? $container->getTemplateFolderRelationRepository();
	}

	public function launch(): Main\Result
	{
		$connection = Application::getConnection();
		$connection->startTransaction();
		try
		{

			$folderIds = [];
			$templateIds = [];
			foreach ($this->templateFolderRelations as $item)
			{
				$entityType = $item->entityType ?? null;
				if ($entityType === null)
				{
					continue;
				}

				if ($entityType->isFolder())
				{
					$folderIds[] = $item->entityId;
				}

				if ($entityType->isTemplate())
				{
					$templateIds[] = $item->entityId;
				}
			}

			$result = $this->templateFolderRelationRepository->deleteByIdsAndType($templateIds, EntityType::TEMPLATE);
			if (!$result->isSuccess())
			{
				throw new SignException('Delete relations error');
			}

			$result = $this->templateFolderRelationRepository->deleteByIdsAndType($folderIds, EntityType::FOLDER);
			if (!$result->isSuccess())
			{
				throw new SignException('Delete relations error');
			}

			$templates = $this->templateRepository->getByIds($templateIds);
			foreach ($templates as $template)
			{
				$result = (new Delete($template))->launch();
				if (!$result->isSuccess())
				{
					throw new SignException('Delete templates error');
				}
			}

			$result = $this->templateFolderRepository->deleteByIds($folderIds);
			if (!$result->isSuccess())
			{
				throw new SignException('Delete folders error');
			}

			$connection->commitTransaction();
		}
		catch (SignException $e)
		{
			$connection->rollbackTransaction();
			return Result::createByErrorMessage($e->getMessage());
		}

		return new Main\Result();
	}
}
