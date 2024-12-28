<?php
namespace Bitrix\Tasks\Rest\Controllers;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TemplateModel;
use Bitrix\Tasks\Access\TemplateAccessController;
use Bitrix\Main\Engine\Response;
use Bitrix\Tasks\Item\Task\Template as TaskTemplate;

class Template extends Base
{
	/**
	 * Return all DB and UF_ fields of task template
	 *
	 * @return array
	 */
	public function fieldsAction()
	{
		return \CTaskTemplates::getFieldsInfo();
	}

	/**
	 * Create new task template
	 *
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return bool|int
	 */
	public function addAction(array $fields, array $params = [])
	{
		$currentUserId = CurrentUser::get()->getId();

		if (!TemplateAccessController::can($currentUserId, ActionDictionary::ACTION_TEMPLATE_CREATE))
		{
			return false;
		}

		$fields = $this->filterFields($fields);

		if (
			array_key_exists('REPLICATE', $fields)
			&& $fields['REPLICATE'] === 'Y'
		)
		{
			$templateModel = TemplateModel::createFromArray($fields);
			if (!TemplateAccessController::can($currentUserId, ActionDictionary::ACTION_TEMPLATE_SAVE, null, $templateModel))
			{
				return false;
			}
		}

		if (array_key_exists('USER_ID', $params))
		{
			unset($params['USER_ID']);
		}
		if (array_key_exists('ID', $fields))
		{
			unset($fields['ID']);
		}

		return (new \CTaskTemplates())->Add($fields, $params);
	}

	/**
	 * Update existing task
	 *
	 * @param int $templateId
	 * @param array $fields See in tasks.api.task.fields
	 * @param array $params
	 *
	 * @return bool
	 */
	public function updateAction(int $templateId, array $fields, array $params = []): bool
	{
		$currentUserId = CurrentUser::get()->getId();

		$oldTemplate = \Bitrix\Tasks\Access\Model\TemplateModel::createFromId($templateId);
		$newTemplate = TemplateModel::createFromArray($fields);
		$isAccess = (new TemplateAccessController($currentUserId))->check(ActionDictionary::ACTION_TEMPLATE_SAVE, $oldTemplate, $newTemplate);
		if (!$isAccess)
		{
			return false;
		}

		$fields = $this->filterFields($fields);

		return (new \CTaskTemplates())->Update($templateId, $fields, $params);
	}

	/**
	 * Remove existing task
	 *
	 * @param int $templateId
	 * @param array $params
	 *
	 * @return bool
	 */
	public function deleteAction(int $templateId, array $params = []): bool
	{
		$currentUserId = CurrentUser::get()->getId();

		if (!TemplateAccessController::can($currentUserId, ActionDictionary::ACTION_TEMPLATE_EDIT, $templateId))
		{
			return false;
		}

		return \CTaskTemplates::Delete($templateId, $params);
	}

	/**
	 * Get list all task
	 *
	 * @param array $params
	 * @param PageNavigation $pageNavigation
	 *
	 * @return Response\DataType\Page
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function listAction(array $params = [], PageNavigation $pageNavigation = null)
	{
		$params['limit'] = $pageNavigation->getLimit();
		$params['offset'] = $pageNavigation->getOffset();

		$result = TaskTemplate::find($params);
		$select = isset($params['select']) ? $params['select'] : null;

		$list = [];
		foreach ($result as $item)
		{
			$list[$item->id] = $item->getData($select);
		}

		return new Response\DataType\Page(
			'task_templates',
			$list, function () use ($params) {
			return TaskTemplate::getCount((array)$params['filter']);
		}
		);
	}

	/**
	 * Get task item data
	 *
	 * @param int $templateId
	 * @param array $params
	 *
	 * @return bool|\CDBResult|\CTaskTemplates
	 */
	public function getAction($templateId, array $params = array())
	{
		$currentUserId = CurrentUser::get()->getId();

		if (!TemplateAccessController::can($currentUserId, ActionDictionary::ACTION_TEMPLATE_READ, $templateId))
		{
			return false;
		}

		$template = new \CTaskTemplates();
		$template = $template->GetByID($templateId, $params);
		$template = $template->Fetch();

		return $template;
	}

	public function createAction(array $fields): array
	{
		$userId = CurrentUser::get()->getId();

		foreach ($fields['ACCOMPLICES'] as $key => $value)
		{
			if (empty($value['ID']))
			{
				unset($fields['ACCOMPLICES'][$key]);
			}
			else
			{
				$fields['ACCOMPLICES'][$key] = $value['ID'];
			}
		}

		$template = new \Bitrix\Tasks\Control\Template($userId);
		$template->add($fields);

		return [];
	}

	public function getComponentAction(): Response\Component
	{
		return new Response\Component(
			"bitrix:tasks.task.template",
			".default",
			[
				'ENABLE_FOOTER' => false,
			],
			['HIDE_ICONS' => 'Y']
		);
	}
}