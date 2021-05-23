<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

if (!\Bitrix\Main\Loader::includeModule('timeman'))
{
	return;
}
Loc::loadMessages(__FILE__);

class TimemanTimePickerPopupContentComponent extends \Bitrix\Timeman\Component\BaseComponent
{
	public function executeComponent()
	{
		$this->initFromParams('END_CLOCK_STEP', 5, 'int');
		$this->initFromParams('START_CLOCK_STEP', 5, 'int');
		$this->initFromParams('TIME_PICKER_CONTENT_ATTRIBUTE_DATA_ROLE', '');
		$this->initFromParams('SHOW_START_END_BLOCKS', true, 'bool');
		$this->initFromParams('SHOW_EDIT_BREAK_LENGTH', false, 'bool');
		$this->initFromParams('SHOW_END_DATE_PICKER', false, 'bool');
		$this->initFromParams('SHOW_START_DATE_PICKER', false, 'bool');
		$this->initFromParams('SHOW_EDIT_REASON', false, 'bool');
		$this->initFromParams('EDIT_REASON_ATTRIBUTE_NAME', 'reason');
		$this->initFromParams('EDIT_BREAK_LENGTH_ATTRIBUTE_NAME', 'breakLength');
		$this->initFromParams('BREAK_LENGTH_VALUE', '00:00');
		$this->initFromParams('BREAK_LENGTH_ATTRIBUTE_DATA_ROLE', '');
		$this->initFromParams('BREAK_LENGTH_INPUT_NAME');
		$this->initFromParams('BREAK_LENGTH_INPUT_ID');
		$this->initFromParams('BREAK_LENGTH_INIT_TIME');
		$this->initFromParams('START_INPUT_NAME');
		$this->initFromParams('START_INPUT_ID');
		$this->initFromParams('START_INIT_TIME');
		$this->initFromParams('END_INPUT_NAME');
		$this->initFromParams('END_INPUT_ID');
		$this->initFromParams('START_DATE_DEFAULT_VALUE');
		$this->initFromParams('END_DATE_DEFAULT_VALUE');
		$this->initFromParams('START_DATE_INPUT_SELECTOR_ROLE');
		$this->initFromParams('END_DATE_INPUT_SELECTOR_ROLE');
		$this->initFromParams('END_INIT_TIME');

		$this->includeComponentTemplate();
	}
}