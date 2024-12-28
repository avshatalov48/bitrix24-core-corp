<?php

namespace Bitrix\Intranet\Integration\Templates\Bitrix24;

use Bitrix\Intranet\Composite\CacheProvider;
use Bitrix\Intranet\Internals\ThemeTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\WorkgroupSiteTable;

class ThemePicker
{
	const MAX_IMAGE_WIDTH = 5000;
	const MAX_IMAGE_HEIGHT = 5000;
	const MAX_CUSTOM_THEMES = 40;
	const MAX_UPLOAD_SIZE = 20971520; //20Mb

	public const ENTITY_TYPE_USER = 'USER';
	public const ENTITY_TYPE_SONET_GROUP = 'SONET_GROUP';

	public const VALID_ENTITY_TYPE_LIST = [
		self::ENTITY_TYPE_USER,
		self::ENTITY_TYPE_SONET_GROUP,
	];

	public const BEHAVIOUR_APPLY = 'apply';
	public const BEHAVIOUR_RETURN = 'return';
	public const VALID_BEHAVIOUR_LIST = [
		self::BEHAVIOUR_APPLY,
		self::BEHAVIOUR_RETURN,
	];

	public const DEFAULT_THEME_ID = 'light:gravity';

	private static $instance = null;
	private static $config = null;

	private $templateId = null;
	private $templatePath = null;
	private $siteId = null;
	private $userId = 0;
	private $currentTheme = null;
	private $zoneId = null;
	private $entityType = self::ENTITY_TYPE_USER;
	private $entityId = 0;
	private $behaviour = self::BEHAVIOUR_APPLY;

	/**
	 * Theme constructor.
	 *
	 * @param string $templateId
	 * @param bool $siteId
	 * @param int $entityId
 	 * @param string $entityType
	 */
	public function __construct($templateId, $siteId = false, $userId = 0, $entityType = self::ENTITY_TYPE_USER, $entityId = 0, $params = [])
	{
		if (!static::isValidTemplateId($templateId))
		{
			throw new ArgumentException("The given argument 'templateId' is incorrect.");
		}

		$this->templateId = $templateId;
		$this->templatePath = \getLocalPath("templates/".$templateId, BX_PERSONAL_ROOT);
		$this->siteId = is_string($siteId)? mb_substr(preg_replace("/[^a-z0-9_]/i", "", $siteId), 0, 2) : SITE_ID;
		if (in_array($entityType, self::VALID_ENTITY_TYPE_LIST))
		{
			$this->entityType = $entityType;
		}

		if (
			isset($params['behaviour'])
			&& in_array((string)$params['behaviour'], self::VALID_BEHAVIOUR_LIST)
		)
		{
			$this->behaviour = (string)$params['behaviour'];
		}

		if (is_numeric($userId) && $userId > 0)
		{
			$this->userId = $userId;
		}
		else
		{
			$user = &$GLOBALS['USER'];
			if ($user instanceof \CUser)
			{
				$this->userId = (int)$user->getId();
			}
		}

		if (is_numeric($entityId) && $entityId > 0)
		{
			$this->entityId = $entityId;
		}
		elseif ($entityType === self::ENTITY_TYPE_USER)
		{
			$this->entityId = $this->userId;
		}

		$this->zoneId = Application::getInstance()->getLicense()->getRegion() ?? 'en';

		$res = ThemeTable::getList([
			'filter' => [
				'=ENTITY_TYPE' => $this->getEntityType(),
				'ENTITY_ID' => $this->getEntityId(),
				'=CONTEXT' => $this->getContext(),
			],
			'select' => [ 'THEME_ID', 'ENTITY_TYPE', 'USER_ID' ],
			'cache' => static::getSelectCacheParams(),
		]);

		if (
			($themeFields = $res->fetch())
			&& $this->isValidTheme($themeFields['THEME_ID'], ($themeFields['ENTITY_TYPE'] === self::ENTITY_TYPE_SONET_GROUP ? (int)$themeFields['USER_ID'] : false))
		)
		{
			$this->currentTheme = $this->getTheme($themeFields['THEME_ID'], ($themeFields['ENTITY_TYPE'] === self::ENTITY_TYPE_SONET_GROUP ? $themeFields['USER_ID'] : false));
		}
		else
		{
			$this->currentTheme = $this->getDefaultTheme();
		}
	}

	public function setThemeForCurrentPage($themeId)
	{
		$this->currentTheme = $this->getTheme($themeId);
	}

	/**
	 * @param string $entityType
	 * @return ThemePicker
	 */
	public static function getInstance($entityType = self::ENTITY_TYPE_USER)
	{
		if (static::$instance === null)
		{
			$templateId = defined("SITE_TEMPLATE_ID") ? SITE_TEMPLATE_ID : "bitrix24";
			static::$instance = new static($templateId, false, 0, $entityType);
		}

		return static::$instance;
	}

	public static function isAdmin()
	{
		if (!is_object($GLOBALS["USER"]))
		{
			return false;
		}

		return
			$GLOBALS["USER"]->isAdmin() ||
			(Loader::includeModule("bitrix24") && \CBitrix24::isPortalAdmin($GLOBALS["USER"]->getID()))
		;
	}

	public static function isAvailable()
	{
		return !Loader::includeModule("bitrix24") || \Bitrix\Bitrix24\Release::isAvailable("17.5.0");
	}

	public static function canSetDefaultTheme(): bool
	{
		if (!static::isAdmin())
		{
			return false;
		}

		if (!Loader::includeModule("bitrix24"))
		{
			return true;
		}

		$licenseType = \CBitrix24::getLicenseType();
		if ((\CBitrix24::isLicensePaid() && $licenseType !== 'basic') || \CBitrix24::IsNfrLicense())
		{
			return true;
		}

		return (
			\CBitrix24::isDemoLicense()
			&& in_array(\CBitrix24::getLicenseType(\CBitrix24::LICENSE_TYPE_PREVIOUS), \CBitrix24::PAID_EDITIONS, true)
			&& $licenseType !== 'basic'
		);
	}

	public function showHeadAssets(): void
	{
		$this->registerJsExtension();
		$this->registerCss();

		$theme = $this->getCurrentTheme();
		$theme = $theme ?: [];

		$options = \CUtil::phpToJSObject(
			array(
				"templateId" => $this->getTemplateId(),
				"siteId" => $this->getSiteId(),
				"themeId" => $this->getCurrentThemeId(),
				"theme" => $theme,
				"maxUploadSize" => static::getMaxUploadSize(),
				"ajaxHandlerPath" => $this->getAjaxHandlerPath(),
				"isAdmin" => static::isAdmin(),
				"allowSetDefaultTheme" => static::canSetDefaultTheme(),
				"isVideo" => isset($theme["video"]),
				'entityType' => $this->getEntityType(),
				'entityId' => $this->getEntityId(),
				'behaviour' => $this->getBehaviour(),
		 	),
			false,
			false,
			true
		);

		Asset::getInstance()->addString(
			"<script>BX.Intranet.Bitrix24.ThemePicker.Singleton = new BX.Intranet.Bitrix24.ThemePicker($options);</script>",
			false,
			AssetLocation::AFTER_JS
		);
	}

	public function showBodyAssets()
	{
		$theme = $this->getCurrentTheme();
		if (!$theme || !isset($theme["video"]) || !is_array($theme["video"]))
		{
			return;
		}

		echo '<div class="theme-video-container" data-theme-id="'.$theme["id"].'">';
		echo '<video poster="'.$theme["video"]["poster"].'" class="theme-video" pip="false" autoplay loop muted playsinline>';

		foreach ($theme["video"]["sources"] as $type => $source)
		{
			echo '<source src="'.$source.'" type="video/'.$type.'">';
		}

		echo '</video>';
		echo '</div>';
	}

	public function shouldShowHint()
	{
		if (!static::isAvailable())
		{
			return false;
		}

		$creationDate = \COption::getOptionInt("main", "~controller_date_create", 0);
		if ($creationDate === 0)
		{
			return \CUserOptions::getOption("intranet", $this->getHintOptionName(), true, $this->getUserId());
		}

		return
			$creationDate < mktime(0, 0, 0, 9, 30, 2017) &&
			time() - $creationDate > 86400 * 14 &&
			\CUserOptions::getOption("intranet", $this->getHintOptionName(), true, $this->getUserId())
		;
	}

	public function hideHint()
	{
		\CUserOptions::setOption("intranet", $this->getHintOptionName(), false, false, $this->getUserId());
	}

	public function getCurrentThemeId()
	{
		return $this->currentTheme !== null ? $this->currentTheme["id"] : $this->getInitialDefaultThemeId();
	}

	public function getCurrentTheme()
	{
		return $this->currentTheme;
	}

	public function getCurrentBaseThemeId()
	{
		[$baseThemeId] = static::getThemeIdParts($this->getCurrentThemeId());
		return $baseThemeId;
	}

	public function getCurrentSubThemeId()
	{
		[, $subThemeId] = static::getThemeIdParts($this->getCurrentThemeId());
		return $subThemeId;
	}

	public function setCurrentThemeId($themeId, $currentUserId = 0): bool
	{
		$contextList = [ $this->getContext() ];
		$entityId = (int)$this->getEntityId();
		if ($entityId <= 0)
		{
			return false;
		}

		if (
			$this->getEntityType() === self::ENTITY_TYPE_SONET_GROUP
			&& Loader::includeModule('socialnetwork')
		)
		{
			$contextList = [];
			$res = WorkgroupSiteTable::getList([
				'filter' => [
					'GROUP_ID' => $entityId
				],
				'select' => [ 'SITE_ID' ]
			]);
			while ($workgroupSiteFields = $res->fetch())
			{
				$contextList[] = $this->getTemplateId() . '_' . $workgroupSiteFields['SITE_ID'];
			}
		}

		if ($this->isValidTheme($themeId))
		{
			//Standard or Custom Own Themes
			if ($themeId !== $this->getDefaultThemeId())
			{
				$currentUserId = (int)$currentUserId;
				if ($currentUserId <= 0)
				{
					$currentUserId = (is_object($GLOBALS["USER"]) ? (int)$GLOBALS["USER"]->getID() : 0);
				}

				foreach ($contextList as $context)
				{
					ThemeTable::set([
						'USER_ID' => $currentUserId,
						'THEME_ID' => $themeId,
						'ENTITY_TYPE' => $this->getEntityType(),
						'ENTITY_ID' => $entityId,
						'CONTEXT' => $context,
					]);
				}
			}
			else
			{
				foreach ($contextList as $context)
				{
					$res = ThemeTable::getList([
						'filter' => [
							'=ENTITY_TYPE' => $this->getEntityType(),
							'ENTITY_ID' => $entityId,
							'=CONTEXT' => $context,
						],
						'select' => [ 'ID' ],
						'cache' => static::getSelectCacheParams(),
					]);
					while ($themeFields = $res->fetch())
					{
						ThemeTable::delete($themeFields['ID']);
					}
				}
			}

			$this->currentTheme = $this->getTheme($themeId);
			$this->setLastUsage($themeId);
			return true;
		}

		if ($themeId === $this->getDefaultThemeId())
		{
			//Custom Admin Theme
			$res = ThemeTable::getList([
				'filter' => [
					'=ENTITY_TYPE' => $this->getEntityType(),
					'ENTITY_ID' => $entityId,
					'=CONTEXT' => $this->getContext(),
				],
				'select' => [ 'ID' ],
				'cache' => static::getSelectCacheParams(),
			]);
			while($themeFields = $res->fetch())
			{
				ThemeTable::delete($themeFields['ID']);
			}

			return true;
		}

		return false;
	}

	public function getList()
	{
		$items = array_merge($this->getCustomThemes(), $this->getStandardThemes());
		$this->sortItems($items);
		return $items;
	}

	public function create(array $fields)
	{
		if (count($this->getCustomThemesOptions()) > static::MAX_CUSTOM_THEMES)
		{
			throw new SystemException(
				Loc::getMessage(
					"INTRANET_B24_INTEGRATION_THEMES_LIMIT_EXCEEDED",
					array("#NUM#" => static::MAX_CUSTOM_THEMES)
				)
			);
		}

		$theme = array();
		if (isset($fields["bgColor"]) && preg_match("/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/", $fields["bgColor"]))
		{
			$theme["bgColor"] = $fields["bgColor"];
		}

		if (isset($fields["bgImage"]["tmp_name"]) && $fields["bgImage"]["tmp_name"] <> '')
		{
			$error = \CFile::checkImageFile(
				$fields["bgImage"],
				static::getMaxUploadSize(),
				static::MAX_IMAGE_WIDTH,
				static::MAX_IMAGE_HEIGHT
			);

			if ($error <> '')
			{
				throw new SystemException($error);
			}

			$imageId = \CFile::saveFile($fields["bgImage"], "bitrix24");
			if (!$imageId)
			{
				throw new SystemException(Loc::getMessage("INTRANET_B24_INTEGRATION_UPLOAD_ERROR"));
			}

			$theme["bgImage"] = $imageId;

			$signer = new Signer();
			$theme["bgImageSignature"] = $signer->sign((string)$imageId, 'theme-picker');
		}

		if (empty($theme))
		{
			throw new SystemException(Loc::getMessage("INTRANET_B24_INTEGRATION_CANT_CREATE_THEME"));
		}

		$baseThemeId = isset($fields["textColor"]) && $fields["textColor"] === "dark" ? "dark" : "light";
		$subThemeId = $this->getCustomThemePrefix().time();
		$themeId = $baseThemeId.":".$subThemeId;

		$customThemes = $this->getCustomThemesOptions();
		$customThemes[$themeId] = $theme;
		$this->setCustomThemesOptions($customThemes);

		$this->setLastUsage($themeId);

		return $themeId;
	}

	public function remove($themeId)
	{
		if ($this->getCustomTheme($themeId) === null)
		{
			return false;
		}

		if (static::isAdmin() && $themeId === $this->getDefaultThemeId())
		{
			$res = ThemeTable::getList([
				'filter' => [
					'=ENTITY_TYPE' => $this->getEntityType(),
					'ENTITY_ID' => 0,
					'=CONTEXT' => $this->getContext(),
				],
				'select' => [ 'ID' ],
				'cache' => static::getSelectCacheParams(),
			]);
			while($themeFields = $res->fetch())
			{
				ThemeTable::delete($themeFields['ID']);
			}

			if (\CHTMLPagesCache::isOn() && Loader::includeModule('intranet'))
			{
				CacheProvider::deleteAllCache();
			}
		}

		$customThemes = $this->getCustomThemesOptions();
		if (isset($customThemes[$themeId]['bgImage']))
		{
			\CFile::delete($customThemes[$themeId]['bgImage']);
		}

		$customThemes = $this->getCustomThemesOptions();
		unset($customThemes[$themeId]);
		$this->setCustomThemesOptions($customThemes);

		$this->setLastUsage($themeId, false);

		if ($this->getCurrentThemeId() === $themeId)
		{
			$res = ThemeTable::getList([
				'filter' => [
					'=ENTITY_TYPE' => $this->getEntityType(),
					'ENTITY_ID' => $this->getEntityId(),
					'=CONTEXT' => $this->getContext(),
				],
				'select' => [ 'ID' ],
				'cache' => static::getSelectCacheParams(),
			]);
			while($themeFields = $res->fetch())
			{
				ThemeTable::delete($themeFields['ID']);
			}
		}

		return true;
	}

	public function getCustomThemes()
	{
		$themes = array();

		/** @var array $customThemes */
		$customThemes = $this->getCustomThemesOptions();

		$defaultTheme = $this->getDefaultTheme();
		$defaultThemeId = $this->getDefaultThemeId();
		$defaultFound = false;

		foreach (array_keys($customThemes) as $index => $themeId)
		{
			$theme = $this->getCustomTheme($themeId);
			if ($theme !== null)
			{
				$theme["sort"] = $index + 100;
				$theme["default"] = false;

				if ($defaultThemeId === $themeId)
				{
					$theme["default"] = true;
					$defaultFound = true;
				}

				$themes[] = $theme;
			}
		}

		if (!$defaultFound && $defaultTheme && $this->isCustomThemeId($defaultThemeId))
		{
			$defaultTheme["sort"] = 100;
			$defaultTheme["default"] = true;
			$defaultTheme["removable"] = false;
			$themes[] = $defaultTheme;
		}

		return $themes;
	}

	public function getCustomTheme($customThemeId, $userId = false)
	{
		$customThemes = $this->getCustomThemesOptions($userId);
		if (!is_string($customThemeId) || !isset($customThemes[$customThemeId]) || !is_array($customThemes[$customThemeId]))
		{
			return null;
		}

		[$baseThemeId] = static::getThemeIdParts($customThemeId);
		$customThemeOptions = $customThemes[$customThemeId];

		$customTheme = array(
			"id" => $customThemeId,
			"css" => $this->getBaseThemeCss($baseThemeId),
			"removable" => true,
			"new" => false,
		);

		$style = "body { ";
		if ($this->validateBgImage($customThemeOptions))
		{
			$bgImage = \CFile::getPath($customThemeOptions["bgImage"]);
			$customTheme["prefetchImages"] = array($bgImage);

			$style .= 'background: url("'.$bgImage.'") fixed 0 0 no-repeat; ';
			$style .= 'background-size: cover; ';

			$previewImage = \CFile::resizeImageGet(
				$customThemeOptions["bgImage"],
				array("width" => 400, "height" => 300),
				BX_RESIZE_IMAGE_PROPORTIONAL
			);

			if (is_array($previewImage))
			{
				$customTheme["previewImage"] = $previewImage["src"];
			}

			$image = \CFile::getFileArray($customThemeOptions["bgImage"]);
			if ($image !== false)
			{
				$customTheme["width"] = $image["WIDTH"];
				$customTheme["height"] = $image["HEIGHT"];
				$customTheme["resizable"] = true;
			}
		}

		if (isset($customThemeOptions["bgColor"]))
		{
			$customTheme["previewColor"] = $customThemeOptions["bgColor"];
			$style .= "background-color: ".$customThemeOptions["bgColor"]."; ";
		}

		$style .= " }";
		$customTheme["style"] = $style;

		return $customTheme;
	}

	protected function validateBgImage(array $customTheme): bool
	{
		if (!isset($customTheme["bgImage"]) || empty($customTheme["bgImageSignature"]))
		{
			return false;
		}

		$signer = new Signer();

		return $signer->sign((string)$customTheme["bgImage"], 'theme-picker') === $customTheme["bgImageSignature"];
	}

	public function getPatternThemes(): array
	{
		$result = [];

		foreach ($this->getStandardThemes() as $theme)
		{
			if (!preg_match('/^(dark|light):pattern-(.+)/isu', $theme['id'], $matches))
			{
				continue;
			}

			$result[] = $theme;
		}

		return $result;
	}

	public function getStandardThemes()
	{
		$themes = array();
		$config = static::getConfig();

		if (is_array($config) && isset($config["subThemes"]) && is_array($config["subThemes"]))
		{
			$defaultThemeId = $this->getDefaultThemeId();
			foreach (array_keys($config["subThemes"]) as $index => $themeId)
			{
				$theme = $this->getStandardTheme($themeId);
				if ($theme !== null)
				{
					$theme["sort"] = $index + 200;
					$theme["default"] = $defaultThemeId === $themeId;
					$themes[] = $theme;
				}
			}
		}

		return $themes;
	}

	public function getStandardTheme($themeId)
	{
		$config = static::getConfig();
		if (!is_string($themeId) || !is_array($config))
		{
			return null;
		}

		[$baseThemeId, $subThemeId] = static::getThemeIdParts($themeId);
		if (!isset($config["baseThemes"][$baseThemeId]) || !isset($config["subThemes"][$themeId]))
		{
			return null;
		}

		$theme = is_array($config["subThemes"][$themeId]) ? $config["subThemes"][$themeId] : array();
		$theme["id"] = $themeId;
		$theme["removable"] = false;
		$theme["new"] = isset($theme["new"]) && is_bool($theme["new"]) && $theme["new"];

		$themePath = $this->getThemesPath()."/".$baseThemeId.($subThemeId ? "/".$subThemeId : "");
		if (isset($theme["previewImage"]))
		{
			$theme["previewImage"] = static::getAssetPath($theme["previewImage"], $themePath);
		}

		$images = array();
		if (isset($theme["prefetchImages"]) && is_array($theme["prefetchImages"]))
		{
			foreach ($theme["prefetchImages"] as $fileName)
			{
				$images[] = static::getAssetPath($fileName, $themePath);
			}
		}
		$theme["prefetchImages"] = $images;

		$css = $this->getBaseThemeCss($baseThemeId);
		if (isset($theme["css"]) && is_array($theme["css"]))
		{
			foreach ($theme["css"] as $fileName)
			{
				$css[] = \CUtil::getAdditionalFileURL($themePath."/".$fileName);
			}
		}
		else if ($subThemeId)
		{
			$css[] = \CUtil::getAdditionalFileURL($themePath."/style.css");
		}

		$theme["css"] = $css;

		if (isset($theme["video"]) && is_array($theme["video"]))
		{
			$theme["video"]["poster"] =
				isset($theme["video"]["poster"])
					? static::getAssetPath($theme["video"]["poster"], $themePath)
					: ""
			;

			if (isset($theme["video"]["sources"]) && is_array($theme["video"]["sources"]))
			{
				foreach ($theme["video"]["sources"] as $type => $source)
				{
					$theme["video"]["sources"][$type] = static::getAssetPath($source, $themePath);
				}
			}
			else
			{
				$theme["video"]["sources"] = array();
			}
		}

		return $theme;
	}

	public function getTheme($themeId, $userId = false)
	{
		if (!is_string($themeId))
		{
			return null;
		}

		if ($this->isCustomThemeId($themeId))
		{
			return $this->getCustomTheme($themeId, $userId);
		}
		else
		{
			return $this->getStandardTheme($themeId);
		}
	}

	public function getDefaultTheme()
	{
		$theme = null;

		$res = ThemeTable::getList([
			'filter' => [
				'=ENTITY_TYPE' => $this->getEntityType(),
				'ENTITY_ID' => 0,
				'=CONTEXT' => $this->getContext(),
			],
			'select' => [ 'THEME_ID', 'USER_ID' ],
			'cache' => static::getSelectCacheParams(),
		]);

		if ($themeFields = $res->fetch())
		{
			if ($this->isCustomThemeId($themeFields['THEME_ID']))
			{
				$theme = $this->getCustomTheme($themeFields['THEME_ID'], $themeFields['USER_ID']);
			}
			else
			{
				$theme = $this->getStandardTheme($themeFields['THEME_ID']);
			}
		}

		return $theme ?: $this->getStandardTheme($this->getInitialDefaultThemeId());
	}

	public function getDefaultThemeId()
	{
		$defaultTheme = $this->getDefaultTheme();
		return $defaultTheme ? $defaultTheme["id"] : $this->getInitialDefaultThemeId();
	}

	public function getInitialDefaultThemeId(): string
	{
		$eastReleaseDate = \DateTime::createFromFormat('d.m.Y H:i', '27.11.2024 10:00', new \DateTimeZone('Europe/Moscow'));
		if (in_array($this->getZoneId(), ['ru', 'kz', 'by']))
		{
			if (time() > $eastReleaseDate->getTimestamp())
			{
				return 'light:gravity'; // New Default East Theme
			}

			return 'light:video-orion'; // Old Default East Theme
		}

		$westernReleaseDate = \DateTime::createFromFormat('d.m.Y H:i', '12.12.2024 10:00', new \DateTimeZone('Europe/Moscow'));
		if (time() > $westernReleaseDate->getTimestamp())
		{
			return 'light:dark-silk'; // New Default West Theme
		}

		return 'light:contrast-horizon'; // Old Default West Theme
	}

	public function setDefaultTheme($themeId, $currentUserId = 0): bool
	{
		if (
			!$this->isValidTheme($themeId)
			|| $this->getEntityType() !== self::ENTITY_TYPE_USER
		)
		{
			return false;
		}

		$currentUserId = (int)$currentUserId;
		if ($currentUserId <= 0)
		{
			$currentUserId = (is_object($GLOBALS["USER"]) ? (int)$GLOBALS["USER"]->getID() : 0);
		}

		return ThemeTable::set([
			'THEME_ID' => $themeId,
			'USER_ID' => $currentUserId,
			'ENTITY_TYPE' => $this->getEntityType(),
			'ENTITY_ID' => 0,
			'CONTEXT' => $this->getContext(),
		]);
	}

	public function getContext()
	{
		return $this->getTemplateId() . '_' . $this->getSiteId();
	}

	public function getBaseThemes()
	{
		$config = static::getConfig();
		if (!isset($config["baseThemes"]) || !is_array($config["baseThemes"]))
		{
			return array();
		}

		$themes = array();
		foreach ($config["baseThemes"] as $baseThemeId => $baseTheme)
		{
			$themes[$baseThemeId] = array(
				"id" => $baseThemeId,
				"css" => $this->getBaseThemeCss($baseThemeId)
			);
		}

		return $themes;
	}

	public function getTemplateId()
	{
		return $this->templateId;
	}

	public function getTemplatePath()
	{
		return $this->templatePath;
	}

	public function getThemesPath()
	{
		return $this->getTemplatePath()."/themes";
	}

	public function getAjaxHandlerPath()
	{
		return "/bitrix/tools/intranet_theme_picker.php";
	}

	public function getSiteId()
	{
		return $this->siteId;
	}

	public function getZoneId()
	{
		return $this->zoneId;
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function getEntityType(): string
	{
		return $this->entityType;
	}

	public function getEntityId()
	{
		return $this->entityId;
	}

	public function getBehaviour()
	{
		return $this->behaviour;
	}

	public function getCurrentThemeOptionName()
	{
		return "bitrix24_theme_".$this->getTemplateId()."_".$this->getSiteId();
	}

	public function getDefaultThemeOptionName()
	{
		return "bitrix24_default_theme_".$this->getTemplateId()."_".$this->getSiteId();
	}

	public function getCustomThemesOptionName()
	{
		return "bitrix24_custom_themes_".$this->getTemplateId();
	}

	public function getLastThemesOptionName()
	{
		return "bitrix24_last_themes_usage".$this->getTemplateId();
	}

	public function getHintOptionName()
	{
		return "bitrix24_themes_show_hint";
	}

	public function getCustomThemePrefix()
	{
		return "custom_";
	}

	private static function isValidTemplateId($templateId)
	{
		return Path::validateFilename($templateId);
	}

	private static function getMaxUploadSize()
	{
		$maxUploadSize = min(
			static::MAX_UPLOAD_SIZE,
			\CUtil::unformat(ini_get("post_max_size")),
			\CUtil::unformat(ini_get("upload_max_filesize"))
		);

		$maxUploadSize -= 1024 * 200;

		return $maxUploadSize;
	}

	private function isValidTheme($themeId, $userId = false)
	{
		if (!is_string($themeId) || $themeId == '')
		{
			return false;
		}

		if ($this->isCustomThemeId($themeId))
		{
			return $this->getCustomTheme($themeId, $userId) !== null;
		}

		if ($this->getStandardTheme($themeId) === null)
		{
			return false;
		}

		//Check physical existence
		[$baseThemeId, $subThemeId] = static::getThemeIdParts($themeId);
		$baseThemePath = Application::getDocumentRoot().$this->getThemesPath()."/".$baseThemeId;

		if (!Path::validateFilename($baseThemeId) || !Directory::isDirectoryExists($baseThemePath))
		{
			return false;
		}

		if (
			$subThemeId !== null &&
			(!Path::validateFilename($subThemeId) || !Directory::isDirectoryExists($baseThemePath."/".$subThemeId))
		)
		{
			return false;
		}

		return true;
	}

	private function setLastUsage($themeId, $autoPrepend = true)
	{
		/** @var array $themesUsage */
		$themesUsage = \CUserOptions::getOption(
			"intranet",
			$this->getLastThemesOptionName(),
			[],
			$this->getUserId()
		);

		foreach ($themesUsage as $index => $id)
		{
			if ($themeId === $id)
			{
				array_splice($themesUsage, $index, 1);
				break;
			}
		}

		if ($autoPrepend)
		{
			array_unshift($themesUsage, $themeId);
		}

		\CUserOptions::setOption("intranet", $this->getLastThemesOptionName(), $themesUsage, false, $this->getUserId());
	}

	private function registerJsExtension()
	{
		\CJSCore::init("intranet_theme_picker");
	}

	private function registerCss()
	{
		$theme = $this->getCurrentTheme();
		if (!$theme || !isset($theme["css"]) || !is_array($theme["css"]))
		{
			return;
		}

		foreach ($theme["css"] as $file)
		{
			Asset::getInstance()->addString(
				'<link '.
					'href="'.\CUtil::getAdditionalFileURL($file).'" '.
					'type="text/css" '.
					'media="screen" '.
					'data-template-style="true" '.
					'data-theme-id="'.$theme["id"].'" rel="stylesheet"'.
				'>',
				false,
				AssetLocation::AFTER_CSS
			);
		}

		if (isset($theme["style"]))
		{
			Asset::getInstance()->addString(
				'<style '.
					'type="text/css" '.
					'data-template-style="true" '.
					'data-theme-id="'.$theme["id"].'" rel="stylesheet"'.
				'>'.
				$theme["style"].
				'</style>',
				false,
				AssetLocation::AFTER_CSS
			);
		}
	}

	/**
	 * @param $baseThemeId
	 *
	 * @return array
	 */
	private function getBaseThemeCss($baseThemeId)
	{
		$css = array();
		$config = static::getConfig();
		if (!is_string($baseThemeId) || !is_array($config))
		{
			return $css;
		}

		if (isset($config["baseThemes"][$baseThemeId]["css"]) && is_array($config["baseThemes"][$baseThemeId]["css"]))
		{
			foreach ($config["baseThemes"][$baseThemeId]["css"] as $fileName)
			{
				$css[] = \CUtil::getAdditionalFileURL($this->getThemesPath()."/".$baseThemeId."/".$fileName);
			}
		}

		return $css;
	}

	private function sortItems(&$themes)
	{
		/** @var array $themesUsage */
		$themesUsage = \CUserOptions::getOption("intranet", $this->getLastThemesOptionName(), [], $this->getUserId());
		$themesUsage = array_flip($themesUsage);

		foreach ($themes as &$theme)
		{
			if (isset($themesUsage[$theme["id"]]))
			{
				$theme["sort"] = $themesUsage[$theme["id"]];
			}
		}

		sortByColumn($themes, array("new" => SORT_DESC, "sort" => SORT_ASC));
	}

	private function getConfig()
	{
		if (static::$config !== null)
		{
			return static::$config;
		}

		$configFile = Application::getDocumentRoot().$this->getThemesPath()."/config.php";
		if (File::isFileExists($configFile))
		{
			Loc::loadLanguageFile($configFile);
			static::$config = $this->filterUnavailableThemes(include($configFile));
		}

		return static::$config;
	}

	private function filterUnavailableThemes($config)
	{
		if (!is_array($config) || !isset($config["subThemes"]) || !is_array($config["subThemes"]))
		{
			return [];
		}

		$now = time();
		$delta = 24 * 3600 * 10;
		foreach ($config["subThemes"] as $themeId => $theme)
		{
			if (isset($theme["releaseDate"]))
			{
				$releaseDate = strtotime($theme["releaseDate"]);
				if ($releaseDate !== false && ($releaseDate - $delta) > $now)
				{
					unset($config["subThemes"][$themeId]);
					continue;
				}
			}

			if (isset($theme["zones"]) && is_array($theme["zones"]) && !in_array($this->getZoneId(), $theme["zones"]))
			{
				unset($config["subThemes"][$themeId]);
				continue;
			}
		}

		return $config;
	}

	public function isCustomThemeId($themeId)
	{
		if (!is_string($themeId))
		{
			return false;
		}

		[, $subThemeId] = static::getThemeIdParts($themeId);
		return preg_match("/^".$this->getCustomThemePrefix()."[0-9]{10}/", $subThemeId);
	}

	private static function getAssetPath($path, $basePath)
	{
		if (preg_match("~^(?:/|https?://)~", $path))
		{
			return $path;
		}

		return $basePath."/".$path;
	}

	private static function getThemeIdParts($themeId)
	{
		$parts = explode(":", $themeId);
		$baseThemeId = $parts[0];
		$subThemeId = isset($parts[1]) ? $parts[1] : null;

		return array($baseThemeId, $subThemeId);
	}

	/**
	 * @param bool $userId
	 *
	 * @return array
	 */
	private function getCustomThemesOptions($userId = false)
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return \CUserOptions::getOption(
			"intranet",
			$this->getCustomThemesOptionName(),
			[],
			$userId !== false ? $userId : $this->getUserId()
		);
	}

	private function setCustomThemesOptions($customThemes)
	{
		\CUserOptions::setOption(
			"intranet",
			$this->getCustomThemesOptionName(),
			$customThemes,
			false,
			$this->getUserId()
		);
	}

	private static function getSelectCacheParams()
	{
		return [
			'ttl' => 3600 * 24 * 365,
		];
	}
}
