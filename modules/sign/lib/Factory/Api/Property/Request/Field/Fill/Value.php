<?php

namespace Bitrix\Sign\Factory\Api\Property\Request\Field\Fill;

use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Service\Container;

final class Value
{
	private FileRepository $fileRepository;

	public function __construct()
	{
		$this->fileRepository = Container::instance()->getFileRepository();
	}

	public function createByValueItem(Item\Field\Value $value): ?Item\Api\Property\Request\Field\Fill\Value\BaseFieldValue
	{
		if ($value->text !== null)
		{
			return new Item\Api\Property\Request\Field\Fill\Value\StringFieldValue($value->text);
		}
		if ($value->fileId !== null)
		{
			$file = $this->fileRepository->getById($value->fileId, readContent: true);
			if ($file === null)
			{
				return null;
			}

			return new Item\Api\Property\Request\Field\Fill\Value\FileFieldValue($file->type, base64_encode($file->content->data));
		}

		return null;
	}
}