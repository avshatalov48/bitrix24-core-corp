<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Storage;
use Bitrix\Disk\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use COption;

abstract class DiskComponent extends BaseComponent
{
	/** @var Storage */
	protected $storage;

	/**
	 * Checks required modules for the component.
	 * @return $this
	 * @throws SystemException
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkRequiredModules()
	{
		if (!Loader::includeModule('disk'))
		{
			throw new SystemException('Install module "disk"');
		}

		return $this;
	}

	/**
	 * Prepares component parameters.
	 * @return $this
	 * @throws ArgumentException
	 */
	protected function prepareParams()
	{
		parent::prepareParams();

		if(!empty($this->arParams['STORAGE']))
		{
			if(!($this->arParams['STORAGE'] instanceof Storage))
			{
				throw new ArgumentException('STORAGE must be instance of \Bitrix\Disk\Storage');
			}
		}
		elseif(!empty($this->arParams['STORAGE_ID']))
		{
			$this->arParams['STORAGE_ID'] = (int)$this->arParams['STORAGE_ID'];
		}
		else
		{
			if(empty($this->arParams['STORAGE_MODULE_ID']))
			{
				throw new ArgumentException('STORAGE_MODULE_ID required');
			}
			if(empty($this->arParams['STORAGE_ENTITY_TYPE']))
			{
				throw new ArgumentException('STORAGE_ENTITY_TYPE required');
			}
			if(!isset($this->arParams['STORAGE_ENTITY_ID']))
			{
				throw new ArgumentException('STORAGE_ENTITY_ID required');
			}
		}

		if(empty($this->arParams['PATH_TO_USER']))
		{
			$siteId = SITE_ID;
			$currentUser = $this->loadCurrentUserModel();
			$default = '/company/personal/user/#user_id#/';
			if($currentUser && $currentUser->isExtranetUser())
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$siteId = \CExtranet::getExtranetSiteID();
				$default = '/extranet/contacts/personal/user/#user_id#/';
			}

			$this->arParams['PATH_TO_USER'] = strtolower(COption::getOptionString('intranet', 'path_user', $default, $siteId));
		}

		return $this;
	}

	private function loadCurrentUserModel()
	{
		$userId = $this->getUser()->getId();
		if(!$userId || $userId <= 0)
		{
			return null;
		}

		$resultQuery = $this->getUser()->getById($userId);
		if(!$resultQuery instanceof \CDBResult)
		{
			return null;
		}

		$userData = $resultQuery->fetch();
		if(!$userData)
		{
			return null;
		}

		return User::buildFromArray($userData);
	}

	/**
	 * Initializes storage by component parameters.
	 * @return $this
	 */
	protected function initializeStorage()
	{
		if(isset($this->arParams['STORAGE']))
		{
			$this->storage = $this->arParams['STORAGE'];

			return $this;
		}
		elseif(isset($this->arParams['STORAGE_ID']))
		{
			$this->storage = Storage::loadById($this->arParams['STORAGE_ID']);
		}
		else
		{
			$this->storage = Storage::load(array(
				'MODULE_ID' => $this->arParams['STORAGE_MODULE_ID'],
				'ENTITY_TYPE' => $this->arParams['STORAGE_ENTITY_TYPE'],
				'ENTITY_ID' => $this->arParams['STORAGE_ENTITY_ID'],
			));
		}
		return $this;
	}

	/**
	 * Processes operations before runs action.
	 * @param string $actionName Action name which will run.
	 * @return bool
	 */
	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->initializeStorage();

		$this
			->application
			->setPageProperty('BodyClass', $this->application->getPageProperty('BodyClass') . ' page-one-column')
		;

		return true;
	}
}