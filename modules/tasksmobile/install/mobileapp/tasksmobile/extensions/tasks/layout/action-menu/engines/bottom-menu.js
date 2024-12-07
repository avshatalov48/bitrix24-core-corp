/**
 * @module tasks/layout/action-menu/engines/bottom-menu
 */
jn.define('tasks/layout/action-menu/engines/bottom-menu', (require, exports, module) => {
	const { BaseEngine } = require('tasks/layout/action-menu/engines/base');
	const { ContextMenu } = require('layout/ui/context-menu');

	class BottomMenuEngine extends BaseEngine
	{
		constructor({ title, testId, analyticsLabel })
		{
			super();

			this.title = title;
			this.testId = testId;
			this.analyticsLabel = analyticsLabel;
		}

		show(actions, options)
		{
			this.menu = new ContextMenu({
				actions,
				params: {
					showCancelButton: true,
					isRawIcon: true,
					title: this.title,
				},
				testId: this.testId,
				analyticsLabel: this.analyticsLabel,
			});
			void this.menu.show();
		}

		close(callback)
		{
			this.menu?.close(callback);
		}
	}

	module.exports = { BottomMenuEngine };
});
