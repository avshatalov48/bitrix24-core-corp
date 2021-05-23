<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if(!\Bitrix\Main\Loader::includeModule('transformer'))
{
	return false;
}

class CBitrixTransformerDummy extends CBitrixComponent
{
	private $types = array(
		'video',
	);

	public function onPrepareComponentParams($arParams)
	{
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$file = $this->arParams['FILE'];

		if(empty($file))
		{
			return false;
		}

		$type = $this->arParams['TYPE'];
		if(!in_array($type, $this->types))
		{
			$type = $this->types[0];
		}

		if($file == intval($file))
		{
			$file = (int)$file;
		}

		$info = \Bitrix\Transformer\FileTransformer::getTransformationInfoByFile($file);
		if($info === false)
		{
			$this->arResult['STATUS'] = 'NOT_STARTED';
			$this->arResult['TITLE'] = Loc::getMessage('VIDEO_TRANSFORMATION_NOT_STARTED_TITLE');
			if(!empty($this->arParams['TRANSFORM_URL']))
			{
				$this->arResult['DESC'] = Loc::getMessage('VIDEO_TRANSFORMATION_NOT_STARTED_DESC');
				$this->arResult['TRANSFORM_URL'] = $this->arParams['TRANSFORM_URL'];
				$this->arResult['TRANSFORM_URL_TEXT'] = Loc::getMessage('VIDEO_TRANSFORMATION_NOT_STARTED_TRANSFORM');
			}
		}
		else
		{
			$status = $info['status'];
			/** @var \Bitrix\Main\Type\DateTime $time */
			$time = $info['time'];
			$this->arResult['COMMAND_ID'] = $info['id'];

			$this->joinToPull($this->arResult['COMMAND_ID']);

			if($status >= \Bitrix\Transformer\Command::STATUS_SUCCESS || time() - $time->getTimestamp() > \Bitrix\Transformer\FileTransformer::MAX_EXECUTION_TIME)
			{
				$this->arResult['STATUS'] = 'ERROR';
				$this->arResult['TITLE'] = Loc::getMessage('VIDEO_TRANSFORMATION_ERROR_TITLE');
				$this->arResult['DESC'] = Loc::getMessage('VIDEO_TRANSFORMATION_ERROR_DESC');
				if(!empty($this->arParams['TRANSFORM_URL']) && time() - $time->getTimestamp() > \Bitrix\Transformer\FileTransformer::MAX_EXECUTION_TIME)
				{
					$this->arResult['TRANSFORM_URL'] = $this->arParams['TRANSFORM_URL'];
					$this->arResult['TRANSFORM_URL_TEXT'] = Loc::getMessage('VIDEO_TRANSFORMATION_ERROR_TRANSFORM');
				}
			}
			else
			{
				$this->arResult['STATUS'] = 'PROCESS';
				$this->arResult['TITLE'] = Loc::getMessage('VIDEO_TRANSFORMATION_IN_PROCESS_TITLE');
				$this->arResult['DESC'] = Loc::getMessage('VIDEO_TRANSFORMATION_IN_PROCESS_DESC');
			}
		}

		$this->arResult['MESSAGES'] = Loc::loadLanguageFile(__FILE__);

		$this->includeComponentTemplate($type);

	}

	protected function joinToPull($id)
	{
		global $USER;
		if($USER->IsAuthorized() && CModule::IncludeModule("pull") && $this->arParams['REFRESH_URL'])
		{
			$this->arResult['REFRESH_URL'] = $this->arParams['REFRESH_URL'];
			\CPullWatch::Add($USER->GetID(), 'TRANSFORMATIONCOMPLETE'.$id, true);
		}
	}
}