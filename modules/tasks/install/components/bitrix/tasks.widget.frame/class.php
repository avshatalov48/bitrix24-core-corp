<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetFrameComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $errorCollection;

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'setState' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function setStateAction($structure, $value)
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		try
		{
			$signer = new \Bitrix\Main\Security\Sign\Signer();
			$structure = unserialize($signer->unsign($structure, 'tasks.widget.frame.structure'), ['allowed_classes' => false]);
		}
		catch(\Bitrix\Main\Security\Sign\BadSignatureException $e)
		{
			$this->addForbiddenError();
			return [];
		}

		$this->getStateInstance($structure)->set($value);

		return [];
	}

	protected function checkParameters()
	{
		static::tryParseStringParameter($this->arParams['FRAME_ID'], '');
		if(!$this->arParams['FRAME_ID'])
		{
			$this->errors->add('ILLEGAL_PARAMETER.FRAME_ID', 'Frame id not set');
		}

		$this->arResult['TASK_LIMIT_EXCEEDED'] = static::tryParseBooleanParameter($this->arParams['TASK_LIMIT_EXCEEDED']);

		return $this->errors->checkNoFatals();
	}

	private function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

	private function getStateInstance($structure)
	{
		$id = trim((string) $structure['ID']);
		if(!$id)
		{
			return null;
		}

		$blocks = array();
		foreach($structure['BLOCKS'] as $name => $desc)
		{
			$blocks[$name] = [
				'VALUE' => [
					'PINNED' => ['VALUE' => \Bitrix\Tasks\Util\Type\StructureChecker::TYPE_BOOLEAN, 'DEFAULT' => false],
				],
				'DEFAULT' => []
			];
		}

		$flags = array();
		foreach($structure['FLAGS'] as $name => $desc)
		{
			$flags[$name] = ['VALUE' => \Bitrix\Tasks\Util\Type\StructureChecker::TYPE_BOOLEAN, 'DEFAULT' => []];
		}

		return new \Bitrix\Tasks\Util\Type\ArrayOption(
			$id,
			[
				'BLOCKS' => ['VALUE' => $blocks, 'DEFAULT' => []],
				'FLAGS' => ['VALUE' => $flags, 'DEFAULT' => []]
			]
		);
	}
}