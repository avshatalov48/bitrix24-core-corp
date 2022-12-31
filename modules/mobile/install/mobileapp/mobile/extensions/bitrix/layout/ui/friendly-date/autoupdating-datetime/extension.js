/**
 * @module layout/ui/friendly-date/autoupdating-datetime
 */
jn.define('layout/ui/friendly-date/autoupdating-datetime', (require, exports, module) => {

	const { Moment } = require('utils/date');

	/**
	 * @abstract
	 * @class AutoupdatingDatetime
	 */
	class AutoupdatingDatetime extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { moment, timestamp } = props;

			if (!moment && !timestamp)
			{
				throw new Error('You must specify "moment" or "timestamp" property');
			}

			if (moment && !(moment instanceof Moment))
			{
				throw new Error('Property "moment" must be instance of Moment class');
			}

			this.intervalId = null;

			this.state = {
				text: this.makeText(this.moment),
			};
		}

		/**
		 * @return {Moment}
		 */
		get moment()
		{
			return this.props.moment || Moment.createFromTimestamp(this.props.timestamp);
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
			this.intervalId = setInterval(() => {
				const text = this.makeText(this.moment);
				if (text !== this.state.text)
				{
					this.setState({text});
				}
			}, this.refreshTimeout);
		}

		componentWillUnmount()
		{
			clearTimeout(this.intervalId);
		}

		componentWillReceiveProps(props)
		{
			const moment = props.moment || Moment.createFromTimestamp(props.timestamp);
			const text = this.makeText(moment);
			if (text !== this.state.text)
			{
				this.state.text = text;
			}
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