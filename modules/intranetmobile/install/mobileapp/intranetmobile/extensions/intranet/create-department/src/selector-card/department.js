/**
 * @module intranet/create-department/src/selector-card/department
 */

jn.define('intranet/create-department/src/selector-card/department', (require, exports, module) => {
	const { BaseSelectorCard } = require('intranet/create-department/src/selector-card/base');
	const { DepartmentSelector } = require('selector/widget/entity/intranet/department');

	/**
	 * @class SelectorCard
	 */
	class DepartmentSelectorCard extends BaseSelectorCard
	{
		onClick = () => {
			this.openDepartmentSelector();
		};

		openDepartmentSelector()
		{
			const { parentWidget, selectedId, onSelected, onViewHidden } = this.props;

			DepartmentSelector.make({
				provider: {
					options: {
						selectMode: 'departmentsOnly',
						restricted: 'create',
					},
				},
				createOptions: {
					enableCreation: false,
				},
				initSelectedIds: selectedId ? [selectedId] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: onSelected,
					onViewHidden,
				},
			}).show({}, parentWidget ?? PageManager);
		}
	}

	module.exports = {
		/**
		 * @param {SelectorCardProps} props
		 * @returns {DepartmentSelectorCard}
		 */
		DepartmentSelectorCard: (props) => new DepartmentSelectorCard(props),
	};
});
