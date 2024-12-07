/**
 * @module calendar/layout/sharing-settings/settings
 */
jn.define('calendar/layout/sharing-settings/settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { EventEmitter } = require('event-emitter');
	const { chevronRight } = require('assets/common');

	const { SharingContext } = require('calendar/model/sharing');
	const { SharingSettingsCard } = require('calendar/layout/sharing-settings/card');
	const { SharingSettingsMembers } = require('calendar/layout/sharing-settings/members');
	const { RuleEditDialog } = require('calendar/layout/sharing-settings/dialog/rule-edit');
	const { SharingAjax } = require('calendar/ajax');
	const { Analytics } = require('calendar/sharing/analytics');
	const { Color } = require('tokens');

	/**
	 * @class SharingSettings
	 */
	class SharingSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

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

			this.layoutWidget = props.layoutWidget;
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		get readOnly()
		{
			return this.props.readOnly || false;
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
				ruleArray: settings.getRuleArray(),
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
					testId: 'SharingPanelSettings',
				},
				this.renderRuleContainer(),
				this.renderMembers(),
			);
		}

		renderRuleContainer()
		{
			return SharingSettingsCard(
				{},
				View(
					{
						clickable: true,
						onClick: this.readOnly ? this.showReadOnlyWarning : this.openRuleEditBackdrop,
					},
					this.renderHeaderContainer(),
				),
			);
		}

		openRuleEditBackdrop()
		{
			const component = (layoutWidget) => new RuleEditDialog({
				layoutWidget,
				model: this.model,
				customEventEmitter: this.customEventEmitter,
			});

			void new BottomSheet({ component })
				.setBackgroundColor(Color.bgNavigation.toHex())
				.setMediumPositionPercent(60)
				.disableContentSwipe()
				.setParentWidget(this.layoutWidget)
				.open()
			;
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

		renderHeaderContainer()
		{
			return View(
				{
					style: styles.headerContainer,
				},
				this.renderHeader(),
				this.renderRightArrow(),
			);
		}

		renderHeader()
		{
			return View(
				{},
				this.renderHeaderTitleContainer(),
				this.renderHeaderSubtitle(),
			);
		}

		renderHeaderTitleContainer()
		{
			return View(
				{
					style: styles.titleContainer,
				},
				this.renderClock(),
				this.renderHeaderTitle(),
			);
		}

		renderClock()
		{
			const isCalendarContext = this.model.getContext() === SharingContext.CALENDAR;

			return View(
				{
					style: styles.clockContainer,
				},
				Image({
					tintColor: isCalendarContext ? AppTheme.colors.accentMainPrimary : AppTheme.colors.base2,
					svg: {
						content: icons.clock,
					},
					style: styles.clockIcon,
				}),
			);
		}

		renderHeaderTitle()
		{
			return Text({
				style: styles.headerTitle,
				text: Loc.getMessage('M_CALENDAR_SETTINGS_DESCRIPTION'),
			});
		}

		renderHeaderSubtitle()
		{
			return View(
				{
					style: styles.headerSubtitleContainer,
				},
				Text({
					text: this.getSubtitleText(),
					style: styles.headerSubtitleText,
				}),
			);
		}

		getSubtitleText()
		{
			if (this.model.getSettings().isDefaultRule())
			{
				return Loc.getMessage('M_CALENDAR_SETTINGS_SUBTITLE_DEFAULT_MSGVER_1');
			}

			return Loc.getMessage('M_CALENDAR_SETTINGS_SUBTITLE_PERSONAL');
		}

		renderRightArrow()
		{
			return Image({
				tintColor: AppTheme.colors.base5,
				svg: {
					content: chevronRight(),
				},
				style: styles.rightArrowIcon,
			});
		}

		renderMembers()
		{
			return new SharingSettingsMembers({
				model: this.model,
				layoutWidget: this.layoutWidget,
			});
		}
	}

	const icons = {
		clock: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 18.8C15.7555 18.8 18.8 15.7555 18.8 12C18.8 8.24447 15.7555 5.2 12 5.2C8.24447 5.2 5.2 8.24447 5.2 12C5.2 15.7555 8.24447 18.8 12 18.8ZM20 12C20 16.4183 16.4183 20 12 20C7.58173 20 4 16.4183 4 12C4 7.58173 7.58173 4 12 4C16.4183 4 20 7.58173 20 12Z" fill="#00A2E8"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M11.9149 7.40651C12.2463 7.40651 12.5149 7.67514 12.5149 8.00651V11.5099L15.3679 11.5099C15.6993 11.5099 15.9679 11.7785 15.9679 12.1099C15.9679 12.4413 15.6993 12.7099 15.3679 12.7099L11.9149 12.7099C11.7558 12.7099 11.6032 12.6467 11.4907 12.5342C11.3781 12.4217 11.3149 12.2691 11.3149 12.1099V8.00651C11.3149 7.67514 11.5835 7.40651 11.9149 7.40651Z" fill="#00A2E8"/></svg>',
	};

	const styles = {
		headerContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'space-between',
		},
		titleContainer: {
			flexDirection: 'row',
		},
		clockContainer: {
			width: 40,
		},
		clockIcon: {
			width: 24,
			height: 24,
		},
		headerTitle: {
			flexDirection: 'column',
			fontSize: 15,
			color: AppTheme.colors.base1,
			flex: 1,
		},
		headerSubtitleContainer: {
			paddingLeft: 40,
		},
		headerSubtitleText: {
			fontSize: 14,
			color: AppTheme.colors.base3,
		},
		rightArrowIcon: {
			alignItems: 'flex-end',
			width: 24,
			height: 24,
		},
		ruleContainer: {
			marginTop: 10,
		},
	};

	module.exports = { SharingSettings };
});
