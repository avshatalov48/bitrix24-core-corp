<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\Model\TypeTable;

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaTypeDetailComponent extends \Bitrix\Rpa\Components\Base
{
	/** @var Type */
	protected $type;
	/** @var \Bitrix\Main\UserField\Dispatcher */
	protected $userFieldDispatcher;
	protected $isNew = false;

	public function onPrepareComponentParams($arParams)
	{
		$this->fillParameterFromRequest('id', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();

		if(!$this->getErrors())
		{
			$userPermissions = \Bitrix\Rpa\Driver::getInstance()->getUserPermissions();
			$typeId = (int) $this->arParams['id'];
			if($typeId > 0)
			{
				$this->type = TypeTable::getById($typeId)->fetchObject();
				if(!$this->type)
				{
					$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
				}
				elseif(!$userPermissions->canModifyType($this->type->getId()))
				{
					$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED'));
				}
				else
				{
					$this->getApplication()->setTitle(
						Loc::getMessage('RPA_EDIT_TITLE', [
							'#TITLE#' => htmlspecialcharsbx($this->type->getTitle()),
						])
					);
					$this->isNew = false;
				}
			}
			elseif($userPermissions->canCreateType())
			{
				if($this->isIframe())
				{
					$this->type = $this->createDraftType();
					$this->getApplication()->setTitle(htmlspecialcharsbx($this->getTitle()));
					$this->isNew = true;
				}
				else
				{
					$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
				}
			}
			else
			{
				$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_CREATE_TYPE_ACCESS_DENIED'));
			}
		}

		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();
	}

	protected function getTitle(): string
	{
		if(empty($this->type->getTitle()))
		{
			return Loc::getMessage('RPA_TITLE_ADD');
		}

		return $this->type->getTitle();
	}

	protected function createDraftType(): ?Type
	{
		$director = \Bitrix\Rpa\Driver::getInstance()->getDirector();
		$scenarios = $director->getDraftTypeScenarios();
		$result = $scenarios->playAll();
		if(!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return null;
		}

		\Bitrix\Rpa\Driver::getInstance()->getUserPermissions()->loadUserPermissions();

		return $result->getData()['type'];
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		if($this->getType()->getId() > 0)
		{
			$method = 'rpa.type.update';
			$analyticsLabel = 'rpaTypeUpdate';
		}
		else
		{
			$method = 'rpa.type.add';
			$analyticsLabel = 'rpaTypeAdd';
		}

		$controller = new \Bitrix\Main\Controller\UserFieldConfig();
		$hiddenFields = $fields = [];
		$userFieldsCollection = $this->type->getUserFieldCollection();
		foreach($userFieldsCollection as $userField)
		{
			$data = $controller->preparePublicData($userField->toArray());
			if($userField->isAvailableOnCreate())
			{
				$fields[$userField->getName()] = $data;
			}
			else
			{
				$hiddenFields[$userField->getName()] = $data;
			}
		}

		$this->arResult['icons'] = \Bitrix\Rpa\Ui\Icon::getCodes();

		$this->arResult['isNew'] = $this->isNew;
		$this->arResult['jsParams'] = [
			'title' => $this->getTitle(),
			'method' => $method,
			'analyticsLabel' => $analyticsLabel,
			'fields' => $fields,
			'hiddenFields' => $hiddenFields,
			'entityId' => $this->type->getItemUserFieldsEntityId(),
			'isCreationEnabled' => true,
			'type' => $this->getTypeDataForPanelItem($this->getType()),
			'languageId' => Loc::getCurrentLang(),
		];

		$this->includeComponentTemplate();
	}

	public function getType(): ?Type
	{
		return $this->type;
	}
}