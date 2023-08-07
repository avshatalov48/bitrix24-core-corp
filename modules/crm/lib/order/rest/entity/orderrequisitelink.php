<?php


namespace Bitrix\Crm\Order\Rest\Entity;


class OrderRequisiteLink extends \Bitrix\Sale\Rest\Entity\Base
{
	public function getFields()
	{
		return [
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
}