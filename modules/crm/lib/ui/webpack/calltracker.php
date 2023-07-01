<?php

namespace Bitrix\Crm\UI\Webpack;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\WebPacker;
use Bitrix\Main\PhoneNumber;
use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class CallTracker
 *
 * @package Bitrix\Crm\UI\Webpack
 */
class CallTracker extends Base
{
	/** @var static $instance */
	protected static $instance;

	protected static $type = self::TYPE_CALL_TRACKER;

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!static::$instance)
		{
			static::$instance = new static(1);
		}

		return static::$instance;
	}

	/**
	 * Rebuild agent.
	 *
	 * @return string
	 */
	public static function rebuildAgent()
	{
		if ((new static(1))->build())
		{
			return '';
		}
		else
		{
			return '\\Bitrix\\Crm\\UI\\Webpack\\CallTracker::rebuildAgent();';
		}
	}

	/**
	 * Rebuild if enabled.
	 *
	 * @return bool
	 */
	public static function rebuildEnabled()
	{
		/*
		if (!static::isEnabled())
		{
			return true;
		}
		*/
		CallTrackerEditor::rebuild(1); // TODO: Remove before release

		return static::rebuild(1);
	}

	/**
	 * Configure. Set extensions and modules to controller.
	 *
	 * @return void
	 */
	public function configure()
	{
		$name = 'crm.tracking.tracker';
		$this->addExtension($name);

		$module = $this->getModule($name);
		$module->getProfile()->setCallParameter($this->getCallParameter());

		$this->embeddedModuleName = $name . '.loader';
	}

	protected function getCallParameter()
	{
		$asset = new WebPacker\Resource\JsAsset();
		$asset->setContent(CallTrackerEditor::instance()->getEmbeddedBody());
		$package = new WebPacker\Resource\Package();
		$package->addAsset($asset);

		$sources = self::getSources();
		$sites = self::getSites();
		$enabled = !empty($sources) && !empty($sites);

		$parameter = [
			'enabled' => $enabled,
			'sources' => $enabled ? $sources : [],
			'sites' => $enabled ? $sites : [],
			'editor' => [
				'resources' => $package->toArray()
			]
		];

		return $parameter;
	}

	/**
	 * Return true if calltracking enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		return !empty(self::getSources()) && !empty(self::getSites());
	}

	/**
	 * Get sources.
	 *
	 * @param bool $withNames With names.
	 * @return array
	 */
	public static function getSources($withNames = false)
	{
		$result = [];
		foreach (Tracking\Provider::getReadySources() as $source)
		{
			$phoneNumbers = array_map(
				function ($phoneNumber)
				{
					return (PhoneNumber\Parser::getInstance()->parse($phoneNumber)->format()
						?: $phoneNumber
					);
				},
				$source['PHONE'] ?: []
			);
			$result[] = [
				'code' => $withNames ? $source['UTM_SOURCE'][0] : null,
				'name' => $withNames ? $source['NAME'] : null,
				'utm' => $source['UTM_SOURCE'],
				'replacement' => [
					'email' => $source['EMAIL'],
					'phone' => $phoneNumbers
				]
			];
		}

		return $result;
	}

	/**
	 * Get demo sources.
	 *
	 * @return array
	 */
	public static function getDemoSources()
	{
		return [
			[
				'code' => 'demo-source-1',
				'name' => Loc::getMessage("CRM_UI_WEBPACK_CALL_TRACKER_DEMO_1"),
				'utm' => ['demo-source-1'],
				'replacement' => [
					'email' => ['demo1@example.com'],
					'phone' => ['+1 111 111 111'],
				]
			],
			[
				'code' => 'demo-source-2',
				'name' => Loc::getMessage("CRM_UI_WEBPACK_CALL_TRACKER_DEMO_2"),
				'utm' => ['demo-source-2'],
				'replacement' => [
					'email' => ['demo2@example.com'],
					'phone' => ['+2 222 222 222 '],
				]
			]
		];
	}

	/**
	 * Get sites.
	 *
	 * @return array
	 */
	public static function getSites()
	{
		$result = [];
		foreach (Tracking\Provider::getReadySites() as $site)
		{
			$replacement = [];
			foreach ($site['EMAILS'] as $value)
			{
				$replacement[] = ['type' => 'email', 'value' => $value];
			}
			foreach ($site['PHONES'] as $value)
			{
				$replacement[] = ['type' => 'phone', 'value' => $value];
			}
			$result[] = [
				'host' => $site['HOST'],
				'b24' => false,
				'replaceText' => $site['REPLACE_TEXT'] === 'Y',
				'enrichText' => $site['ENRICH_TEXT'] === 'Y',
				'resolveDup' => $site['RESOLVE_DUPLICATES'] === 'Y',
				'replacement' => $replacement
			];
		}

		return array_merge($result, self::getB24Sites());
	}

	/**
	 * Get b24 sites.
	 *
	 * @return array
	 */
	public static function getB24Sites()
	{
		$hosts = Tracking\Provider::getReadyB24SiteDomains();
		if (empty($hosts))
		{
			return [];
		}

		return [
			[
				'host' => $hosts,
				'b24' => true,
				'replaceText' => false,
				'enrichText' => false,
				'resolveDup' => false,
				'replacement' => 'all'
			]
		];
	}
}