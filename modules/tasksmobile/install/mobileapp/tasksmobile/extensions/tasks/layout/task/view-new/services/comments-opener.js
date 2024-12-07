/**
 * @module tasks/layout/task/view-new/services/comments-opener
 */
jn.define('tasks/layout/task/view-new/services/comments-opener', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { guid } = require('utils/guid');

	class CommentsOpener
	{
		#isComponentReady;
		#widgetGuid;
		#closingEventInterval;

		constructor()
		{
			this.#isComponentReady = false;
			this.#widgetGuid = guid();
			this.#closingEventInterval = 0;

			BX.addCustomEvent('tasks.task.comments:onComponentReady', () => {
				this.#isComponentReady = true;
			});
		}

		openCommentsWidget(taskId)
		{
			PageManager.openPage({
				backgroundColor: Color.bgSecondary.toHex(),
				url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=comments&TASK_ID=${taskId}&IS_TABS_MODE=false&widgetGuid=${this.#widgetGuid}`,
				titleParams: {
					text: Loc.getMessage('M_TASK_DETAILS_COMMENTS_TITLE'),
					type: 'dialog',
				},
				loading: {
					type: 'comments',
				},
				modal: false,
				cache: true,
			});
		}

		closeCommentsWidget(taskId)
		{
			if (!this.#closingEventInterval)
			{
				this.#closingEventInterval = setInterval(() => {
					if (this.#isComponentReady)
					{
						clearInterval(this.#closingEventInterval);
						this.#closingEventInterval = 0;
						this.#isComponentReady = false;

						BX.postWebEvent('tasks-view-new:onTaskForbidden', { taskId });
					}
				}, 100);
			}
		}
	}

	module.exports = { CommentsOpener };
});
