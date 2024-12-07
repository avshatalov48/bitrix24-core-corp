/**
 * @module ui-system/blocks/switcher
 */
jn.define('ui-system/blocks/switcher', (require, exports, module) => {
	const { transition, parallel } = require('animation');
	const { SwitcherMode } = require('ui-system/blocks/switcher/src/mode-enum');
	const { SwitcherSize } = require('ui-system/blocks/switcher/src/size-enum');

	/**
	 * @typedef {Object} SwitcherProps
	 * @property {string} testId
	 * @property {boolean} [checked=false]
	 * @property {boolean} [disabled=false]
	 * @property {boolean} [useState=true]
	 * @property {boolean} [checked=false]
	 * @property {SwitcherMode} [mode=SwitcherMode.SOLID]
	 * @property {SwitcherSize} [size=SwitcherSize.M]
	 * @property {Object} [trackColor]
	 * @property {Object} [thumbColor]
	 * @property {Function} [onClick]
	 *
	 * class Switcher
	 */
	class Switcher extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.thumbRef = null;
			this.trackRef = null;
			this.animateInProgress = false;

			this.#initializeState(props);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			const { checked } = this.props;

			const shouldUpdate = !this.isUseState() && nextProps.checked === checked;

			return Boolean(nextProps.force) || shouldUpdate;
		}

		componentWillReceiveProps(props)
		{
			this.#initializeState(props);
			this.#animateToggle();
		}

		#initializeState(props = {})
		{
			const { checked } = props;

			this.state = {
				checked: Boolean(checked),
			};
		}

		#animateToggle()
		{
			if (this.isDisabled())
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				const animate = parallel(
					transition(this.thumbRef, {
						duration: 200,
						left: this.#getThumbPosition(),
						...this.getThumbColor(this.isChecked()),
					}),
					transition(this.trackRef, {
						duration: 200,
						...this.getTrackColor(this.isChecked()),
					}),
				);

				this.animateInProgress = true;
				animate()
					.then(() => {
						this.animateInProgress = false;
						resolve();
					})
					.catch((err) => {
						console.error(err);
						this.animateInProgress = false;
						resolve();
					});
			});
		}

		render()
		{
			const { style } = this.props;

			const clickable = this.isUseState();

			return View(
				{
					clickable,
					testId: this.#getTestId(),
					style: {
						alignItems: 'flex-start',
						...style,
					},
					onClick: this.handleOnClick,
				},
				View(
					{
						clickable: false,
						ref: (ref) => {
							this.trackRef = ref;
						},
						style: this.getTrackStyle(),
					},
					View(
						{
							clickable: false,
							ref: (ref) => {
								this.thumbRef = ref;
							},
							style: this.getThumbStyle(),
						},
					),
				),
			);
		}

		handleOnClick = async () => {
			const { onClick } = this.props;

			if (this.isDisabled() || this.animateInProgress)
			{
				return;
			}

			if (this.isUseState())
			{
				await this.toggleChecked();
			}

			if (onClick)
			{
				onClick(this.isChecked());
			}
		};

		toggleChecked()
		{
			return new Promise((resolve) => {
				this.setState(
					{ checked: !this.isChecked() },
					() => {
						this.#animateToggle()
							.then(resolve)
							.catch(resolve);
					},
				);
			});
		}

		getThumbColor(checked)
		{
			const { thumbColor = {} } = this.props;

			const color = {
				...this.#getMode().getThumbColor(),
				...thumbColor,
			};

			return color[checked];
		}

		getTrackColor(checked)
		{
			const { trackColor = {} } = this.props;

			const color = {
				...this.#getMode().getTrackStyle(),
				...trackColor,
			};

			return color[checked];
		}

		getTrackStyle()
		{
			return {
				...this.getTrackColor(this.isChecked()),
				...this.#getSize().getTrackStyle(this.isDisabled()),
			};
		}

		getThumbStyle()
		{
			return {
				position: 'absolute',
				left: this.#getThumbPosition(),
				...this.getThumbColor(this.isChecked()),
				...this.#getSize().getThumbStyle({
					checked: this.isChecked(),
					disabled: this.isDisabled(),
				}),
			};
		}

		#getSize()
		{
			const { compact = false, size } = this.props;

			if (compact)
			{
				return SwitcherSize.S;
			}

			return SwitcherSize.resolve(size, SwitcherSize.M);
		}

		#getMode()
		{
			const { mode = SwitcherMode.SOLID } = this.props;

			return this.isDisabled() ? mode.getDisabled() : mode;
		}

		#getThumbPosition()
		{
			return this.#getSize().getThumbPosition(this.isChecked());
		}

		#getTestId()
		{
			const { testId } = this.props;
			const prefix = this.isChecked() ? '' : 'un';

			return `${testId}_${prefix}selected`;
		}

		isUseState()
		{
			const { useState } = this.props;

			return Boolean(useState);
		}

		isChecked()
		{
			const { checked } = this.state;

			return Boolean(checked);
		}

		isDisabled()
		{
			const { disabled } = this.props;

			return Boolean(disabled);
		}
	}

	Switcher.defaultProps = {
		compact: false,
		checked: false,
		useState: true,
		trackColor: {},
		thumbColor: {},
	};

	Switcher.propTypes = {
		testId: PropTypes.string.isRequired,
		checked: PropTypes.bool,
		useState: PropTypes.bool,
		mode: PropTypes.instanceOf(SwitcherMode),
		size: PropTypes.instanceOf(SwitcherSize),
		trackColor: PropTypes.shape({
			true: PropTypes.shape({
				backgroundColor: PropTypes.string,
			}),
			false: PropTypes.shape({
				backgroundColor: PropTypes.string,
			}),
		}),
		thumbColor: PropTypes.shape({
			true: PropTypes.shape({
				backgroundColor: PropTypes.string,
			}),
			false: PropTypes.shape({
				backgroundColor: PropTypes.string,
			}),
		}),
		onClick: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {SwitcherProps} props
		 * @returns {Switcher}
		 */
		Switcher: (props) => new Switcher(props),
		SwitcherMode,
		SwitcherSize,
	};
});
