<?php
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\CheckListFacade;

class CheckList implements Errorable
{
	const CHECKLIST_COPY_ERROR = "CHECKLIST_COPY_ERROR";

	use ErrorableImplementation;

	/** @var CheckListFacade $facade */
	protected $facade;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Creates CheckLists to entity.
	 *
	 * @param integer $entityId Entity id.
	 * @param integer $executiveUserId Executive user id.
	 * @param array $checkListItems The CheckList fields.
	 */
	public function add($entityId, $executiveUserId, array $checkListItems)
	{
		/** @var CheckListFacade $facade */
		$facade = $this->facade;

		try
		{
			$roots = $facade::getObjectStructuredRoots($checkListItems, $entityId, $executiveUserId);
			foreach ($roots as $checkList)
			{
				/** @var \Bitrix\Tasks\CheckList\Internals\CheckList $checkList */
				$checkList->save();
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error(
				Loc::getMessage("COPY_CHECKLIST_SYSTEM_ERROR"), static::CHECKLIST_COPY_ERROR));
		}
	}

	/**
	 * Returns checklist items.
	 *
	 * @param integer $entityId Entity id.
	 * @return array|bool
	 */
	public function getCheckListItemsByEntityId($entityId)
	{
		$checkListItems = [];

		$facade = $this->facade;

		try
		{
			$checkListItems = $facade::getByEntityId($entityId);
			$checkListItems = array_map(
				function($item)
				{
					$item["COPIED_ID"] = $item["ID"];
					unset($item["ID"]);
					return $item;
				},
				$checkListItems
			);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error(
				Loc::getMessage("COPY_CHECKLIST_SYSTEM_ERROR"), static::CHECKLIST_COPY_ERROR));
		}

		return $checkListItems;
	}
}