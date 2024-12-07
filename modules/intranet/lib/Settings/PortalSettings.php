<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\Settings\Search\SearchEngine;
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
	private Intranet\Service\PortalSettings $portalSettings;
	private Intranet\Portal\PortalLogo $portalLogo;

	private const EXAMPLE_DNS = [
		'ns-1277.awsdns-31.org', 'ns-310.awsdns-38.com', 'ns-581.awsdns-08.net', 'ns-1613.awsdns-09.co.uk'
	];

	private const EXAMPLE_DNS_RU = [
		'a.ns.selectel.ru', 'b.ns.selectel.ru', 'c.ns.selectel.ru', 'd.ns.selectel.ru'
	];

	public function __construct(array $data = [])
	{
		parent::__construct($data);
		$this->isBitrix24 = Main\Loader::includeModule('bitrix24');
		$this->portalSettings = $this->isBitrix24
			? Bitrix24\Service\PortalSettings::getInstance()
			: Intranet\Service\PortalSettings::getInstance()
		;
		$this->portalLogo = new Intranet\Portal\PortalLogo($this->portalSettings);
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();

		if (isset($this->data['title']) && !$this->portalSettings->titleSettings()->canCurrentUserEdit())
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
			$logoSettings = $this->portalSettings->logoSettings();

			if (!$logoSettings->canCurrentUserEdit())
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
					if (
						($error = \CFile::CheckFile($logo, 0, 'image/png', 'png'))
						|| ($error = \CFile::CheckImageFile($logo, 0, Intranet\Portal\PortalLogo::LOGO_PRESETS['2x'][0], Intranet\Portal\PortalLogo::LOGO_PRESETS['2x'][1]))
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
			&& $this->data['subDomainName'] !== $this->portalSettings->domainSettings()->getSubDomain()
		)
		{
			$domainSettings = $this->portalSettings->domainSettings();

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
			$this->portalSettings->nameSettings()->setName($this->data['name']);
		}

		if (isset($this->data['title']))
		{
			$this->portalSettings->titleSettings()->setTitle($this->data['title']);
		}

		if (isset($this->data['logo']))
		{
			if ($this->data['logo'] === 'remove')
			{
				$this->portalLogo->removeLogo();
			}
			else if ($files = Main\Context::getCurrent()->getRequest()->getFile('portal'))
			{
				$this->portalLogo->saveLogo($files);
			}
		}

		if (isset($this->data['logo24']))
		{
			$this->portalSettings->logo24Settings()->setLogo24($this->data['logo24']);
		}

		if (!empty($this->data['subDomainName'])
			&& $this->isBitrix24
			&& $this->data['subDomainName'] !== $this->portalSettings->domainSettings()->getSubDomain()
		)
		{
			$renameResult = $this->portalSettings->domainSettings()->rename(
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

		$data['sectionCompanyTitle'] = new Intranet\Settings\Controls\Section(
			'settings-portal-section-company_title',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE'),
			'ui-icon-set --pencil-draw',
		);

		$data['sectionSiteTheme'] = new Intranet\Settings\Controls\Section(
			'settings-portal-section-theme',
			Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME'),
			'ui-icon-set --picture',
			false,
		);

		$data['portalSettings'] = [
			'name' => $this->portalSettings->nameSettings()->getName(),
			'canUserEditName' => $this->portalSettings->nameSettings()->canCurrentUserEdit(),
			'title' => $this->portalSettings->titleSettings()->getTitle(),
			'canUserEditTitle' =>  $this->portalSettings->titleSettings()->canCurrentUserEdit(),
			'logo24' => $this->portalSettings->logo24Settings()->getLogo24(),
			'canUserEditLogo24' => $this->portalSettings->logo24Settings()->canCurrentUserEdit(),
			'logo' => $this->portalLogo->getLogo(),
			'canUserEditLogo' => $this->portalSettings->logoSettings()->canCurrentUserEdit(),
		];

		$data['portalSettingsLabels'] = [
			'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_INPUT_LABEL'),
			'logo24' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_LOGO24'),
			'name' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_NAME'),
			'logo' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE1'),
		];

		$data['tabCompanyTitle'] = new Intranet\Settings\Controls\Tab(
			'settings-portal-tab-company_title',
			[
				'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE')
			]
		);
		$data['tabCompanyLogo'] = new Intranet\Settings\Controls\Tab(
			'settings-portal-tab-company_logo',
			[
				'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO')
			],
			restricted: ($this->portalSettings->logo24Settings()->canCurrentUserEdit() === false),
			bannerCode: 'limit_admin_logo',
		);

		if ($this->isBitrix24)
		{
			$domain = $this->portalSettings->domainSettings();
			$data['portalDomainSettings'] = [
				'label' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN'),
				'hostname' => $domain->getValue(),
				'subDomainName' => $domain->getSubDomain(),
				'mainDomainName' => $domain->getBitrixDomainName(),
				'isRenameable' => $domain->isRenameable(),
				'isCustomizable' => $domain->isCustomizable(),
				'canUserEdit' => $domain->canCurrentUserEdit(),
				'occupiedDomains' => $domain::RESERVED_SUBDOMAIN_NAME,
				'exampleDns' => $this->getExampleDns(),
			];

			$data['sectionSiteDomain'] = new Intranet\Settings\Controls\Section(
				'settings-portal-section-domain',
				Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN'),
				'ui-icon-set --globe',
				false,
			);

			$data['tabDomainPrefix'] = new Intranet\Settings\Controls\Tab(
				'settings-portal-tab-domain_prefix',
				[
					'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME1')
				]
			);

			$data['tabDomain'] = new Intranet\Settings\Controls\Tab(
				'settings-portal-tab-domain',
				[
					'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME2')
				],
				restricted: $domain->isCustomizable() === false,
				bannerCode: 'limit_office_own_domain',
			);
		}

		$themePicker = $this->getThemePicker();
		$theme = $themePicker->getDefaultTheme();
		$theme = $theme ?: [];
		$data['portalThemeSettings'] = array(
			'label' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME'),
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

	public function find(string $query): array
	{
		$index = [];
		if ($this->isBitrix24)
		{
			$index['settings-portal-section-domain'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_DOMAIN');
			$index['settings-portal-tab-domain_prefix'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME1');
			$index['settings-portal-tab-domain'] = Loc::getMessage('INTRANET_SETTINGS_SECTION_DOMAIN_NAME2');
			$index['subDomainName'] = Loc::getMessage('SETTINGS_RENAMING_IS_NO_LONGER_ALLOWED');
		}

		$searchEngine = SearchEngine::initWithDefaultFormatter($index + [
			//sections
			'settings-portal-section-company_title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE'),
			'settings-portal-section-theme' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_PORTAL_THEME'),
			//tabs
			'settings-portal-tab-company_title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE'),
			'settings-portal-tab-company_logo' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO'),
			//fields
			'title' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_TITLE_INPUT_LABEL'),
			'logo24' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_LOGO24'),
			'name' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TITLE_SITE_NAME'),
			'logo' => Loc::getMessage('INTRANET_SETTINGS_SECTION_TAB_TITLE_WIDGET_LOGO_TITLE1'),
		]);

		return $searchEngine->find($query);
	}

	private function getExampleDns(): array
	{
		$license = \Bitrix\Main\Application::getInstance()->getLicense();

		if (in_array($license->getRegion(), ['ru', 'by']))
		{
			return self::EXAMPLE_DNS_RU;
		}

		return self::EXAMPLE_DNS;
	}
}