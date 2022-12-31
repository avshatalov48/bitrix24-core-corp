/**
 * @module crm/timeline/scheduler
 */
jn.define('crm/timeline/scheduler', (require, exports, module) => {

	const { Loc } = require('loc');

	/**
	 * @type {typeof TimelineSchedulerBaseProvider[]}
	 */
	const providerClasses = Object.values(require('crm/timeline/scheduler/providers'));

	/**
	 * @class TimelineScheduler
	 */
	class TimelineScheduler
	{
		/**
		 * @param {TimelineEntityProps} entity
		 * @param {function} onActivityCreate
		 * @param {function} onClose
		 * @param {function} onCancel
		 * @param {function} onSkip
		 * @param {object} parentWidget
		 */
		constructor({ entity, onActivityCreate, onClose, onCancel, onSkip, parentWidget = PageManager })
		{
			/** @type {TimelineEntityProps} */
			this.entity = entity;

			this.onActivityCreate = onActivityCreate;
			this.onClose = onClose;
			this.onCancel = onCancel;
			this.onSkip = onSkip;

			this.parentWidget = parentWidget;

			this.menu = new ContextMenu({
				actions: this.buildMenuItems(),
				params: {
					showActionLoader: false,
					showCancelButton: false,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MENU_TITLE'),
				}
			});
		}

		/**
		 * @public
		 */
		openMenu()
		{
			void this.menu.show();
		}

		/**
		 * @public
		 * @param {object} context
		 */
		openActivityEditor(context = {})
		{
			const providerClass = providerClasses.find(providerClass => providerClass.getId() === 'activity');
			if (providerClass && providerClass.isSupported())
			{
				this.openEditor(providerClass, context);
			}
		}

		/**
		 * @public
		 * @param {object} context
		 */
		openActivityReminder(context = {})
		{
			const providerClass = providerClasses.find(providerClass => providerClass.getId() === 'activity-reminder');
			if (providerClass && providerClass.isSupported())
			{
				this.openEditor(providerClass, context);
			}
		}

		/**
		 * @private
		 * @return {object[]}
		 */
		buildMenuItems()
		{
			const sorter = (a, b) => a.getMenuPosition() - b.getMenuPosition();

			const supportedItems = providerClasses
				.filter(providerClass => providerClass.isSupported())
				.sort(sorter);

			const unsupportedItems = providerClasses
				.filter(providerClass => !providerClass.isSupported())
				.sort(sorter);

			/** @type {typeof TimelineSchedulerBaseProvider[]} */
			const items = [ ...supportedItems, ...unsupportedItems ];

			return (
				items
					.filter(providerClass => providerClass.isAvailableInMenu())
					.map(providerClass => ({
						id: providerClass.getId(),
						title: providerClass.getMenuTitle(),
						sectionCode: providerClass.isSupported() ? 'default' : 'service',
						subTitle: '',
						data: {
							svgIcon: providerClass.getMenuIcon(),
							svgIconAfter: {
								type: providerClass.isSupported() ? null : ContextMenuItem.ImageAfterTypes.WEB,
							},
						},
						onClickCallback: () => {
							this.menu.close(() => this.onMenuItemClick(providerClass));
							return Promise.resolve();
						},
					}))
			);
		}

		/**
		 * @private
		 * @param {typeof TimelineSchedulerBaseProvider} providerClass
		 */
		onMenuItemClick(providerClass)
		{
			if (providerClass.isSupported())
			{
				this.openEditor(providerClass);
			}
			else
			{
				qrauth.open({
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DESKTOP_VERSION'),
					redirectUrl: this.entity.detailPageUrl,
				});
			}
		}

		/**
		 * @private
		 * @param {typeof TimelineSchedulerBaseProvider} providerClass
		 * @param {object} context
		 */
		openEditor(providerClass, context = {})
		{
			this.parentWidget.openWidget('layout', {
				modal: true,
				backdrop: {
					onlyMediumPosition: false,
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					mediumPositionPercent: 50,
					navigationBarColor: '#EEF2F4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
					...providerClass.getBackdropParams(),
				}}
			).then(widget => {
				widget.setTitle({ text: providerClass.getTitle() });
				widget.enableNavigationBarBorder(false);
				widget.showComponent(new providerClass({
					context,
					layout: widget,
					entity: this.entity,
					onActivityCreate: this.onActivityCreate,
					onClose: this.onClose,
					onCancel: this.onCancel,
					onSkip: this.onSkip,
				}));
			});
		}
	}

	module.exports = { TimelineScheduler };

});