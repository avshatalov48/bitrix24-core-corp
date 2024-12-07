<?php

namespace Bitrix\Sign\Repository;

use Bitrix\Main\Result;
use Bitrix\Sign\Item\Integration\Im\DocumentChat;
use Bitrix\Sign\internal;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;

class DocumentChatRepository
{
	public function add(DocumentChat $item): Result
	{
		$filledDocumentChatEntity = $this->extractModelFromItem($item);

		$saveResult = $filledDocumentChatEntity->save();

		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return (new Result())->setData(['documentChat' => $item]);
	}

	private function extractModelFromItem(DocumentChat $item): internal\DocumentChat
	{
		return $this->getFilledModelFromItem($item);
	}

	private function extractItemFromModel(?internal\DocumentChat $model): DocumentChat|null
	{
		if (!$model)
		{
			return null;
		}

		return new DocumentChat(
			documentId: $model->getDocumentId(),
			chatId:     $model->getChatId(),
			type:       DocumentChatType::from($model->getType()),
			id:         $model->getId(),
		);
	}

	private function getFilledModelFromItem(DocumentChat $item): internal\DocumentChat
	{
		$model = internal\DocumentChatTable::createObject(true);

		return $model
			->setDocumentId($item->documentId)
			->setChatId($item->chatId)
			->setType($item->type->value)
		;
	}

	public function getByDocumentIdAndType(int $documentId, DocumentChatType $chatType): DocumentChat|null
	{
		$model = internal\DocumentChatTable
			::query()
			->addSelect('*')
			->where('DOCUMENT_ID', $documentId)
			->where('TYPE', $chatType->value)
			->setLimit(1)
		;

		return $this->extractItemFromModel($model->fetchObject());
	}
}