/**
 * @module selector/providers/tree-providers/nested-department-provider/src/entities/meta-user
 */
jn.define('selector/providers/tree-providers/nested-department-provider/src/entities/meta-user', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { BaseEntity } = require('selector/providers/tree-providers/nested-department-provider/src/entities/base-entity');

	/**
	 * @class MetaUserEntity
	 */
	class MetaUserEntity extends BaseEntity
	{
		static getId()
		{
			return 'meta-user';
		}

		getEntityForDialog()
		{
			return {
				...super.getEntityForDialog(),
				dynamicSearch: false,
			};
		}

		getEntityOptions()
		{
			return {
				emailUsers: true,
				inviteEmployeeLink: false,
			};
		}

		prepareItemForDrawing(item, initialEntity)
		{
			const metaUserImageStyles = {
				backgroundColor: Color.accentMainSuccess.toHex(),
				image: {
					tintColor: Color.baseWhiteFixed.toHex(),
					contentHeight: 26,
					borderRadiusPx: 6,
				},
				border: {
					color: Color.accentMainSuccess.toHex(),
					width: 2,
				},
			};

			return this.prepareCommonStyles({
				item,
				initialEntity,
				imageStyles: metaUserImageStyles,
				imageUrl: Icon.THREE_PERSONS.getPath(),
			});
		}
	}

	module.exports = { MetaUserEntity };
});
