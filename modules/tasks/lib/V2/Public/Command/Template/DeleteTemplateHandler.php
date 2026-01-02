<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Template;

use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;

class DeleteTemplateHandler
{
	protected TemplateRepositoryInterface $repository;

	public function __construct(TemplateRepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function __invoke(DeleteTemplateCommand $command): void
	{
		if (!$this->repository->getById($command->templateId))
		{
			// todo: refactor to send specific exceptions
			throw new TemplateNotFoundException('Template with id ' . $command->templateId . ' not found');
		}

		$this->repository->delete($command->templateId, $command->deletedBy);
	}
}
