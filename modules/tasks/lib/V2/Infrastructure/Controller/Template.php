<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;


use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\V2\Public\Command\Template\AddTemplateCommand;
use Bitrix\Tasks\V2\Public\Command\Template\DeleteTemplateCommand;
use Bitrix\Tasks\V2\Public\Command\Template\UpdateTemplateCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Template\Permission;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;

class Template extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Template.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Template $template,
		TemplateRepositoryInterface $templateRepository,
	): ?Arrayable
	{
		return $templateRepository->getById($template->getId());
	}

	/**
	 * @restMethod tasks.V2.Template.add
	 */
	public function addAction(
		#[Permission\Add]
		Entity\Template $template
	): ?Arrayable
	{
		$result = (new AddTemplateCommand(template: $template, createdBy: $this->userId))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}

	/**
	 * @restMethod tasks.V2.Template.update
	 */
	public function updateAction(
		#[Permission\Update] Entity\Template $template
	): ?Arrayable
	{
		$result = (new UpdateTemplateCommand(template: $template, updatedBy: $this->userId))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}

	/**
	 * @restMethod tasks.V2.Template.delete
	 */
	public function deleteAction(
		#[Permission\Delete]
		Entity\Template $template
	): ?Arrayable
	{
		$result = (new DeleteTemplateCommand(templateId: $template->getId(), deletedBy: $this->userId))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}
}
