/**
 * @module tasks/layout/fields/time-tracking
 */
jn.define('tasks/layout/fields/time-tracking', (require, exports, module) => {
	const { isOnline } = require('device/connection');
	const { showOfflineToast } = require('toast');
	const { Color, Corner, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { Icon } = require('assets/icons');
	const { TimeTrackingSettingsWidget } = require('tasks/layout/fields/time-tracking/ui/settings-widget');
	const { toTimer } = require('tasks/layout/fields/time-tracking/time-utils');
	const { TimeTrackingTimer } = require('tasks/layout/fields/time-tracking/timer');
	const { FeatureId, TimerState } = require('tasks/enum');
	const { Loc } = require('tasks/loc');
	const { getFeatureRestriction } = require('tariff-plan-restriction');

	const TimerBackgroundColor = {
		[TimerState.OVERDUE]: Color.accentSoftRed3.toHex(),
		[TimerState.PAUSED]: Color.bgContentSecondary.toHex(),
		[TimerState.RUNNING]: Color.accentSoftBlue3.toHex(),
	};

	const TimerTextColor = {
		[TimerState.OVERDUE]: Color.accentMainAlert.toHex(),
		[TimerState.PAUSED]: Color.base4.toHex(),
		[TimerState.RUNNING]: Color.accentMainPrimaryalt.toHex(),
	};

	class TimeTrackingField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.bindContainerRef = this.bindContainerRef.bind(this);
			this.onContentClick = this.onContentClick.bind(this);

			this.fieldContainerRef = null;
			this.moreMenuButtonRef = null;
			this.menu = null;
		}

		bindContainerRef(ref)
		{
			this.fieldContainerRef = ref;
		}

		get testId()
		{
			return this.props.testId;
		}

		isEmpty()
		{
			return !this.props.value?.allowTimeTracking;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRestricted()
		{
			return getFeatureRestriction(FeatureId.TIME_TRACKING).isRestricted();
		}

		validate()
		{
			return true;
		}

		isValid()
		{
			return true;
		}

		isRequired()
		{
			return Boolean(this.props.required);
		}

		getId()
		{
			return this.props.id;
		}

		hasUploadingFiles()
		{
			return false;
		}

		getConfig()
		{
			return this.props.config || {};
		}

		getParentWidget()
		{
			return this.getConfig().parentWidget;
		}

		/**
		 * @public
		 */
		onContentClick()
		{
			if (this.isRestricted())
			{
				this.#showTariffPlanRestriction();

				return;
			}

			if (this.isReadOnly())
			{
				return;
			}

			if (!isOnline())
			{
				showOfflineToast({}, this.getParentWidget());

				return;
			}

			this.#openTimeTrackingSettings();
		}

		#showTariffPlanRestriction()
		{
			getFeatureRestriction(FeatureId.TIME_TRACKING).showRestriction({
				parentWidget: this.getParentWidget(),
				onHidden: () => this.props.onSettingsWidgetClose?.(),
			});
		}

		#showContextMenu()
		{
			if (this.isReadOnly() || !this.moreMenuButtonRef)
			{
				return;
			}

			if (!isOnline())
			{
				showOfflineToast({}, this.getParentWidget());

				return;
			}

			const { testId, value } = this.props;
			const { isTimerRunningForCurrentUser } = value;

			const items = [
				{
					id: isTimerRunningForCurrentUser ? 'menu_item_pause' : 'menu_item_play',
					testId: isTimerRunningForCurrentUser ? `${testId}_menu_item_pause` : `${testId}_menu_item_play`,
					type: 'base',
					title: isTimerRunningForCurrentUser
						? Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_PAUSE_TIMER')
						: Loc.getMessage('M_TASKS_TIME_TRACKING_WIDGET_START_TIMER'),
					sectionCode: '0',
					iconName: isTimerRunningForCurrentUser ? Icon.PAUSE.getIconName() : Icon.PLAY.getIconName(),
				},
				{
					id: 'menu_item_settings',
					testId: `${testId}_menu_item_settings`,
					title: Loc.getMessage('M_TASKS_EDIT'),
					iconName: Icon.EDIT.getIconName(),
					type: 'base',
					sectionCode: '0',
				},
			];

			const sections = [{ id: '0' }];

			if (!this.menu)
			{
				this.menu = dialogs.createPopupMenu();
				this.menu.on('itemSelected', this.#onContextMenuItemClick);
			}

			this.menu.setTarget(this.moreMenuButtonRef);
			this.menu.setData(items, sections);

			this.menu.show();
		}

		#onContextMenuItemClick = (item) => {
			if (item?.id === 'menu_item_settings')
			{
				this.#openTimeTrackingSettings();
			}
			else if (item?.id === 'menu_item_play' || item?.id === 'menu_item_pause')
			{
				const isTimerRunningForCurrentUser = (item.id === 'menu_item_pause');
				const { value, onToggleTimer } = this.props;
				const { taskId } = value;
				if (onToggleTimer)
				{
					onToggleTimer({ isTimerRunningForCurrentUser, taskId, layout: this.getParentWidget() });
				}
			}
		};

		#openTimeTrackingSettings()
		{
			const {
				allowTimeTracking = false,
				timeEstimate = 0,
			} = this.props.value || {};

			TimeTrackingSettingsWidget.open({
				allowTimeTracking,
				timeEstimate,
				parentWidget: this.getParentWidget(),
				onChange: (nextValue) => {
					this.props.onChange?.({
						...this.props.value,
						...nextValue,
					});
				},
				onClose: () => {
					this.props.onSettingsWidgetClose?.();
				},
			});
		}

		render()
		{
			const { ThemeComponent, value, testId, onTimeOver } = this.props;

			if (ThemeComponent)
			{
				return this.props.ThemeComponent({ field: this });
			}

			const {
				timeElapsed = 0,
				timeEstimate = 0,
				timerState,
				isTimerRunningForCurrentUser,
			} = value || {};

			return View(
				{
					testId: `${testId}_${timerState}`,
					style: {
						backgroundColor: TimerBackgroundColor[timerState],
						paddingVertical: Indent.XS.getValue(),
						paddingLeft: Indent.XL.getValue(),
						marginVertical: Indent.M.getValue(),
						borderRadius: Corner.M.getValue(),
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
					},
					onClick: () => (this.isRestricted() ? this.#showTariffPlanRestriction() : this.#showContextMenu()),
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
							alignItems: 'center',
						},
					},
					new TimeTrackingTimer({
						timeEstimate,
						onTimeOver,
						testId: `${testId}_TimeElapsed`,
						color: TimerTextColor[timerState],
						seconds: timeElapsed,
						isActive: isTimerRunningForCurrentUser,
					}),
					timeEstimate > 0 && Text4({
						testId: `${testId}_TimeEstimate`,
						text: ` / ${toTimer(timeEstimate)}`,
						style: {
							color: TimerTextColor[timerState],
						},
					}),
				),
				View(
					{
						testId: `${testId}_Icons`,
						style: {
							height: 32,
							paddingHorizontal: Indent.XL.getValue(),
							alignItems: 'center',
							justifyContent: 'space-between',
							flexDirection: 'row',
						},
					},
					(timerState === TimerState.OVERDUE) && Image({
						named: Icon.ALERT.getIconName(),
						tintColor: TimerTextColor[timerState],
						testId: `${testId}_Icons_Alert`,
						style: {
							width: 22,
							height: 22,
						},
					}),
					this.isRestricted() && Image({
						named: Icon.LOCK.getIconName(),
						tintColor: Color.base1.toHex(),
						testId: `${testId}_Icons_Lock`,
						style: {
							width: 22,
							height: 22,
							marginLeft: Indent.XL.getValue(),
						},
					}),
					!this.isRestricted() && !this.isReadOnly() && Image({
						named: Icon.MORE.getIconName(),
						tintColor: TimerTextColor[timerState],
						testId: `${testId}_Icons_More`,
						ref: (ref) => {
							this.moreMenuButtonRef = ref;
						},
						style: {
							width: 22,
							height: 22,
							marginLeft: Indent.XL.getValue(),
						},
					}),
				),
			);
		}
	}

	module.exports = {
		TimeTrackingField: (props) => new TimeTrackingField(props),
		TimeTrackingFieldClass: TimeTrackingField,
	};
});
