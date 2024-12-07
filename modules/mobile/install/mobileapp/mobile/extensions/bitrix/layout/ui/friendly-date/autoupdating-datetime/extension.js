/**
 * @module layout/ui/friendly-date/autoupdating-datetime
 */
jn.define('layout/ui/friendly-date/autoupdating-datetime', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { Text5 } = require('ui-system/typography/text');

	/**
	 * @abstract
	 * @class AutoupdatingDatetime
	 */
	class AutoupdatingDatetime extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.timeoutId = null;
			this.intervalId = null;

			/** @var {Moment} */
			this.moment = this.initMoment(props);

			this.state = {
				text: this.makeText(this.moment),
			};
		}

		componentWillReceiveProps(newProps)
		{
			this.moment = this.initMoment(newProps);
			this.state.text = this.makeText(this.moment);
		}

		initMoment(props)
		{
			const { moment, timestamp } = props;

			if (!moment && !timestamp)
			{
				throw new TypeError('You must specify "moment" or "timestamp" property');
			}

			if (moment && !(moment instanceof Moment))
			{
				throw new TypeError('Property "moment" must be instance of Moment class');
			}

			return moment || Moment.createFromTimestamp(timestamp);
		}

		get style()
		{
			return this.props.style || {};
		}

		get refreshTimeout()
		{
			const seconds = BX.prop.getNumber(this.props, 'refreshTimeout', 60);

			return seconds * 1000;
		}

		componentDidMount()
		{
			const refreshText = () => {
				const text = this.makeText(this.moment);
				if (text !== this.state.text)
				{
					this.setState({ text }, () => {
						if (this.props.onTextChange)
						{
							this.props.onTextChange();
						}
					});
				}
			};

			// we need to wait until the next second to start updating because Moment works in seconds, not ms.
			const timeoutUntilNextTick = this.refreshTimeout - (Date.now() % this.refreshTimeout) + 1000;

			this.timeoutId = setTimeout(() => {
				refreshText();
				this.intervalId = setInterval(refreshText, this.refreshTimeout);
			}, timeoutUntilNextTick);
		}

		componentWillUnmount()
		{
			clearTimeout(this.timeoutId);
			clearTimeout(this.intervalId);
		}

		/**
		 * @abstract
		 * @param {Moment} moment
		 * @return {string}
		 */
		makeText(moment)
		{
			return '';
		}

		render()
		{
			if (typeof this.props.renderContent === 'function')
			{
				const { state, props } = this;

				return this.props.renderContent({ state, props });
			}

			return Text({
				text: this.state.text,
				style: this.style,
			});
		}
	}

	module.exports = { AutoupdatingDatetime };
});
