<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Ui;

use Bitrix\Tasks;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util\Result;

final class Task extends \Bitrix\Tasks\Dispatcher\PublicAction
{
	/**
	 * Returns HTML of the edit form
	 *
	 * @param int $taskId
	 * @param array $parameters
	 * @return Result
	 */
	public function edit($taskId = 0, array $parameters = array())
	{
		$result = new Result();

		$componentParameters = array();
		if(is_array($parameters['COMPONENT_PARAMETERS']))
		{
			$componentParameters = $parameters['COMPONENT_PARAMETERS'];
		}
		$componentParameters = array_merge(array_intersect_key($componentParameters, array_flip(array(
			// component parameter white-list place here
			'GROUP_ID',
			'PATH_TO_USER_TASKS',
			'PATH_TO_USER_TASKS_TASK',
			'PATH_TO_GROUP_TASKS',
			'PATH_TO_GROUP_TASKS_TASK',
			'PATH_TO_USER_PROFILE',
			'PATH_TO_GROUP',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW',
			'PATH_TO_USER_TASKS_TEMPLATES',
			'PATH_TO_USER_TEMPLATES_TEMPLATE',
			'ENABLE_FOOTER',
			'ENABLE_FORM',

			'TEMPLATE_CONTROLLER_ID',
			'BACKURL',
		))), array(
			// component force-to parameters place here
			'ID' => $taskId,
			'SET_NAVCHAIN' => 'N',
			'SET_TITLE' => 'N',
			'SUB_ENTITY_SELECT' => array(
				'TAG',
				'CHECKLIST',
				'REMINDER',
				'PROJECTDEPENDENCE',
				'TEMPLATE',
				'RELATEDTASK'
			),
			'AUX_DATA_SELECT' => array(
				'COMPANY_WORKTIME',
				'USER_FIELDS',
			),
			'ENABLE_FOOTER_UNPIN' => 'N',
			'ENABLE_MENU_TOOLBAR' => 'N',
			//'REDIRECT_ON_SUCCESS' => 'N',
			'CANCEL_ACTION_IS_EVENT' => true,
		));

		Tasks\Dispatcher::globalDisable();

		$result->setData(static::getComponentHTML(
			"bitrix:tasks.task",
			"",
			$componentParameters
		));

		Tasks\Dispatcher::globalEnable();

		return $result;
	}
}