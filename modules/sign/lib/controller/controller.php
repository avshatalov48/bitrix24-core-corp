<?php
namespace Bitrix\Sign\Controller;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Error;
use Bitrix\Main\Engine\Action;
use Bitrix\Sign;
use Bitrix\Main;

class Controller extends \Bitrix\Main\Engine\Controller
{
	protected function processBeforeAction(Action $action)
	{
		if (!Sign\Config\Storage::instance()->isAvailable() || !Sign\Restriction::isSignAvailable())
		{
			$this->addError(new Main\Error('Access denied.'));
		}

		Logger::getInstance()->dump($action->getArguments(), static::class.' before: ' . $action->getName().'Action');
		return parent::processBeforeAction($action);
	}

	/**
	 * Calls after action executed.
	 * @param Action $action Action which executed.
	 * @param mixed $result Action result.
	 */
	protected function processAfterAction(Action $action, $result)
	{
		Logger::getInstance()->dump($result, static::class.' after: ' . $action->getName().'Action');

		$errorsCollection = Error::getInstance()->getErrors();

		// forward module errors to controller output
		foreach ($errorsCollection as $error)
		{
			$this->addError(new \Bitrix\Main\Error(
				$error->getMessage(),
				$error->getCode()
			));
		}

		if (($controllerErrors = $this->getErrors()) && count($controllerErrors) > 0)
		{
			Logger::getInstance()->dump($controllerErrors, static::class.' errors: ' . $action->getName().'Action');
		}

		if ($this->getErrors())
		{
			return ['sessid' => bitrix_sessid()];
		}
	}

	/**
	 * Get instance of controller to use methods
	 *
	 * @return Controller
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function getInstance(): Controller
	{
		if (!ServiceLocator::getInstance()->has(static::getServiceLocatorIdentifier()))
		{
			$instance = new static();
			ServiceLocator::getInstance()->addInstance(static::getServiceLocatorIdentifier(), $instance);
		}

		return ServiceLocator::getInstance()->get(static::getServiceLocatorIdentifier());
	}

	protected static function getServiceLocatorIdentifier(): string
	{
		return Container::getIdentifierByClassName(static::class);
	}
}
