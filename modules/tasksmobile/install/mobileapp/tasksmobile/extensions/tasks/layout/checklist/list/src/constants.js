/**
 * @module tasks/layout/checklist/list/src/constants
 */
jn.define('tasks/layout/checklist/list/src/constants', (require, exports, module) => {
	const pathToExtension = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/checklist/list/`;

	const directions = {
		LEFT: 'left',
		RIGHT: 'right',
	};

	const CHECKBOX_SIZE = 24;
	const MEMBER_TYPE = {
		accomplice: 'accomplice',
		auditor: 'auditor',
	};

	module.exports = { pathToExtension, directions, CHECKBOX_SIZE, MEMBER_TYPE };
});
