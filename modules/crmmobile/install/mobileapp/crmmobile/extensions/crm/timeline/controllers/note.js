/**
 * @module crm/timeline/controllers/note
 */
jn.define('crm/timeline/controllers/note', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');

	const SupportedActions = {
		START_EDIT: 'Note:StartEdit',
	};

	/**
	 * @class TimelineNoteController
	 */
	class TimelineNoteController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			switch (action)
			{
				case SupportedActions.START_EDIT:
					return this.openNoteEditor();
			}
		}

		/**
		 * @private
		 */
		openNoteEditor()
		{
			this.itemScopeEventBus.emit('Crm.Timeline.Item.OpenNoteEditorRequest');
		}
	}

	module.exports = { TimelineNoteController };
});
