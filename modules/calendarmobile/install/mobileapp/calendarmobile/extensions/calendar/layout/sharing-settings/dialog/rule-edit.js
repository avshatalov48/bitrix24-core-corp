/**
 * @module calendar/layout/sharing-settings/dialog/rule-edit
 */
jn.define('calendar/layout/sharing-settings/dialog/rule-edit', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Duration } = require('utils/date');
	const { plus, chevronLeft } = require('assets/common');
	const { lighten, withPressed } = require('utils/color');

	const { SharingContext } = require('calendar/model/sharing');
	const { SelectField } = require('calendar/layout/fields');
	const { RangeEditComponent } = require('calendar/layout/sharing-settings/dialog/range-edit');

	/**
	 * @class RuleEditDialog
	 */
	class RuleEditDialog extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget || PageManager;

			this.hasChanges = false;
			this.rangeHeight = 0;
			this.customEventEmitter = this.props.customEventEmitter;

			this.onHeaderClickHandler = this.onHeaderClickHandler.bind(this);
			this.onAddRangeButtonClickHandler = this.onAddRangeButtonClickHandler.bind(this);
			this.onRangeRemoveHandler = this.onRangeRemoveHandler.bind(this);
			this.emitRuleSave = this.emitRuleSave.bind(this);

			this.state = this.getState();
		}

		get model()
		{
			return this.props.model;
		}

		get rule()
		{
			return this.model.getSettings().getRule();
		}

		get isCrmContext()
		{
			return this.model.getContext() === SharingContext.CRM;
		}

		componentDidMount()
		{
			this.layoutWidget.on('onViewRemoved', this.emitRuleSave);
			this.layoutWidget.on('onViewHidden', this.emitRuleSave);

			super.componentDidMount();
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();

			this.layoutWidget.off('onViewRemoved', this.emitRuleSave);
			this.layoutWidget.off('onViewHidden', this.emitRuleSave);
		}

		redraw()
		{
			this.setState(this.getState());
		}

		getState()
		{
			return {
				ranges: this.rule.getRanges(),
				canAddRange: this.rule.canAddRange(),
				slotSize: this.rule.getSlotSize(),
				formattedSlotSize: this.rule.getFormattedSlotSize(),
			};
		}

		render()
		{
			return View(
				{},
				this.renderHeader(),
				this.renderEditRanges(),
				this.renderEditSlotSize(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: styles.header,
					clickable: true,
					onClick: this.onHeaderClickHandler,
				},
				this.renderLeftArrow(),
				this.renderTitle(),
			);
		}

		onHeaderClickHandler()
		{
			this.emitRuleSave();
			this.layoutWidget.close();
		}

		renderLeftArrow()
		{
			return Image({
				tintColor: AppTheme.colors.base3,
				svg: {
					content: chevronLeft(),
				},
				style: styles.leftArrowIcon,
			});
		}

		renderTitle()
		{
			return Text({
				text: Loc.getMessage('M_CALENDAR_SETTINGS_DESCRIPTION'),
				style: styles.title,
			});
		}

		renderEditRanges()
		{
			return View(
				{
					style: styles.editRangesContainer,
				},
				...this.state.ranges.map((range) => this.renderEditRange(range)),
				this.state.canAddRange && this.renderAddRangeButton(),
			);
		}

		renderEditRange(range)
		{
			return new RangeEditComponent({
				isCrmContext: this.isCrmContext,
				range,
				rule: this.rule,
				layoutWidget: this.layoutWidget,
				onRemove: this.onRangeRemoveHandler,
				rangeHeight: this.rangeHeight,
				onHeightCalculated: (height) => {
					this.rangeHeight = height;
				},
				onRuleUpdated: () => {
					this.hasChanges = true;
					this.emitRuleUpdated();
				},
			});
		}

		renderAddRangeButton()
		{
			return View(
				{
					style: styles.addRangeButtonContainer,
				},
				View(
					{
						style: styles.addRangeButton,
					},
					!this.isCrmContext && Image({
						tintColor: AppTheme.colors.accentMainLinks,
						svg: {
							content: plus(),
						},
						style: styles.plusIcon,
					}),
					View(
						{
							style: {
								...styles.addRangeButtonTextContainer,
								...(!this.isCrmContext ? styles.addRangeButtonTextContainerBorder : {}),
							},
						},
						Button({
							onClick: this.onAddRangeButtonClickHandler,
							text: Loc.getMessage('M_CALENDAR_SETTINGS_SELECT_ADD_MSGVER_1'),
							style: styles.addRangeButtonText,
						}),
					),
				),
			);
		}

		renderEditSlotSize()
		{
			return View(
				{
					style: styles.editSlotSizeContainer,
				},
				this.renderSlotSizeTitle(),
				this.renderSlotSize(),
			);
		}

		renderSlotSizeTitle()
		{
			return View(
				{
					style: styles.slotSizeTitle,
				},
				Text({
					text: Loc.getMessage('M_CALENDAR_SETTINGS_MEETINGS_DURATION'),
					style: styles.slotSizeTitleText,
				}),
			);
		}

		renderSlotSize()
		{
			const items = this.rule.getAvailableSlotSizes().map((slotSize) => {
				return {
					value: slotSize,
					name: Duration.createFromMinutes(slotSize).format(),
				};
			});

			return new SelectField({
				title: Loc.getMessage('M_CALENDAR_SETTINGS_MEETINGS_DURATION'),
				items,
				currentItem: {
					value: this.state.slotSize,
					name: this.state.formattedSlotSize,
				},
				onChange: (value) => this.onSlotSizeSelectedHandler(value),
				style: {
					field: this.isCrmContext ? styles.field : null,
					text: this.isCrmContext ? styles.fieldText : null,
				},
			});
		}

		onRangeRemoveHandler()
		{
			this.redraw();
			this.hasChanges = true;
			this.emitRuleUpdated();
		}

		onAddRangeButtonClickHandler()
		{
			const workTimeStart = this.model.getSettings().getWorkTimeStart();
			const workTimeEnd = this.model.getSettings().getWorkTimeEnd();
			const workDays = this.model.getSettings().getWorkDays();

			this.rule.addRange({
				from: parseInt(workTimeStart * 60, 10),
				to: parseInt(workTimeEnd * 60, 10),
				weekdays: workDays,
			});
			this.redraw();
			this.hasChanges = true;
			this.emitRuleUpdated();
		}

		onSlotSizeSelectedHandler(value)
		{
			this.rule.setSlotSize(value);
			this.redraw();
			this.hasChanges = true;
			this.emitRuleUpdated();
		}

		emitRuleUpdated()
		{
			this.customEventEmitter.emit('CalendarSharing:RuleUpdated');
		}

		emitRuleSave()
		{
			if (this.hasChanges)
			{
				this.customEventEmitter.emit('CalendarSharing:RuleSave');
				this.hasChanges = false;
			}
		}
	}

	const containerStyle = {
		backgroundColor: AppTheme.colors.bgContentPrimary,
		borderRadius: 12,
		marginBottom: 10,
		paddingHorizontal: 18,
		paddingVertical: 22,
	};

	const styles = {
		header: {
			flexDirection: 'row',
			alignItems: 'center',
			paddingVertical: 12,
			paddingHorizontal: 14,
		},
		leftArrowIcon: {
			width: 23,
			height: 23,
		},
		title: {
			marginLeft: 10,
			fontSize: 17,
			color: AppTheme.colors.base1,
			flex: 1,
		},
		editRangesContainer: {
			...containerStyle,
			paddingHorizontal: 0,
		},
		addRangeButtonContainer: {
			marginTop: 10,
			alignItems: 'center',
			justifyContent: 'center',
		},
		addRangeButton: {
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'center',
		},
		plusIcon: {
			width: 20,
			height: 20,
		},
		addRangeButtonTextContainer: {
			marginLeft: 4,
		},
		addRangeButtonTextContainerBorder: {
			borderBottomColor: lighten(AppTheme.colors.accentMainLinks, 0.4),
			borderBottomWidth: 2,
			borderStyle: 'dash',
			borderDashSegmentLength: 5,
			borderDashGapLength: 5,
		},
		addRangeButtonText: {
			color: withPressed(AppTheme.colors.accentMainLinks),
			fontSize: 14,
			fontWeight: '400',
			height: 20,
		},
		editSlotSizeContainer: {
			...containerStyle,
			flexDirection: 'row',
			alignItems: 'center',
		},
		slotSizeTitle: {
			flex: 1,
		},
		slotSizeTitleText: {
			fontSize: 16,
			color: AppTheme.colors.base1,
		},
		field: {
			borderColor: AppTheme.colors.base6,
			borderWidth: 1,
			borderRadius: 6,
			backgroundColor: undefined,
			paddingHorizontal: 10,
		},
		fieldText: {
			color: AppTheme.colors.base1,
		},
	};

	module.exports = { RuleEditDialog };
});
