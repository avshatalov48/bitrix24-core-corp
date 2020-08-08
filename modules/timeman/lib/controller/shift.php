<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Timeman\UseCase\Schedule\Shift as ShiftHandler;
use Bitrix\Main\Engine\Controller;
use Bitrix\Timeman\Form\Schedule\ShiftForm;

class Shift extends Controller
{
	public function addAction()
	{
		$shiftForm = new ShiftForm();

		if ($shiftForm->load($this->getRequest()) && $shiftForm->validate())
		{
			$result = (new ShiftHandler\Create\Handler())->handle($shiftForm);

			if ($result->isSuccess())
			{
				return ['shiftId' => $result->getShift()->getId()];
			}
		}
		$this->addError($shiftForm->getFirstError());
	}
}