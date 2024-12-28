<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\MobileApp\Mobile;

class Projects implements Tabable
{
	private const INITIAL_COMPONENT = 'tasks:tasks.project.list';
	private const MINIMAL_API_VERSION = 41;

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('tasksmobile'))
		{
			return false;
		}

		if (Loader::includeModule('socialnetwork'))
		{
			$arUserActiveFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $this->context->userId);
			$arSocNetFeaturesSettings = \CSocNetAllowed::getAllowedFeatures();
			$enabled = true;
			if (Loader::includeModule('intranet'))
			{
				$toolsManager = ToolsManager::getInstance();
				$enabled = $toolsManager->checkAvailabilityByToolId('projects') && $toolsManager->checkAvailabilityByToolId('tasks');
			}

			return
				$enabled &&
				array_key_exists('tasks', $arSocNetFeaturesSettings) &&
				array_key_exists('allowed', $arSocNetFeaturesSettings['tasks']) &&
				in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings['tasks']['allowed']) &&
				is_array($arUserActiveFeatures) &&
				in_array('tasks', $arUserActiveFeatures);
		}

		return false;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => 'projects_all_no_list',
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData(): array
	{
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'min_api_version' => self::MINIMAL_API_VERSION,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-projects.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => 'projects_all_no_list',
			],
		];
	}

	public function shouldShowInMenu(): bool
	{
		return false;
	}

	public function canBeRemoved(): bool
	{
		return true;
	}

	public function defaultSortValue(): int
	{
		return 500;
	}

	public function canChangeSort(): bool
	{
		return true;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_PROJECTS');
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_PROJECTS_SHORT');
	}

	public function getId(): string
	{
		return 'projects';
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => 'tasks.project.list',
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'tasks.list',
				'settings' => [
					'objectName' => 'list',
					'useSearch' => true,
					'useLargeTitleMode' => true,
					'emptyListMode' => true,
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'tasks.project.list',
				'SITE_ID' => $this->context->siteId,
				'SITE_DIR' => $this->context->siteDir,
				'USER_ID' => $this->context->userId,
				'PROJECT_NEWS_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectNewsPathTemplate([
					'siteDir' => $this->context->siteDir,
				]),
				'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectCalendarWebPathTemplate([
					'siteId' => $this->context->siteId,
					'siteDir' => $this->context->siteDir,
				]),
				'MIN_SEARCH_SIZE' => Filter\Helper::getMinTokenSize(),
			],
		];
	}

	public function getIconId(): string
	{
		return Mobile::getApiVersion() < 56 ? $this->getId() : 'folder_with_card';
	}
}
