<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Security\QueryBuilder\Result;
use Bitrix\Crm\Security\QueryBuilder\Options;
use Bitrix\Main\NotSupportedException;
use Bitrix\Crm\Security\AccessAttribute\Collection;

class QueryBuilder
{
	private $options;
	private $permissionEntityTypes = [];
	private $userPermissions;
	private $controller;

	public function __construct(
		array $permissionEntityTypes,
		\Bitrix\Crm\Service\UserPermissions $userPermissions,
		Options $options = null
	)
	{
		$this->userPermissions = $userPermissions;
		$this->options = ($options ?? new Options());
		$this->permissionEntityTypes = $permissionEntityTypes;
	}

	/**
	 * Build permissions query. Result contains access flag and permissions sql.
	 *
	 * @return Result
	 * @throws NotSupportedException
	 */
	public function build(): Result
	{
		$result = new Result();

		if ($this->userPermissions->isAdmin())
		{
			$result
				->setHasAccess(true)
				->setSql('')
			;

			return $result;
		}

		$attributes = $this->getAttributes();

		if (empty($attributes->getAllowedEntityTypes()))  // nothing allowed, access is denied
		{
			$result->setHasAccess(false);

			return $result;
		}

		$builder = $this->getController()->getQueryBuilder();

		$sql = $builder->build(
			$attributes,
			$this->options
		);

		$result->setHasAccess(true);
		$result->setSql($sql);

		return $result;
	}

	/**
	 * Result in backward compatibility format.
	 *
	 * @deprecated
	 * @see QueryBuilder::build()
	 * @return false|string
	 */
	public function buildCompatible()
	{
		$result = $this->build();
		if ($result->hasAccess())
		{
			return $result->getSql();
		}

		return false;
	}

	protected function getController(): Controller
	{
		if ($this->controller)
		{
			return $this->controller;
		}

		foreach ($this->permissionEntityTypes as $permissionEntityType)
		{
			$resolvedController = Manager::resolveController($permissionEntityType);
			if ($resolvedController === null)
			{
				throw new NotSupportedException("Permission entity type '{$permissionEntityType}' is not supported in current context");
			}
			if ($this->controller && $this->controller !== $resolvedController)
			{
				throw new NotSupportedException("Set of entity types '". implode(', ', $this->permissionEntityTypes) . "' served by several different controllers");
			}
			$this->controller = $resolvedController;
		}

		return $this->controller;
	}

	protected function getAttributes(): Collection
	{
		$userId = $this->userPermissions->getUserId();
		$userAttributes = new Collection($userId);
		foreach ($this->permissionEntityTypes as $permissionEntityType)
		{
			$operations = $this->options->getOperations();
			foreach ($operations as $operation)
			{
				$userAttributes->addByEntityType(
					$permissionEntityType,
					$this->userPermissions->getAttributesProvider()->getEntityListAttributes(
						$permissionEntityType,
						$operation
					)
				);
			}
		}

		return $userAttributes;
	}
}
