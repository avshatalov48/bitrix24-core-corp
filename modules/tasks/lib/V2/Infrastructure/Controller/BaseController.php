<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Provider\Params\SelectInterface;
use Bitrix\Tasks\DI\Container;
use Bitrix\Tasks\V2\Internal\Access\AccessUserErrorInterface;
use Bitrix\Tasks\V2\Internal\Access\AttributeAccessInterface;
use Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter\IsEnabledFilter;
use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Entity\EntityCollectionInterface;
use Bitrix\Tasks\V2\Internal\Access\Context\Context;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\FlowRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\StageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\UserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskSelect;
use ReflectionAttribute;
use ReflectionMethod;

abstract class BaseController extends JsonController
{
	protected ?Context $context = null;

	protected int $userId = 0;

	public function getAutoWiredParameters(): array
	{
		return array_merge(
			$this->getExactParameters(),
			$this->getInjectionParameters(),
		);
	}

	public function getDefaultPreFilters(): array
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[new IsEnabledFilter()]
		);
	}

	public function getAccessContext(): Context
	{
		return $this->context ?? new Context(0);
	}

	public function setAccessContext(Context $context): static
	{
		$this->context = $context;

		return $this;
	}

	protected function init(): void
	{
		$this->userId = (int)CurrentUser::get()->getId();

		$this->setAccessContext(
			context: (new Context($this->userId)),
		);

		parent::init();
	}

	protected function getInjectionParameter(string $className): Parameter
	{
		return new Parameter(
			$className,
			static fn (): object => Container::getInstance()->get($className),
		);
	}

	protected function getExactParameters(): array
	{
		return [
			new ExactParameter(
				Entity\Task::class,
				'task',
				fn (string $className, array $task): ?EntityInterface
				=> $this->getWithAccess($this, 'task', Entity\Task::mapFromArray($task)),
			),
			new ExactParameter(
				Entity\Task::class,
				'task',
				fn (string $className, int $taskId): ?EntityInterface
				=> $this->getWithAccess($this, 'task', new Entity\Task(id: $taskId)),
			),
			new ExactParameter(
				Entity\Task::class,
				'relatedTask',
				fn (string $className, array $relatedTask): ?EntityInterface
				=> $this->getWithAccess($this, 'relatedTask', Entity\Task::mapFromArray($relatedTask)),
			),
			new ExactParameter(
				Entity\Task::class,
				'relatedTask',
				fn (string $className, int $relatedTaskId): ?EntityInterface
				=> $this->getWithAccess($this, 'relatedTask',new Entity\Task(id: $relatedTaskId)),
			),
			new ExactParameter(
				Entity\TaskCollection::class,
				'tasks',
				fn (string $className, array $tasks): ?Entity\EntityCollectionInterface
				=> $this->getWithAccess($this, 'tasks', Entity\TaskCollection::mapFromArray($tasks)),
			),
			new ExactParameter(
				Entity\TaskCollection::class,
				'tasks',
				fn (string $className, array $taskIds): ?Entity\EntityCollectionInterface
				=> $this->getWithAccess($this, 'tasks', Entity\TaskCollection::mapFromIds($taskIds)),
			),
			new ExactParameter(
				Entity\Template::class,
				'template',
				fn (string $className, array $template): ?EntityInterface
				=> $this->getWithAccess($this, 'template', Entity\Template::mapFromArray($template)),
			),
			new ExactParameter(
				Entity\Group::class,
				'group',
				fn (string $className, array $group): ?EntityInterface
				=> $this->getWithAccess($this, 'group', Entity\Group::mapFromArray($group)),
			),
			new ExactParameter(
				Entity\Flow::class,
				'flow',
				fn (string $className, array $flow): ?EntityInterface
				=> $this->getWithAccess($this, 'flow', Entity\Flow::mapFromArray($flow)),
			),
			new ExactParameter(
				Entity\Result::class,
				'result',
				fn (string $className, array $result): ?EntityInterface
				=> $this->getWithAccess($this, 'result', Entity\Result::mapFromArray($result)),
			),
			new ExactParameter(
				Entity\Task\Reminder::class,
				'reminder',
				fn (string $className, array $reminder): ?EntityInterface
				=> $this->getWithAccess($this, 'reminder', Entity\Task\Reminder::mapFromArray($reminder)),
			),
			new ExactParameter(
				SelectInterface::class,
				'relationTaskSelect',
				fn (string $className, array $relationTaskSelect): ?SelectInterface
				=> new RelationTaskSelect($relationTaskSelect),
			),
			new ExactParameter(
				Entity\Task\GanttLink::class,
				'ganttLink',
				fn (string $className, array $ganttLink): ?EntityInterface
				=> $this->getWithAccess($this, 'ganttLink', Entity\Task\GanttLink::mapFromArray($ganttLink)),
			),
		];
	}

	protected function getInjectionParameters(): array
	{
		return [
			$this->getInjectionParameter(TemplateRepositoryInterface::class),
			$this->getInjectionParameter(StageRepositoryInterface::class),
			$this->getInjectionParameter(TaskLogRepositoryInterface::class),
			$this->getInjectionParameter(GroupRepositoryInterface::class),
			$this->getInjectionParameter(FlowRepositoryInterface::class),
			$this->getInjectionParameter(UserOptionRepositoryInterface::class),
		];
	}

	protected function getWithAccess(
		Controller $controller,
		string $parameterName,
		EntityInterface|EntityCollectionInterface $entity,
		array $parameters = [],
	): null|EntityInterface|EntityCollectionInterface
	{
		$request = $controller->getRequest();
		$parameters = array_merge(
			$request->toArray(),
			$request->getJsonList()->toArray(),
			$parameters
		);

		$accessAttributes = $this->getArgumentAccessAttributes($controller, $parameterName);
		foreach ($accessAttributes as $accessAttribute)
		{
			/** @var AttributeAccessInterface $accessAttribute */
			if (!$accessAttribute->check($entity, $this->getAccessContext(), $parameters))
			{
				if ($accessAttribute instanceof AccessUserErrorInterface)
				{
					return $this->buildForbiddenResponse($accessAttribute->getUserError());
				}

				return $this->buildForbiddenResponse();
			}
		}

		return $entity;
	}

	protected function getArgumentAccessAttributes(Controller $controller, string $parameterName): array
	{
		$action = $this->getControllerAction($controller);
		$reflector = new ReflectionMethod($controller, $action);

		$args = $reflector->getParameters();

		$accessAttributes = [];
		foreach ($args as $arg)
		{
			if ($arg->getName() !== $parameterName)
			{
				continue;
			}

			return array_map(
				static fn (ReflectionAttribute $attribute): AttributeAccessInterface => $attribute->newInstance(),
				$arg->getAttributes(AttributeAccessInterface::class, ReflectionAttribute::IS_INSTANCEOF)
			);
		}

		return $accessAttributes;
	}

	protected function buildForbiddenResponse(?Error $error = null): mixed
	{
		$error ??= new Error(Loc::getMessage('TASKS_ACCESS_ERROR_DEFAULT') ?? 'Access denied');

		$this->errorCollection->setError($error);

		return null;
	}

	protected function getControllerAction(Controller $controller): string
	{
		$action = $controller->getRequest()->get('action');
		$parts = explode('.', $action);
		$method = end($parts);

		return $method . 'Action';
	}
}
