<?php


namespace Bitrix\Crm\Order\Rest\Entity;


use Bitrix\Main\Error;
use Bitrix\Sale\Rest\Attributes;
use Bitrix\Sale\Result;

class RequisiteLink extends \Bitrix\Sale\Rest\Entity\Base
{
	public function getFields()
	{
		return [
			'ENTITY_TYPE_ID'=>[
				'TYPE'=>self::TYPE_INT,
				'ATTRIBUTES'=>[
					Attributes::Immutable,
					Attributes::Required
				]
			],
			'ENTITY_ID'=>[
				'TYPE'=>self::TYPE_INT,
				'ATTRIBUTES'=>[
					Attributes::Immutable,
					Attributes::Required
				]
			],
			'REQUISITE_ID'=>[
				'TYPE'=>self::TYPE_INT
			],
			'BANK_DETAIL_ID'=>[
				'TYPE'=>self::TYPE_INT
			],
			'MC_REQUISITE_ID'=>[
				'TYPE'=>self::TYPE_INT
			],
			'MC_BANK_DETAIL_ID'=>[
				'TYPE'=>self::TYPE_INT
			]
		];
	}

	public function internalizeFieldsModify($fields, $fieldsInfo=[])
	{
		$result = [];

		$fieldsInfo = empty($fieldsInfo)? $this->getFields():$fieldsInfo;
		$listFieldsInfoAdd = $this->getListFieldInfo($fieldsInfo, ['filter'=>['ignoredAttributes'=>[Attributes::Hidden, Attributes::ReadOnly]]]);

		if(isset($fields['ORDER']['REQUISITE_LINK']))
		{
			$result['ORDER']['REQUISITE_LINK'] = $this->internalizeFields($fields['ORDER']['REQUISITE_LINK'], $listFieldsInfoAdd);
		}

		return $result;
	}

	public function checkRequiredFieldsModify($fields)
	{
		$r = new Result();

		$listFieldsInfoAdd = $this->getListFieldInfo($this->getFields(), ['filter'=>['ignoredAttributes'=>[Attributes::Hidden, Attributes::ReadOnly], 'ignoredFields'=>['ENTITY_TYPE_ID', 'ENTITY_ID']]]);

		if(isset($fields['ORDER']['REQUISITE_LINK']))
		{
			$required = $this->checkRequiredFields($fields['ORDER']['REQUISITE_LINK'], $listFieldsInfoAdd);
			if(!$required->isSuccess())
			{
				$r->addError(new Error('[requisiteLink] - '.implode(', ', $required->getErrorMessages()).'.'));
			}
		}

		return $r;
	}
}