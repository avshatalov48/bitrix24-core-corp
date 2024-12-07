/**
 * @module bizproc/in-app-url/routes
 */
jn.define('bizproc/in-app-url/routes', (require, exports, module) => {
	const { Type } = require('type');

	const openTask = (taskId, targetUserId, context) => {
		void requireLazy('bizproc:task/details')
			.then(({ TaskDetails }) => {
				if (
					TaskDetails
					&& Type.isNumber(parseInt(taskId, 10))
					&& (Type.isNil(targetUserId) || Type.isNumber(parseInt(targetUserId, 10)))
				)
				{
					void TaskDetails.open(
						context.parentWidget || PageManager,
						{ taskId, targetUserId },
					);
				}
			})
		;
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		inAppUrl
			.register(
				'/company/personal/bizproc/:taskId/$',
				({ taskId }, { context }) => {
					openTask(taskId, null, context);
				},
			)
			.name('bizproc:myTask')
		;
		inAppUrl
			.register(
				'/company/personal/bizproc/:taskId/\\?USER_ID=:userId',
				({ taskId, userId }, { context }) => {
					openTask(taskId, userId, context);
				},
			)
			.name('bizproc:task')
		;
		inAppUrl
			.register(
				'/bizproc/userprocesses/',
				(pathParams, props) => {
					// eslint-disable-next-line no-undef
					ComponentHelper.openLayout(
						{
							name: 'bizproc:tab',
							canOpenInDefault: true,
							componentParams: {
								setTitle: true,
							},
							object: 'layout',
						},
					);
				},
			)
			.name('bizproc:tab')
		;
	};
});
