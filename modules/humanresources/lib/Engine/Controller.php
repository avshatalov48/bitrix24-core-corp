<?php

namespace Bitrix\HumanResources\Engine;

use Bitrix\HumanResources\Attribute;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Item;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Loader;
use ReflectionClass;
use ReflectionMethod;

abstract class Controller extends Main\Engine\Controller
{
	protected function processBeforeAction(Main\Engine\Action $action): bool
	{
		if (!\Bitrix\HumanResources\Config\Storage::instance()->isPublicStructureAvailable())
		{
			$this->addError(new Main\Error(
				'Access denied',
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
				if ($attribute instanceof Attribute\StructureActionAccess)
				{
					$prefilters[ActionFilter\StructureAccessCheck::PREFILTER_KEY] = $accessCheck->addRule(
						accessPermission: $attribute->permission,
						itemType: $attribute->itemType,
						itemIdRequestKey: $attribute->itemIdRequestKey,
						itemParentIdRequestKey: $attribute->itemParentIdRequestKey,
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
				function ($className, ?int $structureId = null): ?Item\Structure
				{
					$structureRepository = Container::getStructureRepository();

					if (!$structureId)
					{
						return $structureRepository->getByXmlId(Item\Structure::DEFAULT_STRUCTURE_XML_ID);
					}

					return $structureRepository->getById($structureId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\Node::class,
				'node',
				function ($className, $nodeId): ?Item\Node
				{
					return Container::getNodeRepository()->getById($nodeId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\User::class,
				'user',
				function ($className, $userId): ?Item\User
				{
					return Container::getUserRepository()->getById($userId);
				}
			),
			new Main\Engine\AutoWire\ExactParameter(
				Item\NodeMember::class,
				'nodeMember',
				function ($className, $nodeMemberId): ?Item\NodeMember
				{
					return Container::getNodeMemberRepository()->findById($nodeMemberId);
				}
			),
		];
	}
}