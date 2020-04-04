<?php

namespace Bitrix\Disk\Internals\Engine;

use Bitrix\Disk\Internals\Diag;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use \Bitrix\Main\Engine;
use Bitrix\Main\Engine\Action;

class Controller extends Engine\Controller
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_C_22001';
	const ERROR_COULD_NOT_FIND_VERSION = 'DISK_C_22005';
	const ERROR_COULD_NOT_UPDATE_FILE  = 'DISK_C_22006';

	/** @var  ErrorCollection */
	protected $errorCollection;

	protected function init()
	{
		parent::init();
		$this->errorCollection = new ErrorCollection();
	}

	protected function processBeforeAction(Action $action)
	{
		Diag::getInstance()->collectDebugInfo(get_called_class());

		return parent::processBeforeAction($action);
	}

	protected function processAfterAction(Engine\Action $action, $result)
	{
		Diag::getInstance()->logDebugInfo(get_called_class(), get_called_class() . ':' . $action->getName());

		if ($this->errorCollection->getErrorByCode(Engine\ActionFilter\Csrf::ERROR_INVALID_CSRF))
		{
			return Engine\Response\AjaxJson::createDenied()->setStatus('403 Forbidden');
		}

		return $result;
	}
}