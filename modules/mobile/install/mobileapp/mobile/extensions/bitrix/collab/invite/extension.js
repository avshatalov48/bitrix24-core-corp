/**
 * @module collab/invite
 */
jn.define('collab/invite', (require, exports, module) => {
	const { Notify } = require('notify');
	const { EntitySelectorFactory } = require('selector/widget/factory');
	const { Loc } = require('loc');
	const { GuestsTabContent } = require('collab/invite/src/guests-tab-content');
	const { Type } = require('type');
	const { ajaxPublicErrorHandler } = require('error');
	const { addEmployeeToCollab } = require('collab/invite/src/api');
	const { showSuccessInvitationToast } = require('collab/invite/src/utils');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { CollabInviteAnalytics } = require('collab/invite/src/analytics');
	const { MemoryStorage } = require('native/memorystore');
	const { Feature } = require('feature');
	const { isEqual } = require('utils/object');

	const TabType = {
		GUESTS: 'guests',
		EMPLOYEES: 'employees',
	};

	class CollabInvite
	{
		constructor(props)
		{
			this.props = props;
			this.settings = null;
			this.tabsWidget = null;
			this.guestsTabInstance = null;
			this.selectedEmployees = [];
			this.analytics = new CollabInviteAnalytics(props.analytics).setCollabId(props.collabId);
		}

		/**
		 * @returns {CollabInviteAnalytics}
		 */
		getAnalytics = () => {
			return this.analytics;
		};

		#tabsWidgetReady = (tabsWidget) => {
			this.tabsWidget = tabsWidget;
			this.tabsWidget.preventBottomSheetDismiss(true);
			this.tabsWidget.on('preventDismiss', this.#preventDismiss);
			const widgets = this.tabsWidget.nestedWidgets();
			this.#initGuestsTab(widgets.guests);
			this.#initEmployeesSelector(widgets.employees);
		};

		#preventDismiss = () => {
			if (this.selectedEmployees.length === 0)
			{
				this.tabsWidget.close();

				return;
			}

			Haptics.impactLight();
			Alert.confirm(
				Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
				Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
				[
					{
						type: ButtonType.DESTRUCTIVE,
						text: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
						onPress: () => {
							this.tabsWidget.close();
						},
					},
					{
						type: ButtonType.DEFAULT,
						text: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
					}],
			);
		};

		#initGuestsTab = (layout) => {
			if (!layout)
			{
				return;
			}

			this.guestsTabInstance = GuestsTabContent({
				parentLayout: this.props.parentLayout,
				layout,
				boxLayout: this.tabsWidget,
				...this.props,
				pending: !this.settings,
				isBitrix24Included: this.settings?.isBitrix24Included ?? false,
				inviteLink: this.settings?.inviteLink ?? null,
				analytics: this.analytics,
			});

			layout.showComponent(this.guestsTabInstance);
		};

		#initEmployeesSelector = (layout) => {
			if (!layout)
			{
				return;
			}

			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {},
				createOptions: {
					enableCreation: false,
				},
				integrateSelectorToParentLayout: true,
				allowMultipleSelection: true,
				closeOnSelect: true,
				events: {
					onSelectedChanged: (selectedEmployees) => {
						this.selectedEmployees = selectedEmployees;
					},
					onClose: async (selectedUsers) => {
						setTimeout(async () => {
							if (Type.isArrayFilled(selectedUsers))
							{
								await Notify.showIndicatorLoading();
								const userIds = selectedUsers.map((user) => user.id);
								await addEmployeeToCollab(this.props.collabId, userIds);
								this.#close();
								showSuccessInvitationToast({
									collabId: this.props.collabId,
									analytics: this.props.analytics,
									multipleInvitation: selectedUsers.length > 0,
									isTextForInvite: false,
								});
								Notify.hideCurrentIndicator();
							}
						}, 500);
					},
				},
				widgetParams: {
					title: '',
				},
			});

			selector.show({}, layout);
		};

		#close = () => {
			this.tabsWidget?.close?.();
		};

		#getTabsData = () => {
			return {
				items: [
					{
						id: TabType.GUESTS,
						active: true,
						title: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TITLE'),
						widget: {
							name: 'layout',
							code: TabType.GUESTS,
						},
					},
					{
						id: TabType.EMPLOYEES,
						title: Loc.getMessage('COLLAB_INVITE_TAB_EMPLOYEES_TITLE'),
						widget: {
							name: 'selector',
							code: TabType.EMPLOYEES,
							settings: {
								objectName: 'selector',
								sendButtonName: Loc.getMessage('COLLAB_INVITE_SELECTOR_SEND_BUTTON_TEXT'),
							},
						},
					},
				],
			};
		};

		open = () => {
			void this.#initSettings();
			const widgetParams = {
				titleParams: {
					text: Loc.getMessage('COLLAB_INVITE_TITLE'),
					type: 'dialog',
				},
				grabTitle: false,
				grabButtons: false,
				grabSearch: false,
				backdrop: {
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
					mediumPositionHeight: 620,
				},
				type: 'segments',
				tabs: this.#getTabsData(),
			};

			const parentLayout = this.props.parentLayout ?? PageManager;
			parentLayout.openWidget('tabs', widgetParams)
				.then(this.#tabsWidgetReady)
				.catch(console.error);
		};

		#initSettings = async () => {
			this.settings = await this.#getInviteSettingsFromCache(this.props.collabId);
			void this.#fetchAndUpdateInviteSettings(this.props.collabId);
		};

		#fetchAndUpdateInviteSettings = async (collabId) => {
			const response = await BX.ajax.runAction('mobile.Collab.getInviteSettings', {
				json: {
					collabId,
				},
			}).catch(ajaxPublicErrorHandler);

			const { errors, data } = response;
			if (errors && errors.length > 0)
			{
				this.#close();

				return null;
			}

			if (!data.canCurrentUserInvite)
			{
				await this.#showNoPermissionsToInviteAlert();
				this.#close();

				return null;
			}

			await this.#addInviteSettingsToCache(data, collabId);
			if (!isEqual(this.settings, data))
			{
				this.settings = data;
				this.guestsTabInstance?.update({
					pending: false,
					isBitrix24Included: this.settings.isBitrix24Included,
					inviteLink: this.settings.inviteLink,
				});
			}

			return response;
		};

		#addInviteSettingsToCache = async (settings, collabId) => {
			if (Feature.isMemoryStorageSupported())
			{
				const store = new MemoryStorage('collabInviteSettings');
				await store.set(`collab-invite-settings-${collabId}`, {
					settings,
					time: Date.now(),
				});
			}
		};

		#isCachedWithinLast48Hours = (time) => {
			const twelveHoursAgo = new Date(Date.now() - 48 * 60 * 60 * 1000);

			return time > twelveHoursAgo;
		};

		#getInviteSettingsFromCache = async (collabId) => {
			if (Feature.isMemoryStorageSupported())
			{
				const store = new MemoryStorage('collabInviteSettings');
				const data = await store.get(`collab-invite-settings-${collabId}`);
				if (data && this.#isCachedWithinLast48Hours(data.time))
				{
					return data.settings;
				}
			}

			return null;
		};

		#showNoPermissionsToInviteAlert = () => {
			return new Promise((resolve) => {
				Haptics.impactLight();
				Alert.alert(
					Loc.getMessage('COLLAB_INVITE_PERMISSIONS_ALERT_TITLE'),
					Loc.getMessage('COLLAB_INVITE_PERMISSIONS_ALERT_DESCRIPTION'),
					resolve,
					Loc.getMessage('COLLAB_INVITE_PERMISSIONS_ALERT_CONTINUE_BUTTON'),
				);
			});
		};
	}

	/**
	 * @param {object} props
	 * @param {number} props.collabId
	 * @param {CollabInviteAnalytics} props.analytics
	 * @param {object} [props.parentLayout]
	 * @return {CollabInvite}
	 */
	const openCollabInvite = async (props = {}) => {
		const instance = new CollabInvite({
			...props,
		});
		instance.open();

		return instance;
	};

	module.exports = { openCollabInvite, CollabInviteAnalytics };
});
