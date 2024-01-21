/**
 * @module calendar/layout/sharing-settings/dialog/rule-edit
 */
jn.define('calendar/layout/sharing-settings/dialog/rule-edit', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Duration } = require('utils/date');
	const { plus, chevronLeft } = require('assets/common');
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

			this.hasChanges = false;
			this.layoutWidget = null;
			this.rangeHeight = 0;
			this.customEventEmitter = this.props.customEventEmitter;

			this.onAddRangeButtonClickHandler = this.onAddRangeButtonClickHandler.bind(this);
			this.onRangeRemoveHandler = this.onRangeRemoveHandler.bind(this);
		}

		get model()
		{
			return this.props.model;
		}

		get rule()
		{
			return this.model.getSettings().getRule();
		}

		setLayoutWidget(widget)
		{
			this.layoutWidget = widget;
			this.layoutWidget.on('onViewRemoved', () => {
				this.emitRuleSave();
			});
			this.layoutWidget.on('onViewHidden', () => {
				this.emitRuleSave();
			});
		}

		redraw()
		{
			this.setState({ time: Date.now() });
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
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingVertical: 12,
						paddingHorizontal: 14,
					},
					clickable: true,
					onClick: () => {
						this.emitRuleSave();
						this.layoutWidget.close();
					},
				},
				this.renderLeftArrow(),
				this.renderTitle(),
			);
		}

		renderLeftArrow()
		{
			return Image(
				{
					tintColor: AppTheme.colors.base3,
					svg: {
						content: chevronLeft(),
					},
					style: {
						width: 23,
						height: 23,
					},
				},
			);
		}

		renderTitle()
		{
			return Text(
				{
					style: {
						marginLeft: 10,
						fontSize: 17,
						color: AppTheme.colors.base1,
						flex: 1,
					},
					text: Loc.getMessage('M_CALENDAR_SETTINGS_DESCRIPTION'),
				},
			);
		}

		renderEditRanges()
		{
			return View(
				{
					style: {
						...styles.container,
						paddingHorizontal: 0,
					},
				},
				...this.rule.getRanges().map((range) => this.renderEditRange(range)),
				this.rule.canAddRange() && this.renderAddRangeButton(),
			);
		}

		renderEditRange(range)
		{
			return new RangeEditComponent({
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
					style: {
						marginTop: 10,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'center',
						},
						clickable: true,
						onClick: this.onAddRangeButtonClickHandler,
					},
					Image(
						{
							tintColor: AppTheme.colors.accentMainLinks,
							svg: {
								content: plus(),
							},
							style: {
								width: 20,
								height: 20,
							},
						},
					),
					View(
						{
							style: {
								borderBottomColor: AppTheme.colors.accentSoftBlue1,
								borderBottomWidth: 2,
								borderStyle: 'dash',
								borderDashSegmentLength: 5,
								borderDashGapLength: 5,
								// marginVertical: 5,
								marginLeft: 4,
							},
						},
						Text(
							{
								style: {
									color: AppTheme.colors.accentMainLinks,
									fontSize: 14,
									fontWeight: '400',
								},
								text: Loc.getMessage('M_CALENDAR_SETTINGS_SELECT_ADD'),
							},
						),
					),
				),
			);
		}

		renderEditSlotSize()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						...styles.container,
					},
				},
				this.renderSlotSizeTitle(),
				this.renderSlotSize(),
			);
		}

		renderSlotSizeTitle()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				Text(
					{
						style: {
							fontSize: 16,
							color: AppTheme.colors.base1,
						},
						text: Loc.getMessage('M_CALENDAR_SETTINGS_MEETINGS_DURATION'),
					},
				),
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
					value: this.rule.getSlotSize(),
					name: this.rule.getFormattedSlotSize(),
				},
				onChange: (value) => this.onSlotSizeSelectedHandler(value),
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
				weekDays: workDays,
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

	const styles = {
		container: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			marginBottom: 10,
			paddingHorizontal: 18,
			paddingVertical: 22,
		},
	};

	module.exports = { RuleEditDialog };
});
