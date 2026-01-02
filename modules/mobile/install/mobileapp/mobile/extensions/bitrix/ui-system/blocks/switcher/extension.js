/**
 * @module ui-system/blocks/switcher
 */
jn.define('ui-system/blocks/switcher', (require, exports, module) => {
	const { transition, parallel } = require('animation');
	const { PropTypes } = require('utils/validation');
	const { SwitcherMode } = require('ui-system/blocks/switcher/src/enums/mode-enum');
	const { SwitcherSize } = require('ui-system/blocks/switcher/src/enums/size-enum');
	const { TestIdWrapper } = require('ui-system/blocks/switcher/src/test-id-wrapper');

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

		shouldComponentUpdate(nextProps)
		{
			const { checked, disabled } = this.props;

			if (disabled !== nextProps.disabled)
			{
				return true;
			}

			return !this.#isUseState() && nextProps.checked === checked;
		}

		componentWillReceiveProps(props)
		{
			this.#initializeState(props);
			void this.#animateToggle();
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
			return View(
				{
					ref: (ref) => {
						this.trackRef = ref;
					},
					style: this.getTrackStyle(),
					clickable: this.#hasOnClick(),
					onClick: this.handleOnClick,
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
			);
		}

		handleOnClick = async () => {
			const { onClick } = this.props;

			if (this.animateInProgress)
			{
				return;
			}

			if (!this.isDisabled() && this.#isUseState())
			{
				await this.toggleChecked();
			}

			onClick?.(this.isChecked());
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
				position: 'relative',
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

			/** @deprecated use @param {size} */
			if (compact)
			{
				return SwitcherSize.S;
			}

			return SwitcherSize.resolve(size, SwitcherSize.M);
		}

		/**
		 * @returns {SwitcherMode}
		 */
		#getMode()
		{
			const mode = this.props?.mode || SwitcherMode.SOLID;

			return this.isDisabled() ? mode.getDisabled() : mode;
		}

		#getThumbPosition()
		{
			return this.#getSize().getThumbPosition(this.isChecked());
		}

		#isUseState()
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

		#hasOnClick()
		{
			const { onClick } = this.props;

			return Boolean(onClick);
		}
	}

	Switcher.defaultProps = {
		checked: false,
		useState: true,
		trackColor: {},
		thumbColor: {},
	};

	Switcher.propTypes = {
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
		onClick: PropTypes.oneOfType([PropTypes.func, PropTypes.bool]),
	};

	module.exports = {
		/**
		 * @param {SwitcherProps} props
		 * @returns {Switcher}
		 */
		Switcher: (props) => TestIdWrapper(props, Switcher),
		SwitcherMode,
		SwitcherSize,
	};
});
