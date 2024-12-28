/**
 * @module ui-system/blocks/banners/card-banner
 */
jn.define('ui-system/blocks/banners/card-banner', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { H5 } = require('ui-system/typography/heading');
	const { Text6 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');

	/**
	 * @typedef {Object} CardBannerProps
	 * @param {string} testId
	 * @param {string | object} [title]
	 * @param {string | object} [description]
	 * @param {boolean} [hideCross=false]
	 * @param {CardDesign} [design=CardDesign.ACCENT]
	 * @param {image} [image]
	 * @param {function} [onClose=null]
	 * @param {function} [onClick=null]
	 *
	 * @function CardBanner
	 */
	class CardBanner extends LayoutComponent
	{
		get testId()
		{
			const { testId } = this.props;

			return testId;
		}

		renderImage()
		{
			const { image } = this.props;

			return image;
		}

		renderHeader()
		{
			const { title } = this.props;

			return this.renderText({
				type: 'title',
				text: title,
				typography: H5,
				style: {
					marginBottom: Indent.XS.toNumber(),
				},
			});
		}

		renderDescription()
		{
			const { description } = this.props;

			return this.renderText({
				type: 'description',
				text: description,
				typography: Text6,
				style: {},
			});
		}

		renderText({ text, typography, style, type })
		{
			let viewText = null;

			if (text)
			{
				viewText = typeof text === 'string'
					? typography({ text, style })
					: View({ style }, text);
			}

			return viewText;
		}

		renderBody()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingLeft: Indent.XL.toNumber(),
						paddingBottom: Indent.M.toNumber(),
					},
				},
				this.renderHeader(),
				this.renderDescription(),
				this.renderButtons(),
			);
		}

		renderButtons()
		{
			const { buttons } = this.props;

			if (buttons.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: Indent.XL.toNumber(),
						paddingBottom: Indent.XS2.toNumber(),
					},
				},
				...buttons.map((button, i) => View({
					testId: `${this.testId}_actionButton_${i + 1}`,
					style: {
						flex: 1,
						marginRight: Indent.L.toNumber(),
					},
				}, button)),
			);
		}

		render()
		{
			const {
				hideCross = false,
				design = CardDesign.ACCENT,
				onClose = null,
				onClick = null,
			} = this.props;

			return Card(
				{
					hideCross,
					design,
					onClose,
					onClick,
					testId: this.testId,
				},
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderImage(),
					this.renderBody(),
				),
			);
		}
	}

	CardBanner.defaultProps = {
		title: null,
		description: null,
		buttons: [],
		hideCross: false,
		onClose: null,
		onClick: null,
	};

	CardBanner.propTypes = {
		testId: PropTypes.string.isRequired,
		design: PropTypes.object,
		hideCross: PropTypes.bool,
		title: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
		description: PropTypes.oneOfType([PropTypes.string, PropTypes.object]),
		buttons: PropTypes.arrayOf(PropTypes.object),
		onClose: PropTypes.func,
		onClick: PropTypes.func,
	};

	module.exports = {
		CardDesign,
		/**
		 * @param {CardBannerProps} props
		 */
		CardBanner: (props) => new CardBanner(props),
	};
});
