<?php
use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\User;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\Storage;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Type\Collection;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('disk') || !\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
{
	return;
}

Loc::loadMessages(__FILE__);

class DiskAggregatorAjaxController extends \Bitrix\Disk\Internals\Controller
{
	protected function listActions()
	{
		return array(
			'getListStorage' => array(
				'method' => array('POST'),
			),
		);
	}

	protected function processActionGetListStorage()
	{
		$this->checkRequiredPostParams(array('proxyType', ));
		if ($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$proxyTypePost = $this->request->getPost('proxyType');
		$diskSecurityContext = $this->getSecurityContextByUser($this->getUser());

		$siteId = null;
		$siteDir = null;
		if ($this->request->getPost('siteId'))
		{
			$siteId = $this->request->getPost('siteId');
		}
		if ($this->request->getPost('siteDir'))
		{
			$siteDir = rtrim($this->request->getPost('siteDir'), '/');
		}

		$result = array();
		$filterReadableList = array();
		$runningSocnet = false;

		switch ($proxyTypePost)
		{
			case 'user':
				$result['TITLE'] = Loc::getMessage('DISK_AGGREGATOR_USER_TITLE');
				$filterReadableList = array('=STORAGE.ENTITY_TYPE' => ProxyType\User::className());
				break;
			case 'extranet-user':
				$result['TITLE'] = Loc::getMessage('DISK_AGGREGATOR_EXTRANET_USER_TITLE');
				$filterReadableList = array('=STORAGE.ENTITY_TYPE' => ProxyType\User::className());
				break;
			case 'group':
				$runningSocnet = Loader::includeModule('socialnetwork');
				$result['TITLE'] = Loc::getMessage('DISK_AGGREGATOR_GROUP_TITLE');
				$filterReadableList = array('=STORAGE.ENTITY_TYPE' => ProxyType\Group::className());
				break;
		}

		foreach (Storage::getReadableList($diskSecurityContext, array('filter' => $filterReadableList)) as $storage)
		{
			if ($runningSocnet)
			{
				$groupObject = CSocNetGroup::getList(
					array(),
					array('ID' => $storage->getEntityId()),
					false,
					false,
					array('SITE_ID')
				);
				$group = $groupObject->fetch();
				if (!empty($group) && $group['SITE_ID'] != $siteId)
				{
					continue;
				}

				$activeFeatures = CSocNetFeatures::getActiveFeaturesNames(SONET_ENTITY_GROUP, $storage->getEntityId());
				if (is_array($activeFeatures) && !array_key_exists('files', $activeFeatures))
				{
					continue;
				}
			}

			$proxyType = $storage->getProxyType();
			if ($proxyType instanceof ProxyType\User)
			{
				$user = $proxyType->getUser();
				if (($proxyTypePost == 'extranet-user' && !$user->isExtranetUser()) ||
					($proxyTypePost == 'user' && $user->isExtranetUser()))
				{
					continue;
				}
				if ($user->isExtranetUser() && Loader::includeModule('extranet') && CExtranet::isExtranetSite($siteId))
				{
					$siteDir = '';
				}
			}

			$result['DATA'][] = array(
				"TITLE" => $proxyType->getEntityTitle(),
				"URL" => $siteDir.$proxyType->getBaseUrlFolderList(),
				"ICON" => $proxyType->getEntityImageSrc(64,64),
			);
		}
		if (!empty($result['DATA']))
		{
			Collection::sortByColumn($result['DATA'], array('TITLE' => SORT_ASC));
			$this->sendJsonSuccessResponse(array(
				'listStorage' => $result['DATA'],
				'title' => $result['TITLE'],
			));
		}
		else
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_AGGREGATOR_ERROR_COULD_NOT_FIND_DATA'))));
			$this->sendJsonErrorResponse();
		}
	}

	protected function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new DiskSecurityContext($user);
		if (Loader::includeModule('socialnetwork'))
		{
			if (\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new FakeSecurityContext($user);
			}
		}
		if (User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new FakeSecurityContext($user);
		}
		return $diskSecurityContext;
	}
}
$controller = new DiskAggregatorAjaxController();
$controller
	->setActionName(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getQuery('action'))
	->exec()
;