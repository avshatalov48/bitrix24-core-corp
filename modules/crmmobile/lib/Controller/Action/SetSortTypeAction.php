<?php


namespace Bitrix\CrmMobile\Controller\Action;


use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\Mobile\Trait\PublicErrorsTrait;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class SetSortTypeAction extends Action
{
	use PublicErrorsTrait;

	public function run(string $type, int $entityTypeId, ?int $categoryId = 0)
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		if ($categoryId === null)
		{
			$categoryId = 0;
		}

		$result = $this->setSortType($type, $entityTypeId, $categoryId);
		if (!$result->isSuccess())
		{
			$errors = $this->markErrorsAsPublic($result->getErrors());
			$this->addErrors($errors);
		}
	}

	private function setSortType(string $type, int $entityTypeId, int $categoryId): Result
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory->isLastActivityEnabled())
		{
			$result = new Result();
			$result->addError(
				new Error('Sort in ' . $factory->getEntityName() . ' not supported')
			);

			return $result;
		}

		$instance = Entity::getInstance($factory->getEntityName());

		return $instance
			->setCategoryId($categoryId)
			->setCurrentSortType($type)
		;
	}
}
