/**
 * @module crm/timeline/item/ui/body/blocks/date-pill
 */
jn.define('crm/timeline/item/ui/body/blocks/date-pill', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { Moment } = require('utils/date');
	const { datetime, shortTime, dayShortMonth, mediumDate } = require('utils/date/formats');
	const { Loc } = require('loc');

	const Backgrounds = {
		warning: '#FFE9BE',
		default: '#F1F4F6',
	};

	/**
	 * @class TimelineItemBodyDatePillBlock
	 */
	class TimelineItemBodyDatePillBlock extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.state = {
				moment: Moment.createFromTimestamp(this.props.value),
			};
		}

		componentWillReceiveProps(props)
		{
			this.state.moment = Moment.createFromTimestamp(props.value);
		}

		/**
		 * @return {Moment}
		 */
		get moment()
		{
			return this.state.moment;
		}

		get backgroundColor()
		{
			const { backgroundColor } = this.props;

			return Backgrounds[backgroundColor] || Backgrounds.default;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
					onClick: () => {
						if (this.props.action && !this.isReadonly)
						{
							this.openDatePicker();
						}
					},
				},
				View(
					{
						style: {
							backgroundColor: this.backgroundColor,
							borderRadius: 64,
							paddingHorizontal: 8,
							paddingVertical: 4,
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					Text({
						text: this.formatDate(),
						style: {
							fontSize: 12,
							fontWeight: '600',
							color: '#6A737F',
						},
					}),
					this.props.action && !this.isReadonly && Image({
						svg: {
							content: '<svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.3065 0.753906L5.66572 3.39469L5.00042 4.04969L4.34773 3.39469L1.70695 0.753906L0.775096 1.68576L5.00669 5.91735L9.23828 1.68576L8.3065 0.753906Z" fill="#6A737F"/></svg>',
						},
						style: {
							marginLeft: 4,
							width: 10,
							height: 6,
						},
					}),
				),
			);
		}

		formatDate()
		{
			let date;
			if (this.moment.isYesterday)
			{
				date = Loc.getMessage('CRM_TIMELINE_HISTORY_YESTERDAY').toLocaleLowerCase(env.languageId);
			}
			else if (this.moment.isToday)
			{
				date = Loc.getMessage('CRM_TIMELINE_HISTORY_TODAY').toLocaleLowerCase(env.languageId);
			}
			else if (this.moment.isTomorrow)
			{
				date = Loc.getMessage('CRM_TIMELINE_HISTORY_TOMORROW').toLocaleLowerCase(env.languageId);
			}
			else
			{
				let day = this.moment.format('EE');
				if (env.languageId === 'ru')
				{
					day = day.toLocaleLowerCase(env.languageId);
				}
				const dateFormat = this.moment.inThisYear ? dayShortMonth() : mediumDate();
				date = `${day}, ${this.moment.format(dateFormat)}`;
			}

			const time = this.moment.format(shortTime());

			return this.props.withTime ? `${date}, ${time}` : date;
		}

		openDatePicker()
		{
			dialogs.showDatePicker(
				{
					type: this.props.withTime ? 'datetime' : 'date',
					value: this.moment.date.getTime(),
				},
				(eventName, ms) => {
					if (ms)
					{
						const moment = new Moment(ms);
						this.setState({ moment }, () => {
							this.onAction(moment);
						});
					}
				},
			);
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
	}

	module.exports = { TimelineItemBodyDatePillBlock };
});