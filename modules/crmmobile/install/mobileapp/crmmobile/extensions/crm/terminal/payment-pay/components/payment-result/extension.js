/**
 * @module crm/terminal/payment-pay/components/payment-result
 */
jn.define('crm/terminal/payment-pay/components/payment-result', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { mergeImmutable } = require('utils/object');
	const { Button } = require('crm/terminal/payment-pay/components/payment-result/button');

	/**
	 * @abstract
	 * @class PaymentResult
	 */
	class PaymentResult extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.rootRef = null;
		}

		render()
		{
			return View(
				{
					style: mergeImmutable(
						styles.container,
						{
							backgroundColor: this.getBackgroundColor(),
						},
					),
					ref: (ref) => this.rootRef = ref,
				},
				this.renderImage(),
				this.renderText(),
				...this.renderButtons(),
			);
		}

		renderImage()
		{
			return View(
				{
					style: styles.imageContainer,
				},
				Image({
					testId: this.getImageTestId(),
					style: styles.image,
					uri: this.getImagePath(this.getImage()),
				}),
			);
		}

		renderText()
		{
			return View(
				{
					style: styles.textContainer,
				},
				this.text && Text({
					testId: this.getTextTestId(),
					style: styles.text,
					text: this.text,
				}),
			);
		}

		renderButtons()
		{
			return this.actions.map((action, index) => {
				return View(
					{
						style: {
							marginTop: index > 0 ? 12 : 0,
						},
					},
					new Button({
						testId: action.testId,
						onClick: action.action,
						buttonText: action.name,
						styles: action.type === 'primary'
							? this.getPrimaryButtonStyle()
							: this.getDefaultButtonStyle(),
					}),
				);
			});
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getImageTestId()
		{
			this.abstract();
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getTextTestId()
		{
			this.abstract();
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getBackgroundColor()
		{
			this.abstract();
		}

		/**
		 * @abstract
		 * @return {String}
		 */
		getImage()
		{
			this.abstract();
		}

		/**
		 * @abstract
		 * @return {Object}
		 */
		getDefaultButtonStyle()
		{
			this.abstract();
		}

		/**
		 * @abstract
		 * @return {Object}
		 */
		getPrimaryButtonStyle()
		{
			this.abstract();
		}

		get text()
		{
			return BX.prop.getString(this.props, 'text', null);
		}

		get actions()
		{
			return this.props.actions || [];
		}

		componentDidMount()
		{
			if (!this.rootRef)
			{
				return;
			}

			this.rootRef.animate({
				opacity: 1,
				duration: 300,
			});
		}

		/**
		 * @param {string} image
		 * @return {string}
		 */
		getImagePath(image)
		{
			return `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/terminal/payment-pay/images/${image}.png`;
		}

		abstract()
		{
			throw new Error('Abstract method must be implemented in child class');
		}
	}

	const styles = {
		container: {
			flexDirection: 'column',
			flexGrow: 1,
			width: '100%',
			height: '100%',
			opacity: 0.3,
			alignItems: 'center',
		},
		imageContainer: {
			marginTop: 88,
			width: 124,
			height: 124,
			alignItems: 'center',
			justifyContent: 'center',
		},
		image: {
			width: 124,
			height: 124,
		},
		textContainer: {
			marginTop: 14,
			marginHorizontal: 59,
		},
		text: {
			fontSize: 24,
			fontWeight: '500',
			color: AppTheme.colors.baseWhiteFixed,
			textAlign: 'center',
			marginBottom: 40,
		},
	};

	module.exports = { PaymentResult };
});
