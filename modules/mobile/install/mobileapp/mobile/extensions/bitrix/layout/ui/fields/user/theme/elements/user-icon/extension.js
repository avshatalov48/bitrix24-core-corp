/**
 * @module layout/ui/fields/user/theme/elements/user-icon
 */
jn.define('layout/ui/fields/user/theme/elements/user-icon', (require, exports, module) => {
	const { ReduxAvatar } = require('layout/ui/user/avatar');

	/**
	 * @param {UserField} field
	 * @param {object} entity
	 * @param {number} entity.id
	 * @param {string} entity.title
	 * @param {string} entity.imageUrl
	 * @param {string} entity.avatar
	 * @param {number} size
	 * @param {boolean} [canOpenEntity=true]
	 * @param {object} [additionalStyles]
	 */
	const UserAvatar = ({
		field,
		entity,
		size,
		canOpenEntity = true,
		additionalStyles = {},
	}) => {
		const onClick = field.openEntity.bind(field, entity.id);

		return ReduxAvatar({
			id: entity.id,
			size,
			testId: `${field.testId}_USER_${entity.id}_ICON`,
			onClick: canOpenEntity && onClick,
			additionalStyles: {
				wrapper: additionalStyles,
			},
		});
	};

	module.exports = {
		UserAvatar,
	};
});
