/**
 * @module tasks/layout/dod/src/list
 */
jn.define('tasks/layout/dod/src/list', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { ChecklistPreview } = require('tasks/layout/checklist/preview');

	/**
	 * @typedef {Object} DodChecklistsProps
	 * @property {Object} value
	 * @property {boolean} loading
	 * @property {Object} checklistController
	 *
	 * @class DodChecklists
	 */
	class DodChecklists extends PureComponent
	{
		render()
		{
			const { loading, checklistController, value } = this.props;

			return new ChecklistPreview({
				loading,
				value,
				hideTitle: true,
				config: {
					checklistController,
				},
				testId: 'DOD',
				showAddButton: false,
			});
		}
	}

	DodChecklists.propTypes = {
		value: PropTypes.object.isRequired,
		loading: PropTypes.bool.isRequired,
		checklistController: PropTypes.object,
	};

	module.exports = { DodChecklists };
});
