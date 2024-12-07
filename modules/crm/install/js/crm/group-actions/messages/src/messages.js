import './css/messages.css';
import { Loc, Reflection } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Menu } from 'main.popup';
import { EntityCatalog } from 'ui.entity-catalog';
import { BatchWhatsappMessageManager, ProcessRegistry, type ProgressBarRepository } from 'crm.autorun';
import { UI } from 'ui.notification';
import { SettingsCreator } from './settings-creator';
import { TemplateCatalogCreator } from './template-catalog-creator';
import 'ui.design-tokens';

interface Options {
	gridId: string;
	entityTypeId: number;
	selectedIds: number[];
	categoryId: ?number;
	forAll: boolean;
}

export const DEFAULT_PROVIDER = 'ednaru';

const SELECTED_FROM_NUMBER_LOCALSTORE_KEY = 'bx.crm.group_actions.messages.selected_from_number';

export class Messages
{
	static #instance: Messages = null;

	#options: Options;

	#progressBarRepo: ProgressBarRepository;

	#catalog: EntityCatalog;

	#settingsMenu: ?Menu = null;

	#selectedFromNumber: ?string = null;

	#messages = {
		inProgress: Loc.getMessage('CRM_GROUP_ACTIONS_WHATSAPP_MESSAGE_IN_PROGRESS'),
	};

	static getInstance(progressBarRepo: ProgressBarRepository, options: Options): Messages
	{
		if (Messages.#instance)
		{
			Messages.#instance.setOptions(options);
		}
		else
		{
			Messages.#instance = new Messages(progressBarRepo, options);
		}

		return Messages.#instance;
	}

	constructor(progressBarRepo: ProgressBarRepository, options: Options)
	{
		this.#progressBarRepo = progressBarRepo;
		this.#options = options;
		this.#selectedFromNumber = this.#restoreLastSelectedFromNumber();
	}

	setOptions(options: Options)
	{
		this.#options = options;
	}

	async execute()
	{
		EventEmitter.subscribeOnce('BX.Crm.SmsEditorWrapper:click', this.#sendMessages.bind(this));
		EventEmitter.subscribe('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected', this.#fromPhoneSelected.bind(this));

		this.#showGridLoader();

		this.#catalog = await (new TemplateCatalogCreator())
			.create(this.#options.entityTypeId, this.#options.categoryId);

		this.#hideGridLoader();

		this.#catalog.show();

		const popup = this.#catalog.getPopup();
		popup.subscribeOnce('onClose', this.#destroy.bind(this));

		EventEmitter.subscribe(
			'BX.Crm.GroupActionsWhatsApp.Settings:click',
			this.#showSettingsMenu.bind(this),
		);

		EventEmitter.subscribe(
			'BX.Crm.GroupActionsWhatsApp.Settings:help',
			this.#showHelpArticle.bind(this),
		);
	}

	async #showSettingsMenu(event: BaseEvent): void
	{
		if (this.#settingsMenu !== null)
		{
			this.#settingsMenu.close();
			this.#settingsMenu.destroy();
			this.#settingsMenu = null;
		}

		this.#settingsMenu = await (new SettingsCreator(this.#selectedFromNumber)).create();

		this.#settingsMenu.show();
	}

	#showHelpArticle(event: BaseEvent)
	{
		const articleCode = event.getData().code;

		if (!articleCode)
		{
			throw new Error('articleCode is not defined');
		}

		const Helper = Reflection.getClass('top.BX.Helper');

		if (Helper)
		{
			Helper.show(`redirect=detail&code=${articleCode}`);
		}
	}

	#destroy()
	{
		EventEmitter.unsubscribeAll('BX.Crm.SmsEditorWrapper:click');
		EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.Settings:click');
		EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.Settings:help');
		EventEmitter.unsubscribeAll('BX.Crm.GroupActionsWhatsApp.FromPhoneSelected');
		if (this.#catalog)
		{
			this.#catalog.getPopup().unsubscribeAll('onClose');
			this.#catalog.close();
			this.#catalog = null;
		}

		if (this.#settingsMenu)
		{
			this.#settingsMenu.close();
			this.#settingsMenu.destroy();
			this.#settingsMenu = null;
		}
	}

	#fromPhoneSelected(event: BaseEvent)
	{
		const fromNumber = event.getData().phone;
		this.#storeLastSelectedFromNumber(fromNumber);
		this.#selectedFromNumber = fromNumber;
	}

	async #sendMessages(event: BaseEvent)
	{
		const gridId = this.#options.gridId;
		const entityTypeId = this.#options.entityTypeId;

		const messageBody = event.getData()?.text || '';
		const messageTemplate = event.getData()?.templateId || null;

		const container = this.#progressBarRepo
			.getOrCreateProgressBarContainer('whatsapp-message').id;

		const settings = {
			gridId,
			entityTypeId,
			container,
		};

		const bwmManager = BatchWhatsappMessageManager.getInstance(gridId, settings);
		if (bwmManager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(gridId))
		{
			this.#showAnotherProcessRunningNotification();

			return;
		}

		bwmManager.setTemplateParams({
			messageBody,
			messageTemplate,
			fromPhone: this.#selectedFromNumber,
		});

		if (this.#options.forAll)
		{
			bwmManager.resetEntityIds();
		}
		else
		{
			bwmManager.setEntityIds(this.#options.selectedIds);
		}

		bwmManager.execute();

		this.#destroy();
	}

	#showAnotherProcessRunningNotification()
	{
		UI.Notification.Center.notify({
			content: this.#messages.inProgress,
			autoHide: true,
			autoHideDelay: 5000,
		});
	}

	#showGridLoader()
	{
		const gridLoader = this.#getGridLoader();

		if (gridLoader)
		{
			gridLoader.show();
		}
	}

	#hideGridLoader()
	{
		const gridLoader = this.#getGridLoader();
		if (gridLoader)
		{
			gridLoader.hide();
		}
	}

	#getGridLoader(): ?BX.Grid.Loader
	{
		return BX.Main.gridManager.getById(this.#options.gridId)?.instance?.getLoader();
	}

	#restoreLastSelectedFromNumber(): ?string
	{
		return localStorage.getItem(SELECTED_FROM_NUMBER_LOCALSTORE_KEY) || null;
	}

	#storeLastSelectedFromNumber(fromNumber: string): void
	{
		localStorage.setItem(SELECTED_FROM_NUMBER_LOCALSTORE_KEY, fromNumber);
	}
}
