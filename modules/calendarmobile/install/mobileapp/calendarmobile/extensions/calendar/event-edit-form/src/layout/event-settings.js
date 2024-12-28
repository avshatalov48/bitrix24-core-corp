/**
 * @module calendar/event-edit-form/layout/event-settings
 */
jn.define('calendar/event-edit-form/layout/event-settings', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Corner, Indent, Component } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { Card } = require('ui-system/layout/card');
	const { Text3, Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { Duration } = require('utils/date');
	const { ColorPicker } = require('ui-system/popups/color-picker');
	const { BottomSheet } = require('bottom-sheet');

	const { State, observeState } = require('calendar/event-edit-form/state');
	const { ReminderMenu } = require('calendar/event-edit-form/menu/reminder');
	const { AccessibilityMenu } = require('calendar/event-edit-form/menu/accessibility');
	const { ImportanceMenu } = require('calendar/event-edit-form/menu/importance');
	const { BooleanMenu } = require('calendar/event-edit-form/menu/boolean');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { CalendarType } = require('calendar/enums');

	/**
	 * @class EventSettings
	 */
	class EventSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;

			this.refs = {
				color: null,
				reminder: null,
				accessibility: null,
				importance: null,
				privateEvent: null,
			};

			this.reminderMenu = null;
			this.accessibilityMenu = null;
			this.importanceMenu = null;
			this.privateEventMenu = null;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				this.renderFields(),
			);
		}

		renderFields()
		{
			return Area(
				{
					isFirst: true,
					style: {
						flex: 1,
					},
				},
				UIScrollView(
					{
						style: {
							flex: 1,
						},
					},
					this.renderReminderField(),
					this.renderColorField(),
					this.renderAccessibilityField(),
					this.renderImportanceField(),
					this.renderPrivateEventField(),
				),
			);
		}

		renderColorField()
		{
			return Field({
				testId: 'calendar-event-edit-form-color-field',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_COLOR'),
				onClick: this.openColorPicker,
				content: FieldChevron(
					View({
						testId: 'calendar-event-edit-form-color-value',
						style: {
							width: 16,
							height: 16,
							borderRadius: Corner.XS.toNumber(),
							backgroundColor: this.props.color,
							marginHorizontal: Indent.S.toNumber(),
						},
					}),
					this.#bindColorRef,
				),
			});
		}

		#bindColorRef = (ref) => {
			this.refs.color = ref;
		};

		renderReminderField()
		{
			return Field({
				testId: 'calendar-event-edit-form-reminder-field',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_REMINDER'),
				onClick: this.openReminderMenu,
				content: FieldChevron(
					Text4({
						testId: 'calendar-event-edit-form-reminder-value',
						text: this.getReminderTitle(this.props.reminder),
						color: Color.base4,
					}),
					this.#bindReminderRef,
				),
			});
		}

		#bindReminderRef = (ref) => {
			this.refs.reminder = ref;
		};

		renderAccessibilityField()
		{
			return Field({
				testId: 'calendar-event-edit-form-accessibility-field',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_ACCESSIBILITY'),
				onClick: this.openAccessibilityMenu,
				content: FieldChevron(
					Text4({
						testId: 'calendar-event-edit-form-accessibility-value',
						text: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_ACCESSIBILITY_${this.props.accessibility.toUpperCase()}`),
						color: Color.base4,
					}),
					this.#bindAccessibilityRef,
				),
			});
		}

		#bindAccessibilityRef = (ref) => {
			this.refs.accessibility = ref;
		};

		renderImportanceField()
		{
			return Field({
				testId: 'calendar-event-edit-form-importance-field',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_IMPORTANCE'),
				onClick: this.openImportanceMenu,
				content: FieldChevron(
					Text4({
						testId: 'calendar-event-edit-form-importance-value',
						text: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_IMPORTANCE_${this.props.importance.toUpperCase()}`),
						color: Color.base4,
					}),
					this.#bindImportanceRef,
				),
			});
		}

		#bindImportanceRef = (ref) => {
			this.refs.importance = ref;
		};

		renderPrivateEventField()
		{
			return Field({
				testId: 'calendar-event-edit-form-private-event-field',
				text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_PRIVATE_EVENT'),
				onClick: this.openPrivateEventMenu,
				content: FieldChevron(
					Text4({
						testId: 'calendar-event-edit-form-private-event-value',
						text: Loc.getMessage(`M_CALENDAR_EVENT_EDIT_FORM_BOOLEAN_${this.props.privateEvent.toUpperCase()}`),
						color: Color.base4,
					}),
					this.#bindPrivateEventRef,
				),
			});
		}

		#bindPrivateEventRef = (ref) => {
			this.refs.privateEvent = ref;
		};

		openColorPicker = () => {
			const onChangeHandler = (color) => {
				State.setColor(color);
			};

			ColorPicker.show({
				testId: 'calendar-event-edit-form-color-picker',
				parentWidget: this.layout,
				title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_COLOR'),
				buttonText: Loc.getMessage('M_CALENDAR_EVENT_EDIT_SELECT'),
				inputLabel: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_COLOR_SELECTED'),
				currentColor: this.props.color?.toUpperCase(),
				onChange: onChangeHandler,
			});
		};

		openReminderMenu = () => {
			const onItemSelected = (item) => {
				const reminder = parseInt(item.id, 10);
				State.setReminder(reminder);
			};

			this.reminderMenu = new ReminderMenu({
				reminder: this.props.reminder,
				targetElementRef: this.refs.reminder,
				onItemSelected,
			});

			this.reminderMenu.show();
		};

		openAccessibilityMenu = () => {
			const onItemSelected = (item) => {
				State.setAccessibility(item.id);
			};

			this.accessibilityMenu = new AccessibilityMenu({
				accessibility: this.props.accessibility,
				targetElementRef: this.refs.accessibility,
				onItemSelected,
			});

			this.accessibilityMenu.show();
		};

		openImportanceMenu = () => {
			const onItemSelected = (item) => {
				State.setImportance(item.id);
			};

			this.importanceMenu = new ImportanceMenu({
				importance: this.props.importance,
				targetElementRef: this.refs.importance,
				onItemSelected,
			});

			this.importanceMenu.show();
		};

		openPrivateEventMenu = () => {
			const onItemSelected = (item) => {
				State.setPrivateEvent(item.id);
			};

			this.privateEventMenu = new BooleanMenu({
				selected: this.props.privateEvent,
				targetElementRef: this.refs.privateEvent,
				onItemSelected,
			});

			this.privateEventMenu.show();
		};

		getReminderTitle(minutes)
		{
			if (minutes === 0)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_BOOLEAN_N');
			}

			return this.formatMinutes(minutes);
		}

		formatMinutes(minutes)
		{
			return Duration.createFromMinutes(minutes).format();
		}

		/**
		 * @public
		 * @param {PageManager} parentLayout
		 * @return void
		 */
		static open(parentLayout)
		{
			const component = (layout) => new this({
				layout,
			});

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_SETTINGS'),
					type: 'wizard',
				})
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setMediumPositionPercent(60)
				.disableOnlyMediumPosition()
				.open()
			;
		}
	}

	const Field = ({ testId, text, onClick, content }) => Card(
		{
			testId,
			border: true,
			style: {
				marginBottom: Component.cardListGap.toNumber(),
				flexDirection: 'row',
				alignItems: 'center',
				justifyContent: 'space-between',
			},
			onClick,
		},
		Text3({ text }),
		content,
	);

	const FieldChevron = (view, refFunc) => View(
		{
			style: {
				flex: 1,
				alignContent: 'flex-end',
				justifyContent: 'flex-end',
				flexDirection: 'row',
				alignItems: 'center',
			},
		},
		view,
		IconView({
			icon: Icon.CHEVRON_TO_THE_RIGHT,
			size: 20,
			color: Color.base4,
			forwardRef: refFunc,
		}),
	);

	const getColor = (state) => {
		if (Type.isStringFilled(state.color))
		{
			return state.color;
		}

		if (state.calType === CalendarType.USER && env.isCollaber)
		{
			return Color.collabAccentPrimary.toHex();
		}

		return SectionManager.getSectionColor(state.sectionId);
	};

	const mapStateToProps = (state) => ({
		color: getColor(state),
		reminder: state.reminder,
		accessibility: state.accessibility,
		importance: state.importance,
		privateEvent: state.privateEvent,
		sectionId: state.sectionId,
	});

	module.exports = { EventSettings: observeState(EventSettings, mapStateToProps) };
});
