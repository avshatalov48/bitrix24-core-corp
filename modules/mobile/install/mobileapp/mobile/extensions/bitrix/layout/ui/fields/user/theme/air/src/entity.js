/**
 * @module layout/ui/fields/user/theme/air/src/entity
 */
jn.define('layout/ui/fields/user/theme/air/src/entity', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { Text6 } = require('ui-system/typography/text');
	const { UserName } = require('layout/ui/user/user-name');

	const AVATAR_SIZE = 32;

	/**
	 * @typedef {Object} AitEntityProps
	 * @property {UserField} field
	 * @property {UserEntity} entity
	 * @property {boolean} showTitle
	 *
	 * @typedef {Object} UserEntity
	 * @property {string | number} id
	 * @property {string} title
	 */

	class AirEntity extends LayoutComponent
	{
		render()
		{
			const { id, title } = this.getEntity();
			const field = this.getField();

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						flexShrink: 2,
					},
				},
				View(
					{
						testId: `${field.testId}_CONTENT`,
					},
					Avatar({
						id,
						testId: `${field.testId}_USER_${id}_ICON`,
						withRedux: true,
						size: AVATAR_SIZE,
						onClick: this.handleOnClickAvatar,
					}),
				),
				View(
					{
						testId: `${field.testId}_CONTENT`,
						style: {
							flexDirection: 'column',
							marginLeft: Indent.M.toNumber(),
							flexShrink: 2,
						},
					},
					this.shouldShowTitle() && Text6({
						testId: `${field.testId}_TITLE`,
						style: {
							color: Color.base4.toHex(),
							marginBottom: Indent.XS2.toNumber(),
							flexShrink: 2,
						},
						text: field.getTitleText(),
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					UserName({
						id,
						text: title,
						testId: `${field.testId}_USER_${id}_VALUE`,
						style: {
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'end',
					}),
				),
			);
		}

		shouldShowTitle()
		{
			const { showTitle = true } = this.props;

			return Boolean(showTitle);
		}

		/**
		 * @returns {UserField}
		 */
		getField()
		{
			const { field } = this.props;

			return field;
		}

		/**
		 * @returns {UserEntity}
		 */
		getEntity()
		{
			const { entity } = this.props;

			return entity || {};
		}

		handleOnClickAvatar = () => {
			const field = this.getField();

			const { id } = this.getEntity();
			field?.openEntity(id);
		};
	}

	module.exports = {
		/**
		 * @param {AitEntityProps} props
		 */
		Entity: (props) => new AirEntity(props),
		AVATAR_SIZE,
	};
});
