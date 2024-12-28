<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\DiskMobile\AirDiskFeature;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Disk\Integration\Bitrix24Manager;

final class Disk implements Tabable
{
	/**
	 * @var \Bitrix\Mobile\Context $context
	 */
	private $context;

	public function isAvailable()
	{
		return Feature::isEnabled(AirDiskFeature::class);
	}

	public function getData()
	{
		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => $this->getId(),
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData()
	{
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'color' => '#3CD162',
			'imageName' => $this->getIconId(),
			'imageUrl' => 'favorite/icon-disk.png',
			'params' => [
				'showHighlighted' => true,
				'highlightWithCounter' => true,
				'counter' => 'menu_disk_tabs',
				'onclick' => (
					$this->setUserVisitedDiskTabs() .
					Utils::getComponentJSCode($this->getComponentParams())
				),
			],
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => 'disk.tabs',
			'scriptPath' => Manager::getComponentPath('disk:disk.tabs'),
			'rootWidget' => [
				'name' => 'tabs',
				'settings' => [
					'objectName' => 'tabs',
					'titleParams' => [
						'text' => $this->getTitle(),
						'useLargeTitleMode' => true,
					],
					'tabs' => [
						'items' => array_values(array_filter([
							$this->getRecentFilesTab(),
							$this->getMyFilesTab(),
							$this->getSharedFilesTab(),
						])),
					],
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'disk.tabs',
				'USER_ID' => $this->context->userId,
				'SITE_ID' => $this->context->siteId,
				'IS_EXTRANET' => $this->context->extranet,
			],
		];
	}

	private function setUserVisitedDiskTabs()
	{
		return <<<JS
			(async () => {
				const { Tourist } = await requireLazy('tourist');
				await Tourist.ready();
				if (Tourist.rememberFirstTime('visited_disk_tabs'))
				{
 					BX.postComponentEvent('onSetUserCounters', [
						{
							[String(env.siteId)]: { menu_disk_tabs: 0 }
	 					}
					]);
  				}
			})();
JS;

	}

	private function getRecentFilesTab(): ?array
	{
		return [
			'id' => 'disk.tabs.recent',
			'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_RECENT'),
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_RECENT'),
				'componentCode' => 'disk.tabs.recent',
				'scriptPath' => Manager::getComponentPath('disk:disk.tabs.recent'),
				'rootWidget' => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layout',
						'titleParams' => [
							'text' => $this->getTitle(),
							'useLargeTitleMode' => true,
						],
						'useSearch' => true,
						'doNotHideSearchResult' => true,
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'disk.tabs.recent',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'IS_EXTRANET' => $this->context->extranet,
				],
			],
		];
	}

	private function getMyFilesTab(): ?array
	{
		return [
			'id' => 'disk.tabs.my',
			'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_MY_FILES'),
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_MY_FILES'),
				'componentCode' => 'disk.tabs.my',
				'scriptPath' => Manager::getComponentPath('disk:disk.tabs.my'),
				'rootWidget' => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layout',
						'titleParams' => [
							'text' => $this->getTitle(),
							'useLargeTitleMode' => true,
						],
						'useSearch' => true,
						'doNotHideSearchResult' => true,
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'disk.tabs.my',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'IS_EXTRANET' => $this->context->extranet,
				],
			],
		];
	}

	private function getSharedFilesTab(): ?array
	{
		if ($this->context->extranet)
		{
			return null;
		}

		$isCommonStorageRestricted = !Bitrix24Manager::isFeatureEnabled('disk_common_storage');

		return [
			'id' => 'disk.tabs.shared',
			'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_COMPANY_FILES'),
			'icon' => ($isCommonStorageRestricted ? 'lock' : null),
			'selectable' => !$isCommonStorageRestricted,
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_DISK_NAVIGATION_TAB_COMPANY_FILES'),
				'componentCode' => 'disk.tabs.shared',
				'scriptPath' => Manager::getComponentPath('disk:disk.tabs.shared'),
				'rootWidget' => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layout',
						'titleParams' => [
							'text' => $this->getTitle(),
							'useLargeTitleMode' => true,
						],
						'useSearch' => true,
						'doNotHideSearchResult' => true,
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'disk.tabs.shared',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'IS_EXTRANET' => $this->context->extranet,
					'OWNER_ID' =>  'shared_files_' . $this->context->siteId,
					'ENTITY_TYPE' => 'common',
				],
			],
		];
	}

	public function shouldShowInMenu()
	{
		return $this->isAvailable();
	}

	public function canBeRemoved()
	{
		return true;
	}

	public function defaultSortValue()
	{
		return 100;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage('TAB_NAME_DISK');
	}

	public function getShortTitle()
	{
		return Loc::getMessage('TAB_NAME_DISK');
	}

	public function getId()
	{
		return 'disk';
	}

	public function setContext($context)
	{
		$this->context = $context;
	}

	public function getIconId(): string
	{
		return 'file';
	}
}
