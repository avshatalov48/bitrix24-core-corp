<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Driver;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaStageListComponent extends Bitrix\Rpa\Components\Base
{
	/** @var \Bitrix\Rpa\Model\Type */
	protected $type;

	public function onPrepareComponentParams($arParams)
	{
		$this->fillParameterFromRequest('typeId', $arParams);

		return parent::onPrepareComponentParams($arParams);
	}

	protected function init(): void
	{
		parent::init();

		if(!$this->getErrors())
		{
			$userPermissions = \Bitrix\Rpa\Driver::getInstance()->getUserPermissions();
			$typeId = (int) $this->arParams['typeId'];
			if($typeId > 0)
			{
				$this->type = \Bitrix\Rpa\Driver::getInstance()->getType($typeId);
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
						Loc::getMessage('RPA_STAGES_LIST_TITLE', [
							'#TITLE#' => htmlspecialcharsbx($this->type->getTitle()),
						])
					);
				}
			}
			else
			{
				$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('RPA_NOT_FOUND_ERROR'));
			}
		}

		if(!$this->getErrors())
		{
			$director = Driver::getInstance()->getDirector();
			$scenarios = $director->getScenariosForType($this->type);
			if($scenarios->count() > 0)
			{
				$result = $scenarios->playAll();
				if(!$result->isSuccess())
				{
					$this->errorCollection->add($result->getErrors());
				}
			}
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

		$this->arResult['messages'] = static::loadBaseLanguageMessages();
		$this->arResult['params'] = [
			'analyticsLabel' => 'rpaStagesSave',
			'method' => 'rpa.stage.saveAll',
			'typeId' => $this->type->getId(),
			'stages' => [],
		];
		$this->arResult['backUrl'] = Driver::getInstance()->getUrlManager()->getUserItemsUrl($this->type->getId());
		$controller = new \Bitrix\Rpa\Controller\Stage();
		$stages = $this->type->getStages();
		foreach($stages as $stage)
		{
			$this->arResult['params']['stages'][] = $controller->prepareData($stage, false);
		}

		$this->includeComponentTemplate();
	}
}