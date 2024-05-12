<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Security\Controller\QueryBuilder\Conditions\UseJoin;
use Bitrix\Crm\Security\QueryBuilder\OptionsBuilder;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;
use Bitrix\Crm\Security\QueryBuilder\Result;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\NotSupportedException;
use Bitrix\Crm\Security\AccessAttribute\Collection;

class QueryBuilder
{
	private QueryBuilderOptions $options;
	private array $permissionEntityTypes;
	private UserPermissions $userPermissions;
	private ?Controller $controller = null;

	public function __construct(
		array $permissionEntityTypes,
		UserPermissions $userPermissions,
		?QueryBuilderOptions $options = null
	)
	{
		$this->userPermissions = $userPermissions;

		if ($options === null)
		{
			$this->options = OptionsBuilder::makeEmptyOptions();
		}
		else
		{
			$this->options = $options;
		}

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

		$data = $builder->build(
			$attributes,
			$this->options
		);

		$result->setHasAccess(true);
		$result->setSql($data->getSql());

		$idColName = $this->options->getResult()->getIdentityColumnName();
		$ormConditions = $data->getConditions()?->makeOrmConditions(new UseJoin($idColName));

		$result->setOrmConditions($ormConditions);

		$result->setEntity($data->getEntity());

		return $result;
	}

	/**
	 * Result in backward compatibility format.
	 *
	 * @deprecated
	 * @see QueryBuilder::build()
	 * @return false|string
	 */
	public function buildCompatible(): false|string
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
		if ($this->controller !== null)
		{
			return $this->controller;
		}

		foreach ($this->permissionEntityTypes as $permissionEntityType)
		{
			$resolvedController = Manager::resolveController($permissionEntityType);
			if ($resolvedController === null)
			{
				throw new NotSupportedException("Permission entity type '$permissionEntityType' is not supported in current context");
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
		return Collection::build(
			$this->userPermissions,
			$this->permissionEntityTypes,
			$this->options->getOperations()
		);
	}
}
