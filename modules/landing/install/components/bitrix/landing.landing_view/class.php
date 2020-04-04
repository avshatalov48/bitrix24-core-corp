<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Landing\Manager;
use \Bitrix\Landing\Site;
use \Bitrix\Landing\Landing;
use \Bitrix\Landing\Syspage;
use \Bitrix\Landing\Hook;
use \Bitrix\Landing\Rights;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Landing\Source\Selector;
use \Bitrix\Landing\PublicAction\Demos;

\CBitrixComponent::includeComponentClass('bitrix:landing.base');

class LandingViewComponent extends LandingBaseComponent
{
	/**
	 * Total this type sites count.
	 * @var int
	 */
	protected $sitesCount;

	/**
	 * Total pages count in current site.
	 * @var int
	 */
	protected $pagesCount;

	/**
	 * Just redirect to the landing preview page.
	 * @param int $id Landing id.
	 * @return boolean
	 */
	protected function actionPreview($id)
	{
		\Bitrix\Landing\Landing::setPreviewMode(true);

		$landing = Landing::createInstance($id);
		if ($landing->exist())
		{
			\localRedirect(
				$landing->getPublicUrl(false, true, true),
				true
			);
		}

		\Bitrix\Landing\Landing::setPreviewMode(false);

		$this->setErrors(
			$landing->getError()->getErrors()
		);

		return false;
	}

	/**
	 * In some times we need show popup about site is now creating.
	 * @param int $siteId Site id.
	 * @return boolean
	 */
	protected function isNeedFirstPreparePopup($siteId)
	{
		if (!Manager::isB24())
		{
			return false;
		}
		$date = new \Bitrix\Main\Type\DateTime;
		$res = Site::getList(array(
			'filter' => array(
				'ID' => $siteId,
				'>DOMAIN.DATE_MODIFY' => $date->add('-15 seconds')
			)
		));
		if ($row = $res->fetch())
		{
			return true;
		}
		return false;
	}

	/**
	 * Publication landing.
	 * @param int $id Landing id.
	 * @param bool $disabledRedirect Disable redirect after publication.
	 * @return boolean
	 */
	protected function actionPublication($id, $disabledRedirect = false)
	{
		static $publicIds = [];

		if (isset($publicIds[$id]))
		{
			return $publicIds[$id];
		}

		$landing = Landing::createInstance($id);
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();
		$agreementExist = isset($this->arParams['AGREEMENT']) &&
						  !empty($this->arParams['AGREEMENT']);

		// agreement already display
		if (
			$agreementExist &&
			$request->get('agreement') == 'Y'
		)
		{
			$publicIds[$id] = false;
			return $publicIds[$id];
		}

		if ($landing->exist())
		{
			// display agreement
			$uriSave = new \Bitrix\Main\Web\Uri(
				$request->getRequestUri()
			);
			$uriSave->deleteParams(array(
				'agreement'
			));
			if (
				isset($this->arParams['AGREEMENT']) &&
				!empty($this->arParams['AGREEMENT'])
			)
			{
				$uriSave->addParams(array(
					'agreement' => 'Y'
				));
				\localRedirect($uriSave->getUri(), true);
			}
			if ($landing->publication())
			{
				$publicIds[$id] = true;
				// current landing is not area
				$areas = $landing->getAreas();
				if (!in_array($id, $areas))
				{
					foreach ($areas as $aId)
					{
						$landingArea = Landing::createInstance($aId);
						if (
							$landingArea->exist() &&
							$landingArea->publication()
						)
						{
							$publicIds[$aId] = true;
						}
					}
				}
				if ($disabledRedirect)
				{
					return $publicIds[$id];
				}
				if ($this->isNeedFirstPreparePopup($landing->getSiteId()))
				{
					$this->addError(
						'SITE_IS_NOW_CREATING'
					);
					return false;
				}
				else
				{
					$url = $landing->getPublicUrl(false, true, true);
					\localRedirect($this->getTimestampUrl($url), true);
				}
			}
			else
			{
				$this->setErrors(
					$landing->getError()->getErrors()
				 );
				return false;
			}
		}

		$this->setErrors(
			$landing->getError()->getErrors()
		);

		$publicIds[$id] = false;
		return $publicIds[$id];
	}

	/**
	 * Publication all landing in site of current landing.
	 * @param int $id Landing id.
	 * @return boolean
	 */
	protected function actionPublicationAll($id)
	{
		$landing = Landing::createInstance($id);

		if ($landing->exist())
		{
			$pages = $this->getLandings(array(
				'filter' => array(
					'SITE_ID' => $landing->getSiteId()
				)
			));
			foreach ($pages as $page)
			{
				if ($page['PUBLIC'] == 'Y')
				{
					continue;
				}
				if (!$this->actionPublication($page['ID'], true))
				{
					return false;
				}
			}
			if ($this->isNeedFirstPreparePopup($landing->getSiteId()))
			{
				$this->addError(
					'SITE_IS_NOW_CREATING'
				);
				return false;
			}
			$url = $landing->getPublicUrl(false, true, true);
			\localRedirect($this->getTimestampUrl($url), true);
		}

		$this->setErrors(
			$landing->getError()->getErrors()
		);

		return false;
	}

	/**
	 * Cancel publication the landing.
	 * @param int $id Landing id.
	 * @return boolean
	 */
	protected function actionUnpublic($id)
	{
		$landing = Landing::createInstance($id);

		if ($landing->exist())
		{
			if ($landing->unpublic())
			{
				return true;
			}
		}

		$this->setErrors(
			$landing->getError()->getErrors()
		);

		return false;
	}

	/**
	 * Gets sites count.
	 * @return int
	 */
	public function getSitesCount()
	{
		if (is_int($this->sitesCount))
		{
			return $this->sitesCount;
		}

		$res = Site::getList(array(
			'select' => array(
				new \Bitrix\Main\Entity\ExpressionField(
					'CNT', 'COUNT(*)'
				)
			),
			'filter' => array(
				'=TYPE' => $this->arParams['TYPE']
			)
		));
		if ($row = $res->fetch())
		{
			$this->sitesCount = $row['CNT'];
		}
		else
		{
			$this->sitesCount = 0;
		}

		return $this->sitesCount;
	}

	/**
	 * Gets pages count of current site.
	 * @param int $siteId Site id.
	 * @return int
	 */
	public function getPagesCount($siteId = null)
	{
		if (is_int($this->pagesCount))
		{
			return $this->pagesCount;
		}

		$res = Landing::getList(array(
			'select' => array(
				new \Bitrix\Main\Entity\ExpressionField(
					'CNT', 'COUNT(*)'
				)
			),
			'filter' => array(
				'=SITE_ID' => ($siteId === null)
							? $siteId
							: $this->arParams['SITE_ID']
			)
		));
		if ($row = $res->fetch())
		{
			$this->pagesCount = (int) $row['CNT'];
		}
		else
		{
			$this->pagesCount = 0;
		}
		
		return $this->pagesCount;
	}

	/**
	 * Handler on view landing.
	 * @return void
	 */
	protected function onLandingView()
	{
		$type = strtolower($this->arParams['TYPE']);
		$landing = $this->arResult['LANDING'];
		$params = $this->arParams;
		$eventManager = EventManager::getInstance();
		$eventManager->addEventHandler('landing', 'onLandingView',
			function(\Bitrix\Main\Event $event) use ($type, $params, $landing)
			{
				/** @var \Bitrix\Landing\Landing $landing */
				$result = new \Bitrix\Main\Entity\EventResult;
				$b24 = \Bitrix\Landing\Manager::isB24();
				$isStore = \Bitrix\Landing\Manager::isStoreEnabled();
				$options = $event->getParameter('options');
				$meta = $landing->getMeta();
				$options['version'] = Manager::getVersion();
				$options['params'] = (array)$params['PARAMS'];
				$options['params']['type'] = $params['TYPE'];
				$options['sites_count'] = $this->getSitesCount();
				$options['pages_count'] = $this->getPagesCount($landing->getSiteId());
				$options['syspages'] = array();
				$options['promoblocks'] = array();
				$options['helps'] = [
					'DYNAMIC_BLOCKS' => \Bitrix\Landing\Help::getHelpUrl('DYNAMIC_BLOCKS')
				];
				$options['features'] = [
					Manager::FEATURE_DYNAMIC_BLOCK => Manager::checkFeature(
						Manager::FEATURE_DYNAMIC_BLOCK
					)
				];
				$options['rights'] = Rights::getOperationsForSite(
					$landing->getSiteId()
				);
				$options['placements'] = array(
					'blocks' => array(),
					'image' => array()
				);
				$options['hooks'] = array(
					'YACOUNTER' => array(),
					'GACOUNTER' => array()
				);
				$options['lastModified'] = isset($meta['DATE_MODIFY'])
					? $meta['DATE_MODIFY']->getTimestamp()
					: null;
				$options['sources'] = array_values(Selector::getSources([]));
				// gets default pages in this site
				// @todo: should refactor for several types (detail, ...)
				if ($options['sources'])
				{
					foreach ($options['sources'] as &$source)
					{
						$source['default'] = [
							'detail' => ''
						];
						$checkPages = [
							'detail' => []
						];
						// get available templates
						$demoPages = Demos::getPageList(
							'page',
							['section' => 'dynamic:' . $source['id']]
						)->getResult();
						foreach ($demoPages as $demoItem)
						{
							if (in_array('dynamic:detail', $demoItem['SECTION']))
							{
								$checkPages['detail'][] = $demoItem['ID'];
							}
						}
						if ($checkPages['detail'])
						{
							$res = Landing::getList([
								'select' => [
									'ID', 'TPL_CODE'
								],
								'filter' => [
									'SITE_ID' => $this->arParams['SITE_ID']
								],
								'order' => [
									'ID' => 'asc'
								]
							]);
							while ($row = $res->fetch())
							{
								if (in_array($row['TPL_CODE'], $checkPages['detail']))
								{
									$source['default']['detail'] = '#landing' . $row['ID'];
								}
							}
						}
					}
					unset($source);
				}
				// product type
				if (ModuleManager::isModuleInstalled('bitrix24'))
				{
					$options['productType'] = 'b24cloud';
				}
				else if (ModuleManager::isModuleInstalled('intranet'))
				{
					$options['productType'] = 'b24selfhosted';
				}
				else
				{
					$options['productType'] = 'smn';
				}
				// some hooks
				$hookSite = Hook::getForSite($params['SITE_ID']);
				$hookLanding = Hook::getForLanding($params['LANDING_ID']);
				foreach ($options['hooks'] as $hook => &$hookFields)
				{
					$fields = array();
					if (
						isset($hookLanding[$hook]) &&
						$hookLanding[$hook]->enabled()
					)
					{
						$fields = $hookLanding[$hook]->getFields();
					}
					elseif (
						isset($hookSite[$hook]) &&
						$hookSite[$hook]->enabled()
					)
					{
						$fields = $hookSite[$hook]->getFields();
					}
					foreach ($fields as $fieldCode => $field)
					{
						$hookFields[$fieldCode] = $field->getValue();
					}
				}
				unset($hookFields);
				// get system pages
				foreach (Syspage::get($landing->getSiteId()) as $code => $page)
				{
					$options['syspages'][$code] = array(
						'landing_id' => $page['LANDING_ID'],
						'name' => $page['TITLE']
					);
				}
				if ($mainPageId = $this->arResult['SITE']['LANDING_ID_INDEX'])
				{
					$res = Landing::getList([
						'select' => [
							'TITLE'
						],
						'filter' => [
							'ID' => $mainPageId,
							'CHECK_PERMISSIONS' => 'N'
						]
				 	]);
					if ($row = $res->fetch())
					{
						$options['syspages']['mainpage'] = array(
							'landing_id' => $mainPageId,
							'name' => $row['TITLE']
						);
					}
				}
				// special check for type = SMN
				if ($options['params']['type'] == 'SMN')
				{
					if (isset($options['syspages']['catalog']))
					{
						$options['params']['type'] = 'STORE';
					}
				}
				// unset blocks not for this type
				foreach ($options['blocks'] as &$section)
				{
					foreach ($section['items'] as $code => &$block)
					{
						if (
							!empty($block['type']) &&
							!in_array($type, (array)$block['type']) &&
							($b24 || $block['type'] == 'null')
						)
						{
							unset($section['items'][$code]);
						}
						if (
							$block['type'] == 'store' &&
							!$isStore
						)
						{
							unset($section['items'][$code]);
						}
						if (
							$block['version'] &&
							version_compare($options['version'], $block['version']) < 0
						)
						{
							$block['requires_updates'] = true;
						}
						else
						{
							$block['requires_updates'] = false;
						}
					}
					unset($block);
				}
				unset($section);
				// redefine options
				if (\Bitrix\Main\Loader::includeModule('rest'))
				{
					// add promo blocks
					$blocks = \Bitrix\Rest\Marketplace\Client::getByTag(
						array('sites', 'crm'),
						1
					);
					if (isset($blocks['ITEMS']) && !empty($blocks['ITEMS']))
					{
						shuffle($blocks['ITEMS']);
						$blocks = array_shift(array_chunk($blocks['ITEMS'], 5));
						foreach ($blocks as $block)
						{
							$options['promoblocks'][$block['CODE']] = array(
								'name' => $block['NAME'],
								'description' => '',
								'preview' => $block['ICON'],
								'price' => isset($block['PRICE'][1])
											? $block['PRICE'][1]
											: ''
							);
						}
					}
					// add placements
					$res = \Bitrix\Rest\PlacementTable::getList(array(
						'select' => array(
							'ID', 'APP_ID', 'PLACEMENT', 'TITLE',
							'APP_NAME' => 'REST_APP.APP_NAME'
						),
						'filter' => array(
							array(
								'LOGIC' => 'OR',
								['PLACEMENT' => 'LANDING_BLOCK_%'],
								['=PLACEMENT' => 'LANDING_IMAGE']
							)
						),
						'order' => array(
							'ID' => 'DESC'
						)
					));
					while ($row = $res->fetch())
					{
						$placementType = ($row['PLACEMENT'] == 'LANDING_IMAGE')
										? 'image'
										: 'blocks';
						$row['PLACEMENT'] = strtolower(substr($row['PLACEMENT'], 14));
						if (!isset($options['placements'][$placementType][$row['PLACEMENT']]))
						{
							$options['placements'][$placementType][$row['PLACEMENT']] = array();
						}
						$options['placements'][$placementType][$row['PLACEMENT']][$row['ID']] = array(
							'id' => $row['ID'],
							'placement' => $row['PLACEMENT'],
							'app_id' => $row['APP_ID'],
							'title' => trim($row['TITLE'])
										? $row['TITLE']
										: $row['APP_NAME']
						);
					}
				}
				if (\Bitrix\Main\Loader::includeModule('bitrix24'))
				{
					$options['license'] = \CBitrix24::getLicenseType();
				}
				$result->modifyFields(array(
					'options' => $options
				));
				return $result;
			}
		);
	}
	
	/**
	 * Handler on template epilog.
	 * @return void
	 */
	protected function onEpilog()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->addEventHandler('main', 'OnEpilog',
			function()
			{
				Manager::initAssets();
			}
		);
	}

	/**
	 * Base executable method.
	 * @return void
	 */
	public function executeComponent()
	{
		$init = $this->init();

		if ($init)
		{
			$this->checkParam('SITE_ID', 0);
			$this->checkParam('LANDING_ID', 0);
			$this->checkParam('TYPE', '');
			$this->checkParam('PAGE_URL_URL_SITES', '');
			$this->checkParam('PAGE_URL_LANDINGS', '');
			$this->checkParam('PAGE_URL_LANDING_EDIT', '');
			$this->checkParam('PAGE_URL_SITE_EDIT', '');
			$this->checkParam('PARAMS', array());

			Landing::setEditMode();
			$landing = Landing::createInstance($this->arParams['LANDING_ID']);

			$this->arResult['LANDING'] = $landing;
			$this->arResult['~LANDING_FULL_URL'] = $landing->getPublicUrl(false, true, true);
			$this->arResult['LANDING_FULL_URL'] = $this->getTimestampUrl(
				$this->arResult['~LANDING_FULL_URL']
			);

			if ($landing->exist())
			{
				$this->arResult['SITES_COUNT'] = $this->getSitesCount();
				$this->arResult['PAGES_COUNT'] = $this->getPagesCount($landing->getSiteId());
				$this->arResult['SITE'] = $this->getSites(array(
					'filter' => array(
						'ID' => $landing->getSiteId()
					)
				));
				if ($this->arResult['SITE'])
				{
					$this->arResult['SITE'] = array_pop($this->arResult['SITE']);
				}
				else
				{
					return;
				}
				// disable optimisation
				if (\Bitrix\Landing\Manager::isB24())
				{
					$asset = \Bitrix\Main\Page\Asset::getInstance();
					if (
						method_exists($asset, 'disableOptimizeCss') &&
						method_exists($asset, 'disableOptimizeJs')
					)
					{
						$asset->disableOptimizeCss();
						$asset->disableOptimizeJs();
					}
				}
				// get settings placements
				$this->arResult['PLACEMENTS_SETTINGS'] = array();
				if (\Bitrix\Main\Loader::includeModule('rest'))
				{
					$res = \Bitrix\Rest\PlacementTable::getList(array(
						'select' => array(
							'ID', 'APP_ID', 'PLACEMENT', 'TITLE',
							'APP_NAME' => 'REST_APP.APP_NAME'
						),
						'filter' => array(
							'=PLACEMENT' => 'LANDING_SETTINGS'
						),
						'order' => array(
							'ID' => 'DESC'
						)
					));
					while ($row = $res->fetch())
					{
						$this->arResult['PLACEMENTS_SETTINGS'][] = $row;
					}
				}
				// can publication / edit settings for page?
				$canPublication = Manager::checkFeature(
					Manager::FEATURE_PUBLICATION_PAGE,
					array(
						'filter' => array(
							'!ID' => $landing->getId()
						)
					)
				);
				$this->arResult['CAN_PUBLICATION_PAGE'] = $canPublication;
				if ($canPublication)
				{
					$canPublication = Manager::checkFeature(
						Manager::FEATURE_PUBLICATION_SITE,
						array(
							'filter' => array(
								'!ID' => $landing->getSiteId()
							),
							'type' => $this->arParams['TYPE']
						)
					);
					$this->arResult['CAN_PUBLICATION_SITE'] = $canPublication;
				}
				$rights = Rights::getOperationsForSite(
					$landing->getSiteId()
				);
				$this->arResult['CAN_SETTINGS_SITE'] = in_array(
					Rights::ACCESS_TYPES['sett'],
					$rights
				);
				$this->arResult['CAN_PUBLIC_SITE'] = in_array(
					Rights::ACCESS_TYPES['public'],
					$rights
				);
				$this->arResult['CAN_EDIT_SITE'] = in_array(
					Rights::ACCESS_TYPES['edit'],
					$rights
				);

				$this->onLandingView();
				$this->onEpilog();
			}


			// some errors?
			$this->setErrors(
				$landing->getError()->getErrors()
			);
		}

		parent::executeComponent();
	}
}