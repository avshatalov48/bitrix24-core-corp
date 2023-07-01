/**
 * @module crm/timeline/scheduler/providers/activity-reminder
 */
jn.define('crm/timeline/scheduler/providers/activity-reminder', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { Haptics } = require('haptics');
	const { BackdropHeader } = require('layout/ui/banners');
	const { TimelineSchedulerActivityProvider } = require('crm/timeline/scheduler/providers/activity');

	/**
	 * @class TimelineSchedulerActivityReminderProvider
	 */
	class TimelineSchedulerActivityReminderProvider extends TimelineSchedulerActivityProvider
	{
		static getId()
		{
			return 'activity-reminder';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_TITLE');
		}

		/**
		 * @return {boolean}
		 */
		static isAvailableInMenu()
		{
			return false;
		}

		/**
		 * @return {object}
		 */
		static getBackdropParams()
		{
			return {};
		}

		constructor(props)
		{
			super(props);

			this.menu = new ContextMenu({
				actions: this.buildSkipMenuItems(),
				onCancel: () => this.focus(),
				params: {
					title: getEntityMessage(
						'M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_SKIP_TITLE',
						this.entity.typeId,
					),
					showCancelButton: true,
					showActionLoader: false,
				},
			});
		}

		getInitialText()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_DEFAULT_TEXT');
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: '#eef2f4',
					},
					resizableByKeyboard: true,
				},
				this.renderBanner(),
				View(
					{
						style: {
							flex: 1,
							marginVertical: 12,
							backgroundColor: '#ffffff',
							borderRadius: 12,
							borderWidth: 1.4,
							borderColor: '#2fc6f6',
							maxHeight: this.state.maxHeight,
						},
						onLayout: ({ height }) => this.setMaxHeight(height),
					},
					this.renderTextField(),
					this.renderAttachments(),
					this.renderDeadlineAndResponsible(),
				),
				// this.renderToolbar(),
			);
		}

		renderBanner()
		{
			return BackdropHeader({
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_TITLE'),
				description: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_DESCRIPTION'),
				image: `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/timeline/scheduler/providers/activity-reminder/icons/plan-activity.png`,
			});
		}

		renderMenuButton()
		{
			return View(
				{
					style: {
						alignSelf: 'center',
						justifyContent: 'center',
						alignItems: 'center',
						paddingHorizontal: 8,
						paddingVertical: 4,
					},
					onClick: () => this.openSkipMenu(),

				},
				Image({
					style: {
						width: 20,
						height: 20,
					},
					resizeMode: 'contain',
					svg: {
						content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.7904 16.075C11.8335 15.9111 11.961 15.7838 12.1222 15.7316C12.408 15.6389 12.6847 15.526 12.9505 15.3946C13.1004 15.3206 13.2778 15.3216 13.4221 15.4059L14.373 15.9612C14.5339 16.0552 14.7356 16.0475 14.8834 15.9341C15.4149 15.5261 15.8912 15.0498 16.2992 14.5182C16.4127 14.3705 16.4203 14.1688 16.3263 14.0079L15.771 13.0569C15.6867 12.9126 15.6857 12.7352 15.7597 12.5853C15.8911 12.3195 16.004 12.0429 16.0966 11.7571C16.1489 11.5959 16.2761 11.4684 16.44 11.4253L17.484 11.1511C17.6656 11.1034 17.8033 10.9535 17.8267 10.7672C17.9133 10.078 17.9093 9.40356 17.8251 8.75432C17.8011 8.56924 17.6637 8.42077 17.4832 8.37337L16.4271 8.096C16.2649 8.05341 16.1385 7.92806 16.0853 7.76904C15.992 7.49006 15.8796 7.21979 15.7496 6.95979C15.6747 6.80969 15.6754 6.63154 15.76 6.48665L16.3103 5.54429C16.4044 5.38316 16.3966 5.18106 16.2827 5.03319C15.8711 4.49837 15.3914 4.01866 14.8566 3.60709C14.7087 3.49329 14.5066 3.48546 14.3455 3.57955L13.4032 4.12984C13.2583 4.21446 13.0802 4.21516 12.9301 4.14016C12.67 4.01024 12.3998 3.89782 12.1208 3.80447C11.9617 3.75126 11.8364 3.62484 11.7938 3.46266L11.5165 2.40679C11.4691 2.22628 11.3206 2.08891 11.1355 2.06489C10.4862 1.98063 9.81179 1.97668 9.12253 2.06331C8.93628 2.08672 8.78634 2.22447 8.73866 2.40603L8.46165 3.46073C8.41951 3.62118 8.29621 3.7465 8.14004 3.80243C7.87884 3.89598 7.61992 4.01261 7.36642 4.14917C7.21393 4.23132 7.02938 4.23357 6.8798 4.14622L5.98433 3.62328C5.81992 3.52727 5.61336 3.5377 5.4646 3.65652C4.93431 4.08006 4.44518 4.5692 4.02166 5.0995C3.90285 5.24826 3.89242 5.45482 3.98842 5.61922L4.51133 6.51464C4.59868 6.66422 4.59643 6.84877 4.51427 7.00127C4.37768 7.25479 4.26103 7.51373 4.16747 7.77495C4.11153 7.93112 3.98622 8.05441 3.82577 8.09655L2.77124 8.37351C2.58967 8.42119 2.45192 8.57114 2.42852 8.7574C2.3419 9.44668 2.34588 10.1211 2.43016 10.7705C2.45418 10.9555 2.59155 11.104 2.77205 11.1514L3.82766 11.4286C3.98984 11.4712 4.11626 11.5966 4.16947 11.7556C4.26284 12.0347 4.37527 12.305 4.50521 12.5651C4.58021 12.7152 4.57951 12.8933 4.49489 13.0382L3.94477 13.9802C3.85068 14.1413 3.85851 14.3434 3.9723 14.4913C4.38389 15.0262 4.86361 15.5059 5.39846 15.9175C5.54634 16.0313 5.74843 16.0392 5.90956 15.9451L6.85163 15.3949C6.99653 15.3103 7.17468 15.3096 7.32479 15.3846C7.58484 15.5145 7.85516 15.627 8.1342 15.7203C8.29322 15.7735 8.41857 15.8999 8.46117 16.0621L8.73846 17.1179C8.78587 17.2984 8.93433 17.4358 9.1194 17.4598C9.76867 17.5441 10.4431 17.5481 11.1324 17.4615C11.3186 17.4381 11.4686 17.3004 11.5163 17.1188L11.7904 16.075ZM10.9022 13.2125C8.35091 13.7551 6.13461 11.5381 6.67731 8.98737C6.93395 7.78122 8.146 6.56909 9.35215 6.31239C11.903 5.76948 14.1201 7.98593 13.5774 10.5374C13.2927 11.8755 12.2404 12.9279 10.9022 13.2125Z" fill="#BDC1C6"/></svg>',
					},
				}),
			);
		}

		openSkipMenu()
		{
			void this.menu.show(this.layout);
		}

		buildSkipMenuItems()
		{
			const periods = ['day', 'week', 'month', 'forever'];

			return periods.map((period) => ({
				id: period,
				title: Loc.getMessage(`M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_SKIP_${period.toUpperCase()}`),
				onClickCallback: () => new Promise((resolve) => {
					this.menu.close(() => this.skip(period));
					resolve({ closeMenu: false });
				}),
			}));
		}

		skip(period)
		{
			BX.ajax.runAction('crm.activity.todo.skipEntityDetailsNotification', {
				data: {
					entityTypeId: this.entity.typeId,
					period,
				},
			});

			if (this.props.onSkip)
			{
				this.props.onSkip(this);
			}

			BX.postComponentEvent('Crm.Activity.Todo::onChangeNotifications', [false]);

			Haptics.impactLight();

			this.close();
		}
	}

	module.exports = { TimelineSchedulerActivityReminderProvider };
});
