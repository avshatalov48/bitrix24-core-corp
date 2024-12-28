/**
 * @module calendar/event-list-view/layout/invites-banner
 */
jn.define('calendar/event-list-view/layout/invites-banner', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { Text3 } = require('ui-system/typography/text');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { EventManager } = require('calendar/data-managers/event-manager');
	const { EventMeetingStatus, CalendarType, Counters } = require('calendar/enums');
	const { State, observeState } = require('calendar/event-list-view/state');

	class InvitesBanner extends LayoutComponent
	{
		render()
		{
			return View(
				{
					clickable: true,
					onClick: this.onBannerClick,
					style: {
						display: this.props.hide ? 'none' : 'flex',
						borderBottomWidth: this.props.hide ? 0 : 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						backgroundColor: Color.bgContentSecondary.toHex(),
					},
				},
				this.props.counter > 0 && this.renderContent(),
			);
		}

		renderContent()
		{
			return Area(
				{
					style: {
						backgroundColor: this.props.selected ? Color.accentSoftRed2.toHex() : Color.accentSoftRed3.toHex(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					this.renderText(),
					this.renderCounter(),
					this.renderIcon(),
				),
			);
		}

		renderText()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				Text3({
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_INVITES_BANNER_TITLE'),
					color: Color.base1,
					style: {
						fontWeight: this.props.selected ? '500' : '400',
					},
				}),
			);
		}

		renderCounter()
		{
			return View(
				{
					style: {
						paddingHorizontal: Indent.L.toNumber(),
					},
				},
				BadgeCounter({
					value: this.props.counter,
					testId: 'calendar_event_list_invites_counter',
					design: BadgeCounterDesign.ALERT,
				}),
			);
		}

		renderIcon()
		{
			return IconView({
				color: this.props.selected ? Color.base3 : Color.base4,
				icon: this.props.selected ? Icon.CROSS : Icon.CHEVRON_TO_THE_RIGHT,
				size: 24,
			});
		}

		onBannerClick = async () => {
			const selected = !this.props.selected;
			State.setInvitesSelected(selected);

			let eventIds = [];
			if (selected)
			{
				State.setIsLoading(true);

				eventIds = await EventManager.getEventsByFilter(this.getInvitationPresetData());
			}

			State.setFilterResultIds(eventIds);
		};

		getInvitationPresetData()
		{
			return {
				preset: {
					id: 'filter_calendar_meeting_status_q',
					fields: {
						IS_MEETING: 'Y',
						MEETING_STATUS: EventMeetingStatus.QUESTIONED,
					},
				},
				search: '',
				ownerId: State.ownerId,
				calType: State.calType,
			};
		}
	}

	const mapStateToProps = (state) => {
		const counter = State.calType === CalendarType.USER
			? state.counters[Counters.INVITES]
			: state.counters[Counters.GROUP_INVITES]
		;

		return {
			hide: state.isSearchMode && !state.invitesSelected,
			selected: state.invitesSelected,
			counter,
		};
	};

	module.exports = { InvitesBanner: observeState(InvitesBanner, mapStateToProps) };
});
