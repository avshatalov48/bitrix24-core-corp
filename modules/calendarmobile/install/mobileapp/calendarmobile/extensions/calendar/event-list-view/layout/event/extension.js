/**
 * @module calendar/event-list-view/layout/event
 */
jn.define('calendar/event-list-view/layout/event', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { shortTime } = require('utils/date/formats');
	const { DateHelper } = require('calendar/date-helper');

	/**
	 *
	 * @param {EventLayoutProps} props
	 * @returns {object}
	 * @constructor
	 */
	const Event = (props) => {
		const event = props.event;
		const isSearch = props.isSearch;
		const hasPassed = isSearch
			? false
			: event.hasPassed(props.isFullDay)
		;

		return View(
			{
				style: {
					marginLeft: 20,
					marginRight: 20,
					marginTop: 12,
					paddingBottom: 12,
					flexDirection: 'row',
					flexGrow: 1,
					borderBottomWidth: 0.5,
					borderBottomColor: AppTheme.colors.base6,
				},
				clickable: true,
				onClick: () => {
					if (props.onClick)
					{
						const eventData = {
							id: event.id,
						};

						props.onClick(eventData);
					}
				},
				testId: `event_${event.id}`
			},
			EventColor(event),
			View(
				{
					style: {
						justifyContent: 'space-between',
						flexDirection: 'row',
						flexGrow: 1,
					},
				},
				EventInfo(event, hasPassed),
				EventTime(event, hasPassed, props),
			),
		);
	};

	/**
	 *
	 * @param {EventModel} event
	 * @returns {object}
	 */
	const EventColor = (event) => {
		return View(
			{
				style: {
					...colorStyle,
					backgroundColor: event.getColor(),
				},
				testId: `event_${event.id}_color`
			},
		);
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {boolean} hasPassed
	 * @returns {object}
	 */
	const EventInfo = (event, hasPassed) => {
		return View(
			{
				style: {
					flexDirection: 'column',
					flex: 1,
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				EventIcon(event, hasPassed),
				EventName(event, hasPassed),
			),
			event.location && EventLocation(event, hasPassed),
		);
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {boolean} hasPassed
	 * @returns {null|object}
	 */
	const EventIcon = (event, hasPassed) => {
		if (event.isDeclined())
		{
			return Image(
				{
					svg: {
						content: icons.declinedEvent,
					},
					style: {
						height: 18,
						width: 18,
						marginRight: 5,
					},
					tintColor: hasPassed
						? AppTheme.colors.base4
						: AppTheme.colors.base2
					,
					testId: `event_icon_declined`,
				},
			);
		}

		if (event.isInvited())
		{
			return View(
				{
					style: {
						...iconStyle,
						backgroundColor: AppTheme.colors.accentMainAlert,
					},
					testId: `event_icon_invited`,
				},
				Text({
					text: '1',
					style: {
						color: AppTheme.colors.baseWhiteFixed,
						width: '100%',
						textAlign: 'center',
						height: 20,
					},
				}),
			);
		}

		if (event.isSharingEvent())
		{
			return View(
				{
					style: {
						...iconStyle,
						backgroundColor: AppTheme.colors.accentMainWarning,
					},
					testId: `event_icon_sharing`,
				},
				Image(
					{
						svg: {
							content: icons.userAvatar,
						},
						style: {
							position: 'absolute',
							width: 16,
							height: 16,
						},
					},
				),
			);
		}

		return null;
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {boolean} hasPassed
	 * @returns {object}
	 */
	const EventName = (event, hasPassed) => {
		return Text({
			style: {
				marginRight: hasIcon(event, hasPassed) ? 25 : 0,
				fontSize: 18,
				fontWeight: '500',
				opacity: hasPassed ? 0.5 : 1,
				color: event.isSharingEvent()
					? AppTheme.colors.accentMainWarning
					: AppTheme.colors.base1
				,
			},
			text: event.getName(),
			numberOfLines: 1,
			ellipsize: 'end',
			testId: `event_${event.id}_name`,
		});
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {boolean} hasPassed
	 * @returns {object}
	 */
	const EventLocation = (event, hasPassed) => {
		return Text({
			style: {
				marginTop: 3,
				fontSize: 14,
				fontWeight: '400',
				opacity: hasPassed ? 0.5 : 1,
				color: AppTheme.colors.base2,
			},
			text: event.getLocation(),
			numberOfLines: 1,
			ellipsize: 'end',
			testId: `event_${event.id}_location`,
		});
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {Boolean} hasPassed
	 * @returns {*}
	 */
	const hasIcon = (event, hasPassed) => {
		return event.isDeclined()
			|| event.isSharingEvent()
			|| (!hasPassed && event.isInvited())
		;
	};

	/**
	 *
	 * @param {EventModel} event
	 * @param {boolean} hasPassed
	 * @param {EventLayoutProps} props
	 * @returns {object}
	 */
	const EventTime = (event, hasPassed, props) => {
		return View(
			{
				style: {
					alignItems: 'flex-end',
					flexDirection: 'column',
					justifyContent: 'center',
				},
			},
			Text({
				style: {
					...timeStyle,
					opacity: hasPassed ? 0.5 : 1,
				},
				text: getTimeFromText(event.getMomentDateFrom(), event.getMomentDateTo(), props),
				testId: `event_${props.event.id}_time_from`,
			}),
			Text({
				style: {
					...timeStyle,
					marginTop: 3,
					opacity: hasPassed ? 0.5 : 1,
				},
				text: getTimeToText(event.getMomentDateTo(), props),
				testId: `event_${props.event.id}_time_to`,
			}),
		);
	};

	/**
	 *
	 * @param {Moment} dateFrom
	 * @param {Moment} dateTo
	 * @param {EventLayoutProps} props
	 * @returns {?string|string}
	 */
	const getTimeFromText = (dateFrom, dateTo, props) => {
		if (props.isFullDay)
		{
			return Loc.getMessage('M_CALENDAR_EVENT_LIST_ALL_DAY');
		}

		if (props.isLongWithTime)
		{
			if (props.dayCode === DateHelper.getDayCode(dateFrom.date))
			{
				return dateFrom.format(shortTime()).toLocaleUpperCase(env.languageId);
			}

			if (props.dayCode === DateHelper.getDayCode(dateTo.date))
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_TILL_TIME').replace(
					'#TIME#',
					dateTo.format(shortTime()).toLocaleUpperCase(env.languageId),
				);
			}
		}

		return dateFrom.format(shortTime()).toLocaleUpperCase(env.languageId);
	};

	/**
	 *
	 * @param {Moment} dateTo
	 * @param {EventLayoutProps} props
	 * @returns {string}
	 */
	const getTimeToText = (dateTo, props) => {
		if (props.isFullDay || props.isLongWithTime)
		{
			return ' ';
		}

		return dateTo.format(shortTime()).toLocaleUpperCase(env.languageId);
	};

	const timeStyle = {
		fontSize: 14,
		fontWeight: '400',
		color: AppTheme.colors.base2,
	};

	const colorStyle = {
		height: 45,
		width: 6,
		borderRadius: 6,
		marginRight: 5,
	};

	const iconStyle = {
		alignItems: 'center',
		justifyContent: 'center',
		width: 18,
		height: 18,
		borderRadius: 50,
		marginRight: 5,
	};

	const icons = {
		userAvatar: '<svg viewBox="0 0 50 50" width="50" height="50" xmlns="http://www.w3.org/2000/svg"><path d="M21.645 11.713c-1.054-1.67 7.832-3.057 8.422 2.054.232 1.54.232 3.107 0 4.647 0 0 1.328-.152.442 2.372 0 0-.488 1.816-1.238 1.408 0 0 .122 2.296-1.058 2.685 0 0 .084 1.223.084 1.306l.986.147s-.03 1.02.167 1.13c.9.58 1.886 1.021 2.923 1.305 3.062.777 4.616 2.11 4.616 3.278l.823 4.189c-3.544 1.485-7.657 2.373-12.055 2.466H24.22c-4.389-.093-8.493-.977-12.03-2.456.161-1.159.371-2.47.588-3.315.466-1.816 3.087-3.165 5.498-4.202 1.248-.537 1.518-.86 2.774-1.409.07-.334.098-.676.084-1.017l1.068-.127s.14.255-.085-1.245c0 0-1.2-.311-1.256-2.7 0 0-.902.3-.956-1.147-.039-.98-.808-1.832.299-2.537l-.564-1.502s-.592-5.8 2.005-5.33z" fill="#FFF" fill-rule="evenodd"/></svg>',
		declinedEvent: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23FFF" d="M3.7881393,9.53543213 C4.42311341,9.92466127 5.17005184,10.1490532 5.96939983,10.1490532 C8.27775866,10.1490532 10.1490532,8.27775866 10.1490532,5.96939983 C10.1490532,5.17005184 9.92466127,4.42311341 9.53543213,3.7881393 L3.7881393,9.53543213 L3.7881393,9.53543213 Z M2.40336752,8.15066036 L8.15066036,2.40336752 C7.51568624,2.01413838 6.76874781,1.78974643 5.96939983,1.78974643 C3.66104099,1.78974643 1.78974643,3.66104099 1.78974643,5.96939983 C1.78974643,6.76874781 2.01413838,7.51568624 2.40336752,8.15066036 Z M5.96939983,11.9387997 C2.67259134,11.9387997 -1.73105974e-12,9.26620832 -1.73105974e-12,5.96939983 C-1.73105974e-12,2.67259134 2.67259134,-8.21565038e-13 5.96939983,-8.21565038e-13 C9.26620832,-8.21565038e-13 11.9387997,2.67259134 11.9387997,5.96939983 C11.9387997,9.26620832 9.26620832,11.9387997 5.96939983,11.9387997 Z"/></svg>',
	};

	module.exports = { Event };
});
