/**
 * @module crm/timeline/scheduler/providers/activity-reminder
 */
jn.define('crm/timeline/scheduler/providers/activity-reminder', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { Haptics } = require('haptics');
	const { settings } = require('assets/common');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { getEntityMessage } = require('crm/loc');
	const { BackdropHeader, CreateBannerImage } = require('layout/ui/banners');
	const { TimelineSchedulerActivityProvider } = require('crm/timeline/scheduler/providers/activity');

	const EXTENSION_IMAGE_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/timeline/scheduler/providers/activity-reminder/images`;
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
				this.renderBanner(),
				View(
					{
						style: {
							flex: 1,
							marginVertical: 12,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 12,
							borderWidth: 1.4,
							borderColor: AppTheme.colors.accentBrandBlue,
							maxHeight: this.state.maxHeight,
						},
						onLayout: ({ height }) => this.setMaxHeight(height),
					},
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
			return BackdropHeader({
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_TITLE'),
				description: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_ACTIVITY_REMINDER_BANNER_DESCRIPTION'),
				image: CreateBannerImage({
					image: {
						svg: {
							uri: `${EXTENSION_IMAGE_PATH}/${AppTheme.id}/plan-activity.svg`,
						},
					},
				}),
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
						width: 28,
						height: 28,
					},
					resizeMode: 'contain',
					tintColor: AppTheme.colors.base4,
					svg: {
						content: settings(),
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
