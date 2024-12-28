/**
 * @module calendar/event-edit-form/layout/slot-size-selector
 */
jn.define('calendar/event-edit-form/layout/slot-size-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent } = require('tokens');
	const { Duration } = require('utils/date');
	const { Link4, Link5, LinkDesign, LinkMode, Icon } = require('ui-system/blocks/link');
	const { Text4 } = require('ui-system/typography/text');

	const { State, observeState } = require('calendar/event-edit-form/state');
	const { DateHelper } = require('calendar/date-helper');
	const { SlotSizeMenu } = require('calendar/event-edit-form/menu/slot-size');

	/**
	 * @class SlotSizeSelector
	 */
	class SlotSizeSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				link: null,
			};

			this.menu = null;
			this.todayDayCode = DateHelper.getDayCode(new Date());
		}

		get slots()
		{
			return this.props.slots?.[this.props.selectedDate?.getDate()] ?? [];
		}

		render()
		{
			return View(
				{
					style: {
						display: this.slots.length > 0 ? 'flex' : 'none',
						justifyContent: 'space-between',
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: Indent.M.toNumber(),
					},
				},
				this.renderSlotSelector(),
				this.needToShowTodayLink() && this.renderTodayLink(),
			);
		}

		renderSlotSelector()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
					},
				},
				Text4({
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SLOT_SIZE_TITLE'),
					style: {
						marginRight: Indent.S.toNumber(),
					},
				}),
				Link4({
					testId: 'calendar-event-edit-form-slot-size',
					text: this.formatSlotSize(this.props.slotSize),
					design: LinkDesign.BLACK,
					accent: true,
					mode: LinkMode.DASH,
					rightIcon: Icon.CHEVRON_DOWN,
					onClick: this.onLinkClickHandler,
					forwardRef: this.#bindSelectorRef,
				}),
			);
		}

		#bindSelectorRef = (ref) => {
			this.refs.link = ref;
		};

		renderTodayLink()
		{
			return Link5(
				{
					testId: 'calendar-event-edit-form-today',
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_TODAY'),
					design: LinkDesign.LIGHT_GREY,
					mode: LinkMode.DASH,
					onClick: this.onTodayClickHandler,
					style: {
						marginLeft: Indent.S.toNumber(),
					},
				},
			);
		}

		needToShowTodayLink()
		{
			return this.todayDayCode !== this.props.selectedDayCode;
		}

		onLinkClickHandler = () => {
			this.menu = new SlotSizeMenu({
				slotSize: this.props.slotSize,
				targetElementRef: this.refs.link,
				onItemSelected: this.onSlotSizeSelectedHandler,
			});

			this.menu.show();
		};

		onTodayClickHandler = () => {
			State.setTodayButtonClick(true);
		};

		onSlotSizeSelectedHandler = (item) => {
			const slotSize = parseInt(item.id, 10);
			State.setSlotSize(slotSize);
		};

		formatSlotSize(slotSize)
		{
			return Duration.createFromMinutes(slotSize).format();
		}
	}

	const mapStateToProps = (state) => ({
		selectedDate: state.selectedDate,
		selectedDayCode: state.selectedDayCode,
		slotSize: state.slotSize,
		slots: state.slots,
	});

	module.exports = { SlotSizeSelector: observeState(SlotSizeSelector, mapStateToProps) };
});
