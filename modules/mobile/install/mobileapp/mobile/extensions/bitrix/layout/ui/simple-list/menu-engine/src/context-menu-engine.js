/**
 * @module layout/ui/simple-list/menu-engine/src/context-menu-engine
 */
jn.define('layout/ui/simple-list/menu-engine/src/context-menu-engine', (require, exports, module) => {
	const { ContextMenu } = require('layout/ui/context-menu');
	const { BaseMenuEngine } = require('layout/ui/simple-list/menu-engine/src/base-menu-engine');

	class ContextMenuEngine extends BaseMenuEngine
	{
		constructor({ parent, parentId, testId, updateItemHandler, analyticsLabel })
		{
			super();
			this.parent = parent;
			this.parentId = parentId;
			this.testId = testId;
			this.updateItemHandler = updateItemHandler;
			this.analyticsLabel = analyticsLabel;
		}

		show(actions, options)
		{
			this.menu = new ContextMenu({
				parent: this.parent,
				parentId: this.parentId,
				id: `SimpleList-${this.parentId}`,
				actions,
				updateItemHandler: this.updateItemHandler,
				params: {
					showCancelButton: true,
					showPartiallyHidden: actions.length > 7,
					mediumPositionPercent: 51,
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

	module.exports = { ContextMenuEngine };
});
