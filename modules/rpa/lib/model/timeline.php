<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Controller\Comment;
use Bitrix\Rpa\Driver;

class Timeline extends EO_Timeline
{
	public const ACTION_ITEM_CREATE = 'item_create';
	public const ACTION_STAGE_CHANGE = 'stage_change';
	public const ACTION_FIELDS_CHANGE = 'fields_change';
	public const ACTION_TASK_COMPLETE = 'task_complete';
	public const ACTION_COMMENT = 'comment';

	public static function createForItem(Item $item): Timeline
	{
		$timeline = TimelineTable::createObject();
		$timeline->setTypeId($item->getType()->getId());
		$timeline->setItemId($item->getId());

		return $timeline;
	}

	public function getTitle(): ?string
	{
		if(empty(parent::getTitle()))
		{
			$action = $this->getAction();
			if(!empty($action))
			{
				return Loc::getMessage('RPA_TIMELINE_TITLE_'.mb_strtoupper($action));
			}
		}

		return parent::getTitle();
	}

	public function preparePublicData(array $options = []): array
	{
		$isWithFiles = (!isset($options['withFiles']) || $options['withFiles'] !== false);
		$converter = Converter::toJson();

		$fields = $this->collectValues();
		$data = $fields['DATA'] ?? null;
		$fields['TITLE'] = $this->getTitle();
		$fields = $converter->process($fields);
		$fields['data'] = $data;
		$fields['createdTimestamp'] = ($this->getCreatedTime()->getTimestamp() * 1000);

		if($this->getAction() === static::ACTION_COMMENT)
		{
			$parser = Comment::getCommentParser($isWithFiles ? $this->getId() : 0);
			$fields['htmlDescription'] = $parser->getHtml($fields['description']);
			$fields['textDescription'] = $parser->getText($fields['description']);
			$uiComment = Comment::getUiComment();
			$files = $uiComment->getFileUserFields($this->getId());
			if(isset($files[Comment::USER_FIELD_FILES]['VALUE']))
			{
				$fields['data']['files'] = $files[Comment::USER_FIELD_FILES]['VALUE'];
			}
		}
		$fields['users'] = Base::getUsers([$this->getUserId()]);

		return $fields;
	}

	public function getItem(): ?Item
	{
		$type = Driver::getInstance()->getType($this->getTypeId());
		if($type)
		{
			return $type->getItem($this->getItemId());
		}

		return null;
	}
}