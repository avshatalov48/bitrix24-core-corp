/**
 * @module layout/ui/email-input-box/src/invite-button
 */
jn.define('layout/ui/email-input-box/src/invite-button', (require, exports, module) => {
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} InviteButtonProps
	 * @property {string} text
	 * @property {number} counter
	 * @property {boolean} pending
	 * @property {boolean} disabled
	 * @property {function} onButtonClick

	 * @class InviteButton
	 * @param {InviteButtonProps} props
	 */
	class InviteButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'invite-button',
				context: this,
			});
			this.#initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initState(props);
		}

		#initState = (props) => {
			this.state = {
				counter: props.counter ?? 0,
				pending: props.pending ?? false,
				disabled: props.disabled ?? true,
			};
		};

		render()
		{
			const { text } = this.props;
			const { pending, disabled } = this.state;

			return Button({
				testId: this.getTestId(),
				text,
				design: ButtonDesign.FILLED,
				size: ButtonSize.L,
				loading: pending,
				stretched: true,
				badge: this.#renderCounter(),
				borderRadius: 0,
				disabled,
				style: {
					paddingVertical: 0,
				},
				onClick: this.#onButtonClick,
			});
		}

		#renderCounter()
		{
			const { counter } = this.state;
			if (counter === 0)
			{
				return null;
			}

			return BadgeCounter({
				testId: this.getTestId('badge-counter'),
				value: String(counter),
				design: BadgeCounterDesign.WHITE,
			});
		}

		#onButtonClick = () => {
			const { pending, disabled } = this.state;
			if (pending || disabled)
			{
				return;
			}

			const { onButtonClick } = this.props;
			onButtonClick?.();
		};
	}

	InviteButton.defaultProps = {
		counter: 0,
		pending: false,
		disabled: false,
		onButtonClick: null,
	};

	InviteButton.propTypes = {
		counter: PropTypes.number,
		pending: PropTypes.bool,
		disabled: PropTypes.bool,
		onButtonClick: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {InviteButtonProps} props
		 * @returns {InviteButton}
		 */
		InviteButton: (props) => new InviteButton(props),
	};
});
