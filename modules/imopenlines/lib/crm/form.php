<?php
namespace Bitrix\ImOpenLines\Crm;

use Bitrix\ImOpenLines\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

use Bitrix\Crm\WebForm;
use Bitrix\Crm\Tracking;

use Bitrix\Calendar\UserField\ResourceBooking;

use Bitrix\ImConnector\InteractiveMessage\Connectors\IMessage;

/**
 * Class Form
 * @package Bitrix\ImOpenLines\Crm
 */
class Form
{
	/**
	 * Preparing a value for saving the selected time in the resource reservation field.
	 *
	 * @param $name
	 * @param DateTime $value
	 * @param $idForm
	 * @return array
	 */
	public static function prepareFormDateValues($name, DateTime $value, $idForm): array
	{
		$result = [];
		if(
			Loader::includeModule('calendar') &&
			Loader::includeModule('crm')
		)
		{
			$formConfig = WebForm\Embed\Config::createById($idForm)->toArray();

			if(!empty($formConfig['data']['fields']))
			{
				foreach ($formConfig['data']['fields'] as $field)
				{
					if(
						$field['name'] == $name &&
						!empty($field['booking']) &&
						!empty($field['booking']['entity_field_name']) &&
						!empty($field['booking']['settings_data']) &&
						in_array($field['type'], IMessage\Output::LIST_NATIVE_TIME_FIELDS_ID, true)
					)
					{
						$result = ResourceBooking::prepareFormDateValues(
							$value,
							$field['booking']['entity_field_name'],
							[
								'settingsData' => $field['booking']['settings_data']
							]
						);
					}
				}
			}
		}

		return $result;
	}

	protected $isLoad = false;
	protected $connectorId;
	protected $formId;
	protected $values;
	protected $form;

	/**
	 * Form constructor.
	 * @param $connectorId
	 * @param $formId
	 */
	public function __construct($connectorId, $formId)
	{
		if(Loader::includeModule('crm'))
		{
			$this->form = new WebForm\Form($formId);
			$this->connectorId = $connectorId;
			$this->formId = $formId;
		}
	}

	/**
	 * @return bool
	 */
	protected function isLoad(): bool
	{
		$result = false;
		if(
			!empty($this->formId) &&
			!empty($this->connectorId) &&
			!empty($this->form) && $this->form instanceof WebForm\Form &&
			$this->form->isActive()
		)
		{
			$result = true;
		}

		return $result;
	}


	/**
	 * Save CRM form
	 *
	 * @param $values
	 * @return Result
	 */
	public function save($values): Result
	{
		$result = new Result();

		if($this->isLoad())
		{
			$trace = Tracking\Trace::create()->addChannel(
				new Tracking\Channel\Imol($this->connectorId)
			);

			$resultSave = $this->form->fill()
				->setValues($values)
				->setFieldChecking(false)
				->setTrace($trace)
				->save();

			$result->setResult($resultSave);
		}
		else
		{
			$result->addError(new Error('Failed to load the CRM form'));
		}

		return $result;
	}

	/**
	 * @return WebForm\Form
	 */
	public function getForm()
	{
		return $this->form;
	}
}