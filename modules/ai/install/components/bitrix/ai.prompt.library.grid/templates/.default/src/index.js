import { BaseEvent } from 'main.core.events';
import { type BannerDispatcher as BannerDispatcherType } from 'ui.banner-dispatcher';
import { PopupCategoriesRenderer } from './popup-categories-renderer';
import { SharesListRenderer } from './shares-list-renderer';
import { PopupWithLoader } from './popup-with-loader';
import { ajax, bind, Runtime, Loc, Event, Dom, Text } from 'main.core';
import { showNotification, wrapTextToHtmlWithWordBreak } from './helpers';
import { sendData } from 'ui.analytics';
import { type Guide as GuideType } from 'ui.tour';
import type { PromptMasterPopupOptions } from 'ai.prompt-master';

type CategoryWithTranslate = {
	name: string;
	code: string;
};

export class Controller
{
	/**
	 * @var BX.Main.Grid
	 */
	static #grid;
	static #categoriesListPopup: PopupWithLoader = null;
	static #allSharesListPopup: PopupWithLoader = null;
	static #categoriesWithTranslate: CategoryWithTranslate[] = [];
	static #promptSuccessSavingEventHandler: Function = null;

	static async fetchPromptCategories(promptCode: string): Promise<Array> {
		const data = new FormData();
		data.append('promptCode', promptCode);

		const res = await ajax.runAction('ai.prompt.getCategoriesForPrompt', {
			data,
		});

		return res.data.list;
	}

	static handleClickOnDeletePromptSwitcher(event: PointerEvent, promptCode: string, promptTitle: string): void
	{
		event.preventDefault();
		event.stopPropagation();

		this.#sendRowAction('toggle-deleted', {
			promptCode,
			needDeleted: 1,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_HIDE',
					{ '#NAME#': `<b>${Text.encode(promptTitle)}</b>` },
				),
			);
		});
	}

	static handleClickOnUndoDeletePromptSwitcher(event: PointerEvent, promptCode: string, promptTitle: string): void
	{
		event.preventDefault();
		event.stopPropagation();

		this.#sendRowAction('toggle-deleted', {
			promptCode,
			needDeleted: 0,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_SHOW',
					{ '#NAME#': `<b>${Text.encode(promptTitle)}</b>` },
				),
			);
		});
	}

	static handleClickOnActivatePromptMenuItem(event: PointerEvent, promptCode: string, promptName: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-active', {
			promptCode,
			needActivate: 1,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_ACTIVATE',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
				)),
			);
		});
	}

	static handleClickOnDeactivatePromptMenuItem(event: PointerEvent, promptCode: string, promptName: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-active', {
			promptCode,
			needActivate: 0,
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_DEACTIVATE',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
				)),
			);
		});
	}

	static async handleClickOnSharesCell(sharePromptCode: string, event: PointerEvent): void
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

			formData.append('promptCode', sharePromptCode);

			const res = await ajax.runAction('ai.prompt.getShareForPrompt', {
				data: formData,
			});

			const list = res.data.list;

			this.#allSharesListPopup.setList(list.slice(5));
		}
		catch (e)
		{
			console.error(e);
			await showNotification(Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_SHOW_PROMPT_USERS_ERROR'));
			this.#allSharesListPopup.hide();
		}
		finally
		{
			this.#allSharesListPopup.setLoading(false);
		}
	}

	static handleClickOnCategoriesCell(e: PointerEvent, promptCode: string, excludedCategoryCode: string)
	{
		e.preventDefault();
		e.stopImmediatePropagation();

		const target = e.target;

		if (Controller.#categoriesListPopup)
		{
			Controller.#categoriesListPopup.hide();

			return;
		}

		Controller.#categoriesListPopup = new PopupWithLoader({
			bindElement: target,
			listRenderer: new PopupCategoriesRenderer(),
			events: {
				onPopupDestroy: () => {
					Controller.#categoriesListPopup = null;
				},
			},
		});

		Controller.#categoriesListPopup.setLoading(true);
		Controller.#categoriesListPopup.show();

		Controller.fetchPromptCategories(promptCode)
			.then((list) => {
				const excludedCategoryListItemIndex = list.findIndex((item) => item.code === excludedCategoryCode);

				list.splice(excludedCategoryListItemIndex, 1);

				Controller.#categoriesWithTranslate = list;
				Controller.#categoriesListPopup.setLoading(false);
				Controller.#categoriesListPopup.setList(Controller.#categoriesWithTranslate);
			})
			.catch((err) => {
				console.error(err);
				Controller.#categoriesListPopup.setLoading(false);
				Controller.#categoriesListPopup.setList([]);
			});

		Controller.#categoriesListPopup.show();
	}

	static async handleClickOnPromptName(event: PointerEvent, promptCode: string): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.editPrompt(promptCode);
	}

	static async editPrompt(promptCode: string): void
	{
		this.#grid.getLoader().show();
		this.#grid.tableFade();
		const formData = new FormData();
		formData.append('promptCode', promptCode);

		const fetchPromptByCodePromise = ajax.runAction('ai.prompt.getPromptByCodeForUpdate', {
			method: 'POST',
			data: formData,
		});

		const loadPromptMasterExtensionPromise = Runtime.loadExtension('ai.prompt-master');

		try
		{
			const results = await Promise.all([fetchPromptByCodePromise, loadPromptMasterExtensionPromise]);

			const res = results[0];
			const PromptMasterPopup = results[1].PromptMasterPopup;
			const PromptMasterPopupEvents = results[1].PromptMasterPopupEvents;

			const prompt = res.data.prompt;

			const options: PromptMasterPopupOptions = {
				masterOptions: {
					code: prompt.code,
					prompt: prompt.prompt,
					type: prompt.type,
					name: prompt.translate,
					icon: prompt.icon,
					categories: Object.values(prompt.categories),
					accessCodes: prompt.accessCodes,
					authorId: prompt.authorId,
				},
			};
			const popup = new PromptMasterPopup({
				...options,
				popupEvents: {
					onPopupDestroy: () => {
						popup.unsubscribe(PromptMasterPopupEvents.SAVE_SUCCESS, Controller.#promptSuccessSavingEventHandler);
					},
				},
				analyticFields: {
					c_section: 'list',
				},
			});

			Controller.#promptSuccessSavingEventHandler = Controller.#handlePromptSuccessSaving.bind(Controller);

			popup.subscribe(PromptMasterPopupEvents.SAVE_SUCCESS, Controller.#promptSuccessSavingEventHandler);

			popup.show();
		}
		catch (e)
		{
			console.error(e);

			showNotification(Loc.getMessage('PROMPT_LIBRARY_GRID_ACTION_OPEN_EDIT_MASTER_ERROR'));
		}
		finally
		{
			this.#grid.getLoader().hide();
			this.#grid.tableUnfade();
		}
	}

	static async handleClickOnCreatePromptButton(button: Button): void
	{
		try
		{
			button.setClocking(true);
			const { PromptMasterPopup, PromptMasterPopupEvents } = await Runtime.loadExtension('ai.prompt-master');

			const popup = new PromptMasterPopup({
				popupEvents: {
					onPopupDestroy: () => {
						popup.unsubscribe(PromptMasterPopupEvents.SAVE_SUCCESS, Controller.#promptSuccessSavingEventHandler);
					},
				},
				analyticFields: {
					c_section: 'list',
				},
			});

			Controller.#promptSuccessSavingEventHandler = Controller.#handlePromptSuccessSaving.bind(Controller);

			popup.subscribe(PromptMasterPopupEvents.SAVE_SUCCESS, Controller.#promptSuccessSavingEventHandler);

			popup.show();
		}
		catch (e)
		{
			console.error(e);
			showNotification(Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_PROMPT_MASTER_OPEN_ERROR'));
		}
		finally
		{
			button.setClocking(false);
		}
	}

	static handleClickOnPromptIsFavouriteLabel(
		event: PointerEvent,
		promptCode: string,
		favourite: 'true' | 'false',
		promptName: string,
	): void
	{
		event.preventDefault();
		event.stopImmediatePropagation();

		this.#sendRowAction('toggle-favourite', {
			promptCode,
			favourite,
			page: this.#getCurrentPage(),
		}, () => {
			const message = favourite === 'true'
				? wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
				))
				: wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
				))
			;

			showNotification(message);
		});
	}

	static togglePromptFavourite(promptCode: string, favourite: boolean, promptName: string): void
	{
		this.#sendRowAction('toggle-favourite', {
			promptCode,
			favourite,
			page: this.#getCurrentPage(),
		}, () => {
			const message = favourite === 'true'
				? wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_FAVOURITE_ADD',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
				))
				: wrapTextToHtmlWithWordBreak(Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_FAVOURITE_REMOVE',
					{ '#NAME#': `<b>${Text.encode(promptName)}</b>` },
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
			selectedSharePromptsCodes: this.#grid.getRows().getSelectedIds(),
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
				return Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_MASS_ACTIVATE');
			}

			case 'multiple-deactivate':
			{
				return Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_MASS_DEACTIVATE');
			}

			case 'multiple-show-for-me':
			{
				return Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_MASS_SHOW');
			}

			case 'multiple-hide-from-me':
			{
				return Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_MASS_HIDE');
			}

			default:
			{
				return Loc.getMessage('PROMPT_LIBRARY_GRID_NOTIFICATION_MASS_ACTION_DEFAULT');
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
				category: 'prompt_saving',
				event: 'use_filter',
				c_section: 'list',
				status: 'success',
			});
		});

		BX.UI.Hint.init(BX('main-grid-table'));
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
			Dom.addClass(btn, 'ai__prompt-library-grid_share-initials');
		}
		else
		{
			Dom.removeClass(btn, 'ui-btn-disabled');
			Dom.removeClass(btn, 'ai__prompt-library-grid_share-initials');
		}
	}

	static #handlePromptSuccessSaving(event: BaseEvent): void
	{
		this.#sendRowAction('edit-prompt', {
			page: this.#getCurrentPage(),
		}, () => {
			showNotification(
				Loc.getMessage(
					'PROMPT_LIBRARY_GRID_NOTIFICATION_PROMPT_SAVE_SUCCESS',
					{ '#NAME#': `<b>${Text.encode(event.getData().promptTitle)}</b>` },
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
				id: 'share-prompt-grid-create-prompt-hint',
				simpleMode: true,
				overlay: false,
				onEvents: true,
				autoSave: true,
				steps: [
					{
						target: '.ui-btn.ui-btn-success',
						title: Loc.getMessage('PROMPT_LIBRARY_GRID_TOUR_TITLE'),
						text: Loc.getMessage('PROMPT_LIBRARY_GRID_TOUR_DESCRIPTION'),
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
