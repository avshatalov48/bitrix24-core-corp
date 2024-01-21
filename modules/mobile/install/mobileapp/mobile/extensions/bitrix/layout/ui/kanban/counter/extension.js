/**
 * @module layout/ui/kanban/counter
 */
jn.define('layout/ui/kanban/counter', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { dayShortMonth } = require('utils/date/formats');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Loc } = require('loc');

	const MORE_THAN_99_COUNTER = '99+';
	const COUNTER_COLOR = {
		error: AppTheme.colors.accentMainAlert,
		incoming: AppTheme.colors.accentMainSuccess,
		empty: AppTheme.colors.base5,
	};
	const INDICATOR_COLOR = {
		own: AppTheme.colors.accentExtraDarkblue,
		someone: AppTheme.colors.base3,
	};

	/**
	 * @class CounterComponent
	 */
	class CounterComponent extends LayoutComponent
	{
		render()
		{
			const counters = BX.prop.getArray(this.props, 'counters', []);
			const timeView = this.renderTime();

			return View(
				{
					style: styles.wrapper,
					onClick: this.props.onClick && (() => this.props.onClick(this.props.itemId)),
					onLongClick: this.props.onLongClick && (() => this.props.onLongClick(this.props.itemId)),
				},
				View(
					{
						style: styles.blockWrapper(Boolean(timeView)),
					},
					View(
						{
							style: styles.iconWrapper,
						},
						Image({
							style: styles.icon,
							svg: {
								content: counters.length > 0 ? ICON : EMPTY_ICON,
							},
						}),
					),
				),
				this.renderBadges(),
				Text({
					style: styles.title,
					text: Loc.getMessage('M_UI_KANBAN_COUNTER_TITLE'),
				}),
				timeView,
			);
		}

		renderBadges()
		{
			const badges = this.prepareBadges();
			if (badges.length === 0)
			{
				return null;
			}

			return View(
				{
					style: styles.badgeWrapper,
				},
				...badges,
				this.renderIndicator(badges.length),
			);
		}

		prepareBadges()
		{
			const counters = BX.prop.getArray(this.props, 'counters', []);
			const badgesCount = counters.length;
			const totalCount = this.props.activityCounterTotal || 0;

			return counters
				.reverse()
				.flatMap((counter, index) => this.renderBadge(counter, totalCount, index, badgesCount));
		}

		renderBadge(counter, totalCount, index, countersLength)
		{
			const commonBadgeStyles = styles.commonBadge;
			const badgeStyles = styles.badge(this.getBadgeColor(counter.id), countersLength, index);

			const results = [
				Text({
					style: {
						...commonBadgeStyles,
						...badgeStyles,
					},
					text: index === countersLength - 1
						? String(totalCount > 99 ? MORE_THAN_99_COUNTER : totalCount)
						: '',
				}),
			];

			if (totalCount > 1 && index < countersLength - 1)
			{
				const dividerStyles = styles.dividerBadge;
				results.push(
					Text({
						style: {
							...commonBadgeStyles,
							...dividerStyles,
						},
					}),
				);
			}

			return results;
		}

		/**
		 * @return {View}
		 */
		renderIndicator()
		{
			const { indicator } = this.props;

			if (!indicator)
			{
				return null;
			}

			return View(
				{
					style: styles.indicator,
				},
				View({
					style: {
						width: 4,
						height: 4,
						borderRadius: 4,
						backgroundColor: this.getIndicatorColor(indicator.id),
					},
				}),
			);
		}

		renderTime()
		{
			if (!this.props.activityCounterTotal || this.props.skipTimeRender)
			{
				return null;
			}

			const timestamp = this.props.lastActivity || null;
			if (!timestamp)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(timestamp);

			const style = {
				color: AppTheme.colors.accentExtraDarkblue,
				fontSize: 11,
				textAlign: 'center',
			};

			return View(
				{
					style: styles.time,
				},
				new FriendlyDate({
					moment,
					style,
					defaultFormat: dayShortMonth(),
					showTime: true,
					useTimeAgo: true,
					timeSeparator: '\r\n',
				}),
			);
		}

		getIndicatorColor(indicatorType)
		{
			const color = INDICATOR_COLOR[indicatorType];

			return color || INDICATOR_COLOR.own;
		}

		getBadgeColor(counterType)
		{
			const color = COUNTER_COLOR[counterType];

			return color || COUNTER_COLOR.empty;
		}
	}

	const styles = {
		wrapper: {
			paddingTop: 10,
			paddingBottom: 10,
			flexDirection: 'column',
			alignItems: 'center',
			justifyContent: 'center',
			width: 76,
		},
		blockWrapper: (hasTime) => ({
			marginBottom: hasTime ? 10 : 0,
			padding: 12,
			borderRadius: 20,
			borderWidth: 1,
			borderColor: AppTheme.colors.base7,
			alignItems: 'center',
			justifyContent: 'center',
			width: 36,
			height: 36,
		}),
		iconWrapper: {
			alignItems: 'center',
			justifyContent: 'center',
		},
		icon: {
			width: 19,
			height: 17,
		},
		badgeWrapper: {
			position: 'absolute',
			flexDirection: 'row-reverse',
			justifyContent: 'flex-end',
			top: 9,
			right: 2,
			width: 30,
		},
		commonBadge: {
			borderRadius: 9,
			minWidth: 18,
			paddingHorizontal: 1,
			height: 18,
		},
		badge: (backgroundColor, countersLength, index) => {
			return {
				color: AppTheme.colors.baseWhiteFixed,
				backgroundColor,
				fontSize: 12,
				fontWeight: '500',
				textAlign: 'center',
				marginRight: index ? -16 : 0,
			};
		},
		dividerBadge: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			marginRight: -16,
		},
		indicator: {
			position: 'absolute',
			alignItems: 'center',
			justifyContent: 'center',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			width: 8,
			height: 8,
			left: 13,
			top: 0,
			borderRadius: 4,
		},
		time: {
			borderWidth: 2,
			borderColor: AppTheme.colors.base0,
			borderRadius: 10,
			backgroundColor: AppTheme.colors.accentSoftBlue2,
			paddingVertical: 4,
			paddingHorizontal: 6,
			marginTop: -18,
		},
		title: {
			marginTop: 3,
			fontSize: 9.5,
			color: AppTheme.colors.base4,
			textAlign: 'center',
		},
	};

	const EMPTY_ICON = `<svg width="19" height="17" viewBox="0 0 19 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2 0C1.44772 0 1 0.447715 1 1V3.26756C0.402199 3.61337 0 4.25972 0 5C0 5.74028 0.402199 6.38663 1 6.73244V10.2676C0.402199 10.6134 0 11.2597 0 12C0 12.7403 0.402199 13.3866 1 13.7324V16C1 16.5523 1.44772 17 2 17C2.55228 17 3 16.5523 3 16V13.7324C3.5978 13.3866 4 12.7403 4 12C4 11.2597 3.5978 10.6134 3 10.2676V6.73244C3.5978 6.38663 4 5.74028 4 5C4 4.25972 3.5978 3.61337 3 3.26756V1C3 0.447715 2.55228 0 2 0ZM7 2C6.44772 2 6 2.44772 6 3V6.5C6 7.05228 6.44772 7.5 7 7.5H18C18.5523 7.5 19 7.05228 19 6.5V3C19 2.44772 18.5523 2 18 2H7ZM6 10.5C6 9.94772 6.44772 9.5 7 9.5H18C18.5523 9.5 19 9.94772 19 10.5V14C19 14.5523 18.5523 15 18 15H7C6.44772 15 6 14.5523 6 14V10.5Z" fill="${AppTheme.colors.accentExtraDarkblue}"/></svg>`;

	const ICON = `<svg width="20" height="17" viewBox="0 0 20 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.06641 0C1.51412 0 1.06641 0.447715 1.06641 1V3.26756C0.468605 3.61337 0.0664062 4.25972 0.0664062 5C0.0664062 5.74028 0.468605 6.38663 1.06641 6.73244V10.2676C0.468605 10.6134 0.0664062 11.2597 0.0664062 12C0.0664062 12.7403 0.468605 13.3866 1.06641 13.7324V16C1.06641 16.5523 1.51412 17 2.06641 17C2.61869 17 3.06641 16.5523 3.06641 16V13.7324C3.66421 13.3866 4.06641 12.7403 4.06641 12C4.06641 11.2597 3.66421 10.6134 3.06641 10.2676V6.73244C3.66421 6.38663 4.06641 5.74028 4.06641 5C4.06641 4.25972 3.66421 3.61337 3.06641 3.26756V1C3.06641 0.447715 2.61869 0 2.06641 0ZM7.06641 2C6.51412 2 6.06641 2.44772 6.06641 3V6.5C6.06641 7.05228 6.51412 7.5 7.06641 7.5H18.0664C18.6187 7.5 19.0664 7.05228 19.0664 6.5V3C19.0664 2.44772 18.6187 2 18.0664 2H7.06641ZM6.06641 10.5C6.06641 9.94772 6.51412 9.5 7.06641 9.5H18.0664C18.6187 9.5 19.0664 9.94772 19.0664 10.5V14C19.0664 14.5523 18.6187 15 18.0664 15H7.06641C6.51412 15 6.06641 14.5523 6.06641 14V10.5Z" fill="${AppTheme.colors.accentExtraDarkblue}"/></svg>`;

	module.exports = { CounterComponent };
});
