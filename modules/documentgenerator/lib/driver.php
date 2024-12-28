<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\DocumentGenerator\DataProvider\Filterable;
use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Model\RegionTable;
use Bitrix\DocumentGenerator\Model\Role;
use Bitrix\DocumentGenerator\Model\RoleAccessTable;
use Bitrix\DocumentGenerator\Model\RoleTable;
use Bitrix\DocumentGenerator\Model\TemplateProviderTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\DocumentGenerator\Storage\BFile;
use Bitrix\DocumentGenerator\Storage\Disk;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Numerator\Numerator;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Web\Uri;

final class Driver
{
	public const MODULE_ID = 'documentgenerator';
	public const REST_MODULE_ID = 'rest';
	public const NUMERATOR_TYPE = 'DOCUMENT';

	protected $usersPermissions = [];
	protected $dataProviderManager;
	protected $documentClassName;
	protected $virtualDocumentClassName;
	protected $templateClassName;
	protected $userPermissionsClassName;
	protected $regionList;

	/** @var Bitrix24Manager */
	protected $bitrix24Manager = Bitrix24Manager::class;

	/** @var  Driver */
	private static $instance;

	private function __construct()
	{
		$this->initClasses();
	}

	private function __clone()
	{
	}

	/**
	 * Returns Singleton of Driver
	 * @return Driver
	 */
	public static function getInstance(): Driver
	{
		if (!isset(self::$instance))
		{
			self::$instance = new Driver;
		}

		return self::$instance;
	}

	public function getDataProviderManager(): DataProviderManager
	{
		return $this->dataProviderManager;
	}

	/**
	 * @return Document
	 */
	public function getDocumentClassName(): string
	{
		return $this->documentClassName;
	}

	/**
	 * @return Document
	 */
	public function getVirtualDocumentClassName(): string
	{
		return $this->virtualDocumentClassName;
	}

	/**
	 * @return Template
	 */
	public function getTemplateClassName(): string
	{
		return $this->templateClassName;
	}

	public function isEnabled(): bool
	{
		return (
			class_exists('\DOMDocument', true)
			&& class_exists('\ZipArchive', true)
		);
	}

	/**
	 * @return Storage
	 */
	public function getDefaultStorage(): Storage
	{
		$storageType = Option::get('documentgenerator', 'default_storage_type');
		if($storageType && is_a($storageType, Storage::class, true))
		{
			return new $storageType();
		}

		if(ModuleManager::isModuleInstalled('disk'))
		{
			return new Disk();
		}

		return new BFile();
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		global $USER;
		if(is_object($USER))
		{
			return (int) CurrentUser::get()->getId();
		}

		return 0;
	}

	protected function getDefaultNumeratorId(): int
	{
		return (int) Option::get(self::MODULE_ID, 'default_numerator', 0);
	}

	/**
	 * @param $id
	 * @return $this
	 */
	protected function setDefaultNumeratorId(int $id): Driver
	{
		Option::set(self::MODULE_ID, 'default_numerator', $id);

		return $this;
	}

	/**
	 * @param $source
	 * @return null|Numerator
	 */
	public function getDefaultNumerator($source = null): ?Numerator
	{
		$numeratorId = $this->getDefaultNumeratorId();
		$numerator = Numerator::load($numeratorId, $source);
		if(!$numerator)
		{
			Loc::loadLanguageFile(__FILE__);
			$numerator = Numerator::create();
			$numerator->setConfig([
				Numerator::getType() => [
					'name'     => Loc::getMessage('DOCUMENTGENERATOR_DEFAULT_NUMERATOR_TITLE'),
					'template' => '{NUMBER}',
					'type'     => static::NUMERATOR_TYPE,
				],
			]);
			$saveResult = $numerator->save();
			if($saveResult->isSuccess())
			{
				$numeratorId = $saveResult->getId();
				$this->setDefaultNumeratorId($numeratorId);
			}
			else
			{
				$numerator = null;
			}
		}

		return $numerator;
	}

	/**
	 * @param $providerClassName
	 * @param $moduleId
	 * @param $placeholder
	 * @return Uri|null
	 */
	public function getPlaceholdersListUri($providerClassName = null, $moduleId = null, $placeholder = null): ?Uri
	{
		if($providerClassName && !DataProviderManager::checkProviderName($providerClassName, $moduleId))
		{
			return null;
		}

		static $componentPath = null;
		if($componentPath === null)
		{
			$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.placeholders');
			if($componentPath)
			{
				$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
			}
		}
		if(!$componentPath)
		{
			return null;
		}
		$uri = new Uri($componentPath);
		if($moduleId)
		{
			$uri->addParams(['module' => $moduleId]);
		}
		if($providerClassName)
		{
			$uri->addParams(['provider' => mb_strtolower($providerClassName), 'apply_filter' => 'Y']);
		}
		if($placeholder)
		{
			$uri->addParams(['placeholder' => $placeholder, 'apply_filter' => 'Y']);
		}

		return $uri;
	}

	/**
	 * @return array
	 */
	public function getRegionsList(): array
	{
		if (is_array($this->regionList))
		{
			return $this->regionList;
		}

		Loc::loadLanguageFile(__FILE__);
		$regions = $this->getDefaultRegions();
		$userRegionsList = RegionTable::getList([
			"cache" => ["ttl" => 86400]
		]);
		while($userRegion = $userRegionsList->fetch())
		{
			$userRegion['CODE'] = $userRegion['ID'];
			$regions[$userRegion['ID']] = $userRegion;
		}

		$this->regionList = $regions;
		return $regions;
	}

	/**
	 * @return array
	 */
	public function getDefaultRegions(): array
	{
		return [
			'ru' => [
				'CODE' => 'ru',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_RU'),
				'LANGUAGE_ID' => 'ru',
			],
			'by' => [
				'CODE' => 'by',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_BY'),
				'LANGUAGE_ID' => 'ru',
			],
			'kz' => [
				'CODE' => 'kz',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_KZ'),
				'LANGUAGE_ID' => 'kz',
			],
			'ua' => [
				'CODE' => 'ua',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_UA'),
				'LANGUAGE_ID' => 'ua',
			],
			'de' => [
				'CODE' => 'de',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_DE'),
				'LANGUAGE_ID' => 'de',
			],
			'uk' => [
				'CODE' => 'uk',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_UK'),
				'LANGUAGE_ID' => 'en',
			],
			'br' => [
				'CODE' => 'br',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_BR'),
				'LANGUAGE_ID' => 'br',
			],
			'sp' => [
				'CODE' => 'sp',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_SP'),
				'LANGUAGE_ID' => 'la',
			],
			'mx' => [
				'CODE' => 'mx',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_MX'),
				'LANGUAGE_ID' => 'la',
			],
			'pl' => [
				'CODE' => 'pl',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_PL'),
				'LANGUAGE_ID' => 'pl',
			],
			'fr' => [
				'CODE' => 'fr',
				'TITLE' => Loc::getMessage('DOCUMENTGENERATOR_REGIONS_FR'),
				'LANGUAGE_ID' => 'fr',
			],
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getCurrentRegion(): array
	{
		$region = [];

		if($this->bitrix24Manager::isEnabled())
		{
			$region = $this->bitrix24Manager::getPortalZone();
			if(empty($region))
			{
				$region = $this->getRegionByLanguageId($this->bitrix24Manager::getDefaultLanguage());
			}
			else
			{
				if (isset($this->getRegionsList()[$region]))
				{
					$region = $this->getRegionsList()[$region];
				}
				else
				{
					$region = $this->getRegionByLanguageId($region);
				}
			}
		}
		if(empty($region))
		{
			$region = $this->getRegionByLanguageId(LANGUAGE_ID);
		}
		if(empty($region))
		{
			$region = $this->getRegionByLanguageId('en');
		}

		return $region;
	}

	/**
	 * @param $languageId
	 * @return array
	 */
	protected function getRegionByLanguageId(string $languageId): array
	{
		foreach($this->getRegionsList() as $region => $description)
		{
			if($description['LANGUAGE_ID'] === $languageId)
			{
				return $description;
			}
		}

		return [];
	}

	protected function initClasses(): void
	{
		$classes = $this->collectClasses();
		foreach($this->getDefaultClasses() as $name => $className)
		{
			if(
				isset($classes[$name])
				&& is_string($classes[$name])
				&& is_a($classes[$name], $className, true))
			{
				$className = $classes[$name];
			}

			if(strpos($name, 'ClassName'))
			{
				$this->$name = $className;
			}
			else
			{
				$this->$name = new $className();
			}
		}
	}

	protected function collectClasses(): array
	{
		$classes = [];

		$event = new Event(static::MODULE_ID, 'onDriverCollectClasses');
		EventManager::getInstance()->send($event);
		foreach($event->getResults() as $result)
		{
			if($result->getType() === EventResult::SUCCESS && is_array($result->getParameters()))
			{
				/** @noinspection SlowArrayOperationsInLoopInspection */
				$classes = array_merge($classes, $result->getParameters());
			}
		}

		return $classes;
	}

	protected function getDefaultClasses(): array
	{
		return [
			'documentClassName' => Document::class,
			'virtualDocumentClassName' => VirtualDocument::class,
			'templateClassName' => Template::class,
			'userPermissionsClassName' => UserPermissions::class,
			'dataProviderManager' => DataProviderManager::class,
		];
	}

	/**
	 * @param $userId
	 * @return UserPermissions
	 */
	public function getUserPermissions(int $userId = null): UserPermissions
	{
		if($userId === null)
		{
			$userId = $this->getUserId();
		}

		if(!isset($this->usersPermissions[$userId]))
		{
			$this->usersPermissions[$userId] = new $this->userPermissionsClassName($userId);
		}

		return $this->usersPermissions[$userId];
	}

	//region Agents
	/**
	 * @param bool $rewrite
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function installDefaultTemplatesForCurrentRegion()
	{
		$connection = Application::getConnection();
		if (
			!$connection->isTableExists(TemplateTable::getTableName())
			|| !$connection->isTableExists(FileTable::getTableName())
		)
		{
			return '\\Bitrix\\DocumentGenerator\\Driver::installDefaultTemplatesForCurrentRegion();';
		}

		$regionCode = self::getInstance()->getCurrentRegion()['CODE'];
		$lockName = self::MODULE_ID . '_install_default_templates_agent_region_' . $regionCode;
		if (!$connection->lock($lockName))
		{
			return '\\Bitrix\\DocumentGenerator\\Driver::installDefaultTemplatesForCurrentRegion();';
		}

		$controller = new Controller\Template();
		$result = $controller::getDefaultTemplateList(['REGION' => $regionCode]);
		if ($result->isSuccess())
		{
			foreach ($result->getData() as $template)
			{
				$isExists = isset($template['ID']) && $template['ID'] > 0;
				$isWasDeleted = isset($template['IS_DELETED']) && $template['IS_DELETED'] === 'Y';

				if (!$isExists || $isWasDeleted)
				{
					// if template already exists, it will be overwritten completely
					$controller->installDefaultTemplate($template);
				}
			}
		}

		$connection->unlock($lockName);

		return '';
	}

	final public static function updateBodyOfInstalledDefaultTemplates(): string
	{
		$connection = Application::getConnection();

		if (
			!$connection->isTableExists(TemplateTable::getTableName())
			|| !$connection->isTableExists(FileTable::getTableName())
		)
		{
			return '\\Bitrix\\DocumentGenerator\\Driver::updateBodyOfInstalledDefaultTemplates();';
		}

		$controller = new Controller\Template();
		$result = $controller::getDefaultTemplateList(); // check for templates of all regions
		if ($result->isSuccess())
		{
			foreach ($result->getData() as $template)
			{
				$isExists = isset($template['ID']) && $template['ID'] > 0;
				$isWasDeleted = isset($template['IS_DELETED']) && $template['IS_DELETED'] === 'Y';
				// we don't want to override user-uploaded template body. update body only if it wasn't modified by user
				$isBodyWasNotChangedSinceInstall = isset($template['IS_DEFAULT']) && $template['IS_DEFAULT'] === 'Y';

				if ($isExists && !$isWasDeleted && $isBodyWasNotChangedSinceInstall)
				{
					$controller->updateBodyOfDefaultTemplate($template);
				}
			}
		}

		return '';
	}

	public static function extendTemplateProviders($moduleId, $provider)
	{
		global $DB;
		if(!$DB->TableExists(TemplateProviderTable::getTableName()))
		{
			return '';
		}
		if(!Loader::includeModule($moduleId))
		{
			return '';
		}
		if(!is_a($provider, Filterable::class, true))
		{
			return '';
		}
		$provider = trim(mb_strtolower($provider), '\\');
		/** @var Filterable $provider */
		$extendedList = $provider::getExtendedList();
		$templateProviders = TemplateProviderTable::getList(['filter' => ['=PROVIDER' => mb_strtolower($provider)]])->fetchAll();
		foreach($templateProviders as $templateProvider)
		{
			TemplateProviderTable::delete($templateProvider);
			foreach($extendedList as $item)
			{
				TemplateProviderTable::add([
					'TEMPLATE_ID' => $templateProvider['TEMPLATE_ID'],
					'PROVIDER' => $item['PROVIDER'],
				]);
			}
		}

		return '';
	}

	public static function normalizeCurrentTemplates()
	{
		$templates = TemplateTable::getList(['select' => ['ID'], 'filter' => ['IS_DELETED' => 'N']]);
		while($template = $templates->fetch())
		{
			TemplateTable::normalizeBody($template['ID']);
		}
	}

	public static function deleteTemplatesWithEmptyModuleId()
	{
		global $DB;
		if(!$DB->TableExists(TemplateTable::getTableName()))
		{
			return '';
		}
		$templates = TemplateTable::getList(['select' => ['ID'], 'filter' => [
			'MODULE_ID' => 'delete',
		]]);
		while($template = $templates->fetch())
		{
			TemplateTable::delete($template['ID'], true);
		}

		return '';
	}

	public static function installDefaultRoles()
	{
		global $DB;
		if(!$DB->TableExists(RoleTable::getTableName()))
		{
			return '\\Bitrix\\DocumentGenerator\\Driver::installDefaultRoles();';
		}
		$rolesCount = RoleTable::getCount();
		if($rolesCount > 0)
		{
			return '';
		}

		$role = new Role();
		$role->setCode('ADMIN')->setName('ADMIN');
		$addResult = $role->save();
		if($addResult->isSuccess())
		{
			$role->setPermissions([
				UserPermissions::ENTITY_SETTINGS => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
				],
				UserPermissions::ENTITY_TEMPLATES => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
				],
				UserPermissions::ENTITY_DOCUMENTS => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
					UserPermissions::ACTION_VIEW => UserPermissions::PERMISSION_ANY,
				],
			]);
			RoleAccessTable::add(array(
				'ROLE_ID' => $role->getId(),
				'ACCESS_CODE' => 'G1'
			));
		}

		$role = new Role();
		$role->setCode('MANAGER')->setName('MANAGER');
		$addResult = $role->save();
		if($addResult->isSuccess())
		{
			$role->setPermissions([
				UserPermissions::ENTITY_SETTINGS => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
				],
				UserPermissions::ENTITY_TEMPLATES => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
				],
				UserPermissions::ENTITY_DOCUMENTS => [
					UserPermissions::ACTION_MODIFY => UserPermissions::PERMISSION_ANY,
					UserPermissions::ACTION_VIEW => UserPermissions::PERMISSION_ANY,
				],
			]);
			if(Loader::includeModule('intranet'))
			{
				$departmentTree = \CIntranetUtils::GetDeparmentsTree();
				$rootDepartment = (int)$departmentTree[0][0];

				if ($rootDepartment > 0)
				{
					RoleAccessTable::add(array(
						'ROLE_ID' => $role->getId(),
						'ACCESS_CODE' => 'DR'.$rootDepartment
					));
				}
			}
		}

		return "";
	}

	public static function moveTemplateFilesToFolder()
	{
		global $DB;
		if(!$DB->TableExists(TemplateTable::getTableName()))
		{
			return '';
		}
		if(!Loader::includeModule('disk'))
		{
			return '';
		}
		if(!$DB->TableExists(ObjectTable::getTableName()))
		{
			return '';
		}

		$folder = Disk::getTemplatesFolder();
		if(!$folder)
		{
			return '';
		}
		$files = \Bitrix\Disk\File::getModelList([
			'filter' => Query::filter()
				->whereNot('PARENT_ID', $folder->getId())
				->whereIn('ID',
					FileTable::query()
						->addSelect('STORAGE_WHERE')
						->where('STORAGE_TYPE', '=', 'Bitrix\\DocumentGenerator\\Storage\\Disk')
						->whereIn('ID',
							TemplateTable::query()->addSelect('FILE_ID')
						)
					)
		]);
		foreach($files as $file)
		{
			/** @var \Bitrix\Disk\File $file */
			$file->moveTo($folder, 0, true);
		}

		return '';
	}
	//endregion
	//region events
	/**
	 * @return array
	 */
	public static function onRestServiceBuildDescription(): array
	{
		return [
			static::MODULE_ID => [
				static::MODULE_ID.'.stub' => [],
			],
		];
	}

	/**
	 * @return array
	 */
	public static function onGetDependentModule(): array
	{
		return [
			'MODULE_ID' => static::MODULE_ID,
			'USE' => ['PUBLIC_SECTION'],
		];
	}
	//endregion
}
