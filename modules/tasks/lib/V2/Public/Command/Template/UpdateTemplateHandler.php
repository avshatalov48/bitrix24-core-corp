<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Template;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;

class UpdateTemplateHandler
{
	protected TemplateRepositoryInterface $repository;

	public function __construct(TemplateRepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function __invoke(UpdateTemplateCommand $command): Entity\Template
	{
		// todo: wrap with transaction

		$id = $this->repository->save($command->template, $command->updatedBy);

		return $this->repository->getById($id);
	}
}
