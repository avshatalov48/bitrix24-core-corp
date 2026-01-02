/**
 * @module calendar/event-edit-form/layout/slot/list
 */
jn.define('calendar/event-edit-form/layout/slot/list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { UIScrollView } = require('layout/ui/scroll-view');

	const { SlotItem, slotItemHeight } = require('calendar/event-edit-form/layout/slot/item');
	const { SlotListSkeleton } = require('calendar/event-edit-form/layout/slot/list-skeleton');
	const { SlotListEmpty } = require('calendar/event-edit-form/layout/slot/list-empty');
	const { SlotCustom } = require('calendar/event-edit-form/layout/slot/custom');
	const { State, observeState } = require('calendar/event-edit-form/state');
	const { CustomTimePage } = require('calendar/event-edit-form/pages/custom-time-page');
	const { DateHelper } = require('calendar/date-helper');

	const isAndroid = Application.getPlatform() !== 'ios';

	/**
	 * @class SlotList
	 */
	class SlotList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				scrollRef: null,
			};

			void State.loadAccessibility();
		}

		get slots()
		{
			return this.props.slots?.[this.props.selectedDate?.getDate()] ?? [];
		}

		componentWillReceiveProps(props)
		{
			const dateChanged = this.props.selectedDate.getTime() !== props.selectedDate.getTime();
			const loaderChanged = props.isLoading !== this.props.isLoading;
			this.dontScroll = !dateChanged && !loaderChanged;
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (this.props.selectedDate !== prevProps.selectedDate)
			{
				if (isAndroid)
				{
					setTimeout(() => {
						this.scrollToSlot();
					}, 100);
				}
				else
				{
					this.scrollToSlot();
				}
			}
		}

		render()
		{
			return View(
				{
					testId: 'calendar-event-edit-form-slot-list',
					style: {
						flex: 1,
					},
				},
				(this.props.isLoading) && SlotListSkeleton(),
				this.hasToRenderCustom() && this.renderCustom(),
				this.hasToRenderList() && this.renderList(),
				this.hasToRenderEmpty() && SlotListEmpty({
					showButton: this.hasToShowEmptyButton(),
					onButtonClick: this.onCustomEditClick,
				}),
			);
		}

		renderCustom()
		{
			return SlotCustom({
				selected: this.props.isCustomSelected,
				text: this.getCustomText(),
				onSlotSelected: this.onCustomClick,
				onEditClick: this.onCustomEditClick,
			});
		}

		renderList()
		{
			const selectedSlot = this.props.selectedSlot;
			const lastSlot = this.slots.at(-1);

			return UIScrollView(
				{
					style: {
						flex: 1,
						opacity: 0,
					},
					showsVerticalScrollIndicator: false,
					ref: this.#bindScrollRef,
				},
				View(
					{},
					...this.slots.map((slot) => SlotItem({
						slot,
						selectedSlot,
						lastSlot,
						onSlotSelected: this.onSlotSelected,
						onLayout: this.scrollToSlot,
					})),
				),
			);
		}

		#bindScrollRef = (ref) => {
			this.refs.scrollRef = ref;
			ref?.animate({ duration: 200, opacity: 1 });
		};

		onSlotSelected = (slot) => {
			State.setSelectedSlot(slot);
		};

		scrollToSlot = () => {
			if (this.dontScroll || !this.refs.scrollRef)
			{
				return;
			}

			const slotToScroll = this.getSlotToScroll(this.props);
			if (!slotToScroll)
			{
				return;
			}

			const position = this.slots.map((slot) => slot.id).indexOf(slotToScroll.id);
			if (position >= 0)
			{
				const borderOffset = isAndroid ? 1 : 0;
				this.refs.scrollRef.scrollTo({
					y: position * slotItemHeight - borderOffset,
					animated: isAndroid,
				});
			}
		};

		getSlotToScroll(props)
		{
			const slots = props.slots?.[props.selectedDate?.getDate()] ?? [];

			const slotsContainSelectedSlot = slots.map((slot) => slot.id).includes(props.selectedSlot?.id);
			if (!slotsContainSelectedSlot)
			{
				const startWorkDay = new Date(props.selectedDate).setHours(9, 0);

				return slots.find((slot) => slot.from >= startWorkDay);
			}

			return props.selectedSlot;
		}

		getCustomText()
		{
			if (this.props.hasCustomPeriod)
			{
				if (this.isSameDate())
				{
					if (this.props.fullDay)
					{
						return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATE', {
							'#DATE#': this.getCustomDateFormatted(this.props.customDateFrom / 1000, this.props.fullDay),
						});
					}

					return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_DATE_FROM_TO', {
						'#DATE#': this.getCustomDateFormatted(this.props.customDateFrom / 1000, true),
						'#FROM#': DateHelper.formatTimestamp(this.props.customDateFrom / 1000, DateHelper.getTimeFormat()),
						'#TO#': DateHelper.formatTimestamp(this.props.customDateTo / 1000, DateHelper.getTimeFormat()),
					});
				}

				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_FROM_TO', {
					'#FROM#': this.getCustomDateFormatted(this.props.customDateFrom / 1000, this.props.fullDay),
					'#TO#': this.getCustomDateFormatted(this.props.customDateTo / 1000, this.props.fullDay),
				});
			}

			return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_CUSTOM_SLOT');
		}

		getCustomDateFormatted(timestamp, isFullDay)
		{
			const format = isFullDay
				? DateHelper.getDateFormat(timestamp)
				: DateHelper.getDatetimeFormat(timestamp)
			;

			return DateHelper.formatTimestamp(timestamp, format);
		}

		onCustomClick = () => {
			if (!this.props.hasCustomPeriod)
			{
				this.onCustomEditClick();

				return;
			}

			State.setIsCustomSelected(true);
		};

		onCustomEditClick = () => {
			CustomTimePage.open(this.props.layout);
		};

		hasToRenderCustom()
		{
			return !this.props.isLoading && (this.slots.length > 0 ? true : this.props.hasCustomPeriod);
		}

		hasToRenderList()
		{
			return !this.props.isLoading && this.slots.length > 0;
		}

		hasToRenderEmpty()
		{
			return !this.props.isLoading && this.slots.length === 0 && !this.props.hasCustomPeriod;
		}

		hasToShowEmptyButton()
		{
			const today = new Date().setHours(0, 0, 0, 0);

			return this.props.selectedDate >= today;
		}

		isSameDate()
		{
			const dateFrom = new Date(this.props.customDateFrom);
			const dateTo = new Date(this.props.customDateTo);

			return DateHelper.getDayCode(dateFrom) === DateHelper.getDayCode(dateTo);
		}
	}

	const mapStateToProps = (state) => ({
		isLoading: !state.hasAccessibility,
		slots: state.slots,
		selectedDate: state.selectedDate,
		selectedSlot: state.selectedSlot,
		customDateFrom: state.customDateFrom,
		customDateTo: state.customDateTo,
		isCustomSelected: state.isCustomSelected,
		hasCustomPeriod: state.hasCustomPeriod,
		fullDay: state.fullDay,
	});

	module.exports = { SlotList: observeState(SlotList, mapStateToProps) };
});
