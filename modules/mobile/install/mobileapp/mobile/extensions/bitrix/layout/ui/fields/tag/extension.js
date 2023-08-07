/**
 * @module layout/ui/fields/tag
 */
jn.define('layout/ui/fields/tag', (require, exports, module) => {
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');

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
					backgroundColor: '#e5f9ff',
					paddingVertical: 3,
					paddingHorizontal: 12,
					marginRight: 4,
					marginBottom: 5,
				},
				numberSign: {
					color: '#bdc1c6',
					fontSize: 14,
					marginRight: 2,
				},
				tagTitle: {
					fontSize: 14,
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
	}

	module.exports = {
		TagType: 'tag',
		TagField: (props) => new TagField(props),
	};
});
