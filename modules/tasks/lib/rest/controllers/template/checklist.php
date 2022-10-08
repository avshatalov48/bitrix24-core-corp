<?php
namespace Bitrix\Tasks\Rest\Controllers\Template;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\CheckList\Internals\CheckList as CheckListItem;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Rest\Controllers\Base;
use Bitrix\Tasks\Util\Result;

Loc::loadMessages(__FILE__);

/**
 * Class Checklist
 *
 * @package Bitrix\Tasks\Rest\Controllers\Template
 */
class Checklist extends Base
{
	/**
	 * @return array
	 */
	public function getAutoWiredParameters()
	{
		return [
			new ExactParameter(
				CheckListItem::class,
				'checkListItem',
				static function ($className, $checkListItemId)
				{
					$userId = CurrentUser::get()->getId();
					$fields = TemplateCheckListFacade::getList([], ['ID' => $checkListItemId])[$checkListItemId];

					/** @var ChecklistItem $className */
					return new $className(0, $userId, TemplateCheckListFacade::class, $fields);
				}
			),
		];
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 */
	public function getAction($templateId, CheckListItem $checkListItem)
	{
		if (!TemplateAccessController::can((int) CurrentUser::get()->getId(), ActionDictionary::ACTION_TEMPLATE_READ, (int) $templateId))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TEMPLATE_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		return $this->getReturnValue($checkListItem);
	}

	/**
	 * @param $templateId
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function listAction($templateId, array $filter = [], array $select = [], array $order = [])
	{
		if (!TemplateAccessController::can((int) CurrentUser::get()->getId(), ActionDictionary::ACTION_TEMPLATE_READ, (int) $templateId))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('TASKS_REST_TEMPLATE_CHECKLIST_ACCESS_DENIED'))]);
			return null;
		}

		$filter['TEMPLATE_ID'] = $templateId;
		$items = TemplateCheckListFacade::getList($select, $filter, $order);

		foreach (array_keys($items) as $id)
		{
			unset($items[$id]['ENTITY_ID'], $items[$id]['UF_CHECKLIST_FILES']);
		}

		return ['checkListItems' => $this->convertKeysToCamelCase($items)];
	}

	/**
	 * @param $templateId
	 * @param array $fields
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function addAction($templateId, array $fields)
	{
		$addResult = TemplateCheckListFacade::add($templateId, CurrentUser::get()->getId(), $fields);
		return $this->getReturn($addResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $fields
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function updateAction($templateId, CheckListItem $checkListItem, array $fields)
	{
		$updateResult = TemplateCheckListFacade::update(
			$templateId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$fields
		);

		return $this->getReturn($updateResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @return bool|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function deleteAction($templateId, CheckListItem $checkListItem)
	{
		$deleteResult = TemplateCheckListFacade::delete($templateId, CurrentUser::get()->getId(), $checkListItem);

		if ($deleteResult->isSuccess())
		{
			return true;
		}

		if ($deleteResult->getErrors())
		{
			$this->errorCollection[] = new Error($deleteResult->getErrors()->getMessages()[0]);
		}

		return null;
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function completeAction($templateId, CheckListItem $checkListItem)
	{
		$completeResult = TemplateCheckListFacade::complete($templateId, CurrentUser::get()->getId(), $checkListItem);
		return $this->getReturn($completeResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function renewAction($templateId, CheckListItem $checkListItem)
	{
		$renewResult = TemplateCheckListFacade::renew($templateId, CurrentUser::get()->getId(), $checkListItem);
		return $this->getReturn($renewResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param $beforeItemId
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function moveBeforeAction($templateId, CheckListItem $checkListItem, $beforeItemId)
	{
		$userId = CurrentUser::get()->getId();
		$position = TemplateCheckListFacade::MOVING_POSITION_BEFORE;
		$moveResult = TemplateCheckListFacade::moveItem($templateId, $userId, $checkListItem, $beforeItemId, $position);

		return $this->getReturn($moveResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param $afterItemId
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public function moveAfterAction($templateId, CheckListItem $checkListItem, $afterItemId)
	{
		$userId = CurrentUser::get()->getId();
		$position = TemplateCheckListFacade::MOVING_POSITION_AFTER;
		$moveResult = TemplateCheckListFacade::moveItem($templateId, $userId, $checkListItem, $afterItemId, $position);

		return $this->getReturn($moveResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $members
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function addMembers($templateId, CheckListitem $checkListItem, array $members)
	{
		$userId = CurrentUser::get()->getId();
		$addMembersResult = TemplateCheckListFacade::addMembers($templateId, $userId, $checkListItem, $members);

		return $this->getReturn($addMembersResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $membersIds
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function removeMembers($templateId, CheckListItem $checkListItem, array $membersIds)
	{
		$userId = CurrentUser::get()->getId();
		$removeMembersResult = TemplateCheckListFacade::removeMembers($templateId, $userId, $checkListItem, $membersIds);

		return $this->getReturn($removeMembersResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $attachmentParameters
	 * @return array|null
	 */
	public function addAttachmentByContentAction($templateId, CheckListItem $checkListItem, array $attachmentParameters)
	{
		$addAttachmentResult = TemplateCheckListFacade::addAttachmentByContent(
			$templateId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$attachmentParameters
		);

		return $this->getReturn($addAttachmentResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $filesIds
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function addAttachmentsFromDiskAction($templateId, CheckListItem $checkListItem, array $filesIds)
	{
		$addAttachmentsResult = TemplateCheckListFacade::addAttachmentsFromDisk(
			$templateId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$filesIds
		);

		return $this->getReturn($addAttachmentsResult);
	}

	/**
	 * @param $templateId
	 * @param CheckListItem $checkListItem
	 * @param array $attachmentsIds
	 * @return array|null
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @throws ObjectException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public function removeAttachmentsAction($templateId, CheckListItem $checkListItem, array $attachmentsIds)
	{
		$removeAttachmentsResult = TemplateCheckListFacade::removeAttachments(
			$templateId,
			CurrentUser::get()->getId(),
			$checkListItem,
			$attachmentsIds
		);

		return $this->getReturn($removeAttachmentsResult);
	}

	/**
	 * @param Result $result
	 * @return array|null
	 */
	private function getReturn(Result $result)
	{
		if ($result->isSuccess())
		{
			return $this->getReturnValue($result);
		}

		if ($errors = $result->getErrors())
		{
			$this->errorCollection[] = new Error($errors->getMessages()[0]);
		}

		return null;
	}

	/**
	 * @param $value
	 * @return array
	 */
	private function getReturnValue($value)
	{
		$checkListItemData = [];

		if ($value instanceof Result)
		{
			/** @var CheckListItem $checkListItem */
			$checkListItem = $value->getData()['ITEM'];
			$checkListItemData = $checkListItem->getFields();
		}
		else if ($value instanceof CheckListItem)
		{
			$checkListItemData = $value->getFields();
		}

		$checkListItemData['TEMPLATE_ID'] = $checkListItemData['ENTITY_ID'];
		unset($checkListItemData['ENTITY_ID']);

		return ['checkListItem' => $this->convertKeysToCamelCase($checkListItemData)];
	}
}