/**
 * @module calendar/layout/sharing-settings/settings
 */
jn.define('calendar/layout/sharing-settings/settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { chevronRight } = require('assets/common');
	const { SharingSettingsRule } = require('calendar/layout/sharing-settings/rule');
	const { BottomSheet } = require('bottom-sheet');
	const { RuleEditDialog } = require('calendar/layout/sharing-settings/dialog/rule-edit');
	const { EventEmitter } = require('event-emitter');
	const { SharingAjax } = require('calendar/ajax');
	const { Analytics } = require('calendar/sharing/analytics');

	/**
	 * @class SharingSettings
	 */
	class SharingSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.readOnly = this.props.readOnly || false;

			if (this.props.customEventEmitter)
			{
				this.customEventEmitter = this.props.customEventEmitter;
			}
			else
			{
				// eslint-disable-next-line no-undef
				this.uid = Random.getString();
				this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			}

			this.showReadOnlyWarning = this.showReadOnlyWarning.bind(this);
			this.openRuleEditBackdrop = this.openRuleEditBackdrop.bind(this);
			this.onRuleUpdated = this.onRuleUpdated.bind(this);
			this.onRuleSave = this.onRuleSave.bind(this);

			Analytics.sendPopupOpened(this.model.getContext());
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		get model()
		{
			return this.props.model;
		}

		bindEvents()
		{
			this.customEventEmitter.on('CalendarSharing:RuleUpdated', this.onRuleUpdated);
			this.customEventEmitter.on('CalendarSharing:RuleSave', this.onRuleSave);
		}

		unbindEvents()
		{
			this.customEventEmitter.off('CalendarSharing:RuleUpdated', this.onRuleUpdated);
			this.customEventEmitter.off('CalendarSharing:RuleSave', this.onRuleSave);
		}

		onRuleUpdated()
		{
			this.redraw();
		}

		onRuleSave()
		{
			this.saveSharingRule();
		}

		saveSharingRule()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				SharingAjax.saveLinkRule(this.prepareDataForRuleSave()).then((response) => {
					if (response.errors && response.errors.length > 0)
					{
						console.error('save settings error');
					}

					Analytics.sendRuleUpdated(this.model.getContext(), this.model.getSettings().getChanges());

					resolve(response);
				});
			});
		}

		prepareDataForRuleSave()
		{
			const settings = this.model.getSettings();

			return {
				linkHash: settings.getRule().getHash(),
				ruleArray: {
					ranges: settings.getRule().getRanges().map((range) => {
						return {
							from: range.getFrom(),
							to: range.getTo(),
							weekdays: range.getWeekDays(),
						};
					}),
					slotSize: settings.getRule().getSlotSize(),
				},
			};
		}

		redraw()
		{
			this.setState({ time: Date.now() });
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingHorizontal: 12,
						paddingTop: 12,
						paddingBottom: 14,
					},
					clickable: true,
					onClick: this.readOnly ? this.showReadOnlyWarning : this.openRuleEditBackdrop,
				},
				this.renderHeader(),
				this.renderRule(),
			);
		}

		openRuleEditBackdrop()
		{
			const parentLayoutWidget = this.props.layoutWidget;
			const component = this.getRuleEditDialog();

			// eslint-disable-next-line promise/catch-or-return
			new BottomSheet({ component })
				.setBackgroundColor(AppTheme.colors.bgNavigation)
				.setMediumPositionPercent(60)
				.setParentWidget(parentLayoutWidget)
				.open()
				.then((widget) => {
					component.setLayoutWidget(widget);
				});
		}

		showReadOnlyWarning()
		{
			// eslint-disable-next-line no-undef
			include('InAppNotifier');
			// eslint-disable-next-line no-undef
			InAppNotifier.showNotification({
				title: Loc.getMessage('M_CALENDAR_SETTINGS_READONLY'),
				backgroundColor: '#e6000000',
			});
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: 10,
					},
				},
				this.renderClock(),
				this.renderTitle(),
				this.renderRightArrow(),
			);
		}

		renderClock()
		{
			return View(
				{
					style: {
						width: 40,
					},
				},
				Image(
					{
						tintColor: AppTheme.colors.accentMainPrimary,
						svg: {
							content: icons.clock,
						},
						style: {
							width: 24,
							height: 24,
						},
					},
				),
			);
		}

		renderTitle()
		{
			return Text(
				{
					style: {
						fontSize: 15,
						color: AppTheme.colors.base1,
						flex: 1,
					},
					text: Loc.getMessage('M_CALENDAR_SETTINGS_DESCRIPTION'),
				},
			);
		}

		renderRightArrow()
		{
			return Image(
				{
					tintColor: AppTheme.colors.base3,
					svg: {
						content: chevronRight(),
					},
					style: {
						alignItems: 'flex-end',
						width: 24,
						height: 24,
					},
				},
			);
		}

		renderRule()
		{
			return new SharingSettingsRule({
				model: this.model,
			});
		}

		getRuleEditDialog()
		{
			return new RuleEditDialog({
				model: this.model,
				customEventEmitter: this.customEventEmitter,
			});
		}
	}

	const icons = {
		clock: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.8"><path d="M10.9917 7.98797H12.9917V10.988H15.9917V12.988H10.9917V7.98797Z" fill="#29A8DF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.73197 15.268C6.06564 18.2337 9.06758 20.0915 12.3168 19.962C16.6274 19.8738 20.0516 16.3101 19.9678 11.9995C19.9676 8.74766 17.9915 5.82222 14.9749 4.60793C11.9584 3.39365 8.50658 4.13417 6.25355 6.47895C4.00052 8.82373 3.3983 12.3023 4.73197 15.268ZM6.58701 14.4339C7.58028 16.6426 9.81604 18.0263 12.2359 17.9298C15.4463 17.8641 17.9966 15.21 17.9341 11.9995C17.934 9.57771 16.4623 7.39894 14.2156 6.49458C11.969 5.59022 9.39822 6.14173 7.72024 7.88805C6.04225 9.63437 5.59374 12.2251 6.58701 14.4339Z" fill="#29A8DF"/></g></svg>',
	};

	module.exports = { SharingSettings };
});
