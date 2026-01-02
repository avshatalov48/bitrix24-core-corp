/**
 * @module ui-system/blocks/department-card
 */
jn.define('ui-system/blocks/department-card', (require, exports, module) => {
	const { connect } = require('statemanager/redux/connect');
	const { DepartmentCard } = require('ui-system/blocks/department-card/src/card');
	const { mapStateToProps } = require('ui-system/blocks/department-card/src/redux-provider');
	const { DepartmentCardMode } = require('ui-system/blocks/department-card/src/mode-enum');

	module.exports = {
		/**
		 * @param {DepartmentCardProps} props
		 * @returns {DepartmentCard}
		 */
		DepartmentCard: (props) => new DepartmentCard(props),
		DepartmentCardClass: DepartmentCard,
		DepartmentCardMode,
		ReduxDepartmentCard: connect(mapStateToProps)(DepartmentCard),
	};
});
