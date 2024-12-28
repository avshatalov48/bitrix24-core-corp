import { BaseEvent } from 'main.core.events';
import { type BannerDispatcher as BannerDispatcherType } from 'ui.banner-dispatcher';
import { SharesListRenderer } from './shares-list-renderer';
import { PopupWithLoader } from './popup-with-loader';
import { ajax, bind, Runtime, Loc, Event, Dom, Text } from 'main.core';
import { showNotification, wrapTextToHtmlWithWordBreak } from './helpers';
import { sendData } from 'ui.analytics';
import { type Guide as GuideType } from 'ui.tour';
import type { RoleMasterPopupOptions } from 'ai.role-master';

export class Controller
{
	/**
	 * @var BX.Main.Grid
	 */
	static #grid;
	static #categoriesListPopup: PopupWithLoader = null;
	static #allSharesListPopup: PopupWithLoader = null;
	static #roleSuccessSavingEventHandler: Function = null;

	static handleClickOnDeleteRoleSwitcher(event: PointerEvent, roleCode: string, roleName: string): void
	{
		event.preventDefault();
		event.stopPropagation();

		this.#sendRowAction('toggle-deleted', {
			roleCode,
			needDeleted: 1,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_HIDE',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				),
			);
		});
	}

	static handleClickOnUndoDeleteRoleSwitcher(event: PointerEvent, roleCode: string, roleName: string): void
	{
		event.preventDefault();
		event.stopPropagation();

		this.#sendRowAction('toggle-deleted', {
			roleCode,
			needDeleted: 0,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_SHOW',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				),
			);
		});
	}

	static handleClickOnActivateRoleMenuItem(event: PointerEvent, roleCode: string, roleName: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-active', {
			roleCode,
			needActivate: 1,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_ACTIVATE',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				)),
			);
		});
	}

	static handleClickOnDeactivateRoleMenuItem(event: PointerEvent, roleCode: string, roleName: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-active', {
			roleCode,
			needActivate: 0,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_DEACTIVATE',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				)),
			);
		});
	}

	static async handleClickOnRoleName(event: PointerEvent, roleCode: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.editRole(roleCode);
	}

	static async editRole(roleCode: string): void
	{
		this.#grid.getLoader().show();
		this.#grid.tableFade();
		const formData = new FormData();
		formData.append('roleCode', roleCode);

		const fetchRoleByCodePromise = ajax.runAction('ai.shareRole.getRoleByCodeForUpdate', {
			method: 'POST',
			data: formData,
		});

		const loadRoleMasterExtensionPromise = Runtime.loadExtension('ai.role-master');

		try
		{
			const results = await Promise.all([fetchRoleByCodePromise, loadRoleMasterExtensionPromise]);
			const res = results[0];
			const RoleMasterPopup = results[1].RoleMasterPopup;
			const RoleMasterPopupEvents = results[1].RoleMasterPopupEvents;

			const role = res.data.role;
			const options: RoleMasterPopupOptions = {
				roleMaster: {
					id: role.code,
					text: role.instruction,
					name: role.nameTranslate,
					avatar: role.avatar,
					avatarUrl: role.avatarUrl,
					itemsWithAccess: role.accessCodes,
					authorId: role.authorId,
					description: role.descriptionTranslate,
				},
			};
			const popup = new RoleMasterPopup({
				...options,
				popupEvents: {
					onPopupDestroy: () => {
						popup.unsubscribe(RoleMasterPopupEvents.SAVE_SUCCESS, Controller.#roleSuccessSavingEventHandler);
					},
				},
				analyticFields: {
					c_section: 'list',
				},
			});
			Controller.#roleSuccessSavingEventHandler = Controller.#handleRoleSuccessSaving.bind(Controller);

			popup.subscribe(RoleMasterPopupEvents.SAVE_SUCCESS, Controller.#roleSuccessSavingEventHandler);

			popup.show();
		}
		catch (e)
		{
			console.error(e);

			showNotification(Loc.getMessage('ROLE_LIBRARY_GRID_ACTION_OPEN_EDIT_MASTER_ERROR'));
		}
		finally
		{
			this.#grid.getLoader().hide();
			this.#grid.tableUnfade();
		}
	}

	static async handleClickOnCreateRoleButton(button: Button): void
	{
		try
		{
			button.setClocking(true);
			const { RoleMasterPopup, RoleMasterPopupEvents } = await Runtime.loadExtension('ai.role-master');
			const popup = new RoleMasterPopup({
				popupEvents: {
					onPopupDestroy: () => {
						popup.unsubscribe(
							RoleMasterPopupEvents.SAVE_SUCCESS,
							Controller.#roleSuccessSavingEventHandler,
						);
					},
				},
				analyticFields: {
					c_section: 'list',
				},
			});
			Controller.#roleSuccessSavingEventHandler = Controller.#handleRoleSuccessSaving.bind(Controller);

			popup.subscribe(RoleMasterPopupEvents.SAVE_SUCCESS, Controller.#roleSuccessSavingEventHandler);

			popup.show();
		}
		catch (e)
		{
			console.error(e);
			showNotification(Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_ROLE_MASTER_OPEN_ERROR'));
		}
		finally
		{
			button.setClocking(false);
		}
	}

	static handleClickOnRoleIsFavouriteLabel(
		event: PointerEvent,
		roleCode: string,
		favourite: 'true' | 'false',
		roleName: string,
	): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-favourite', {
			roleCode,
			favourite,
			page: this.#getCurrentPage(),
		}, () => {
			const message = favourite === 'true'
				? wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				))
				: wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				))
			;

			showNotification(message);
		});
	}

	static toggleRoleFavourite(roleCode: string, favourite: boolean, roleName: string): void
	{
		this.#sendRowAction('toggle-favourite', {
			roleCode,
			favourite,
			page: this.#getCurrentPage(),
		}, () => {
			const message = favourite === 'true'
				? wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				))
				: wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE',
					{ '#NAME#': `<b>${Text.encode(roleName)}</b>` },
				))
			;

			showNotification(message);
		});
	}

	static applyMultipleAction(): void
	{
		const action = this.#grid.getActionsPanel().getPanel().querySelector('#action-menu span').dataset.value;

		const actionWithoutQuotes = action.replaceAll('"', '');

		const message = this.#getNotificationMessageForMassAction(actionWithoutQuotes);

		this.#sendRowAction(actionWithoutQuotes, {
			selectedShareRolesCodes: this.#grid.getRows().getSelectedIds(),
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(message);
		});
	}

	static #getNotificationMessageForMassAction(actionName: string): string
	{
		switch (actionName)
		{
			case 'multiple-activate':
			{
				return Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_ACTIVATE');
			}

			case 'multiple-deactivate':
			{
				return Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_DEACTIVATE');
			}

			case 'multiple-show-for-me':
			{
				return Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_SHOW');
			}

			case 'multiple-hide-from-me':
			{
				return Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_HIDE');
			}

			default:
			{
				return Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_MASS_ACTION_DEFAULT');
			}
		}
	}

	static init(gridId: string, isShowTour: boolean = false)
	{
		if (isShowTour)
		{
			this.#showSimpleTour();
		}

		this.#grid = BX.Main.gridManager.getById(gridId)?.instance;

		bind(this.#grid.getScrollContainer(), 'scroll', () => {
			this.#categoriesListPopup?.hide();
			this.#allSharesListPopup?.hide();
		});

		Controller.#updateApplyButtonClassname();
		Controller.#observeSelectActionButtonValue();

		Event.EventEmitter.subscribe('Grid::updated', () => {
			Controller.#updateApplyButtonClassname();
			Controller.#observeSelectActionButtonValue();
			BX.UI.Hint.init(BX('main-grid-table'));
		});

		Event.EventEmitter.subscribe('BX.Main.Filter:apply', () => {
			sendData({
				tool: 'ai',
				category: 'roles_saving',
				event: 'use_filter',
				c_section: 'list',
				status: 'success',
			});
		});

		BX.UI.Hint.init(BX('main-grid-table'));
	}

	static async handleClickOnSharesCell(shareRoleCode: string, event: PointerEvent): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		if (this.#allSharesListPopup)
		{
			this.#allSharesListPopup.hide();

			return;
		}

		this.#allSharesListPopup = new PopupWithLoader({
			bindElement: event.target,
			listRenderer: new SharesListRenderer(),
			events: {
				onPopupDestroy: () => {
					this.#allSharesListPopup = null;
				},
			},
			filter: (item: {code: string, name: string}, searchValue: string) => {
				return item.name.toLowerCase().includes(searchValue?.toLowerCase());
			},
			useSearch: true,
		});

		try
		{
			this.#allSharesListPopup.setLoading(true);
			this.#allSharesListPopup.show();

			const formData = new FormData();

			formData.append('roleCode', shareRoleCode);

			const res = await ajax.runAction('ai.shareRole.getShareForRole', {
				data: formData,
			});

			const list = res.data.list;
			this.#allSharesListPopup.setList(list.slice(5));
		}
		catch (e)
		{
			console.error(e);
			await showNotification(Loc.getMessage('ROLE_LIBRARY_GRID_NOTIFICATION_SHOW_ROLE_USERS_ERROR'));
			this.#allSharesListPopup.hide();
		}
		finally
		{
			this.#allSharesListPopup.setLoading(false);
		}
	}

	static #observeSelectActionButtonValue(): void
	{
		const panel = this.#grid.getActionsPanel();

		if (!panel)
		{
			return;
		}

		const attributesObserver = new MutationObserver(() => {
			Controller.#updateApplyButtonClassname();
		});

		const selectActionButton = panel.getControls()[0];

		attributesObserver.observe(selectActionButton, {
			childList: false,
			subtree: true,
			characterDataOldValue: false,
			attributes: true,
			attributeOldValue: true,
			attributeFilter: ['data-value'],
			characterData: false,
		});
	}

	static #updateApplyButtonClassname(): void
	{
		const panel = this.#grid.getActionsPanel();

		if (!panel)
		{
			return;
		}

		const values = panel.getValues();

		const btn = panel.getPanel().querySelector('#apply_button_control.ui-btn');

		const action = values['action-menu'];

		if (action === '"select-action"' || action === 'select-action')
		{
			Dom.addClass(btn, 'ui-btn-disabled');
			Dom.addClass(btn, 'ai__role-library-grid_share-initials');
		}
		else
		{
			Dom.removeClass(btn, 'ui-btn-disabled');
			Dom.removeClass(btn, 'ai__role-library-grid_share-initials');
		}
	}

	static #handleRoleSuccessSaving(event: BaseEvent): void
	{
		this.#sendRowAction('edit-role', {
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'ROLE_LIBRARY_GRID_NOTIFICATION_ROLE_SAVE_SUCCESS',
					{ '#NAME#': `<b>${Text.encode(event.getData().roleTitle)}</b>` },
				),
			);
		});
	}

	static async #showSimpleTour(): void
	{
		const loadGuideExtensionPromise = Runtime.loadExtension('ui.tour');
		const loadBannerDispatcherExtensionPromise = Runtime.loadExtension('ui.banner-dispatcher');

		const result = await Promise.all([loadGuideExtensionPromise, loadBannerDispatcherExtensionPromise]);

		const Guide = result[0].Guide;
		const BannerDispatcher: BannerDispatcherType = result[1].BannerDispatcher;

		BannerDispatcher.critical.toQueue((onDone) => {
			const guide: GuideType = new Guide({
				id: 'share-role-grid-create-prompt-hint',
				simpleMode: true,
				overlay: false,
				onEvents: true,
				autoSave: true,
				steps: [
					{
						target: '.ui-btn.ui-btn-success',
						title: Loc.getMessage('ROLE_LIBRARY_GRID_TOUR_TITLE'),
						text: Loc.getMessage('ROLE_LIBRARY_GRID_TOUR_DESCRIPTION'),
					},
				],
			});

			Event.EventEmitter.subscribe('UI.Tour.Guide:onFinish', () => {
				guide.save();
				onDone();
			});

			guide.start();
		});
	}

	static #getCurrentPage(): number
	{
		const currentPageElement = document.body.querySelector('.main-ui-pagination-page.main-ui-pagination-active');

		return Number.parseInt(currentPageElement?.innerText, 10) || 1;
	}

	static #sendRowAction(action: string, data: Object, callback: Function): void
	{
		const dataWithAction = {
			[this.#grid.getActionKey()]: action,
			...data,
		};

		this.#grid.reloadTable('POST', dataWithAction, callback);
	}
}
