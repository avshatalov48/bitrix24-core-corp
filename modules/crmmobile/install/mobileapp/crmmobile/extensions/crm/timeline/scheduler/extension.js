/**
 * @module crm/timeline/scheduler
 */
jn.define('crm/timeline/scheduler', (require, exports, module) => {
	const { FloatingMenuItem } = require('layout/ui/detail-card/floating-button/menu/item');
	const { Loc } = require('loc');

	/**
	 * @type {typeof TimelineSchedulerBaseProvider[]}
	 */
	const providerClasses = Object.values(require('crm/timeline/scheduler/providers'))
		.filter((item) => Boolean(item));

	/**
	 * @class TimelineScheduler
	 */
	class TimelineScheduler
	{
		static getFloatingMenuItems(context)
		{
			const floatingMenuItemsSettings = context?.detailCard?.componentParams?.floatingMenuItemsSettings;

			return (
				providerClasses
					.filter((providerClass) => providerClass && providerClass.isAvailableInMenu(context))
					.map((providerClass) => {
						const id = providerClass.getId();
						let position = null;
						let disabled = false;
						if (floatingMenuItemsSettings && floatingMenuItemsSettings[id])
						{
							position = floatingMenuItemsSettings[id].position;
							disabled = floatingMenuItemsSettings[id].disabled;
						}

						return new FloatingMenuItem({
							id,
							title: providerClass.getMenuTitle(),
							subtitle: providerClass.getMenuSubtitle(),
							subtitleType: providerClass.getMenuSubtitleType(),
							shortTitle: providerClass.getMenuShortTitle(),
							isSupported: providerClass.isSupported(context),
							isAvailable: true,
							position: providerClass.getMenuPosition(position),
							badges: providerClass.getMenuBadges(),
							icon: providerClass.getMenuIcon(),
							disabled,
						});
					})
			);
		}

		/**
		 * @param {TimelineEntityProps} entity
		 * @param {TimelineUserProps} user
		 * @param {function} onActivityCreate
		 * @param {function} onClose
		 * @param {function} onCancel
		 * @param {function} onSkip
		 * @param {object} parentWidget
		 */
		constructor({ entity, user, onActivityCreate, onClose, onCancel, onSkip, parentWidget = PageManager })
		{
			/** @type {TimelineEntityProps} */
			this.entity = entity;

			/** @type {TimelineUserProps | null} */
			this.user = user;

			this.onActivityCreate = onActivityCreate;
			this.onClose = onClose;
			this.onCancel = onCancel;
			this.onSkip = onSkip;

			this.parentWidget = parentWidget;
		}

		/**
		 * @public
		 * @param {object} context
		 */
		openActivityEditor(context = {})
		{
			this.openEditorByProviderId('todo', context);
		}

		/**
		 * @public
		 * @param {object} context
		 */
		openActivityReminder(context = {})
		{
			this.openEditorByProviderId('activity-reminder', context);
		}

		/**
		 * @public
		 * @param {string} providerId
		 * @param {object} context
		 * @return {void}
		 */
		openEditorByProviderId(providerId, context = {})
		{
			const providerClass = providerClasses.find((item) => item.getId() === providerId);
			if (providerClass && providerClass.isSupported(context))
			{
				this.openEditor(providerClass, context);
			}
			else
			{
				qrauth.open({
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DESKTOP_VERSION'),
					redirectUrl: this.entity.detailPageUrl,
					analyticsSection: 'crm',
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
			providerClass.open({ scheduler: this, context });
		}
	}

	module.exports = { TimelineScheduler };
});
