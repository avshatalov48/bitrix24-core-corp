/**
 * @module layout/ui/date-pill
 */
jn.define('layout/ui/date-pill', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { chevronDown } = require('assets/common');
	const { shortTime, dayShortMonth, mediumDate } = require('utils/date/formats');

	const WARNING = 'warning';
	const DEFAULT = 'default';

	const Backgrounds = {
		[WARNING]: AppTheme.colors.accentSoftOrange1,
		[DEFAULT]: AppTheme.colors.bgContentSecondary,
	};

	/**
	 * @class DatePill
	 */
	class DatePill extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

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
			let { backgroundColor, backgroundColorType } = this.props;

			if (backgroundColor === WARNING || backgroundColor === DEFAULT)
			{
				backgroundColorType = backgroundColor;
			}

			if (backgroundColorType)
			{
				backgroundColor = Backgrounds[backgroundColorType];
			}

			return backgroundColor || Backgrounds.default;
		}

		get textColor()
		{
			return this.props.textColor ?? AppTheme.colors.base2;
		}

		get fontSize()
		{
			return this.props.fontSize ?? 12;
		}

		get fontWeight()
		{
			return this.props.fontWeight ?? 600;
		}

		get imageSize()
		{
			return this.props.imageSize ?? 12;
		}

		/**
		 * @return {boolean}
		 */
		get isReadonly()
		{
			return this.props.isReadonly ?? true;
		}

		getMoment()
		{
			return this.state.moment;
		}

		render()
		{
			return View(
				{
					testId: 'DatePillContainer',
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
					onClick: () => {
						if (!this.isReadonly)
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
						testId: 'DatePillText',
						text: this.formatDate(),
						style: {
							fontSize: this.fontSize,
							fontWeight: this.fontWeight,
							color: this.textColor,
						},
					}),
					!this.isReadonly && Image({
						tintColor: this.textColor,
						svg: {
							content: chevronDown(AppTheme.colors.base2, { box: true }),
						},
						style: {
							width: this.imageSize,
							height: this.imageSize,
						},
					}),
				),
			);
		}

		formatDate()
		{
			let date = '';
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
							this.props.onChange && this.props.onChange(moment);
						});
					}
				},
			);
		}
	}

	module.exports = { DatePill };
});
