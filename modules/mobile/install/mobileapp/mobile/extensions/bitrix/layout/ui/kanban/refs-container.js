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
			this.statefulLists = new Map();
			this.slider = null;
			this.toolbar = null;
		}

		/**
		 * @param {String} columnName
		 * @param {StatefulList} statefulList
		 */
		setColumn(columnName, statefulList)
		{
			this.statefulLists.set(columnName, statefulList);
		}

		/**
		 * @param {String} columnName
		 * @returns {StatefulList|null}
		 */
		getColumn(columnName)
		{
			return this.statefulLists.get(columnName);
		}

		/**
		 * @param {String} columnName
		 * @returns {boolean}
		 */
		hasColumn(columnName)
		{
			return this.statefulLists.has(columnName);
		}

		/**
		 * @param {Slider} slider
		 */
		setSlider(slider)
		{
			this.slider = slider;
		}

		/**
		 * @returns {Slider|null}
		 */
		getSlider()
		{
			return this.slider;
		}

		/**
		 * @param {DealToolbar|LeadToolbar} toolbar
		 */
		setToolbar(toolbar)
		{
			this.toolbar = toolbar;
		}

		/**
		 * @returns {DealToolbar|null}
		 */
		getToolbar()
		{
			return this.toolbar;
		}

		/**
		 * @returns {boolean}
		 */
		hasToolbar()
		{
			return (this.toolbar !== null);
		}
	}

	module.exports = { RefsContainer }
});
