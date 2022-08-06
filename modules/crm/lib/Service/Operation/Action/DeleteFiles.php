<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class DeleteFiles extends Action
{
	/** @var Field\Collection */
	private $fileFields;

	public function __construct(Field\Collection $fileFields)
	{
		parent::__construct();
		$this->fileFields = $fileFields;
	}

	public function process(Item $item): Result
	{
		$result = new Result();

		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			$result->addError(new Error('itemBeforeSave is required in ' . static::class));

			return $result;
		}

		$fileUploader = Container::getInstance()->getFileUploader();
		foreach ($this->fileFields as $field)
		{
			$fileIds = (array)$itemBeforeSave->get($field->getName());
			foreach ($fileIds as $fileId)
			{
				$fileUploader->deleteFilePersistently((int)$fileId);
			}
		}

		return $result;
	}
}
