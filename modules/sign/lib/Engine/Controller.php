<?php

namespace Bitrix\Sign\Engine;

use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Service\Container;
use ReflectionClass;
use ReflectionMethod;

class Controller extends \Bitrix\Main\Engine\Controller
{
	protected readonly Container $container;

	public function __construct(Main\Request $request = null)
	{
		parent::__construct($request);
		$this->container = Container::instance();
	}

	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		return
			[
				new Main\Engine\ActionFilter\ContentType([Main\Engine\ActionFilter\ContentType::JSON]),
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\HttpMethod(
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST],
				),
				new Intranet\ActionFilter\IntranetUser(),
			];
	}

	public function configureActions(): array
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
			$accessCheck = new ActionFilter\AccessCheck();
			foreach ($method->getAttributes() as $attr)
			{
				$attr = $attr->newInstance();
				if ($attr instanceof Attribute\ActionAccess
					|| $attr instanceof Attribute\Access\LogicOr
					|| $attr instanceof Attribute\Access\LogicAnd
				)
				{
					$prefilters[ActionFilter\AccessCheck::PREFILTER_KEY] = $accessCheck->addRuleFromAttribute($attr);
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

	protected function addErrorByMessage(string $message): static
	{
		$this->addError(new Main\Error($message));

		return $this;
	}

	protected function addErrorsFromResult(Main\Result $result): static
	{
		$this->addErrors($result->getErrors());

		return $this;
	}

	protected function addB2eTariffRestrictedError(): static
	{
		$this->addError(B2eTariff::instance()->getCommonAccessError());

		return $this;
	}

	/**
	 * @throws SystemException
	 */
	protected function getAccessController(): AccessController
	{
		$userId = $this->getCurrentUser()?->getId();
		if (!is_numeric($userId))
		{
			throw new Main\SystemException('Cant get current user');
		}

		return new AccessController($userId);
	}
}
