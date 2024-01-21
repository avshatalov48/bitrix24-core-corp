/**
 * @module crm/timeline/item/ui/body/blocks/date-pill
 */
jn.define('crm/timeline/item/ui/body/blocks/date-pill', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { DatePill } = require('layout/ui/date-pill');
	const { datetime } = require('utils/date/formats');

	/**
	 * @class TimelineItemBodyDatePill
	 */
	class TimelineItemBodyDatePill extends TimelineItemBodyBlock
	{
		constructor(props, factory)
		{
			super(props, factory);

			this.onAction = this.onAction.bind(this);
		}

		render()
		{
			return new DatePill({
				...this.props,
				onChange: this.onAction,
			});
		}

		/**
		 * @param {Moment} moment
		 */
		onAction(moment)
		{
			if (this.props.action)
			{
				const { actionParams } = this.props.action;
				actionParams.value = moment.format(datetime());
				actionParams.valueTs = moment.timestamp;

				this.emitAction({
					...this.props.action,
					actionParams,
				});
			}
		}

		/**
		 * @private
		 * @param {any} params
		 */
		emitAction(params)
		{
			if (this.factory.onAction && params)
			{
				this.factory.onAction(params);
			}
		}
	}

	module.exports = { TimelineItemBodyDatePill };
});
