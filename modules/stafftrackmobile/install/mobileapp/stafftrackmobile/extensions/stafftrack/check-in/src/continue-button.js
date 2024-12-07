/**
 * @module stafftrack/check-in/continue-button
 */
jn.define('stafftrack/check-in/continue-button', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { animate } = require('animation');
	const { Color, Indent } = require('tokens');

	const { Text2 } = require('ui-system/typography/text');

	class ContinueButton extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.isShown = false;
			this.buttonRef = null;

			this.onButtonClick = this.onButtonClick.bind(this);
		}

		show()
		{
			if (!this.isShown)
			{
				void this.animateToggle({ show: true });
			}
		}

		hide()
		{
			if (this.isShown)
			{
				void this.animateToggle({ show: false });
			}
		}

		animateToggle({ show })
		{
			this.isShown = show;

			return animate(this.buttonRef, {
				opacity: show ? 1 : 0,
				duration: show ? 300 : 0,
			});
		}

		render()
		{
			return View(
				{
					testId: 'stafftrack-checkin-continue-button',
					resizableByKeyboard: true,
					style: {
						flex: 1,
						opacity: 0,
						width: '100%',
						position: 'absolute',
						left: 0,
						bottom: 0,
					},
					safeArea: {
						bottom: true,
					},
					onClick: this.onButtonClick,
					ref: (ref) => {
						this.buttonRef = ref;
					},
				},
				this.renderButton(),
			);
		}

		renderButton()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.accentMainPrimary.toHex(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				this.renderButtonText(),
			);
		}

		renderButtonText()
		{
			return View(
				{
					style: {
						paddingVertical: Indent.L.toNumber(),
					},
				},
				Text2({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CONTINUE'),
					color: Color.baseWhiteFixed,
				}),
			);
		}

		onButtonClick()
		{
			Keyboard.dismiss();
		}
	}

	module.exports = { ContinueButton };
});
