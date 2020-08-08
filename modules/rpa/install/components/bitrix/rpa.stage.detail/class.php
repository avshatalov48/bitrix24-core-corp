<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Model\FieldTable;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\UserField\UserField;

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaStageDetailComponent extends \Bitrix\Rpa\Components\Base
{
	/** @var \Bitrix\Rpa\Model\Type */
	protected $type;
	/** @var \Bitrix\Rpa\Model\Stage */
	protected $stage;
	protected $userPermissions;

	public function onPrepareComponentParams($arParams)
	{
		$this->fillParameterFromRequest('id', $arParams);
		$this->fillParameterFromRequest('typeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();
		$this->userPermissions = \Bitrix\Rpa\Driver::getInstance()->getUserPermissions();

		$typeId = (int) $this->arParams['typeId'];
		if($typeId > 0)
		{
			$this->type = TypeTable::getById($typeId)->fetchObject();
		}
		if(!$this->type)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
		}
		elseif($this->userPermissions->canModifyType($this->type->getId()))
		{
			$stageId = (int) $this->arParams['id'];
			if($stageId > 0)
			{
				$stages = $this->type->getStages();
				$this->stage = $stages->getByPrimary($stageId);
			}
			else
			{
				$this->stage = \Bitrix\Rpa\Model\StageTable::createObject();
				$this->stage->setTypeId($this->type->getId());
			}
			if(!$this->stage)
			{
				$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_STAGE_NOT_FOUND_ERROR'));
			}
			else
			{
				$this->stage->setType($this->type);
			}
		}
		else
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_STAGE_TYPE_ACCESS_DENIED'));
		}
	}

	public function executeComponent()
	{
		$this->init();
		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		if($this->stage->getId() > 0)
		{
			$this->getApplication()->setTitle(Loc::getMessage('RPA_STAGE_DETAIL_TITLE', ['#TITLE#' => $this->stage->getName()]));
			$method = 'rpa.stage.update';
			$label = 'rpaStageUpdate';
		}
		else
		{
			$this->getApplication()->setTitle(Loc::getMessage('RPA_STAGE_CREATE_TITLE'));
			$method = 'rpa.stage.add';
			$label = 'rpaStageAdd';
		}

		$visibilities = [
			FieldTable::VISIBILITY_VISIBLE => Loc::getMessage('RPA_STAGE_VISIBLE_FIELDS_TITLE'),
			FieldTable::VISIBILITY_EDITABLE => Loc::getMessage('RPA_STAGE_EDITABLE_FIELDS_TITLE'),
			FieldTable::VISIBILITY_MANDATORY => Loc::getMessage('RPA_STAGE_MANDATORY_FIELDS_TITLE'),
		];

		$fields = [];
		$userFields = $this->stage->getUserFieldCollection();
		foreach($visibilities as $visibility => $title)
		{
			$fieldsByVisibility = $userFields->getFieldsByVisibility($visibility);
			$fields[$visibility] = [
				'title' => $title,
				'id' => $visibility.'-fields-button',
				'fields' => [],
			];
			foreach($userFields as $userField)
			{
				$fields[$visibility]['fields'][] = $this->prepareFieldData($userField, isset($fieldsByVisibility[$userField->getName()]));
			}
		}

		$this->arResult['type'] = $this->type;
		$this->arResult['stage'] = $this->stage;
		$this->arResult['jsParams'] = [
			'method' => $method,
			'analyticsLabel' => $label,
			'fields' => $fields,
		];
		$this->arResult['isFirstStage'] = $this->stage->isFirst();
		$this->arResult['messages'] = Loc::loadLanguageFile(__FILE__);

		$this->includeComponentTemplate();
	}

	protected function prepareFieldData(UserField $userField, bool $checked): array
	{
		return [
			'name' => $userField->getName(),
			'title' => $userField->getTitle(),
			'checked' => $checked,
		];
	}
}