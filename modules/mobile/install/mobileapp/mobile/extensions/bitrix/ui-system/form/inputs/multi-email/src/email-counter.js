/**
 * @module ui-system/form/inputs/multi-email/src/email-counter
 */
jn.define('ui-system/form/inputs/multi-email/src/email-counter', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Text7 } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @typedef {Object} EmailCounterProps
	 * @property {number} [value]

	 * @class EmailCounter
	 * @param {EmailCounterProps} props
	 */
	class EmailCounter extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.initState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.initState(nextProps);
		}

		initState(props)
		{
			this.state = {
				value: props.value ?? 0,
			};
		}

		render()
		{
			const { value } = this.state;

			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'flex-end',
						alignItems: 'flex-end',
						alignContent: 'flex-end',
					},
				},
				Text7({
					text: String(value),
					color: Color.base3,
				}),
			);
		}
	}

	module.exports = {
		/**
		 * @param {EmailCounterProps} props
		 * @returns {EmailCounter}
		 */
		EmailCounter: (props) => new EmailCounter(props),
	};
});
