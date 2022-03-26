<?php

use Bitrix\Disk\Document\OnlyOffice\Templates\TemplateManager;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Im\Call\Call;
use Bitrix\Main\Engine\Contract\Controllerable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CDiskFileEditorTemplatesComponent extends BaseComponent implements Controllerable
{
	/** @var Call */
	protected $call;

	public function configureActions()
	{
		return [];
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if (!($this->arParams['MESSENGER']['CALL'] instanceof Call))
		{
			throw new \Bitrix\Main\ArgumentTypeException('CALL', Call::class);
		}

		$this->call = $this->arParams['MESSENGER']['CALL'];

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult['MESSENGER']['CALL'] = [
			'ID' => $this->call->getId(),
		];

		$this->arResult['TEMPLATES'] = [];
		$templateManager = new TemplateManager();
		foreach ($templateManager->list() as $template)
		{
			$this->arResult['TEMPLATES'][] = [
				'ID' => $template['id'],
				'NAME' => $template['name'],
			];
		}

		$this->includeComponentTemplate();
	}
}