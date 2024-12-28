<?php

namespace Bitrix\HumanResources\Engine;

use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Exception\ElementNotFoundException;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\MemberEntityType;
use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use ReflectionClass;
use ReflectionMethod;

abstract class Controller extends Main\Engine\Controller
{
	protected function getDefaultPreFilters()
	{
		return
			[
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\Csrf(),
				new Main\Engine\ActionFilter\HttpMethod(
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
				),
				new Intranet\ActionFilter\IntranetUser(),
				new Main\Engine\ActionFilter\CloseSession()
			];
	}

	protected function processBeforeAction(Main\Engine\Action $action): bool
	{
		if (!\Bitrix\HumanResources\Config\Storage::instance()->isPublicStructureAvailable())
		{
			$this->addError(new Main\Error(
				Loc::getMessage('HR_ACTION_ACCESS_DENIED'),
				'STRUCTURE_ACCESS_DENIED'
			));

			return false;
		}

		if (!Storage::instance()->isCompanyStructureConverted())
		{
			$this->addError(new Main\Error(
				'Structure has not been converted yet',
				'STRUCTURE_IS_NOT_CONVERTED'
			));

			return false;
		}

		if (!Loader::includeModule('intranet'))
		{
			$this->addError(new Main\Error('Module intranet is not installed'));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function configureActions()
	{
		$config = [];
		$class = new ReflectionClass($this);
		$parent = $class->getParentClass();
		foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
		{
			$name = $method->getName();
			if (
				mb_substr($name, -6) !== 'Action'
				|| $method->isConstructor()
				|| !$method->isPublic()
				|| $method->isStatic()
				|| !$method->isUserDefined()
				|| $parent->hasMethod($name)
			)
			{
				continue;
			}

			$name = mb_substr($name, 0, -6);
			$prefilters = [];
			$accessCheck = new ActionFilter\StructureAccessCheck();
			foreach ($method->getAttributes() as $attribute)
			{
				$attribute = $attribute->newInstance();
				if (
					$attribute instanceof Attribute\StructureActionAccess
					|| $attribute instanceof Attribute\Access\LogicOr
					|| $attribute instanceof Attribute\Access\LogicAnd
				)
				{
					$prefilters[ActionFilter\StructureAccessCheck::PREFILTER_KEY] = $accessCheck->addRuleFromAttribute(
						$attribute
					);
				}
			}

			if (!$prefilters)
			{
				continue;
			}

			$config[$name] = [
				'+prefilters' => $prefilters,
			];
		}

		return $config;
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new Main\Engine\AutoWire\ExactParameter(
				Item\Structure::class,
				'structure',
				function ($className, $structureId = null): ?Item\Structure
				{
					$structureRepository = Container::getStructureRepository();

					if (!$structureId)
					{
						return $structureRepository->getByXmlId(Item\Structure::DEFAULT_STRUCTURE_XML_ID);
					}

					if (!is_numeric($structureId))
					{
						$this->addError(new Error("Parameter 'structureId' must be numeric"));

						return null;
					}

					return $structureRepository->getById((int)$structureId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\Node::class,
				'node',
				function ($className, $nodeId): ?Item\Node
				{
					if (!is_numeric($nodeId))
					{
						$this->addError(new Error("Parameter 'nodeId' must be numeric"));

						return null;
					}

					return Container::getNodeRepository()->getById((int)$nodeId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\Node::class,
				'targetNode',
				function ($className, $targetNodeId): ?Item\Node
				{
					if (!is_numeric($targetNodeId))
					{
						$this->addError(new Error("Parameter 'targetNodeId' must be numeric"));

						return null;
					}

					return Container::getNodeRepository()->getById((int)$targetNodeId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\User::class,
				'user',
				function ($className, $userId): ?Item\User
				{
					if (!is_numeric($userId))
					{
						$this->addError(new Error("Parameter 'userId' must be numeric"));

						return null;
					}

					return Container::getUserRepository()->getById((int)$userId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\NodeMember::class,
				'nodeMember',
				function ($className, $nodeMemberId): ?Item\NodeMember
				{
					if (!is_numeric($nodeMemberId))
					{
						$this->addError(new Error("Parameter 'nodeMemberId' must be numeric"));

						return null;
					}

					return Container::getNodeMemberRepository()->findById((int)$nodeMemberId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\NodeMember::class,
				'nodeUserMember',
				function ($className, $nodeId, $userId): ?Item\NodeMember
				{
					if (!is_numeric($nodeId))
					{
						$this->addError(new Error("Parameter 'nodeId' must be numeric"));

						return null;
					}

					if (!is_numeric($userId))
					{
						$this->addError(new Error("Parameter 'userId' must be numeric"));

						return null;
					}

					return Container::getNodeMemberRepository()->findByEntityTypeAndEntityIdAndNodeId(MemberEntityType::USER, (int)$userId, (int)$nodeId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\Role::class,
				'role',
				function ($className, $roleId = null, ?string $roleXmlId = null): ?Item\Role
				{
					if (!$roleId && !$roleXmlId)
					{
						throw new ElementNotFoundException('Role not found');
  					}

					$roleRepository = Container::getRoleRepository();
					if ($roleId)
					{
						if (!is_numeric($roleId))
						{
							$this->addError(new Error("Parameter 'roleId' must be numeric"));

							return null;
						}

						return $roleRepository->getById((int)$roleId);
					}

					return $roleRepository->findByXmlId($roleXmlId);
				}
			),
		];
	}
}