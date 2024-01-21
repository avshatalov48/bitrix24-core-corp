<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Intranet;
use Bitrix\Bitrix24;

class PortalSettings extends AbstractSettings
{
	public const TYPE = 'portal';

	private bool $isBitrix24;
	private Intranet\PortalSettings $portalSettings;
	private const LOGO_PRESETS = ['logo' => [222, 55], '2x' => [444, 110]];

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = Main\Loader::includeModule('bitrix24');
		$this->portalSettings = ($this->isBitrix24 ? Bitrix24\PortalSettings::getInstance() :
			Intranet\PortalSettings::getInstance());
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		if (isset($this->data['title']) && !$this->portalSettings->canCurrentUserEditTitle())
		{
			$errors->add([new Error(
				'Access denied.', 
				403,
				[
					'page' => $this->getType(),
					'field' => 'title',
				]
			)]);
		}

		//region Logo
		if (isset($this->data['logo']))
		{
			if (!$this->portalSettings->canCurrentUserEditLogo())
			{
				$errors->add([new Error(
					'Access denied.',
					403,
					[
						'page' => $this->getType(),
						'field' => 'logo',
					])
				]);
			}
			else if ($files = Main\Context::getCurrent()->getRequest()->getFile('portal'))
			{
				$logo = array_combine(array_keys($files), array_column($files, 'logo_file'));
				if (!empty($logo))
				{
					if (($error = \CFile::CheckFile($logo, 0, 'image/png', 'png'))
						|| ($error = \CFile::CheckImageFile($logo, 0, self::LOGO_PRESETS['2x'][0], self::LOGO_PRESETS['2x'][1]))
					)
					{
						$errors->setError(new Error($error, 400,
							[
								'page' => $this->getType(),
								'field' => 'logo',
							])
						);
					}
				}
			}
		}
		//endregion

		if (!empty($this->data['subDomainName'])
			&& $this->isBitrix24
			&& $this->data['subDomainName'] !== $this->portalSettings->getDomain()->getSubDomain()
		)
		{
			$domainSettings = $this->portalSettings->getDomain();
			if (!$domainSettings->canCurrentUserEdit())
			{
				$errors->add([new Error(
					Main\Localization\Loc::getMessage('SETTINGS_RENAMING_ACCESS_DENIED'),
					403,
					[
						'page' => $this->getType(),
						'field' => 'subDomainName',
					]
				)]);
			}
			else if (!$domainSettings->isRenameable())
			{
				$errors->add([new Error(
					Main\Localization\Loc::getMessage('SETTINGS_RENAMING_IS_NO_LONGER_ALLOWED'),
					405,
					[
						'page' => $this->getType(),
						'field' => 'subDomainName',
					]
				)]);
			}
			else
			{
				foreach ($domainSettings
					->validateSubdomainName($this->data['subDomainName'])
					->getErrors() as $error
				)
				{
					$errors->setError(
						new Error(
							$error->getMessage(),
							$error->getCode(),
							[
								'page' => $this->getType(),
								'field' => 'subDomainName',
							]
						)
					);
				}
			}
		}

		if (isset($this->data['themeId']))
		{
			$themePicker = $this->getThemePicker();
			$theme = $themePicker->getDefaultTheme() ?? [];
			if ($this->data['themeId'] !== $theme['id'] && $themePicker::canSetDefaultTheme() !== true)
			{
				$errors->add([new Error('Access denied.', [
					'page' => $this->getType(),
					'field' => 'themeId',
				])]);
			}
		}

		return $errors;
	}

	public function save(): Result
	{
		$result = new Result();

		Intranet\Composite\CacheProvider::deleteUserCache();

		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('bitrix24_left_menu');
		}

		if (isset($this->data['name']))
		{
			$this->portalSettings->setName($this->data['name']);
		}

		if (isset($this->data['title']))
		{
			$this->portalSettings->setTitle($this->data['title']);
		}

		if (isset($this->data['logo']))
		{
			if ($this->data['logo'] === 'remove')
			{
				$this->portalSettings->removeLogo();
			}
			else if ($files = Main\Context::getCurrent()->getRequest()->getFile('portal'))
			{
				$file = array_combine(array_keys($files), array_column($files, 'logo_file'));
				$result = $this->saveLogo($file);
				if ($result->isSuccess())
				{
					$this->portalSettings->setLogo(...array_values($result->getId()));
				}
			}
		}

		if (isset($this->data['logo24']))
		{
			$this->portalSettings->setLogo24($this->data['logo24']);
		}

		if (!empty($this->data['subDomainName'])
			&& $this->isBitrix24
			&& $this->data['subDomainName'] !== $this->portalSettings->getDomain()->getSubDomain()
		)
		{
			$renameResult = $this->portalSettings->getDomain()->rename(
				$this->data['subDomainName']
			);
			if (!$renameResult->isSuccess())
			{
				$result->addErrors([new Error(
					Loc::getMessage('SETTINGS_RENAMING_INTERNAL_SERVER_ERROR'),
					500,
					[
						'page' => $this->getType(),
						'field' => 'subDomainName',
					])
				]);
			}
		}

		if (isset($this->data['themeId']))
		{
			$themePicker = $this->getThemePicker();
			$theme = $themePicker->getDefaultTheme() ?? [];

			if ($this->data['themeId'] !== $theme['id'] && $themePicker::canSetDefaultTheme())
			{
				if ($themePicker->setCurrentThemeId($this->data['themeId']))
				{
					$themePicker->setDefaultTheme($this->data['themeId']);
				}
			}
		}

		Intranet\Composite\CacheProvider::deleteUserCache();


		return $result;
	}

	private function saveLogo(array $file): Main\ORM\Data\AddResult
	{
		$result = new Main\ORM\Data\AddResult();

		$file['MODULE_ID'] = 'bitrix24';
		$ids = [];
		foreach (self::LOGO_PRESETS as $presetId => [$width, $height])
		{
			$saveFile = $file;
			$saveFile['name'] = 'logo_' . Main\Security\Random::getString(10) . '.png';
			$enough = true;

			if (\CFile::CheckImageFile($saveFile, 0, $width, $height) !== null)
			{
				$enough = false;
				\CFile::ResizeImage($saveFile, ['width' => $width, 'height' => $height]);
			}

			if (!($id = (int)\CFile::SaveFile($saveFile, 'bitrix24')))
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->GetException()?->GetString()));
				break;
			}
			else
			{
				$ids[$presetId] = $id;
			}

			if ($enough)
			{
				break;
			}
		}

		if ($result->isSuccess())
		{
			$result->setId($ids);
		}
		else
		{
			foreach ($ids as $id)
			{
				\CFile::Delete($id);
			}
		}

		return $result;
	}

	private function getThemePicker(): Intranet\Integration\Templates\Bitrix24\ThemePicker
	{
		static $themePicker;
		if (!isset($themePicker))
		{
			$themePicker = new Intranet\Integration\Templates\Bitrix24\ThemePicker(defined('SITE_TEMPLATE_ID') ? SITE_TEMPLATE_ID : 'bitrix24');
		}

		return $themePicker;
	}

	public function get(): SettingsInterface
	{
		$data = [];

		$data['portalSettings'] = [
			'name' => $this->portalSettings->getName(),
			'canUserEditName' => $this->portalSettings->canCurrentUserEditName(),
			'title' => $this->portalSettings->getTitle(),
			'canUserEditTitle' => $this->portalSettings->canCurrentUserEditTitle(),
			'logo24' => $this->portalSettings->getLogo24(),
			'canUserEditLogo24' => $this->portalSettings->canCurrentUserEditLogo24(),
			'logo' => $this->portalSettings->getLogo(),
			'canUserEditLogo' => $this->portalSettings->canCurrentUserEditLogo(),
		];

		if ($this->isBitrix24)
		{
			$domain = Bitrix24\Domain::getCurrent();
			$data['portalDomainSettings'] = [
				'hostname' => $domain->getValue(),
				'subDomainName' => $domain->getSubDomain(),
				'mainDomainName' => $domain->getBitrixDomainName(),
				'isRenameable' => $domain->isRenameable(),
				'isCustomizable' => $domain->isCustomizable(),
				'canUserEdit' => $domain->canCurrentUserEdit(),
				'occupiedDomains' => $domain::RESERVED_SUBDOMAIN_NAME,
			];
		}

		$themePicker = $this->getThemePicker();
		$theme = $themePicker->getDefaultTheme();
		$theme = $theme ?: [];
		$data['portalThemeSettings'] = array(
			"templateId" => $themePicker->getTemplateId(),
			"siteId" => $themePicker->getSiteId(),

			'themeId' => $themePicker->getDefaultThemeId(),
			'theme' => $theme,
			'themes' => $themePicker->getList(),
			'baseThemes' => $themePicker->getBaseThemes(),

			//"maxUploadSize" => static::getMaxUploadSize(),
			"ajaxHandlerPath" => $themePicker->getAjaxHandlerPath(),

			"allowSetDefaultTheme" => $themePicker::canSetDefaultTheme(),
			"isVideo" => isset($theme["video"]),

			'entityType' => $themePicker->getEntityType(),
			'entityId' => $themePicker->getEntityId(),
			'behaviour' => $themePicker->getBehaviour(),

			'canUserEdit' => $themePicker::canSetDefaultTheme(),
		);

		return new static($data);
	}
}