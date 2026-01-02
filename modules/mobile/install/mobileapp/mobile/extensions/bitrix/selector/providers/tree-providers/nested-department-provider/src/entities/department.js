/**
 * @module selector/providers/tree-providers/nested-department-provider/src/entities/department
 */

jn.define('selector/providers/tree-providers/nested-department-provider/src/entities/department', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { BaseEntity } = require('selector/providers/tree-providers/nested-department-provider/src/entities/base-entity');
	const { Navigator } = require('selector/widget/entity/tree-selectors/shared/navigator');

	/**
	 * @class DepartmentEntity
	 */
	class DepartmentEntity extends BaseEntity
	{
		constructor(options)
		{
			super();
			this.options = options;
			this.root = null;
		}

		static getId()
		{
			return 'department';
		}

		setRoot(root)
		{
			this.root = root;
		}

		getEntityOptions()
		{
			const {
				shouldAddCounters,
				allowFlatDepartments,
				allowSelectRootDepartment,
			} = this.options;

			return {
				allowFlatDepartments,
				allowSelectRootDepartment,
				selectMode: 'usersAndDepartments',
				shouldCountSubdepartments: shouldAddCounters(),
				shouldCountUsers: shouldAddCounters(),
			};
		}

		prepareItemForDrawing(item, initialEntity)
		{
			const departmentImageStyles = {
				backgroundColor: Color.accentExtraAqua.toHex(),
				image: {
					tintColor: Color.baseWhiteFixed.toHex(),
					contentHeight: 26,
					borderRadiusPx: 6,
				},
				border: {
					color: Color.accentExtraAqua.toHex(),
					width: 2,
				},
			};

			return this.prepareCommonStyles({
				item,
				initialEntity,
				imageStyles: departmentImageStyles,
				imageUrl: Icon.GROUP.getPath(),
			});
		}

		findItem(id, items)
		{
			return Navigator.findInTree(this.root, (item) => this.isEqualById(item, id));
		}
	}

	module.exports = { DepartmentEntity };
});
