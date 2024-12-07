<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Sign\Item;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Internal\LegalLog\LegalLog;
use Bitrix\Sign\Internal\LegalLog\LegalLogTable;

class LegalLogRepository
{
	public function add(Item\B2e\LegalLog $item): Result
	{
		$filledMemberEntity = $this
			->extractModelFromItem($item)
			->setDateCreate(new DateTime())
		;

		$saveResult = $filledMemberEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return (new Result());
	}

	private function extractModelFromItem(Item\B2e\LegalLog $item): LegalLog
	{
		return $this->getFilledModelFromItem($item, LegalLogTable::createObject(false));
	}

	private function getFilledModelFromItem(Item\B2e\LegalLog $item, LegalLog $model): LegalLog
	{
		return $model
			->setCode($item->code)
			->setDocumentId($item->documentId)
			->setDocumentUid($item->documentUid)
			->setMemberId($item->memberId)
			->setMemberUid($item->memberUid)
			->setDescription($item->description)
			->setUserId($item->userId)
		;
	}

}
