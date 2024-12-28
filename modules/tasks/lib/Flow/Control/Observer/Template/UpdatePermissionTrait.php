<?php

namespace Bitrix\Tasks\Flow\Control\Observer\Template;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\InvalidCommandException;
use Bitrix\Tasks\Flow\Template\Access\Permission\TemplatePermissionCommand;
use Bitrix\Tasks\Flow\Template\Access\Permission\TemplatePermissionService;
use Bitrix\Tasks\Integration\Intranet\Department;
use Psr\Container\NotFoundExceptionInterface;

trait UpdatePermissionTrait
{
	/**
	 * @throws InvalidCommandException
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	public function updatePermission(AddCommand|UpdateCommand $command): void
	{
		$accessCodes = $this->convertAccessCodes($command);

		$service = ServiceLocator::getInstance()->get('tasks.flow.template.permission.service');

		$templateCommand = (new TemplatePermissionCommand())
			->setTemplateId($command->templateId)
			->setAccessCodes($accessCodes)
			->setPermissionId(PermissionDictionary::TEMPLATE_VIEW)
			->setValue(\Bitrix\Main\Access\Permission\PermissionDictionary::VALUE_YES);

		$service->merge($templateCommand);
	}

	protected function convertAccessCodes(AddCommand|UpdateCommand $command): array
	{
		$accessCodes = [];
		foreach ($command->taskCreators as $taskCreatorAccessCode)
		{
			if ($taskCreatorAccessCode === 'UA')
			{
				$accessCodes[] = Department::getMainDepartmentAccessCode();
				continue;
			}

			$ac = new AccessCode($taskCreatorAccessCode);
			if ($ac->getEntityPrefix() === 'D')
			{
				// map 'only department members' to 'department with subdepartment'
				$accessCodes[] = 'DR' . $ac->getEntityId();
				continue;
			}

			$accessCodes[] = $taskCreatorAccessCode;
		}

		return array_unique($accessCodes);
	}
}