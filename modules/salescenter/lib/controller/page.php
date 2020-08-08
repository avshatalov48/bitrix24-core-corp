<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Error;
use Bitrix\Main\UrlPreview\UrlPreview;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Model\PageParamTable;
use Bitrix\SalesCenter\Model\PageTable;

class Page extends Base
{
	public function getAction(\Bitrix\SalesCenter\Model\Page $page)
	{
		$data = $page->collectValues();
		$data['NAME'] = $page->getName();
		$data['URL'] = $page->getUrl();
		$data['MODIFIED_AGO'] = $page->getDateModifiedAgo();
		$data['IS_ACTIVE'] = $page->isActive();
		$data['SITE_ID'] = $page->getSiteId();
		$data['CODE'] = $page->getCode();
		$data['PARAMS'] = $page->getParams();
		return ['page' => $this->convertKeysToCamelCase($data)];
	}

	/**
	 * @return \Bitrix\SalesCenter\Model\Page[]
	 */
	public function getList()
	{
		$hiddenLandingIds = $landingIds = $pages = [];

		// get existing pages that are not hidden
		$pageList = PageTable::getList(['order' => ['SORT' => 'ASC']]);
		while($page = $pageList->fetchObject())
		{
			if($page->getLandingId() > 0 && $page->isHidden())
			{
				$hiddenLandingIds[] = $page->getLandingId();
			}
			elseif(!$page->isHidden())
			{
				if(isset($landingIds[$page->getLandingId()]))
				{
					continue;
				}
				$pages[$page->getId()] = $page;
				if($page->getLandingId() > 0)
				{
					$landingIds[$page->getLandingId()] = $page->getId();
				}
			}
		}

		// load all landings from connected site and all added manually landings from any site
		$landingManager = LandingManager::getInstance()
			->setHiddenLandingIds($hiddenLandingIds)
			->setAdditionalLandingIds(array_keys($landingIds));
		$landings = $landingManager->getLandings();
		foreach($landingIds as $landingId => $pageId)
		{
			$isFound = false;
			foreach($landings as $landingPage)
			{
				if($landingPage['ID'] == $landingId)
				{
					$isFound = true;
					break;
				}
			}
			if(!$isFound)
			{
				unset($pages[$landingIds[$landingId]]);
			}
		}

		$orderLandingId = false;
		$orderPublicUrlInfo = $landingManager->getOrderPublicUrlInfo();
		if($orderPublicUrlInfo)
		{
			$orderLandingId = $orderPublicUrlInfo['landingId'];
		}

		foreach($landings as $landingPage)
		{
			// create rows for new landings from connected site
			if(!isset($landingIds[$landingPage['ID']]))
			{
				// skip order system page if it wasn't added on purpose
				if($orderLandingId && $landingPage['ID'] == $orderLandingId)
				{
					continue;
				}
				$page = new \Bitrix\SalesCenter\Model\Page();
				$page->setLandingId($landingPage['ID']);
				$page->save();
				$pages[$page->getId()] = $page;
			}
		}

		$pageParams = [];
		$paramList = PageParamTable::getList([
			'filter' => [
				'=PAGE_ID' => array_keys($pages),
			],
		]);
		while($param = $paramList->fetch())
		{
			if(!isset($pageParams[$param['PAGE_ID']]))
			{
				$pageParams[$param['PAGE_ID']] = [];
			}
			$pageParams[$param['PAGE_ID']][] = $param;
		}
		foreach($pageParams as $pageId => $params)
		{
			$pages[$pageId]->setParams($params);
		}

		return $pages;
	}

	/**
	 * @return \Bitrix\Main\Engine\Response\DataType\Page
	 */
	public function listAction()
	{
		$result = [];
		$pages = $this->getList();
		foreach($pages as $page)
		{
			$result[] = $this->getAction($page)['page'];
		}
		return new \Bitrix\Main\Engine\Response\DataType\Page('pages', $result, function() use ($result)
		{
			return count($result);
		});
	}

	/**
	 * @param array $fields
	 * @return array|null
	 */
	public function addAction(array $fields)
	{
		$page = null;
		if(isset($fields['landingId']))
		{
			$fields['landingId'] = intval($fields['landingId']);
		}
		if($fields['landingId'] > 0)
		{
			$page = PageTable::getList(['filter' => ['=LANDING_ID' => $fields['landingId']]])->fetchObject();
		}
		if(!$page)
		{
			$page = new \Bitrix\SalesCenter\Model\Page();
		}
		return $this->updateAction($page, $fields);
	}

	/**
	 * @param \Bitrix\SalesCenter\Model\Page $page
	 * @param array $fields
	 * @return array|null
	 */
	public function updateAction(\Bitrix\SalesCenter\Model\Page $page, array $fields)
	{
		$converter = new Converter(Converter::TO_UPPER | Converter::KEYS | Converter::TO_SNAKE);
		$fields = $converter->process($fields);
		unset($fields['ID']);
		foreach($fields as $name => $value)
		{
			if($page->entity->hasField($name))
			{
				$page->set($name, $value);
			}
		}
		$result = $page->save();
		if($result->isSuccess())
		{
			if(isset($fields['PARAMS']))
			{
				if(!is_array($fields['PARAMS']))
				{
					$fields['PARAMS'] = [];
				}
				$this->processPageParams($page, $fields['PARAMS']);
			}
			return $this->getAction($page);
		}
		else
		{
			$this->errorCollection->add($result->getErrors());
			return null;
		}
	}

	/**
	 * @param \Bitrix\SalesCenter\Model\Page $page
	 */
	public function deleteAction(\Bitrix\SalesCenter\Model\Page $page)
	{
		$page->delete();
	}

	public function hideAction(\Bitrix\SalesCenter\Model\Page $page)
	{
		if($page->getLandingId() > 0)
		{
			$this->updateAction($page, [
				'hidden' => true,
			]);
		}
		else
		{
			$this->deleteAction($page);
		}
	}

	/**
	 * @param $url
	 * @param bool $reuseExistingMetadata
	 * @return array|false
	 */
	public function getUrlDataAction($url, $reuseExistingMetadata = false)
	{
		$result = $this->convertKeysToCamelCase(UrlPreview::getMetadataByUrl($url, true, $reuseExistingMetadata));

		if($result['extra']['xFrameOptions'])
		{
			$result['isFrameDenied'] = $this->isFrameDenied($result['extra']['xFrameOptions'], $url);
		}

		return $result;
	}

	/**
	 * @param $xFrameOptions
	 * @param $url
	 * @return bool
	 */
	public function isFrameDenied($xFrameOptions, $url)
	{
		if(!is_array($xFrameOptions))
		{
			$xFrameOptions = [$xFrameOptions];
		}
		$currentHostUri = new Uri(UrlManager::getInstance()->getHostUrl());
		$currentHost = $currentHostUri->getHost();
		foreach($xFrameOptions as $frameOption)
		{
			$frameOption = trim(mb_strtolower($frameOption));
			if($frameOption == 'deny')
			{
				return true;
			}
			if(!$currentHost)
			{
				return true;
			}
			$allowedHost = false;
			if($frameOption == 'sameorigin')
			{
				$allowedHostUri = new Uri($url);
				$allowedHost = $allowedHostUri->getHost();
			}
			elseif(mb_strpos($frameOption, 'allow-from') === 0)
			{
				list(, $allowedHost) = explode(' ', $frameOption);
				if($allowedHost)
				{
					$allowedHostUri = new Uri($allowedHost);
					$allowedHost = $allowedHostUri->getHost();
				}
			}
			if($allowedHost && $currentHost && $allowedHost == $currentHost)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @param \Bitrix\SalesCenter\Model\Page $page
	 * @param array $options
	 */
	public function sendAction(\Bitrix\SalesCenter\Model\Page $page, array $options)
	{
		$emptyOptions = $this->checkArrayRequiredParams($options, ['dialogId']);
		if(!empty($emptyOptions))
		{
			$this->addError(new Error('Empty options: '.implode(', ', $emptyOptions)));
		}
		else
		{
			$imOpenLinesManager = ImOpenLinesManager::getInstance();
			if(isset($options['sessionId']) && $options['sessionId'] > 0)
			{
				$imOpenLinesManager->setSessionId($options['sessionId']);
			}
			$result = $imOpenLinesManager->sendPage($page, $options['dialogId']);
			if(!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}
	}

	/**
	 * @param $formId
	 * @return array|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addFormPageAction($formId)
	{
		$result = LandingManager::getInstance()->createWebFormLanding($formId, true);
		if($result->isSuccess())
		{
			$pageId = $result->getData()['pageId'];
			$page = PageTable::getById($pageId)->fetchObject();
			return $this->getAction($page);
		}
		else
		{
			$this->addErrors($result->getErrors());
		}

		return false;
	}

	public function getPageUrl(\Bitrix\SalesCenter\Model\Page $page, array $params)
	{

	}

	protected function processPageParams(\Bitrix\SalesCenter\Model\Page $page, array $params)
	{
		$currentParams = $page->getParams();
		$skipParams = [];
		foreach($currentParams as $currentParam)
		{
			if(in_array($currentParam['FIELD'], $params))
			{
				$skipParams[] = $currentParam['FIELD'];
				continue;
			}
			else
			{
				PageParamTable::delete($currentParam['ID']);
			}
		}
		foreach($params as $param)
		{
			if(!in_array($param, $skipParams))
			{
				PageParamTable::add([
					'PAGE_ID' => $page->getId(),
					'FIELD' => $param,
				]);
			}
		}
	}
}