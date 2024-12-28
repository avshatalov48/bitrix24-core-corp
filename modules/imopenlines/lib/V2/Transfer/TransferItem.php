<?php

namespace Bitrix\ImOpenLines\V2\Transfer;

abstract class TransferItem implements Transferable
{

	public static function getInstance(mixed $id): ?self
	{
		if (is_numeric($id))
		{
			return UserTransfer::getInstance($id);
		}

		if (is_string($id))
		{
			return QueueTransfer::getInstance($id);
		}

		return null;
	}
}