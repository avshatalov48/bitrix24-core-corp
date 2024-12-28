<?php
namespace Bitrix\Tasks\Integration\Disk\Connector\CheckList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Disk\Connector\Task as TaskConnector;
use Bitrix\Tasks\Internals\Task\Template\CheckListTable;
use Bitrix\Tasks\Item\Task\Template as TemplateItem;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Tasks\Access\ActionDictionary;

/**
 * Class Template
 *
 * @package Bitrix\Tasks\Integration\Disk\Connector\CheckList
 */
class Template extends TaskConnector
{
	/**
	 * @return string
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function getTitle()
	{
		$templateId = static::getTemplateIdByCheckList($this->entityId);
		return Loc::getMessage('DISK_UF_CHECKLIST_TEMPLATE_CONNECTOR_TITLE', ['#ID#' => $templateId]);
	}

	/**
	 * @param $userId
	 * @return array|bool|mixed|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function loadTaskData($userId)
	{
		if ($this->taskPostData === null)
		{
			$templateId = static::getTemplateIdByCheckList($this->entityId);

			$template = new TemplateItem($templateId, $userId);
			$this->taskPostData = $template->getData();
		}

		return $this->taskPostData;
	}

	public function canRead($userId): bool
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		$templateId = static::getTemplateIdByCheckList($this->entityId);

		$this->canRead = TemplateAccessController::can($userId, ActionDictionary::ACTION_TEMPLATE_READ, $templateId);

		return $this->canRead;
	}

	public function canUpdate($userId): bool
	{
		return $this->canRead($userId);
	}

	/**
	 * @param $checkListId
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function getTemplateIdByCheckList($checkListId)
	{
		return CheckListTable::getList(['select' => ['TEMPLATE_ID'], 'filter' => ['ID' => $checkListId]])->fetch()['TEMPLATE_ID'];
	}

	/**
	 * @param $authorId
	 * @param array $data
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}
}
