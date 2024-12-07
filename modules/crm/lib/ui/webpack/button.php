<?php

namespace Bitrix\Crm\UI\Webpack;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\WebPacker;
use Bitrix\Crm\SiteButton;
use Bitrix\Crm\Tracking;

/**
 * Class GlobalTag
 *
 * @package Bitrix\Crm\UI\Webpack
 */
class Button extends Base
{
	protected static $type = self::TYPE_SITE_BUTTON;

	/** @var SiteButton\Button $button */
	protected $button;

	/** @var array|null $widgets */
	protected $widgets;

	/** @var string $languageId */
	protected $languageId;

	/**
	 * Get instance.
	 *
	 * @param int $buttonId Button ID.
	 * @return static
	 */
	public static function instance($buttonId)
	{
		return new static($buttonId);
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->button = new SiteButton\Button($this->getId());
		$data = $this->button->getData();

		$this->fileDir = 'site_button';
		$this->fileName = str_replace(
			['#id#', '#sec#'],
			[$this->button->getId(), $data['SECURITY_CODE']],
			'loader_#id#_#sec#.js'
		);

		if (true)
		{
			$this->addModule(CallTracker::instance()->getEmbeddedModule());
		}
		else
		{
			$this->addModule(CallTracker::instance()->getModule('crm.tracking.tracker'));
		}

		$name = 'crm.site.button';
		$this->addExtension($name);

		$colorLangAsset = new WebPacker\Resource\LangAsset();
		$colorLangAsset->setContent([
			'location' => SiteButton\Internals\ButtonTable::getLocationCode($data['LOCATION']),
			'colorBackground' => $data['BACKGROUND_COLOR'] ?: '#339BFF',
			'colorIcon' => $data['ICON_COLOR'] ?: '#FFFFFF',
		]);
		$module = $this->getModule($name);
		$module->getPackage()->addAsset($colorLangAsset);
		foreach ($this->getWidgetResources() as $asset)
		{
			$module->getPackage()->addAsset($asset);
		}

		$module->getProfile()->setCallParameter($this->getCallParameter());
	}

	protected function getCallParameter()
	{
		if ($this->button->getLanguageId() != Context::getCurrent()->getLanguage())
		{
			$this->languageId = $this->button->getLanguageId();
			Loc::setCurrentLang($this->languageId);
		}

		$data = $this->button->getData();
		$disableOnMobile = (isset($data['SETTINGS']) && isset($data['SETTINGS']['DISABLE_ON_MOBILE']) && $data['SETTINGS']['DISABLE_ON_MOBILE'] == 'Y');

		$parameter = [
			'isActivated' => $data['ACTIVE'] !== 'N',
			'id' => $data['ID'] ?? null,
			'tracking' => [
				'channel' => [
					'code' => Tracking\Channel\Base::Button,
					'value' => $this->button->getId(),
				]
			],
			'disableOnMobile' => $disableOnMobile,
			'location' => (int) $data['LOCATION'],
			'delay' => (int) $data['DELAY'],
			'bgColor' => $data['BACKGROUND_COLOR'],
			'iconColor' => $data['ICON_COLOR'],
			'widgets' => $this->getWidgets(),
			'hello' => $this->getHelloData()
		];

		if ($this->languageId)
		{
			Loc::setCurrentLang(LANGUAGE_ID);
		}

		return $parameter;
	}

	public function getWidgets()
	{
		if ($this->widgets !== null)
		{
			return $this->widgets;
		}

		$this->widgets = [];
		$typeList = SiteButton\Manager::getTypeList();
		foreach ($typeList as $typeId => $typeName)
		{
			if(!$this->button->hasActiveItem($typeId))
			{
				continue;
			}

			$item = $this->button->getItemByType($typeId);
			$config = $item['CONFIG'] ?? [];
			$typeWidgets = SiteButton\ChannelManager::getWidgets(
				$typeId,
				$item['EXTERNAL_ID'],
				$this->button->isCopyrightRemoved(),
				$this->languageId,
				$config
			);

			if(count($typeWidgets) <= 0)
			{
				continue;
			}

			$pages = [
				'mode' => 'EXCLUDE',
				'list' => []
			];
			if($this->button->hasItemPages($typeId))
			{
				$pages['mode'] = $item['PAGES']['MODE'];
				$pages['list'] = $item['PAGES']['LIST'][$pages['mode']];
			}

			$workTime = $this->button->getItemWorkTime($typeId);
			if ($workTime['ENABLED'])
			{
				$workTime = SiteButton\WorkTime::convertToJS($workTime);
			}
			else
			{
				$workTime = null;
			}

			foreach ($typeWidgets as $typeWidget)
			{
				$typeWidget['type'] = $typeId;
				$typeWidget['pages'] = $pages;
				$typeWidget['workTime'] = $workTime;
				$this->widgets[] = $typeWidget;
			}
		}

		return $this->widgets;
	}

	protected function getHelloData()
	{
		$widgetOrderList = array(
			SiteButton\Manager::ENUM_TYPE_OPEN_LINE,
			SiteButton\Manager::ENUM_TYPE_OPEN_LINE . '_livechat',
			SiteButton\Manager::ENUM_TYPE_CALLBACK,
			SiteButton\Manager::ENUM_TYPE_CRM_FORM,
		);
		$showWidgetId = '';
		$widgetIdList = [];
		$widgets = $this->getWidgets();
		foreach ($widgets as $widget)
		{
			$widgetIdList[] = $widget['id'];
		}

		foreach ($widgetOrderList as $widgetOrderId)
		{
			if (in_array($widgetOrderId, $widgetIdList))
			{
				$showWidgetId = $widgetOrderId;
				break;
			}
		}

		if (!$showWidgetId && $widgetIdList[0])
		{
			$showWidgetId = $widgetIdList[0];
		}

		$buttonData = $this->button->getData();
		$settings = is_array($buttonData['SETTINGS']) ? $buttonData['SETTINGS'] : array();
		$hello = is_array($settings['HELLO']) ? $settings['HELLO'] : array();
		$hello['CONDITIONS'] = is_array($hello['CONDITIONS']) ? $hello['CONDITIONS'] : array();
		$conditions = array();

		if ($hello['ACTIVE'])
		{
			foreach ($hello['CONDITIONS'] as $condition)
			{
				if ($condition['PAGES'] && is_array($condition['PAGES']['LIST']))
				{
					$condition['PAGES']['LIST'] = array_values($condition['PAGES']['LIST']);
				}

				$conditions[] = array(
					'icon' => $condition['ICON'],
					'name' => $condition['NAME'],
					'text' => $condition['TEXT'],
					'pages' => $condition['PAGES'],
					'delay' => $condition['DELAY'],
				);
			}

			if ($hello['MODE'] == 'INCLUDE' && isset($conditions[0]))
			{
				unset($conditions[0]);
				sort($conditions);
			}
		}

		return array(
			'delay' => 1,
			'showWidgetId' => $showWidgetId,
			'conditions' => $conditions
		);
	}

	public function getWidgetResources()
	{
		$resources = array();
		$widgetList = SiteButton\Manager::getWidgetList();
		foreach ($widgetList as $item)
		{
			if(!$this->button->hasActiveItem($item['TYPE']))
			{
				continue;
			}

			if (empty($item['RESOURCES']) || !is_array($item['RESOURCES']))
			{
				continue;
			}

			$resources = array_merge($resources, $item['RESOURCES']);
		}

		return $resources;
	}
}