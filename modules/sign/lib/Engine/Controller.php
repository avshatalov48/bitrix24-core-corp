<?php

namespace Bitrix\Sign\Engine;

use Bitrix\Main;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Service\Container;
use ReflectionClass;
use ReflectionMethod;
use Bitrix\Sign\Attribute;
use Bitrix\Sign\Engine\ActionFilter;
use Bitrix\Intranet;

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
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
				),
				new Intranet\ActionFilter\IntranetUser()
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
				if ($attr instanceof Attribute\ActionAccess)
				{
					$prefilters[ActionFilter\AccessCheck::PREFILTER_KEY] = $accessCheck->addRule(
						accessPermission: $attr->permission,
						itemType: $attr->itemType,
						itemIdRequestKey: $attr->itemIdRequestKey
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
}
