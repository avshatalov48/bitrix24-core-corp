<?php

namespace Bitrix\Crm\UI\Barcode\Payment;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Format;

abstract class DataAssembler
{
	public static function createTransactionDataByRequisites(
		int $receiverRequisiteId,
		int $receiverBankDetailId,
		?int $senderRequisiteId = null,
		?int $senderBankDetailId = null
	): TransactionData
	{
		$receiverData = static::createTransactionPartyDataByRequisites($receiverRequisiteId, $receiverBankDetailId);
		$senderData = null;
		if ($senderRequisiteId > 0 || $senderBankDetailId > 0)
		{
			$senderData = static::createTransactionPartyDataByRequisites((int)$senderRequisiteId, (int)$senderBankDetailId);
		}

		return new TransactionData($receiverData, $senderData);
	}

	public static function createTransactionPartyDataByRequisites(
		int $requisiteId,
		int $bankDetailId
	): TransactionPartyData
	{
		$requisites = [];
		if ($requisiteId > 0)
		{
			$fetchedRequisites = EntityRequisite::getSingleInstance()->getList([
				'select' => ['*'],
				'filter' => ['=ID' => $requisiteId]
			])->fetch();

			if (is_array($fetchedRequisites))
			{
				$requisites = $fetchedRequisites;
			}
		}

		$bankDetail = [];
		if ($bankDetailId > 0)
		{
			$fetchedBankDetail = EntityBankDetail::getSingleInstance()->getList([
				'select' => ['*'],
				'filter' => ['=ID' => $bankDetailId]
			])->fetch();

			if (is_array($fetchedBankDetail))
			{
				$bankDetail = $fetchedBankDetail;
			}
		}

		return static::createTransactionPartyDataByRequisiteData($requisites, $bankDetail);
	}

	public static function createTransactionPartyDataByRequisiteData(
		array $requisites,
		array $bankDetail
	): TransactionPartyData
	{
		$data = new TransactionPartyData();

		$name = Format\Requisite::formatOrganizationName($requisites);
		if (!is_null($name))
		{
			$data->setName($name);
		}

		if (!empty($requisites['RQ_INN']))
		{
			$data->setInn((string)$requisites['RQ_INN']);
		}

		if (!empty($requisites['RQ_KPP']))
		{
			$data->setKpp((string)$requisites['RQ_KPP']);
		}

		if (!empty($bankDetail['RQ_ACC_NUM']))
		{
			$data->setAccountNumber((string)$bankDetail['RQ_ACC_NUM']);
		}

		if (!empty($bankDetail['RQ_BANK_NAME']))
		{
			$data->setBankName((string)$bankDetail['RQ_BANK_NAME']);
		}

		if (!empty($bankDetail['RQ_BIK']))
		{
			$data->setBic((string)$bankDetail['RQ_BIK']);
		}

		if (!empty($bankDetail['RQ_COR_ACC_NUM']))
		{
			$data->setCorrAccountNumber((string)$bankDetail['RQ_COR_ACC_NUM']);
		}

		return $data;
	}
}
