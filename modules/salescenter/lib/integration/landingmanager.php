<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Landing\Block;
use Bitrix\Landing\Field;
use Bitrix\Landing\Internals\BlockTable;
use Bitrix\Landing\Landing;
use Bitrix\Landing\Rights;
use Bitrix\Landing\Site;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Driver;
use Bitrix\SalesCenter\Model\Meta;
use Bitrix\SalesCenter\Model\PageTable;

class LandingManager extends Base
{
	const SITE_TEMPLATE_CODE = 'store-chats';
	const OPTION_SALESCENTER_SITE_ID = '~connected_site_id';

	protected $connectedSite;
	protected $orderPublicUrlInfo;
	protected $loadedLandings = [];
	protected $hiddenLandingIds = [];
	protected $additionalLandingIds = [];
	protected $connectedWebForms;

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'landing';
	}

	//region event handlers
	/**
	 * This event handler triggers after creation a site.
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onAfterDemoCreate(Event $event)
	{
		$result = new EventResult(EventResult::SUCCESS);
		$code = $event->getParameter('code');
		if(static::isSalesChatTemplateCode($code))
		{
			$landingId = $event->getParameter('id');
			if($landingId != static::getInstance()->getConnectedSiteId())
			{
				static::getInstance()->setConnectedSiteId($landingId);
				static::getInstance()->createWebFormPages();
				if(static::getInstance()->isConnectionAvailable())
				{
					\Bitrix\Landing\PublicAction\Site::publication($landingId, true);
				}
			}
		}

		return $result;
	}

	/**
	 * This handler is for orm-event on deletion landing from table.
	 *
	 * @param \Bitrix\Main\ORM\Event $event
	 * @return bool
	 */
	public static function onDeleteLanding(\Bitrix\Main\ORM\Event $event)
	{
		$landingId = $event->getParameter('primary');
		if($landingId > 0)
		{
			$pageList = PageTable::getList([
				'select' => ['ID'],
				'filter' => ['=LANDING_ID' => $landingId]
			]);
			while($page = $pageList->fetch())
			{
				PageTable::delete($page['ID']);
			}
		}

		return true;
	}

	/**
	 * This handler changes link on a template in the list - show connect slider before actual creation.
	 *
	 * @return \Bitrix\Main\ORM\EventResult
	 */
	public static function onBuildTemplatePreviewUrl()
	{
		$result = new \Bitrix\Main\ORM\EventResult();
		if(Driver::getInstance()->isEnabled())
		{
			$connectPath = Driver::getInstance()->getConnectPath();
			if($connectPath)
			{
				$result->modifyFields([
					'store-chats' => [
						'href' => $connectPath.'?withRedirect=y&context=landing_shop&analyticsLabel=salescenterStartConnection',
						'width' => 760,
					],
				]);
			}
		}

		return $result;
	}

	/**
	 * This handler rewrites og-properties on a page by url-parameter
	 *
	 * @return \Bitrix\Main\ORM\EventResult
	 */
	public static function onHookExec()
	{
		$result = new \Bitrix\Main\ORM\EventResult();

		$getMetaFromUrl = function(\Bitrix\Landing\Hook\Page $hook)
		{
			$hash = Application::getInstance()->getContext()->getRequest()->get(ImOpenLinesManager::META_PARAM);
			if($hash)
			{
				$meta = Meta::getByHash($hash);
				if($meta)
				{
					$title = $meta->getMeta('title');
					$description = $meta->getMeta('description');
					$image = $meta->getMeta('image');
					if($title || $description || $image)
					{
						if(!$title)
						{
							$title = $hook->getTitle();
						}
						if(!$description)
						{
							$description = $hook->getDescription();
						}
						$content = '<meta property="og:title" content="'.$title.'" />
						<meta property="og:description" content="'.$description.'" />';
						if($image)
						{
							$content .= '<meta property="og:image" content="'.$image.'" />';
						}

						\Bitrix\Landing\Manager::setPageView('MetaOG', $content);

						return true;
					}
				}
			}

			return false;
		};

		$result->modifyFields([
			'METAOG' => $getMetaFromUrl
		]);

		return $result;
	}

	/**
	 * Update data in the interface after publication of a landing
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onLandingPublication(Event $event)
	{
		$landingId = (int) $event->getParameter('id');
		if($landingId > 0)
		{
			PullManager::getInstance()->sendLandingPublicationEvent($landingId);
		}

		return new EventResult(EventResult::SUCCESS);
	}

	/**
	 * Set connected site id on restoring site from recycle if there is no other not deleted connected site.
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeSiteRecycle(Event $event)
	{
		if($event->getParameter('delete') !== 'Y')
		{
			if(!static::getInstance()->isSiteExists())
			{
				$siteId = $event->getParameter('id');
				static::getInstance()->setConnectedSiteId($siteId);
			}
		}

		return new EventResult(EventResult::SUCCESS);
	}

	/**
	 * Delete link from page table on moving page to recycle.
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeLandingRecycle(Event $event)
	{
		if($event->getParameter('delete') === 'Y')
		{
			$landingId = $event->getParameter('id');
			$page = PageTable::getList(['select' => ['ID'], 'filter' => ['=LANDING_ID' => $landingId]])->fetchObject();
			if($page)
			{
				$page->delete();
			}
		}

		return new EventResult(EventResult::SUCCESS);
	}

	/**
	 * Update data in the interface after unpublication of a landing
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onLandingAfterUnPublication(Event $event)
	{
		$landingId = (int) $event->getParameter('id');
		if($landingId > 0)
		{
			PullManager::getInstance()->sendLandingUnPublicationEvent($landingId);
		}

		return new EventResult(EventResult::SUCCESS);
	}
	//endregion

	//region site
	/**
	 * @return int
	 */
	public function getConnectedSiteId()
	{
		return (int) Option::get(Driver::MODULE_ID, static::OPTION_SALESCENTER_SITE_ID);
	}

	/**
	 * @param int $landingId
	 */
	protected function setConnectedSiteId($landingId)
	{
		$landingId = (int) $landingId;
		Option::set(Driver::MODULE_ID, static::OPTION_SALESCENTER_SITE_ID, $landingId);
		PullManager::getInstance()->sendConnectEvent();
		$this->loadedLandings = [];
		$this->orderPublicUrlInfo = null;
	}

	/**
	 * @return bool
	 */
	public function isSitePublished()
	{
		$connectedSite = $this->getConnectedSite();
		return (is_array($connectedSite) && $connectedSite['ACTIVE'] === 'Y');
	}

	/**
	 * @return bool
	 */
	public function isSiteExists()
	{
		return ($this->isEnabled && $this->getConnectedSite() !== false);
	}

	/**
	 * @param $code
	 * @return bool
	 */
	protected static function isSalesChatTemplateCode($code)
	{
		if(is_string($code))
		{
			return strpos($code, static::SITE_TEMPLATE_CODE) === 0;
		}

		return false;
	}

	/**
	 * @return array|false
	 */
	protected function getConnectedSite()
	{
		if($this->connectedSite === null)
		{
			$this->connectedSite = false;

			$siteId = $this->getConnectedSiteId();
			if($siteId > 0 && $this->isEnabled)
			{
				Rights::setOff();
				$this->connectedSite = Site::getList(['select' => ['ID', 'ACTIVE', 'XML_ID'], 'filter' => [
					'=ID' => $siteId,
					'=DELETED' => 'N',
				]])->fetch();
				Rights::setOn();
			}
		}

		return $this->connectedSite;
	}

	/**
	 * @return bool
	 */
	public function isConnectionAvailable()
	{
		if($this->isEnabled)
		{
			return \Bitrix\Landing\Manager::checkFeature(
				\Bitrix\Landing\Manager::FEATURE_PUBLICATION_SITE,
				[
					'type' => 'STORE'
				]
			);
		}

		return false;
	}
	//endregion

	//region landings
	/**
	 * @param array $hiddenLandingIds
	 * @return $this
	 */
	public function setHiddenLandingIds(array $hiddenLandingIds)
	{
		$this->hiddenLandingIds = $hiddenLandingIds;
		return $this;
	}

	/**
	 * @param array $additionalLandingIds
	 * @return $this
	 */
	public function setAdditionalLandingIds(array $additionalLandingIds)
	{
		$this->additionalLandingIds = $additionalLandingIds;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getLandings()
	{
		$filter = [];
		if(!empty($this->hiddenLandingIds))
		{
			$filter['!=ID'] = $this->hiddenLandingIds;
		}
		if(!empty($this->additionalLandingIds))
		{
			$filter[] = [
				'LOGIC' => 'OR',
				[
					'=SITE_ID' => $this->getConnectedSiteId(),
				],
				[
					'=ID' => $this->additionalLandingIds,
				]
			];
		}
		else
		{
			$filter['=SITE_ID'] = $this->getConnectedSiteId();
		}
		return $this->loadLandings($filter);
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	protected function loadLandings(array $filter = [])
	{
		$landings = [];
		if(!$this->isEnabled)
		{
			return $landings;
		}
		$filter = array_merge([
			'CHECK_PERMISSIONS' => 'N',
			'=SITE.DELETED' => 'N',
			'=DELETED' => 'N',
		], $filter);
		$pageList = Landing::getList([
			'select' => [
				'ID',
				'TITLE',
				'ACTIVE',
				'DESCRIPTION',
				'DATE_MODIFY',
				'SITE_ACTIVE' => 'SITE.ACTIVE',
				'SITE_ID',
				'CODE',
			],
			'filter' => $filter,
		]);
		while($landing = $pageList->fetch())
		{
			$landing['SITE_ID'] = (int) $landing['SITE_ID'];
			$landings[$landing['ID']] = $landing;
		}

		$areas = \Bitrix\Landing\TemplateRef::landingIsArea(array_keys($landings));
		$landings = array_filter($landings, function($landing) use ($areas)
			{
				return !$areas[$landing['ID']];
			}
		);

		$landing = Landing::createInstance(0);
		$publicUrls = $landing->getPublicUrl(array_keys($landings));
		if(is_array($publicUrls))
		{
			foreach($publicUrls as $id => $url)
			{
				$landings[$id]['PUBLIC_URL'] = $url;
			}
		}

		$this->loadedLandings += $landings;

		return $this->loadedLandings;
	}

	/**
	 * @param int $landingId
	 * @param bool $withAdditionalFields
	 * @return array|null
	 */
	public function getLanding($landingId, $withAdditionalFields = false)
	{
		if(!isset($this->loadedLandings[$landingId]))
		{
			$this->loadLandings(['=ID' => $landingId]);
		}

		if($withAdditionalFields && $this->loadedLandings[$landingId] && !$this->loadedLandings[$landingId]['additionalFields'])
		{
			$this->loadedLandings[$landingId]['additionalFields'] = Landing::getAdditionalFields($landingId);
		}


		return $this->loadedLandings[$landingId];
	}
	//endregion

	//region preview
	/**
	 * @param $landingId
	 * @return array|null
	 */
	public function getLandingUrlPreviewData($landingId)
	{
		$landing = $this->getLanding($landingId, true);
		if($landing)
		{
			$title = $this->getAdditionalFieldValue($landing['additionalFields']['METAOG_TITLE']);
			if(!$title)
			{
				$title = $landing['TITLE'];
			}
			$description = $this->getAdditionalFieldValue($landing['additionalFields']['METAOG_DESCRIPTION']);
			if(!$description)
			{
				$description = $landing['DESCRIPTION'];
			}
			return [
				'title' => $title,
				'description' => $description,
				'image' => $this->getAdditionalFieldValue($landing['additionalFields']['METAOG_IMAGE']),
			];
		}

		return null;
	}

	/**
	 * @param null $field
	 * @return mixed|null
	 */
	private function getAdditionalFieldValue($field = null)
	{
		if($field && $field instanceof Field)
		{
			return $field->getValue();
		}

		return $field;
	}

	/**
	 * @param array $urlParameters
	 * @return false|array
	 */
	public function getOrderPublicUrlInfo(array $urlParameters = [])
	{
		if($this->orderPublicUrlInfo === null)
		{
			$this->orderPublicUrlInfo = false;
			if($this->isEnabled && $this->isSiteExists())
			{
				Rights::setOff();
				$sysPages = \Bitrix\Landing\Syspage::get($this->getConnectedSiteId());
				if(isset($sysPages['order']))
				{
					$landingId = (int) $sysPages['order']['LANDING_ID'];
					$landing = Landing::createInstance($landingId);
					if($landing->exist())
					{
						$this->orderPublicUrlInfo = [
							'url' => $landing->getPublicUrl(false, true),
							'title' => $landing->getTitle(),
							'landingId' => $landingId,
							'isActive' => ($landing->isActive() && $this->isSitePublished()),
						];
					}
				}
				Rights::setOn();
			}
		}

		$orderPublicUrlInfo = $this->orderPublicUrlInfo;

		if(is_array($orderPublicUrlInfo) && !empty($urlParameters))
		{
			$uri = new Uri($orderPublicUrlInfo['url']);
			$uri->addParams($urlParameters);
			$orderPublicUrlInfo['url'] = $uri->getLocator();
		}

		return $orderPublicUrlInfo;
	}

	/**
	 * @return bool
	 */
	public function isOrderPublicUrlAvailable()
	{
		$orderPublicUrlInfo = $this->getOrderPublicUrlInfo();
		if($orderPublicUrlInfo)
		{
			return $orderPublicUrlInfo['isActive'];
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isOrderPublicUrlExists()
	{
		$orderPublicUrlInfo = $this->getOrderPublicUrlInfo();
		return ($orderPublicUrlInfo !== false);
	}
	//endregion

	//region webforms
	/**
	 * @param array $landingIds
	 * @return array
	 */
	protected function getBlocksWithWebForms(array $landingIds)
	{
		if(empty($landingIds))
		{
			return [];
		}

		return BlockTable::getList([
			'select' => ['*'],
			'filter' => [
				'=LID' => $landingIds,
				'CONTENT' => '%data-b24form=%',
			]
		])->fetchAll();
	}

	/**
	 * @return array
	 */
	public function getConnectedWebForms()
	{
		if($this->connectedWebForms === null)
		{
			$this->connectedWebForms = [];
			$landings = $this->getLandings();
			$blocks = $this->getBlocksWithWebForms(array_keys($landings));
			foreach($blocks as $block)
			{
				if(preg_match('#data-b24form\=\"(\d+)\|([a-z0-9]{6})\"#', $block['CONTENT'], $matches))
				{
					$this->connectedWebForms[] = [
						'blockId' => $block['ID'],
						'formId' => $matches[1],
						'landingId' => $block['LID'],
						'formCode' => $matches[2],
					];
				}
			}
		}

		return $this->connectedWebForms;
	}

	/**
	 * @return array
	 */
	public function getConnectedWebFormIds()
	{
		$result = [];

		$connectedWebForms = $this->getConnectedWebForms();
		foreach($connectedWebForms as $form)
		{
			$result[] = $form['formId'];
		}

		return array_unique($result);
	}

	/**
	 * @param \LandingSiteDemoComponent $component
	 * @return string|false
	 */
	public function getWebFormPageCode(\LandingSiteDemoComponent $component)
	{
		static $webFormPageCode;
		if($webFormPageCode === null)
		{
			$webFormPageCode = false;
			$templateCode = $this->getConnectedSiteTemplateCode();
			if(!$templateCode)
			{
				return false;
			}
			$demoSite = $component->getDemoSite()[$templateCode];
			if($demoSite && is_array($demoSite) && is_array($demoSite['DATA']) && is_array($demoSite['DATA']['items']))
			{
				$pageCodes = $demoSite['DATA']['items'];
				$demoPages = $component->getDemoPage();
				foreach($pageCodes as $code)
				{
					if(isset($demoPages[$code]) && is_array($demoPages[$code]['DATA']) && $demoPages[$code]['DATA']['is_webform_page'] === 'Y')
					{
						$webFormPageCode = $code;
						break;
					}
				}
			}
		}

		return $webFormPageCode;
	}

	/**
	 * @return \LandingSiteDemoComponent|false
	 */
	protected function getLandingDemoComponent()
	{
		static $landingDemoComponent;
		if($landingDemoComponent === null)
		{
			$landingDemoComponent = false;
			if($this->isEnabled)
			{
				$componentName = 'bitrix:landing.demo';
				$className = \CBitrixComponent::includeComponentClass($componentName);
				/** @var \LandingSiteDemoComponent $landingDemoComponent */
				$landingDemoComponent = new $className;
				$landingDemoComponent->initComponent($componentName);
				$landingDemoComponent->arParams = [
					'TYPE' => 'STORE',
					'SITE_ID' => $this->getConnectedSiteId(),
					'SITE_WORK_MODE' => 'N',
					'DISABLE_REDIRECT' => 'Y'
				];

				$additionalFields = Site::getAdditionalFields($this->getConnectedSiteId());
				$landingDemoComponent->setAdditionalFields([
					'THEME_CODE' => $this->getAdditionalFieldValue($additionalFields['THEME_CODE']),
					'THEME_CODE_TYPO' => $this->getAdditionalFieldValue($additionalFields['THEME_CODE_TYPO']),
				]);
			}
		}

		return $landingDemoComponent;
	}

	/**
	 * @param $formId
	 * @param bool $isPublic
	 * @return Result
	 */
	public function createWebFormLanding($formId, $isPublic = false)
	{
		$result = new Result();
		if(!$this->isSiteExists())
		{
			return $result->addError(new Error('Site is not found'));
		}
		$component = $this->getLandingDemoComponent();
		if(!$component)
		{
			return $result->addError(new Error('Landing demo component is not found'));
		}
		$webFormPageCode = $this->getWebFormPageCode($component);
		if(!$webFormPageCode)
		{
			return $result->addError(new Error('Landing template for web form is not found'));
		}
		// todo replace this with runtime event
		$previousWebFormPage = $this->getLastCreatedWebFormLanding($webFormPageCode);
		$component->actionSelect($webFormPageCode);
		$lastWebFormPage = $this->getLastCreatedWebFormLanding($webFormPageCode);
		if($lastWebFormPage && !$previousWebFormPage || $lastWebFormPage['ID'] > $previousWebFormPage['ID'])
		{
			$landingId = $lastWebFormPage['ID'];
			$result->setData(['landingId' => $landingId]);
			$setResult = $this->setPageWebFormId($landingId, $formId);
			if($setResult->isSuccess())
			{
				$updateResult = $this->setLandingPageConnection($landingId, $formId);
				if($updateResult->isSuccess())
				{
					$result->setData(array_merge($result->getData(), ['pageId' => $updateResult->getId()]));
					if($isPublic)
					{
						$landing = Landing::createInstance($landingId);
						$landing->publication();
						if(!$landing->getError()->isEmpty())
						{
							$result->addErrors($landing->getError()->getErrors());
						}
					}
				}
				else
				{
					$result->addErrors($updateResult->getErrors());
				}
			}
			else
			{
				Landing::delete($landingId);
				$result->addErrors($setResult->getErrors());
			}
		}
		else
		{
			$result->addError(new Error('Could not create web form page'));
		}

		return $result;
	}

	/**
	 * @param $landingId
	 * @param $formId
	 * @return Result
	 */
	protected function setPageWebFormId($landingId, $formId)
	{
		$result = new Result();
		$attributeValue = $this->getWebFormAttributeValue($formId);
		if(!$attributeValue)
		{
			return $result->addError(new Error('Form not found'));
		}
		$blocks = $this->getBlocksWithWebForms([$landingId]);
		if(empty($blocks))
		{
			return $result->addError(new Error('Could not found block with form on the page'));
		}
		foreach($blocks as $blockData)
		{
			$block = new Block($blockData['ID'], $blockData);
			$block->setAttributes([
				'.bitrix24forms' => [
					'data-b24form' => $attributeValue,
				]
			]);
			$block->save();
			if(!$block->getError()->isEmpty())
			{
				$result->addErrors($block->getError()->getErrors());
			}
			else
			{
				if($this->connectedWebForms !== null)
				{
					$this->connectedWebForms[] = [
						'blockId' => $blockData['ID'],
						'formId' => $formId,
						'landingId' => $landingId,
						'formCode' => CrmManager::getInstance()->getWebForms()[$formId]['SECURITY_CODE'],
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @param $formId
	 * @return false|string
	 */
	protected function getWebFormAttributeValue($formId)
	{
		$form = CrmManager::getInstance()->getWebForms()[$formId];
		if($form)
		{
			return $form['ID'].'|'.$form['SECURITY_CODE'];
		}

		return false;
	}

	/**
	 * @param $webFormPageCode
	 * @return array|false
	 */
	protected function getLastCreatedWebFormLanding($webFormPageCode)
	{
		if($this->isSiteExists())
		{
			return Landing::getList([
				'select' => ['*'],
				'filter' => [
					'=SITE_ID' => $this->getConnectedSiteId(),
					'=TPL_CODE' => $webFormPageCode,
				],
				'order' => ['ID' => 'DESC'],
			])->fetch();
		}

		return false;
	}

	/**
	 * @param bool $isSkipFirstForm
	 * @return Result
	 */
	protected function createWebFormPages($isSkipFirstForm = true)
	{
		$result = new Result();
		if(!$this->isSiteExists())
		{
			return $result->addError(new Error('Site it not found'));
		}

		$forms = CrmManager::getInstance()->getWebForms();
		if(empty($forms))
		{
			return $result;
		}
		if($isSkipFirstForm)
		{
			$form = array_shift($forms);
			$result = $this->updateFirstWebFormPage($form['ID']);
		}
		if(empty($forms))
		{
			return $result;
		}

		foreach($forms as $form)
		{
			$formResult = $this->createWebFormLanding($form['ID']);
			if(!$formResult->isSuccess())
			{
				$result->addErrors($formResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @return string|false
	 */
	protected function getConnectedSiteTemplateCode()
	{
		if($this->isSiteExists())
		{
			$xmlId = $this->getConnectedSite()['XML_ID'];
			list(, $code) = explode('|', $xmlId);

			return $code;
		}

		return false;
	}

	/**
	 * @param $formId
	 * @return Result
	 */
	protected function updateFirstWebFormPage($formId)
	{
		$result = new Result();
		$component = $this->getLandingDemoComponent();
		if(!$component)
		{
			return $result->addError(new Error('Landing demo component is not found'));
		}
		$webFormPageCode = $this->getWebFormPageCode($component);
		if(!$webFormPageCode)
		{
			return $result->addError(new Error('Landing template for web form is not found'));
		}

		$firstWebFormPage = $this->getLastCreatedWebFormLanding($webFormPageCode);
		if($firstWebFormPage)
		{
			$result = $this->setLandingPageConnection($firstWebFormPage['ID'], $formId);
		}

		return $result;
	}

	/**
	 * @param $landingId
	 * @param $formId
	 * @return AddResult|UpdateResult|Result
	 */
	protected function setLandingPageConnection($landingId, $formId)
	{
		$result = new Result();
		$form = CrmManager::getInstance()->getWebForms()[$formId];
		if(!$form)
		{
			return $result->addError(new Error('Web form '.$formId.' is not found'));
		}
		$result = Landing::update($landingId, [
			'TITLE' => $form['NAME'],
		]);
		if(!$result->isSuccess())
		{
			return $result;
		}

		$page = new \Bitrix\SalesCenter\Model\Page();
		$page->setLandingId($landingId);
		$page->setIsWebform(true);
		return $page->save();
	}
	//endregion
}