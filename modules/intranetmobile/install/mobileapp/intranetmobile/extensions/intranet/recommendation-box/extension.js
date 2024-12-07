/**
 * @module intranet/recommendation-box
 */
jn.define('intranet/recommendation-box', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent, Component } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { Text4 } = require('ui-system/typography/text');
	const { H4 } = require('ui-system/typography/heading');
	const { downloadImages } = require('asset-manager');
	const {
		ButtonSize,
		ButtonDesign,
		Button,
	} = require('ui-system/form/buttons/button');

	/**
	 * @class RecommendationBox
	 */
	class RecommendationBox extends PureComponent
	{
		componentDidMount()
		{
			void downloadImages([this.imageUri]);
		}

		get imageUri()
		{
			return this.props.imageUri || '';
		}

		get title()
		{
			return this.props.title || '';
		}

		get description()
		{
			return this.props.description || '';
		}

		get buttonText()
		{
			return this.props.buttonText || '';
		}

		get additionalContent()
		{
			return this.props.additionalContent || null;
		}

		get buttonTestId()
		{
			return this.props.buttonTestId || 'recommendation_box_action_button';
		}

		onButtonClick()
		{
			if (this.props.onButtonClick)
			{
				this.props.onButtonClick();
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						justifyContent: 'center',
						alignContent: 'flex-start',
						alignItems: 'center',
						flexWrap: 'wrap',
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				Area(
					{
						isFirst: false,
						divider: false,
						style: {
							flex: 1,
							minWidth: '100%',
						},
					},
					this.content(),
				),
				View(
					{
						style: {
							paddingVertical: Indent.XL.toNumber(),
							paddingHorizontal: Component.paddingLr.toNumber(),
							flex: 1,
							width: '100%',
						},
					},
					this.actionButton(),
				),
			);
		}

		content()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						justifyContent: 'center',
						paddingVertical: Indent.XS.toNumber(),
					},
				},
				Image({
					svg: {
						uri: this.imageUri,
					},
				}),
				H4({
					text: this.title,
					style: {
						textAlign: 'center',
						marginTop: Indent.XL3.toNumber(),
					},
				}),
				Text4({
					text: this.description,
					style: {
						textAlign: 'center',
						marginTop: Indent.M.toNumber(),
					},
				}),
				this.additionalContent,
			);
		}

		actionButton()
		{
			return Button({
				testId: this.buttonTestId,
				text: this.buttonText,
				size: ButtonSize.L,
				design: ButtonDesign.FILLED,
				stretched: true,
				style: {
					width: '100%',
				},
				onClick: () => this.onButtonClick(),
			});
		}
	}

	module.exports = { RecommendationBox };
});
