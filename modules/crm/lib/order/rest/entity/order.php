<?php


namespace Bitrix\Crm\Order\Rest\Entity;



use Bitrix\Main\Error;
use Bitrix\Sale\Result;

class Order extends \Bitrix\Sale\Rest\Entity\Order
{
	public function internalizeFieldsModify($fields, $fieldsInfo=[])
	{
		$argumentsFields = $fields;

		$orderContactCompany = new OrderContactCompany();
		$requisiteLink = new RequisiteLink();

		$fields = parent::internalizeFieldsModify($fields, $fieldsInfo);

		if(isset($argumentsFields['ORDER']['CLIENTS']))
			$fields['ORDER']['CLIENTS'] = $orderContactCompany->internalizeFieldsModify($argumentsFields)['ORDER']['CLIENTS'];

		if(isset($argumentsFields['ORDER']['REQUISITE_LINK']))
			$fields['ORDER']['REQUISITE_LINK'] = $requisiteLink->internalizeFieldsModify($argumentsFields)['ORDER']['REQUISITE_LINK'];

		return $fields;
	}

	public function externalizeFields($fields)
	{
		$orderContactCompany = new OrderContactCompany();
		$requisiteLink = new RequisiteLink();

		$result = parent::externalizeFields($fields);

		if(isset($fields['CLIENTS']))
			$result['CLIENTS'] = $orderContactCompany->externalizeListFields($fields['CLIENTS']);

		if(isset($fields['REQUISITE_LINK']))
			$result['REQUISITE_LINK'] = $requisiteLink->externalizeFields($fields['REQUISITE_LINK']);

		return $result;
	}

	public function checkRequiredFieldsModify($fields)
	{
		$requisiteLink = new RequisiteLink();
		$orderContactCompany = new OrderContactCompany();

		$r = parent::checkRequiredFieldsModify($fields);

		$required = $requisiteLink->checkRequiredFieldsModify($fields);
		if($required->isSuccess() == false)
		{
			$r->addError(new Error(implode(', ', $required->getErrorMessages())));
		}

		$required = $orderContactCompany->checkRequiredFieldsModify($fields);
		if($required->isSuccess() == false)
		{
			$r->addError(new Error(implode(', ', $required->getErrorMessages())));
		}

		return $r;
	}
}