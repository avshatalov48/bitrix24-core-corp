/**
 * @module layout/ui/fields/tag
 */
jn.define('layout/ui/fields/tag', (require, exports, module) => {
	const {EntitySelectorFieldClass} = require('layout/ui/fields/entity-selector');
	const {Type} = require('type');

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

		renderEmptyContent()
		{
			return this.renderEmptyTags();
		}

		renderEmptyEntity()
		{
			return this.renderEmptyTags();
		}

		renderEmptyTags()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				this.renderTag(),
				View(
					{
						style: {
							flexBasis: '40%',
						},
					},
					this.renderTag(),
				),
				this.renderTag(),
			);
		}

		renderEntity(tag = {}, showPadding = false)
		{
			return this.renderTag(tag);
		}

		renderTag(tag = null)
		{
			const isEmpty = Type.isNil(tag);

			return View(
				{
					style: this.styles.tag(isEmpty),
				},
				Text({
					style: this.styles.numberSign,
					text: '#',
				}),
				this.renderTagTitle(tag),
			);
		}

		renderTagTitle(tag)
		{
			const isEmpty = Type.isNil(tag);

			if (isEmpty)
			{
				return View(
					{
						style: {
							flex: 1,
							height: 5,
							borderRadius: 10,
							backgroundColor: '#bdc1c6',
						},
					},
				);
			}

			return Text({
				style: this.styles.tagTitle,
				numberOfLines: 1,
				ellipsize: 'end',
				text: tag.title,
			});
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
				tag: (isEmpty) => ({
					flex: (isEmpty ? 1 : undefined),
					flexDirection: 'row',
					alignItems: 'center',
					height: 24,
					borderRadius: 12,
					backgroundColor: (isEmpty ? '#efefef' : '#e5f9ff'),
					paddingVertical: 3,
					paddingHorizontal: 12,
					marginRight: 4,
					marginBottom: 5,
				}),
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
		TagField: props => new TagField(props),
	};
});
