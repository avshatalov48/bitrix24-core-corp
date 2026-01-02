/**
 * @module ui-system/blocks/reaction/icon
 */
jn.define('ui-system/blocks/reaction/icon', (require, exports, module) => {
	const { Component, Color } = require('tokens');
	const { ReactionIcon } = require('assets/icons');
	const { withCurrentDomain } = require('utils/url');

	const DEFAULT_SIZE = 20;
	const REACTION_ICON_TYPE = {
		svg: 'svg',
		png: 'png',
	};

	/**
	 * @typedef {Object} ReactionIconViewProps
	 * @property {ReactionIcon} icon
	 * @property {number} [size]
	 * @property {string} testId
	 */

	class ReactionIconView extends LayoutComponent
	{
		render()
		{
			const shouldRenderOffset = this.getOffset() > 0;

			return shouldRenderOffset
				? this.renderOffsetWrapper(this.renderReactionImage())
				: this.renderReactionImage();
		}

		renderOffsetWrapper(content)
		{
			return View(
				{
					style: {
						borderRadius: Component.elementAccentCorner.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
						padding: this.getOffset(),
						...this.getStyle(),
					},
				},
				content,
			);
		}

		renderReactionImage()
		{
			const { icon, testId, resizeMode, type = REACTION_ICON_TYPE.svg } = this.props;
			const size = this.getSize();

			return Image({
				style: {
					width: size,
					height: size,
					...this.getStyle(),
				},
				testId,
				resizeMode,
				...this.getImagePropsByType(icon, type),
			});
		}

		getSize()
		{
			const { size } = this.props;

			return size ?? DEFAULT_SIZE;
		}

		getOffset()
		{
			const { offset } = this.props;

			return offset || 0;
		}

		getStyle()
		{
			const { style } = this.props;

			return style || {};
		}

		getImagePropsByType(icon, type)
		{
			if (type === REACTION_ICON_TYPE.svg)
			{
				return { svg: { uri: withCurrentDomain(icon.getPath()) } };
			}

			return { uri: withCurrentDomain(icon.getImageUrl()) };
		}
	}

	ReactionIconView.propTypes = {
		icon: PropTypes.instanceOf(ReactionIcon).isRequired,
		size: PropTypes.oneOfType([
			PropTypes.number,
			PropTypes.exact({
				height: PropTypes.number,
				width: PropTypes.number,
			}),
		]),
		testId: PropTypes.string,
		type: PropTypes.string,
	};

	module.exports = {
		/**
		 * @param {ReactionIconViewProps} props
		 */
		ReactionIconView: (props) => new ReactionIconView(props), ReactionIcon,
		REACTION_ICON_TYPE,
	};
});
