/**
 * @module intranet/department-structure/src/redux-structure
 */
jn.define('intranet/department-structure/src/redux-structure', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { createTestIdGenerator } = require('utils/test');
	const { ReduxDepartmentCard } = require('ui-system/blocks/department-card');
	const { renderSkeleton } = require('intranet/department-structure/src/skeleton');
	const { Type } = require('type');
	const { Company } = require('intranet/department-structure/src/company');

	/**
	 * @typedef {Object} ReduxDepartmentStructureProps
	 * @property {string} testId
	 * @property {number|null} userId
	 * @property {Array} departmentIds
	 * @property {boolean} pending
	 * @property {Object} containerStyle
	 * @property {function} [onLayout]
	 * @property {boolean} [chevron = false]

	 * @class ReduxDepartmentStructure
	 */
	class ReduxDepartmentStructure extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				context: this,
			});
		}

		render()
		{
			const { containerStyle = {}, pending, onLayout } = this.props;

			if (pending)
			{
				return renderSkeleton();
			}

			const renderedDepartments = this.#getDepartmentIdsWithoutCompany().map(
				(departmentId, index) => this.#renderDepartment(departmentId, index),
			);

			return View(
				{
					onLayout,
					style: containerStyle,
				},
				this.#renderCompany(),
				...renderedDepartments,
			);
		}

		#getDepartmentIdsWithoutCompany()
		{
			return this.props.departmentIds.slice(1);
		}

		#renderCompany()
		{
			const { departmentIds, chevron = false } = this.props;
			if (Type.isArrayFilled(departmentIds))
			{
				return Company({
					departmentId: departmentIds[0],
					chevron,
				});
			}

			return null;
		}

		#renderDepartment = (departmentId, index) => {
			const departmentsCount = this.#getDepartmentIdsWithoutCompany().length;
			const { userId = null, chevron = false } = this.props;

			return ReduxDepartmentCard({
				departmentId,
				depth: index,
				userId,
				departmentsCount,
				chevron,
			});
		};
	}

	module.exports = {
		ReduxDepartmentStructure,
	};
});
