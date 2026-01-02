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
	const {
		showSuccessInvitationToast,
		openGuestsInviteRestrictionsBox,
		openCurrentCollabGuestsInviteRestrictionsBox,
	} = require('collab/invite/src/utils');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { CollabInviteAnalytics } = require('collab/invite/src/analytics');
	const { NotifyManager } = require('notify-manager');
	const { Feature } = require('feature');

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
			this.tabsWidget.on('onTabSelected', this.#onTabSelected);
			const widgets = this.tabsWidget.nestedWidgets();
			this.#initGuestsTab(widgets.guests);
			this.#initEmployeesSelector(widgets.employees);
		};

		#onTabSelected = async (item) => {
			const { canInviteCollabersInPortalSettings, allowGuestsInvitation } = this.settings;
			if (!canInviteCollabersInPortalSettings
				&& item.id === TabType.GUESTS
				&& Feature.isSelectorWidgetOnViewHiddenEventBugFixed())
			{
				await openGuestsInviteRestrictionsBox({
					parentWidget: this.tabsWidget,
					onClose: () => {
						this.tabsWidget.setActiveItem(TabType.EMPLOYEES);
					},
				});

				return;
			}

			if (!allowGuestsInvitation && item.id === TabType.GUESTS && Feature.isSelectorWidgetOnViewHiddenEventBugFixed())
			{
				await openCurrentCollabGuestsInviteRestrictionsBox({
					parentWidget: this.tabsWidget,
					onClose: () => {
						this.tabsWidget.setActiveItem(TabType.EMPLOYEES);
					},
				});
			}
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
					},
				],
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
				analytics: this.analytics,
				canInviteCollabersInPortalSettings: this.settings?.canInviteCollabersInPortalSettings ?? false,
				allowGuestsInvitation: this.settings?.allowGuestsInvitation ?? false,
				languages: this.settings?.languages ?? [],
			});

			layout.showComponent(this.guestsTabInstance);
		};

		#initEmployeesSelector = (layout) => {
			if (!layout)
			{
				return;
			}

			try
			{
				const { MemberSelector } = require('im/messenger/controller/selector/member');
				const memberSelector = new MemberSelector({
					integrateSelectorToParentLayout: true,
					onSelectMembers: this.#onSelectMembers,
				});
				memberSelector.open(layout);
			}
			catch (e)
			{
				console.warn(e);

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
							this.#onSelectMembers(selectedUsers.map((user) => user.id));
						},
					},
					widgetParams: {
						title: '',
					},
				});
				selector.show({}, layout);
			}
		};

		#onSelectMembers = async (membersIds) => {
			setTimeout(async () => {
				if (Type.isArrayFilled(membersIds))
				{
					await Notify.showIndicatorLoading();
					await addEmployeeToCollab(this.props.collabId, membersIds);
					this.#close();
					showSuccessInvitationToast({
						collabId: this.props.collabId,
						analytics: this.props.analytics,
						multipleInvitation: membersIds.length > 0,
						isTextForInvite: false,
					});
					Notify.hideCurrentIndicator();
				}
			}, 500);
		};

		#close = () => {
			this.tabsWidget?.close?.();
		};

		#getTabsData = () => {
			const { canInviteCollabersInPortalSettings, allowGuestsInvitation } = this.settings;

			const guestsTab = {
				id: TabType.GUESTS,
				title: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TITLE'),
				widget: {
					name: 'layout',
					code: TabType.GUESTS,
				},
			};

			const employeesTab = {
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
			};

			const items = (
				!canInviteCollabersInPortalSettings || !allowGuestsInvitation
			) && Feature.isSelectorWidgetOnViewHiddenEventBugFixed()
				? [employeesTab, guestsTab]
				: [guestsTab, employeesTab];
			items[0].active = true;

			return {
				items,
			};
		};

		open = async () => {
			await NotifyManager.showLoadingIndicator();
			const response = await this.#fetchInviteSettings(this.props.collabId);
			NotifyManager.hideLoadingIndicatorWithoutFallback();

			const { errors, data } = response;
			if (errors && errors.length > 0)
			{
				return;
			}

			this.settings = data;
			if (!data.canCurrentUserInvite)
			{
				await this.#showNoPermissionsToInviteAlert();

				return;
			}

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

		#fetchInviteSettings = async (collabId) => {
			return BX.ajax.runAction('mobile.Collab.getInviteSettings', {
				json: {
					collabId,
				},
			}).catch(ajaxPublicErrorHandler);
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
