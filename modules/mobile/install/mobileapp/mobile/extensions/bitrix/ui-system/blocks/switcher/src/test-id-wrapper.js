/**
 * @module ui-system/blocks/switcher/src/test-id-wrapper
 */
jn.define('ui-system/blocks/switcher/src/test-id-wrapper', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');

	/**
	 * class TestIdWrapper
	 */
	class TestIdWrapper extends LayoutComponent
	{
		/**
		 * @param {SwitcherProps & Switcher} props
		 */
		constructor(props)
		{
			super(props);

			this.#initializeState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initializeState(props);
		}

		#initializeState(props = {})
		{
			const { checked } = props;

			this.state = {
				checked: Boolean(checked),
			};
		}

		render()
		{
			const { Switcher, style, useState, ...restProps } = this.props;

			const clickable = this.#isUseState() && this.#hasOnClick();

			return View(
				{
					style: {
						alignItems: 'flex-start',
						...style,
					},
					testId: this.#getTestId(),
				},
				new Switcher({
					...restProps,
					useState: this.#isUseState(),
					checked: this.#isChecked(),
					onClick: clickable && this.#handleOnClick,
				}),
			);
		}

		#handleOnClick = (checked) => {
			const { onClick } = this.props;

			if (!this.#isUseState())
			{
				onClick?.(checked);

				return;
			}

			this.setState(
				{ checked },
				() => {
					onClick?.(checked);
				},
			);
		};

		#isChecked()
		{
			const { checked } = this.state;

			return Boolean(checked);
		}

		#getTestId()
		{
			const { testId } = this.props;
			const prefix = this.#isChecked() ? '' : 'un';

			return `${testId}_${prefix}selected`;
		}

		#isUseState()
		{
			const { useState } = this.props;

			return Boolean(useState);
		}

		#hasOnClick()
		{
			const { onClick } = this.props;

			return Boolean(onClick);
		}
	}

	TestIdWrapper.defaultProps = {
		useState: true,
		checked: false,
	};

	TestIdWrapper.propTypes = {
		testId: PropTypes.string.isRequired,
	};

	module.exports = {
		/**
		 * @param {SwitcherProps} props
		 * @param {Switcher} Switcher
		 */
		TestIdWrapper: (props, Switcher) => new TestIdWrapper({ ...props, Switcher }),
	};
});
