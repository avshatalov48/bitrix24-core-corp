/**
 * @module calendar/event-edit-form/layout/slot-list
 */
jn.define('calendar/event-edit-form/layout/slot-list', (require, exports, module) => {
	const { UIScrollView } = require('layout/ui/scroll-view');

	const { SlotItem, slotItemHeight } = require('calendar/event-edit-form/layout/slot-item');
	const { SlotListSkeleton } = require('calendar/event-edit-form/layout/slot-list-skeleton');
	const { SlotListEmptyState } = require('calendar/event-edit-form/layout/slot-list-empty-state');
	const { State, observeState } = require('calendar/event-edit-form/state');

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
				(!this.props.isLoading && this.slots.length > 0) && this.renderList(),
				(!this.props.isLoading && this.slots.length === 0) && SlotListEmptyState(),
			);
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
				this.refs.scrollRef.scrollTo({
					y: position * slotItemHeight,
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
	}

	const mapStateToProps = (state) => ({
		isLoading: !state.hasAccessibility,
		slots: state.slots,
		selectedDate: state.selectedDate,
		selectedSlot: state.selectedSlot,
	});

	module.exports = { SlotList: observeState(SlotList, mapStateToProps) };
});
