/**
 * @module crm/timeline/scheduler/providers/activity-reminder
 */
jn.define('crm/timeline/scheduler/providers/activity-reminder', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { settingsOutline } = require('assets/common');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { getEntityMessage } = require('crm/loc');
	const { TimelineSchedulerActivityProvider } = require('crm/timeline/scheduler/providers/activity');

	const isAndroid = Application.getPlatform() === 'android';

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
						'M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_SKIP_TITLE2',
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
					},
					resizableByKeyboard: true,
				},
				View(
					{
						style: {
							flex: 1,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							maxHeight: '100%',
						},
						onLayout: ({ height }) => this.setMaxHeight(height),
					},
					this.renderBanner(),
					this.renderTextField(),
					this.renderAttachments(),
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								paddingHorizontal: isAndroid ? 16 : 12,
								paddingBottom: 14,
							},
						},
						this.renderReminders(),
					),
					this.renderBottom(),
				),
				// this.renderToolbar(),
			);
		}

		renderBanner()
		{
			return View(
				{
					style: {
						marginHorizontal: 14,
						marginTop: 12,
						borderRadius: 8,
						borderWidth: 1,
						borderColor: AppTheme.colors.bgSeparatorPrimary,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: 14,
					},
				},
				View(
					{},
					Text(
						{
							style: {
								fontSize: 14,
								fontWeight: 600,
								color: AppTheme.colors.base1,
							},
							text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_TITLE_MSGVER_1'),
						},
					),
					Text(
						{
							style: {
								marginTop: 4,
								fontSize: 12,
								fontWeight: 400,
								color: AppTheme.colors.base4,
							},
							text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_DESCRIPTION_MSGVER_1'),
						},
					),
				),
				this.renderMenuButton(),
			);
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
						width: 17,
						height: 17,
					},
					resizeMode: 'contain',
					tintColor: AppTheme.colors.base4,
					svg: {
						content: settingsOutline(),
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
