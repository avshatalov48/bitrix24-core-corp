<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Exception;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\V2\Internal\Entity\Template;
use Bitrix\Tasks\V2\Internal\Repository\Trait\ApplicationErrorTrait;
use Bitrix\Tasks\Control;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TemplateControlMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TemplateMapper;

class TemplateRepository implements TemplateRepositoryInterface
{
	use ApplicationErrorTrait;

	protected TemplateMapper $mapper;

	public function __construct(TemplateMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	public function getById(int $id): Template|null
	{
		$template = TemplateTable::getByPrimary($id)->fetchObject();

		if ($template === null)
		{
			return null;
		}

		return $this->mapper->mapFromTemplateObject($template);
	}

	public function save(Template $entity, int $userId): int
	{
		return $entity->getId()
			? $this->update($entity, $userId)
			: $this->add($entity, $userId)
		;
	}

	public function delete(int $id, int $userId): void
	{
		$control = new Control\Template($userId);

		$result = $control->delete($id);

		if ($result === false)
		{
			$message = $this->getErrorMessage() ?? 'Unexpected error';

			// todo: custom exception
			throw new Exception($message);
		}
	}

	private function update(Template $entity, int $userId): int
	{
		$currentTemplate = $this->getById($entity->getId());

		if ($currentTemplate === null)
		{
			// todo: custom exception
			throw new Exception('Not found');
		}

		$control = (new Control\Template($userId));

		$templateToUpdate = Template::mapFromArray($entity->diff($currentTemplate));

		$control->update(
			id: $entity->getId(),
			fields: (new TemplateControlMapper())->mapToControl($templateToUpdate),
		);

		return $entity->getId();
	}

	private function add(Template $entity, int $userId): int
	{
		$control = new Control\Template($userId);

		return $control->add(fields: (new TemplateControlMapper())->mapToControl($entity))->getId();
	}
}
