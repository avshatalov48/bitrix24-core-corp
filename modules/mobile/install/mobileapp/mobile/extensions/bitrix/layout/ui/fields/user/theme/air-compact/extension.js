/**
 * @module layout/ui/fields/user/theme/air-compact
 */
jn.define('layout/ui/fields/user/theme/air-compact', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { UserFieldClass } = require('layout/ui/fields/user');
	const { withTheme } = require('layout/ui/fields/theme');
	const { Chip } = require('ui-system/blocks/chips/chip');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView } = require('ui-system/blocks/icon');
	const { SafeImage } = require('layout/ui/safe-image');
	const { ColorScheme } = require('layout/ui/fields/base/theme/air-compact');
	const { withCurrentDomain } = require('utils/url');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { UserName } = require('layout/ui/user/user-name');

	const ICON_SIZE = 20;

	/**
	 * @class AirCompactThemeWrapper
	 */
	class AirCompactThemeWrapper extends LayoutComponent
	{
		render()
		{
			const field = this.getField();
			const testId = `${field.testId}_COMPACT_FIELD`;
			const {
				content: contentColor,
				border: borderColor,
			} = this.getColorScheme();

			let children = [];

			if (field.isEmpty())
			{
				children = [
					View(
						{
							testId: `${testId}_CONTENT_EMPTY_VIEW`,
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								flexShrink: 2,
							},
						},
						this.getLeftIcon(`${field.testId}_CONTENT_EMPTY_VIEW_ICON`),
						Text4({
							testId: `${testId}_COMPACT_FIELD_TEXT`,
							text: field.getTitleText(),
							color: contentColor,
							style: {
								marginLeft: Indent.XS.toNumber(),
								flexShrink: 2,
							},
							numberOfLines: 1,
							ellipsize: 'middle',
						}),
					),
				];
			}
			else
			{
				const entityList = field.getEntityList();
				const entities = entityList.map(({ id }) => id);
				const showName = entityList.length === 1;

				children = [
					this.getLeftIcon(`${testId}_ICON`),
					AvatarStack({
						testId: `${field.testId}_COMPACT_AVATAR_STACK`,
						entities,
						size: ICON_SIZE,
						visibleEntityCount: 3,
						withRedux: true,
						onClick: field.getContentClickHandler(),
					}),
					showName && this.renderUserName({ user: entityList[0], color: contentColor }),
				];
			}

			return Chip({
				testId: `${field.testId}_COMPACT_FIELD`,
				style: {
					maxWidth: 250,
				},
				onClick: field.getContentClickHandler(),
				indent: {
					left: Indent.M,
					right: Indent.L,
				},
				backgroundColor: Color.bgContentPrimary,
				borderColor,
				children,
			});
		}

		renderUserName(params)
		{
			const field = this.getField();
			const { user, color } = params;
			const { title, id } = user;

			return UserName({
				testId: `${field.testId}_COMPACT_USER_${id}_TITLE`,
				id,
				color,
				text: title,
				style: {
					marginLeft: Indent.XS.toNumber(),
					flexShrink: 2,
				},
				ellipsize: 'end',
				numberOfLines: 1,
			});
		}

		getLeftIcon(testId)
		{
			const field = this.getField();
			const leftIcon = field.getLeftIcon();
			const defaultLeftIcon = field.getDefaultLeftIcon();

			if (leftIcon.icon)
			{
				return this.renderIcon(testId);
			}

			if (leftIcon.uri)
			{
				return SafeImage({
					testId: 'leftIcon_image',
					uri: withCurrentDomain(leftIcon.uri),
					resizeMode: 'cover',
					style: {
						width: ICON_SIZE,
						height: ICON_SIZE,
						borderRadius: ICON_SIZE / 2,
						opacity: (field.isReadOnly() ? 0.22 : 1),
					},
				});
			}

			if (defaultLeftIcon)
			{
				return this.renderIcon(testId);
			}

			return null;
		}

		renderIcon(testId)
		{
			const color = this.getColorScheme();
			const field = this.getField();
			const defaultLeftIcon = field.getDefaultLeftIcon();
			const iconColor = field.isRestricted() ? Color.base1 : color.content;

			return IconView({
				testId,
				icon: defaultLeftIcon,
				color: iconColor,
				size: ICON_SIZE,
			});
		}

		/**
		 * @return {UserField}
		 */
		getField()
		{
			const { field } = this.props;

			return field;
		}

		/**
		 * @returns {ColorSchemeObject}
		 */
		getColorScheme()
		{
			const field = this.getField();

			let colors = ColorScheme.resolve(ColorScheme.DEFAULT);

			if (field.isReadOnly())
			{
				colors = ColorScheme.resolve(ColorScheme.READONLY);
			}
			else if (field.isEmpty())
			{
				colors = ColorScheme.resolve(ColorScheme.EMPTY);
			}
			else if (field.hasErrorMessage())
			{
				colors = ColorScheme.resolve(ColorScheme.ERROR);
			}

			return colors;
		}
	}

	module.exports = {
		UserField: withTheme(UserFieldClass, (props) => new AirCompactThemeWrapper(props)),
	};
});
