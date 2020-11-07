<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Bitrix\Rpa;

use Bitrix\Intranet\Util;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Rpa\UrlManager\ParseResult;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;

class UrlManager
{
	protected const GET_COMPONENT_NAME = 'c';
	protected const GET_COMPONENT_PARAMETERS = 'cp';
	protected const GET_COMPONENT_TEMPLATE = 'cpt';
	protected const OPTION_DEFAULT_ROOT = 'rpa_default_root';
	protected const OPTION_NAME_TEMPLATES = 'rpa_url_templates';

	protected const USER_OPTION_ITEMS_LIST_VIEW = 'rpa_items_list_view';
	protected const USER_OPTION_TYPES_LIST_VIEW = 'rpa_types_list_view';
	
	public const TYPES_LIST_VIEW_TILES = 'tiles';
	public const TYPES_LIST_VIEW_GRID = 'grid';
	public const ITEMS_LIST_VIEW_KANBAN = 'kanban';
	public const ITEMS_LIST_VIEW_LIST = 'list';

	protected $siteId;
	protected $root;
	protected $isSefMode;
	protected $siteData;
	protected $userId;

	public function __construct()
	{
		$this->root = $this->getDefaultRoot();
		$this->isSefMode = true;
		$this->siteId = Application::getInstance()->getContext()->getSite();
	}

	public function setUserId(int $userId): UrlManager
	{
		$this->userId = $userId;

		return $this;
	}

	public function getUserId(): int
	{
		if($this->userId > 0)
		{
			return $this->userId;
		}

		return Driver::getInstance()->getUserId();
	}

	public function setRoot(string $root): UrlManager
	{
		$this->root = $root;

		return $this;
	}

	public function getRoot(): string
	{
		return $this->root;
	}

	public function setSefMode(bool $isSefMode): UrlManager
	{
		$this->isSefMode = $isSefMode;

		return $this;
	}

	public function isSefMode(): bool
	{
		return $this->isSefMode;
	}

	public function setSiteId(string $siteId): UrlManager
	{
		$this->siteId = $siteId;
		return $this;
	}

	public function getSiteId(): ?string
	{
		return $this->siteId;
	}

	protected function getDefaultRoot(): string
	{
		return Option::get(Driver::MODULE_ID, static::OPTION_DEFAULT_ROOT, Driver::MODULE_ID);
	}

	protected function getSiteData(): ?array
	{
		if($this->siteData === null || (is_array($this->siteData) && $this->siteData['LID'] !== $this->siteId))
		{
			$data = $this->siteId ? SiteTable::getById($this->siteId)->fetch() : null;
			if(!$data)
			{
				$data = null;
			}

			$this->siteData = $data;
		}

		return $this->siteData;
	}

	protected function getSiteFolder(): string
	{
		$dir = '/';

		$data = $this->getSiteData();
		if(is_array($data) && isset($data['DIR']) && !empty($data['DIR']))
		{
			$dir = $data['DIR'];
		}

		return $dir;
	}

	public function getDefaultUrlTemplates(): array
	{
		return [
			'bitrix:rpa.panel' => '/',
			'bitrix:rpa.type.detail' => 'type/#id#/',
			'bitrix:rpa.stage.detail' => 'stage/#id#/',
			'bitrix:rpa.kanban' => 'kanban/#typeId#/',
			'bitrix:rpa.item.list' => 'list/#typeId#/',
			'bitrix:rpa.item.detail' => 'item/#typeId#/#id#/',
			'bitrix:rpa.automation' => 'automation/#typeId#/',
			'bitrix:rpa.automation.addrobot' => 'automation/#typeId#/addrobot/',
			'bitrix:rpa.automation.editrobot' => 'automation/#typeId#/editrobot/',
			'bitrix:rpa.task.list' => 'tasks/',
			'bitrix:rpa.task' => 'task/#typeId#/#elementId#/',
			'bitrix:rpa.stage.list' => 'stages/#typeId#/',
			'bitrix:rpa.feedback' => 'feedback/',
		];
	}

	public function saveCustomUrlTemplates(array $templates): void
	{
		Option::set(Driver::MODULE_ID, static::OPTION_NAME_TEMPLATES, Json::encode($templates));
	}

	public function getCustomUrlTemplates(): array
	{
		$result = [];

		$optionTemplates = Option::get(Driver::MODULE_ID, static::OPTION_NAME_TEMPLATES);
		if(is_string($optionTemplates) && !empty($optionTemplates))
		{
			try
			{
				$result = Json::decode($optionTemplates);
			}
			catch(ArgumentException $e)
			{
				$result = [];
			}
		}

		return $result;
	}

	protected function getUrlTemplates(): array
	{
		return \CComponentEngine::makeComponentUrlTemplates($this->getDefaultUrlTemplates(), $this->getCustomUrlTemplates());
	}

	public function getPreparedTemplates(): array
	{
		$result = [];

		$templates = $this->getUrlTemplates();
		foreach($templates as $name => $template)
		{
			$result[$name] = $this->getFullPath($template);
		}
		$result['fieldsList'] = $this->getFieldsListTemplateUrl();
		$result['fieldDetail'] = $this->getFieldsDetailTemplateUrl();

		return $result;
	}

	public function parseRequest(HttpRequest $httpRequest = null): ParseResult
	{
		if($this->isSefMode())
		{
			return $this->parseRequestInSefMode($httpRequest);
		}

		return $this->parseRequestParameters($httpRequest);
	}

	protected function parseRequestInSefMode(HttpRequest $httpRequest = null): ParseResult
	{
		if($httpRequest)
		{
			$requestUrl = $httpRequest->getRequestUri();
		}
		else
		{
			$requestUrl = false;
		}
		$componentParameters = [];
		$componentName = \CComponentEngine::parseComponentPath($this->getRoot(), $this->getUrlTemplates(), $componentParameters, $requestUrl);
		if(!is_string($componentName))
		{
			$result = new ParseResult();
		}
		else
		{
			$result = new ParseResult($componentName, $componentParameters);
		}

		return $result;
	}

	protected function parseRequestParameters(HttpRequest $httpRequest = null): ParseResult
	{
		if(!$httpRequest)
		{
			$httpRequest = Application::getInstance()->getContext()->getRequest();
		}

		$componentName = $httpRequest->get(static::GET_COMPONENT_NAME);
		if(!array_key_exists($componentName, $this->getUrlTemplates()) || mb_strpos($httpRequest->getRequestUri(), $this->getFullPath()) !== 0)
		{
			$componentName = null;
		}

		return new ParseResult($componentName, $httpRequest->get(static::GET_COMPONENT_PARAMETERS), $httpRequest->get(static::GET_COMPONENT_TEMPLATE));
	}

	public function getPanelUrl(): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.panel');
	}

	public function getKanbanUrl(int $typeId): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.kanban', [
			'typeId' => $typeId
		]);
	}

	public function getAutomationUrl(int $typeId = null): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.automation', [
			'typeId' => $typeId
		]);
	}

	public function getAutomationEditRobotUrl(int $typeId = null): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.automation.editrobot', [
			'typeId' => $typeId
		]);
	}

	public function getTypeDetailUrl(int $typeId = null): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.type.detail', [
			'id' => $typeId
		]);
	}

	public function getStageDetailUrl(int $stageId = null): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.stage.detail', [
			'id' => $stageId
		]);
	}

	public function getItemDetailUrl(int $typeId, int $itemId = null, int $stageId = null): ?Uri
	{
		$getParameters = [];
		if($stageId > 0)
		{
			$getParameters['stageId'] = $stageId;
		}

		return $this->getUrlForTemplate('bitrix:rpa.item.detail', [
			'typeId' => $typeId,
			'id' => $itemId,
		], $getParameters);
	}

	public function getTasksUrl(): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.task.list');
	}

	public function getTaskUrl(int $typeId, int $elementId): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.task', [
			'typeId' => $typeId,
			'elementId' => $elementId,
		]);
	}

	public function getTaskIdUrl($elementId): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.task', [
			'typeId' => 'id',
			'elementId' => $elementId,
		]);
	}

	public function getFeedbackUrl(string $context = null): ?Uri
	{
		$uri = $this->getUrlForTemplate('bitrix:rpa.feedback');
		if ($uri && $context)
		{
			$uri->addParams(['context' => $context]);
		}

		return $uri;
	}

	public function getItemsListUrl(int $typeId): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.item.list', [
			'typeId' => $typeId,
		]);
	}

	public function getStageListUrl(int $typeId): Uri
	{
		return $this->getUrlForTemplate('bitrix:rpa.stage.list', [
			'typeId' => $typeId,
		]);
	}

	public function getUserPersonalUrlTemplate(): string
	{
		return Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $this->getSiteId());
	}

	public function getUserPersonalUrl(int $userId): Uri
	{
		$template = $this->getUserPersonalUrlTemplate();

		return new Uri(str_replace('#USER_ID#', $userId, $template));
	}

	public function getFieldsListTemplateUrl(): ?string
	{
		if(Loader::includeModule('intranet'))
		{
			$entityId = Driver::getInstance()->getFactory()->getUserFieldEntityPrefix() . '#typeId#';
			$uri = Util::getUserFieldListConfigUrl(Driver::MODULE_ID, $entityId);

			return urldecode($uri->getLocator());
		}

		return null;
	}

	public function getFieldsDetailTemplateUrl(): ?string
	{
		if(Loader::includeModule('intranet'))
		{
			$entityId = Driver::getInstance()->getFactory()->getUserFieldEntityPrefix() . '#typeId#';
			$uri = Util::getUserFieldDetailConfigUrl(Driver::MODULE_ID, $entityId);
			$uri->addParams(['fieldId' => '#fieldId#']);

			return urldecode($uri->getLocator());
		}

		return null;
	}

	public function getTypeFieldsListUrl(int $typeId): ?Uri
	{
		$type = Driver::getInstance()->getType($typeId);
		if($type)
		{
			$entityId = $type->getItemUserFieldsEntityId();
			if($entityId && Loader::includeModule('intranet'))
			{
				return Util::getUserFieldListConfigUrl(Driver::MODULE_ID, $entityId);
			}
		}

		return null;
	}

	public function getFieldDetailUrl(int $typeId, int $fieldId): ?Uri
	{
		$type = Driver::getInstance()->getType($typeId);
		if($type)
		{
			$entityId = $type->getItemUserFieldsEntityId();
			if($entityId && Loader::includeModule('intranet'))
			{
				return Util::getUserFieldDetailConfigUrl(Driver::MODULE_ID, $entityId, $fieldId);
			}
		}

		return null;
	}

	public function getFileUrlTemplate(int $typeId, int $itemId, string $fieldName): string
	{
		$uri = \Bitrix\Main\Engine\UrlManager::getInstance()->create('rpa.item.getfile', [
			'typeId' => $typeId,
			'id' => $itemId,
			'fieldName' => $fieldName,
		]);

		return ($uri->getUri() . '&file_id=#file_id#');
	}

	public function getFileUrl(int $typeId, int $itemId, string $fieldName, int $fileId): Uri
	{
		return new ContentUri(\Bitrix\Main\Engine\UrlManager::getInstance()->create('rpa.item.getfile', [
			'typeId' => $typeId,
			'id' => $itemId,
			'fieldName' => $fieldName,
			'file_id' => $fileId,
		], true)->getUri());
	}

	//region user urls
	public function getItemListViews(): array
	{
		return [
			static::ITEMS_LIST_VIEW_KANBAN => [
				'template' => 'bitrix:rpa.kanban',
			],
			static::ITEMS_LIST_VIEW_LIST => [
				'template' => 'bitrix:rpa.item.list',
			],
		];
	}

	/**
	 * Returns url to the items list in remembered view for this user
	 *
	 * @param int $typeId
	 * @return Uri|null
	 */
	public function getUserItemsUrl(int $typeId): ?Uri
	{
		$views = $this->getItemListViews();
		$view = $this->getUserItemListView($typeId);
		return $this->getUserUrl($views, $view, [
			'typeId' => $typeId,
		]);
	}

	public function getUserItemListView(int $typeId): ?string
	{
		return $this->getUserView(static::USER_OPTION_ITEMS_LIST_VIEW . '_' . $typeId);
	}

	public function setUserItemListView(int $typeId, string $view): UrlManager
	{
		return $this->setUserView(static::USER_OPTION_ITEMS_LIST_VIEW . '_' . $typeId, $view);
	}

	public function getTypeListViews(): array
	{
		return [
			static::TYPES_LIST_VIEW_TILES => [
				'template' => 'bitrix:rpa.panel',
			],
			static::TYPES_LIST_VIEW_GRID => [
				'template' => 'bitrix:rpa.panel',
				'getParameters' => [
					'view' => static::TYPES_LIST_VIEW_GRID,
				],
			]
		];
	}

	public function getCurrentTypeListView(): string
	{
		if(Application::getInstance()->getContext()->getRequest()->get('view') === static::TYPES_LIST_VIEW_GRID)
		{
			return static::TYPES_LIST_VIEW_GRID;
		}

		return static::TYPES_LIST_VIEW_TILES;
	}

	/**
	 * Returns url to types list in remembered view for this user
	 *
	 * @return Uri|null
	 */
	public function getUserTypesUrl(): ?Uri
	{
		$views = $this->getTypeListViews();
		$view = $this->getUserTypeListView();
		return $this->getUserUrl($views, $view);
	}

	public function getUserTypeListView(): ?string
	{
		return $this->getUserView(static::USER_OPTION_TYPES_LIST_VIEW);
	}

	public function setUserTypeListView(string $view): UrlManager
	{
		return $this->setUserView(static::USER_OPTION_TYPES_LIST_VIEW, $view);
	}

	public function getUserItemsUrlWithTasks(int $typeId): ?Uri
	{
		$url = $this->getUserItemsUrl($typeId);
		if($url)
		{
			return $url->addParams([
				'apply_filter' => 'y',
				TaskManager::TASKS_FILTER_FIELD => TaskManager::TASKS_FILTER_HAS_TASKS_VALUE,
			]);
		}

		return null;
	}

	public function getUserTypesUrlWithTasks(): ?Uri
	{
		$url = $this->getUserTypesUrl();
		if($url)
		{
			return $url->addParams([
				'apply_filter' => 'y',
				TaskManager::TASKS_FILTER_FIELD => TaskManager::TASKS_FILTER_HAS_TASKS_VALUE,
			]);
		}

		return null;
	}

	protected function getUserUrl(array $views, string $view = null, array $parameters = []): ?Uri
	{
		if(!$view || !isset($views[$view]))
		{
			$userView = reset($views);
		}
		else
		{
			$userView = $views[$view];
		}
		if(!isset($userView['getParameters']) || !is_array($userView['getParameters']))
		{
			$userView['getParameters'] = [];
		}

		return $this->getUrlForTemplate($userView['template'], $parameters, $userView['getParameters']);
	}

	protected function getUserView(string $optionName): ?string
	{
		$view = null;
		$userId = $this->getUserId();
		if($userId > 0)
		{
			$view = \CUserOptions::GetOption(Driver::MODULE_ID, $optionName, null, $userId);
		}
		if(!is_string($view))
		{
			$view = null;
		}

		return $view;
	}

	protected function setUserView(string $optionName, string $view): UrlManager
	{
		$userId = $this->getUserId();
		if($userId > 0)
		{
			\CUserOptions::SetOption(Driver::MODULE_ID, $optionName, $view, false, $userId);
		}

		return $this;
	}
	//endregion

	protected function getUrlForTemplate(string $componentName, array $parameters = [], array $getParameters = []): ?Uri
	{
		$template = $this->getUrlTemplates()[$componentName];
		if($template)
		{
			if($this->isSefMode())
			{
				$path = \CComponentEngine::makePathFromTemplate($template, $parameters);
				if($path)
				{
					$url = new Uri($this->getFullPath($path));
					if(!empty($getParameters))
					{
						$url->addParams($getParameters);
					}

					return $url;
				}
			}
			else
			{
				$uri = new Uri($this->getFullPath());
				$params = [
					static::GET_COMPONENT_NAME => $componentName,
					static::GET_COMPONENT_PARAMETERS => $parameters,
				];
				$params = array_merge($getParameters, $params);

				return $uri->addParams($params);
			}
		}

		return null;
	}

	protected function getFullPath(string $folder = ''): string
	{
		return Path::combine($this->getSiteFolder(), $this->getRoot(), $folder) . '/';
	}
}