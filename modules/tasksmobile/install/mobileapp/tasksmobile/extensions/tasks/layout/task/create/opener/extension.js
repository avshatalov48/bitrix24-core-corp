/**
 * @module tasks/layout/task/create/opener
 */
jn.define('tasks/layout/task/create/opener', (require, exports, module) => {
	const { Feature } = require('feature');
	const { TaskCreate } = require('tasks/layout/task/create');
	const { CreateNew } = require('tasks/layout/task/create-new');

	/**
	 * @typedef {object} CreateTaskUserDto
	 * @property {number} id
	 * @property {string} name
	 * @property {string?} image
	 * @property {string?} link
	 * @property {string?} workPosition
	 */

	/**
	 * @typedef {object} CreateTaskFileDto
	 * @property {string} id format 'n123'
	 * @property {string} name
	 * @property {string} type
	 * @property {string} url
	 */

	/**
	 * @typedef {object} CreateTaskCrmElementDto
	 * @property {string} id
	 * @property {string} title
	 * @property {string} subtitle
	 * @property {string} type
	 * @property {boolean} hidden
	 */

	/**
	 * @param {{
	 *     initialTaskData?: {
	 *         responsible?: CreateTaskUserDto,
	 *         accomplices?: CreateTaskUserDto[],
	 *         auditors?: CreateTaskUserDto[],
	 *         priority?: '1' | '2',
	 *         deadline?: Date,
	 *         groupId?: number,
	 *         group?: { id: number, name: string, image: string, additionalData: {} },
	 *         flowId?: number,
	 *         tags?: { id: string, name: string }[],
	 *         crm?: CreateTaskCrmElementDto[],
	 *         files?: CreateTaskFileDto[],
	 *         IM_CHAT_ID?: number,
	 *         IM_MESSAGE_ID?: number,
	 *         parentId?: number,
	 *         relatedTaskId?: number,
	 *     },
	 *     view?: 'LIST' | 'KANBAN' | 'PLANNER' | 'DEADLINE',
	 *     closeAfterSave?: boolean,
	 *     stage?: { id: number, name: string, statusId: string } | null,
	 *     copyId?: number,
	 * }} data
	 */
	function openTaskCreateForm(data)
	{
		if (Feature.isAirStyleSupported())
		{
			const crm = data?.initialTaskData?.crm;

			// todo this conversion needed for compatibility with old-school task creation form
			// and should be dropped together with TaskCreate
			if (crm && typeof crm === 'object' && !Array.isArray(crm))
			{
				// eslint-disable-next-line no-param-reassign
				data.initialTaskData.crm = Object.values(crm);
			}

			CreateNew.open(data);
		}
		else
		{
			TaskCreate.open(data);
		}
	}

	module.exports = { openTaskCreateForm };
});
