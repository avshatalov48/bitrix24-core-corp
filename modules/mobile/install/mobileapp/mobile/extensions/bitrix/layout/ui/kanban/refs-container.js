/**
 * @module layout/ui/kanban/refs-container
 */
jn.define('layout/ui/kanban/refs-container', (require, exports, module) => {
	/**
	 * @class RefsContainer
	 */
	class RefsContainer
	{
		constructor()
		{
			this.toolbar = null;
			this.currentStage = null;
		}

		/**
		 * @public
		 */
		reset()
		{
			this.toolbar = null;
			this.currentStage = null;
		}

		/**
		 * @public
		 * @param {object} currentStage
		 */
		setCurrentStage(currentStage)
		{
			this.currentStage = currentStage;
		}

		/**
		 * @public
		 * @return {StatefulList|null}
		 */
		getCurrentStage()
		{
			return this.currentStage;
		}

		/**
		 * @param {KanbanToolbar} toolbar
		 */
		setToolbar(toolbar)
		{
			this.toolbar = toolbar;
		}

		/**
		 * @returns {KanbanToolbar|null}
		 */
		getToolbar()
		{
			return this.toolbar;
		}
	}

	module.exports = { RefsContainer };
});
