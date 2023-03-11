<?php

/**
 * Bitrix Framework
 *
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Web\Json;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TagAccessController;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Action\Filter\BooleanFilter;
use Bitrix\Tasks\Manager\Task;
use Bitrix\Tasks\Slider\Exception\SliderException;
use Bitrix\Tasks\Slider\Factory\SliderFactory;
use Bitrix\Tasks\Slider\Path\PathMaker;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Slider\Path\TemplatePathMaker;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

class TasksTagsSelector extends \CBitrixComponent implements Errorable, Controllerable
{
	private $errorCollection;
	private int $userId = 0;
	private array $tags = [];
	private bool $templateContext = false;
	private PathMaker $pathMaker;

	/**
	 * @param null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	public function configureActions()
	{
		if (!Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'updateTags' => [
				'+prefilters' => [
					new BooleanFilter(),
				],
			],
		];
	}

	/**
	 * @return array|\Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
	}

	public function updateTagsAction($taskId, $tagIds = [], string $newTag = ''): ?array
	{
		$taskId = (int)$taskId;
		if (!$taskId)
		{
			return null;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$task = TaskRegistry::getInstance()->get($taskId);

		if (is_null($task))
		{
			return null;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $taskId))
		{
			$this->addForbiddenError();
			return [];
		}

		$groupId = 0;
		$groupName = '';

		if (!is_null($task['GROUP_INFO']))
		{
			$groupId = $task['GROUP_INFO']['ID'];
			$groupName = $task['GROUP_INFO']['NAME'];
		}

		if (!empty(trim($newTag)))
		{
			$tagService = new Bitrix\Tasks\Control\Tag($this->userId);

			if ($tagService->isExists($newTag, $groupId, $taskId))
			{
				return [
					'success' => false,
					'error' => Loc::getMessage('TASKS_TAG_SELECTOR_TASK_TAG_ALREADY_EXISTS'),
				];
			}

			$tagIds[] = $newTag;
		}

		$this->updateTask(
			$taskId,
			[
				'TAGS' => $tagIds,
			]
		);

		if ($this->errorCollection->checkNoFatals())
		{
			return [
				'success' => true,
				'error' => '',
				'owner' => empty($groupName) ? CurrentUser::get()->getFormattedName() : $groupName,
			];
		}

		return [
			'success' => false,
			'error' => Loc::getMessage('TASKS_TS_UNEXPECTED_ERROR'),
		];
	}

	public function executeComponent()
	{
		$this->initParams();
		$this->formatTags();
		$this->includeComponentTemplate();
	}

	private function initParams(): void
	{
		$this->arResult['VALUE'] = [];

		if (array_key_exists('VALUE', $this->arParams))
		{
			if (is_array($this->arParams['VALUE']))
			{
				$this->arResult['VALUE'] = $this->arParams['VALUE'];
			}
			elseif ($this->arParams['VALUE'])
			{
				$this->arResult['VALUE'] = explode(',', $this->arParams['VALUE']);
			}
		}
		$this->arResult['VALUE'] = array_map('trim', $this->arResult['VALUE']);
		$this->arResult['NAME'] = htmlspecialcharsbx($this->arParams['NAME']);

		if (isset($this->arParams['PATH_TO_TASKS']) && !empty($this->arParams['PATH_TO_TASKS']))
		{
			$this->arResult['PATH_TO_TASKS'] = $this->arParams["PATH_TO_TASKS"];
		}
		else
		{
			$this->arResult['PATH_TO_TASKS'] = "/company/personal/user/{$this->userId}/tasks/";
		}

		$this->arResult['CAN_EDIT'] = ($this->arParams['CAN_EDIT'] ?? false);

		$this->arResult['GROUP_ID'] = 0;
		if (array_key_exists('GROUP_ID', $this->arParams))
		{
			$this->arResult['GROUP_ID'] = (int)$this->arParams['GROUP_ID'];
		}

		$this->arResult['TASK_ID'] = 0;
		if (array_key_exists('TASK_ID', $this->arParams))
		{
			$this->arResult['TASK_ID'] = (int)$this->arParams['TASK_ID'];
		}

		$this->arResult['TEMPLATE_ID'] = 0;
		if (array_key_exists('TEMPLATE_ID', $this->arParams))
		{
			$this->arResult['TEMPLATE_ID'] = (int)$this->arParams['TEMPLATE_ID'];
		}

		$this->arResult['IS_SCRUM_TASK'] = ($this->arParams['IS_SCRUM_TASK'] ?? false);
		$this->tags = array_unique($this->arResult['VALUE']);

		if (
			array_key_exists('CONTEXT', $this->arParams)
			&& $this->arParams['CONTEXT'] === 'TEMPLATE'
		)
		{
			$this->templateContext = true;
		}

		if ($this->templateContext)
		{
			$this->pathMaker = new TemplatePathMaker(
				$this->arParams['TEMPLATE_ID'],
				PathMaker::DEFAULT_ACTION,
				$this->userId,
				PathMaker::PERSONAL_CONTEXT
			);
		}
		else
		{
			$this->pathMaker = new TaskPathMaker(
				$this->arParams['TASK_ID'],
				PathMaker::DEFAULT_ACTION,
				$this->userId,
				PathMaker::PERSONAL_CONTEXT
			);
		}
	}

	private function updateTask(int $taskId, array $data): void
	{
		try
		{
			Task::update(
				$this->userId,
				$taskId,
				$data,
				[
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errorCollection,
				]
			);
		}
		catch (TasksException $e)
		{
			$messages = @unserialize($e->getMessage(), ['allowed_classes' => false]);
			if (is_array($messages))
			{
				foreach ($messages as $message)
				{
					$this->errorCollection->add('TASK_EXCEPTION', $message['text'], false, ['ui' => 'notification']);
				}
			}
		}
		catch (\Exception $e)
		{
			$this->errorCollection->add('UNKNOWN_EXCEPTION', Loc::getMessage('TASKS_TS_UNEXPECTED_ERROR'), false,
				['ui' => 'notification']);
		}
	}

	private function init()
	{
		if (!Loader::includeModule('tasks'))
		{
			return null;
		}
		$this->userId = User::getId();
		$this->errorCollection = new Collection();
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function formatTags(): void
	{
		$items = [];
		foreach ($this->tags as $tag)
		{
			$encodedTag = urlencode(htmlspecialcharsback($tag));
			$type = 'href';
			if ($this->templateContext)
			{
				$action = "{$this->pathMaker->makeEntitiesListPath()}?apply_filter=Y&TAGS={$encodedTag}";
			}
			else
			{
				$action = "{$this->arResult['PATH_TO_TASKS']}?apply_filter=Y&TAG={$encodedTag}";
			}

			$items[] = "<a data-slider-ignore-autobinding=\"true\" target=\"_top\" {$type}='{$action}' style='cursor: pointer;'>{$tag}</a>";
		}

		$this->arResult['TAGS'] = implode(',', $items);
	}
}