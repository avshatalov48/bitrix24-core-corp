/**
 * @module layout/ui/fields/tag
 */
jn.define('layout/ui/fields/tag', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { Icon } = require('assets/icons');

	/**
	 * @class TagField
	 */
	class TagField extends EntitySelectorFieldClass
	{
		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				selectorType: (config.selectorType === '' ? 'tag' : config.selectorType),
			};
		}

		renderEntity(tag, showPadding = false)
		{
			return View(
				{
					style: this.styles.tag,
				},
				Text({
					style: this.styles.numberSign,
					text: '#',
				}),
				Text({
					style: this.styles.tagTitle,
					numberOfLines: 1,
					ellipsize: 'end',
					text: tag.title,
				}),
			);
		}

		shouldShowEditIcon()
		{
			return BX.prop.getBoolean(this.props, 'showEditIcon', false);
		}

		renderLeftIcons()
		{
			return null;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				tag: {
					flexDirection: 'row',
					alignItems: 'center',
					height: 24,
					borderRadius: 12,
					backgroundColor: AppTheme.colors.accentSoftBlue2,
					paddingVertical: 3,
					paddingHorizontal: 12,
					marginRight: 4,
					marginBottom: 5,
					flexShrink: 2,
				},
				numberSign: {
					color: AppTheme.colors.base5,
					fontSize: 14,
					marginRight: 2,
				},
				tagTitle: {
					fontSize: 14,
					flexShrink: 2,
				},
				wrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
				readOnlyWrapper: {
					paddingTop: (this.isLeftTitlePosition() ? 10 : 7),
					paddingBottom: (this.hasErrorMessage() ? 5 : 10),
				},
			};
		}

		getDefaultLeftIcon()
		{
			return Icon.TAG;
		}

		getAddButtonText()
		{
			return BX.message('FIELDS_TAG_ADD_BUTTON_TEXT');
		}
	}

	TagField.propTypes = {
		...EntitySelectorFieldClass.propTypes,
	};

	TagField.defaultProps = {
		...EntitySelectorFieldClass.defaultProps,
		showEditIcon: false,
	};

	module.exports = {
		TagType: 'tag',
		TagFieldClass: TagField,
		TagField: (props) => new TagField(props),
	};
});
