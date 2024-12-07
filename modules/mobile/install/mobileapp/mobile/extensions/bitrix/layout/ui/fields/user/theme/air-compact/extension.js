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
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');
	const { Entity } = require('layout/ui/fields/user/theme/air-compact/src/entity');
	const { ColorScheme } = require('layout/ui/fields/base/theme/air-compact');
	const { withCurrentDomain } = require('utils/url');

	const ICON_SIZE = 20;

	/**
	 * @param {UserField} field
	 * @return {Chip}
	 * @constructor
	 */
	const AirCompactThemeWrapper = ({ field }) => {
		const testId = `${field.testId}_COMPACT_FIELD`;

		/** @type {{ content: Color, border: Color }} */
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

		const getLeftIcon = (additionalProps = {}) => {
			const leftIcon = field.getLeftIcon();
			const defaultLeftIcon = field.getDefaultLeftIcon();

			if (leftIcon.icon)
			{
				return IconView({
					icon: leftIcon.icon,
					color: (field.isRestricted() ? Color.base1 : colors.content),
					size: ICON_SIZE,
					...additionalProps,
				});
			}

			if (leftIcon.uri)
			{
				return SafeImage({
					uri: withCurrentDomain(leftIcon.uri),
					resizeMode: 'cover',
					style: {
						width: ICON_SIZE,
						height: ICON_SIZE,
						borderRadius: ICON_SIZE / 2,
						opacity: (field.isReadOnly() ? 0.22 : 1),
					},
					...additionalProps,
				});
			}

			if (defaultLeftIcon)
			{
				return IconView({
					icon: defaultLeftIcon,
					color: (field.isRestricted() ? Color.base1 : colors.content),
					size: ICON_SIZE,
					...additionalProps,
				});
			}

			return null;
		};

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
					getLeftIcon({ testId: `${field.testId}__CONTENT_EMPTY_VIEW_ICON` }),
					Text4({
						testId: `${testId}_COMPACT_FIELD_TEXT`,
						text: field.getTitleText(),
						color: colors.content,
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
			const showName = entityList.length === 1;

			children = [
				getLeftIcon({ testId: `${testId}_ICON` }),
				ElementsStack(
					{
						direction: ElementsStackDirection.RIGHT,
						indent: 1,
						offset: Indent.S,
						showRest: true,
						maxElements: 3,
						textColor: colors.content,
						style: {
							marginLeft: Indent.XS.toNumber(),
							opacity: entityList.length > 1 && field.isReadOnly() ? 0.22 : 1,
						},
					},
					...entityList.map((entity) => Entity({
						field,
						entity,
						avatarSize: ICON_SIZE,
						canOpenEntity: false,
						avatarStyle: {
							opacity: entityList.length === 1 && field.isReadOnly() ? 0.22 : 1,
						},
					})),
				),
				showName && Text4({
					testId: `${field.testId}_COMPACT_USER_${entityList[0].id}_TITLE`,
					text: entityList[0].title,
					color: colors.content,
					style: {
						marginLeft: Indent.XS.toNumber(),
						flexShrink: 2,
					},
					ellipsize: 'end',
					numberOfLines: 1,
				}),
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
			borderColor: colors.border,
			children,
		});
	};

	/** @type {function(object): object} */
	const UserField = withTheme(UserFieldClass, AirCompactThemeWrapper);

	module.exports = {
		UserField,
	};
});
