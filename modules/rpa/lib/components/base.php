<?php

namespace Bitrix\Rpa\Components;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Model\TypeTable;

abstract class Base extends \CBitrixComponent implements Errorable
{
	/** @var ErrorCollection */
	protected $errorCollection;

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function init(): void
	{
		$this->errorCollection = new ErrorCollection();
		// always load common messages
		static::loadBaseLanguageMessages();

		if (!Driver::getInstance()->isEnabled())
		{
			$this->errorCollection->setError(new Error(Loc::getMessage('RPA_IS_DISABLED')));
		}
	}

	protected function isIframe(): bool
	{
		return ($this->request->get('IFRAME') === 'Y');
	}

	protected function getApplication(): \CAllMain
	{
		global $APPLICATION;
		return $APPLICATION;
	}

	protected function fillParameterFromRequest(string $parameterName, array &$arParams, HttpRequest $request = null)
	{
		if(!$request)
		{
			$request = $this->request;
		}

		if(!empty($arParams[$parameterName]))
		{
			return;
		}

		$value = $request->get($parameterName);
		if(!empty($value))
		{
			$arParams[$parameterName] = $value;
		}
	}

	public static function getUsers(array $userIds): array
	{
		$users = [];
		$currentUserId = Driver::getInstance()->getUserId();
		if($currentUserId > 0)
		{
			$userIds[] = $currentUserId;
		}
		if(empty($userIds))
		{
			return $users;
		}

		$nameFormat = Application::getInstance()->getContext()->getCulture()->getNameFormat();
		$converter = Converter::toJson();
		$urlManager = Driver::getInstance()->getUrlManager();

		$userList = UserTable::getList([
			'select' => [
				'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE', 'PERSONAL_PHOTO', 'WORK_POSITION'
			], 'filter' => [
				'=ID' => $userIds,
			],
		]);
		while($user = $userList->fetch())
		{
			$userId = (int) $user['ID'];
			$user['FULL_NAME'] = \CUser::FormatName($nameFormat, $user, false, false);
			$user['LINK'] = $urlManager->getUserPersonalUrl($userId);
			if($user['PERSONAL_PHOTO'] > 0)
			{
				$photo = \CFile::ResizeImageGet($user['PERSONAL_PHOTO'], [
					'width' => 63,
					'height' => 63,
				], BX_RESIZE_IMAGE_EXACT, true, false, true);
				if($photo)
				{
					$user['PHOTO'] = $photo['src'];
				}
			}
			unset($user['PERSONAL_PHOTO']);
			$users[$userId] = $converter->process($user);
		}

		return $users;
	}

	protected function prepareUserDataForGrid(array $userData): string
	{
		return '<a href="'.htmlspecialcharsbx($userData['link']).'">'.htmlspecialcharsbx($userData['fullName']).'</a>';
	}

	protected function getTypeId(): ?int
	{
		return null;
	}

	public function addTopPanel(\CBitrixComponentTemplate $template)
	{
		$template->setViewTarget('above_pagetitle');
		$menuId = Driver::MODULE_ID;
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:main.interface.buttons',
			'',
			[
				'ID' => $menuId,
				'ITEMS' => $this->getTopPanelItems(),
			]
		);
		$template->endViewTarget();
	}

	protected function getTopPanelItems(): array
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		$sort = 10;

		$items = [
			[
				'TEXT' => Loc::getMessage('RPA_TOP_PANEL_PANEL'),
				'URL' => $urlManager->getUserTypesUrl(),
				'URL_CONSTANT' => false,
				'SORT' => $sort,
				'ID' => 'rpa-top-panel-main-section',
			]
		];

		$typeId = $this->getTypeIdForTopPanel();
		if($typeId)
		{
			$sort += 10;
			$componentName = $urlManager->parseRequest()->getComponentName();
			$isActive = ($componentName === 'bitrix:rpa.kanban' || $componentName === 'bitrix:rpa.item.list');
			$items[] = [
				'TEXT' => Loc::getMessage('RPA_TOP_PANEL_KANBAN'),
				'URL' => $urlManager->getUserItemsUrl($typeId),
				'URL_CONSTANT' => false,
				'SORT' => $sort,
				'IS_ACTIVE' => $isActive,
				'ID' => 'rpa-top-panel-last-type',
			];
		}

		$tasksCounter = 0;
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$tasksCounter = $taskManager->getUserTotalIncompleteCounter();
		}

		$sort += 10;
		$items[] = [
			'TEXT' => Loc::getMessage('RPA_TOP_PANEL_TASK'),
			'URL' => $urlManager->getTasksUrl(),
			'URL_CONSTANT' => true,
			'SORT' => $sort,
			'COUNTER' => $tasksCounter,
			'ID' => 'rpa-top-panel-tasks',
		];

//		$sort += 10;
//		$items[] = [
//			'TEXT' => Loc::getMessage('RPA_COMMON_PERMISSIONS'),
//			'IS_DISABLED' => true,
//			'SORT' => $sort,
//		    'ID' => 'rpa-top-panel-permissions',
//		];

		return $items;
	}

	protected function getTypeIdForTopPanel(): ?int
	{
		$typeId = $this->getLastVisitedTypeId();
		if($typeId > 0)
		{
			$filter = [
				[
					'LOGIC' => 'OR',
					Driver::getInstance()->getUserPermissions()->getFilterForViewableTypes(),
					'=ID' => $typeId,
				]
			];
			$types = TypeTable::getList([
				'select' => ['ID'],
				'filter' => $filter,
				'order' => [
					'ID' => 'ASC',
				],
			]);
		}
		else
		{
			$filter = Driver::getInstance()->getUserPermissions()->getFilterForViewableTypes();
			$types = TypeTable::getList([
				'select' => ['ID'],
				'filter' => $filter,
				'order' => [
					'ID' => 'ASC',
				],
				'limit' => 1,
			]);
		}
		$firstTypeId = null;
		while($typeData = $types->fetch())
		{
			if(!$typeId)
			{
				return (int) $typeData['ID'];
			}

			if(!$firstTypeId)
			{
				$firstTypeId = (int)$typeData['ID'];
			}

			if((int) $typeData['ID'] === $typeId)
			{
				return $typeId;
			}
		}

		return $firstTypeId;
	}

	protected function setLastVisitedTypeId(int $typeId, int $userId = null): Base
	{
		if(!$userId)
		{
			$userId = false;
		}
		\CUserOptions::SetOption(Driver::MODULE_ID, 'last_visited_type_id', $typeId, false, $userId);

		return $this;
	}

	protected function getLastVisitedTypeId(int $userId = null): int
	{
		if(!$userId)
		{
			$userId = false;
		}
		return (int) \CUserOptions::GetOption(Driver::MODULE_ID, 'last_visited_type_id', false, $userId);
	}

	public function addToolbar(\CBitrixComponentTemplate $template)
	{
		$parameters = $this->getToolbarParameters();
		if(!empty($parameters))
		{
			$template->SetViewTarget('below_pagetitle', 100);
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				"bitrix:rpa.toolbar",
				"",
				$parameters,
				$this
			);
			$template->EndViewTarget();
		}
	}

	protected function getToolbarParameters(): array
	{
		return [
			'typeId' => $this->getTypeId(),
			'buttons' => [], //ui.toolbar buttons
			'filter' => [], //filter options
			'views' => [], //views switcher
			'tasks' => 0, // tasks counter
		];
	}

	public static function loadBaseLanguageMessages(): array
	{
		return Loc::loadLanguageFile(__FILE__);
	}

	protected function getTypeDataForPanelItem(Type $type, array $tasks = null): array
	{
		$bitrix24Manager = Driver::getInstance()->getBitrix24Manager();
		$tasksCounter = 0;
		if(is_array($tasks))
		{
			if(isset($tasks[$type->getId()]))
			{
				$tasksCounter = (isset($tasks[$type->getId()]) && is_array($tasks[$type->getId()])) ? count($tasks[$type->getId()]) : 0;
			}
		}
		else
		{
			$taskManager = Driver::getInstance()->getTaskManager();
			if($taskManager)
			{
				$tasksCounter = count($taskManager->getUserIncompleteTasksForType($type->getId()));
			}
		}
		$urlManager = Driver::getInstance()->getUrlManager();
		return [
			'id' => 'rpa-type-'.$type->getId(),
			'typeId' => $type->getId(),
			'title' => $type->getTitle(),
			'image' => $type->getImage(),
			'listUrl' => $urlManager->getUserItemsUrl($type->getId()),
			'canDelete' => Driver::getInstance()->getUserPermissions()->canModifyType($type->getId()),
			'tasksCounter' => $tasksCounter,
			'isSettingsRestricted' => $bitrix24Manager->isTypeSettingsRestricted($type->getId()),
		];
	}
}