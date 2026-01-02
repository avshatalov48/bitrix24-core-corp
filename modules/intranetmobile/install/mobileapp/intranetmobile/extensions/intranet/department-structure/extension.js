/**
 * @module intranet/department-structure
 */
jn.define('intranet/department-structure', (require, exports, module) => {
	const { ReduxDepartmentStructure } = require('intranet/department-structure/src/redux-structure');

	module.exports = {
		/**
		 * @param {ReduxDepartmentStructureProps} props
		 * @returns {ReduxDepartmentStructure}
		 */
		ReduxDepartmentStructure: (props) => new ReduxDepartmentStructure(props),
	};
});
