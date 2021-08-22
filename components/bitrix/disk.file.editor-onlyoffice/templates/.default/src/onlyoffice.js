import {ajax as Ajax, Runtime, Type} from "main.core";
import { EventEmitter } from "main.core.events";
import { MenuItem } from 'main.popup';
import {PULL, PullClient} from "pull.client";
import type {EditorOptions, DocumentSession, Context, BaseObject} from "./types";
import {ButtonManager, Button, SplitButton, SaveButton, CloseButton} from "ui.buttons";
import ClientCommandHandler from "./client-command-handler";
import UserManager from "./user-manager";
import {LegacyPopup, SharingControlType} from "disk.sharing-legacy-popup";
import {ExternalLink} from 'disk.external-link';

export default class OnlyOffice
{
	editor: any = null;
	editorJson: any = null;
	userBoxNode: HTMLElement = null;
	editorNode: HTMLElement = null;
	editorWrapperNode: HTMLElement = null;
	targetNode: HTMLElement = null;
	documentSession: DocumentSession = null;
	linkToEdit: string = null;
	pullConfig: any = null;
	editButton: SplitButton = null;
	setupSharingButton: Button = null;
	documentWasChanged: boolean = false;
	dontEndCurrentDocumentSession: boolean = false;
	context: Context = null;
	usersInDocument: UserManager = null;
	sharingControlType: ?SharingControlType = null;

	constructor(editorOptions: EditorOptions)
	{
		const options = Type.isPlainObject(editorOptions) ? editorOptions : {};

		this.pullConfig = options.pullConfig;
		this.documentSession = options.documentSession;
		this.linkToEdit = options.linkToEdit;
		this.targetNode = options.targetNode;
		this.userBoxNode = options.userBoxNode;
		this.editorNode = options.editorNode;
		this.editorWrapperNode = options.editorWrapperNode;
		this.editButton = ButtonManager.createByUniqId(editorOptions.panelButtonUniqIds.edit);
		this.setupSharingButton = ButtonManager.createByUniqId(editorOptions.panelButtonUniqIds.setupSharing);
		this.sharingControlType = editorOptions.sharingControlType;
		this.context = {
			currentUser: options.currentUser,
			documentSession: this.documentSession,
			object: options.object,
			attachedObject: options.attachedObject,
		};
		this.usersInDocument = new UserManager({
			context: this.context,
			userBoxNode: this.userBoxNode,
		});

		this.sendTelemetryEvent('load');
		this.initializeEditor(options.editorJson);

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (currentSlider)
		{
			currentSlider.getData().set('documentSession', this.documentSession);
			this.loadDiskExtensionInTopWindow();
		}

		this.initPull();
		this.bindEvents();
	}

	initPull(): void
	{
		if (this.pullConfig)
		{
			BX.PULL = new PullClient({
				skipStorageInit: true
			});
			BX.PULL.start(this.pullConfig);
		}
	}

	sendTelemetryEvent(action, data): void
	{
		data = data || {};

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (!currentSlider)
		{
			return;
		}

		const currentSliderData = currentSlider.getData();
		data.action = action;
		data.uid = currentSliderData.get('uid');
		data.documentSessionId = this.context.documentSession.id;
		data.documentSessionHash = this.context.documentSession.hash;
		data.fileSize = this.context.object.size;

		BX.Disk.sendTelemetryEvent(data);
	}

	bindEvents(): void
	{
		EventEmitter.subscribe("SidePanel.Slider:onClose", this.handleClose.bind(this));
		window.addEventListener("beforeunload", this.handleClose.bind(this));

		if (this.editorJson.document.permissions.edit === true && this.editButton)
		{
			if (this.editButton.hasOwnProperty('mainButton'))
			{
				this.editButton.getMainButton().bindEvent('click', this.handleClickEditButton.bind(this));

				let menuWindow = this.editButton.getMenuWindow();
				let menuItems = Runtime.clone(menuWindow.getMenuItems());

				for (let i = 0; i < menuItems.length; i++)
				{
					let menuItem = menuItems[i];
					let menuItemOptions = Runtime.clone(menuItem.options);
					menuItemOptions.onclick = this.handleClickEditSubItems.bind(this);

					menuWindow.removeMenuItem(menuItem.getId());
					menuWindow.addMenuItem(menuItemOptions);
				}
			}
			else
			{
				this.editButton.bindEvent('click', this.handleClickEditButton.bind(this));
			}
		}
		if (this.setupSharingButton)
		{
			let menuWindow = this.setupSharingButton.getMenuWindow();
			let extLinkOptions = menuWindow.getMenuItem('ext-link').options;
			extLinkOptions.onclick = this.handleClickSharingByExternalLink.bind(this);

			menuWindow.removeMenuItem('ext-link');
			menuWindow.addMenuItem(extLinkOptions);

			let sharingOptions = menuWindow.getMenuItem('sharing').options;
			sharingOptions.onclick = this.handleClickSharing.bind(this);

			menuWindow.removeMenuItem('sharing');
			menuWindow.addMenuItem(sharingOptions);
		}

		PULL.subscribe(new ClientCommandHandler({
			context: this.context,
			userManager: this.usersInDocument,
		}));
	}

	initializeEditor(options): void
	{
		this.adjustEditorHeight(options);
		options.events = {
			onDocumentStateChange: this.handleDocumentStateChange.bind(this),
			onDocumentReady: this.handleDocumentReady.bind(this),
			onMetaChange: this.handleMetaChange.bind(this),
			onInfo: this.handleInfo.bind(this),
			// onRequestClose: this.handleClose.bind(this),
		}

		if (options.document.permissions.rename)
		{
			options.events.onRequestRename = this.handleRequestRename.bind(this);
		}

		this.editorJson = options;
		this.editor = new DocsAPI.DocEditor(this.editorNode.id, options);
	}

	adjustEditorHeight(options): void
	{
		options.height = (document.body.clientHeight - 70) + 'px';
	}

	loadDiskExtensionInTopWindow(): void
	{
		if (window.top !== window && !BX.getClass('window.top.BX.Disk.endEditSession'))
		{
			top.BX.loadExt('disk');
		}
	}

	emitEventOnSaved(): void
	{
		const sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
		if (sliderByWindow)
		{
			BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onSaved', {
				documentSession: this.documentSession,
				object: this.context.object,
			});
		}

		EventEmitter.emit('Disk.OnlyOffice:onSaved', {
			documentSession: this.documentSession,
			object: this.context.object,
		});
	}

	emitEventOnClosed(): void
	{
		const sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
		let process = 'edit';
		if (sliderByWindow)
		{
			process = sliderByWindow.getData().get('process') || 'edit';

			BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onClosed', {
				documentSession: this.documentSession,
				object: this.context.object,
				process: process,
			});
		}

		EventEmitter.emit('Disk.OnlyOffice:onClosed', {
			documentSession: this.documentSession,
			object: this.context.object,
			process: process,
		});
	}

	handleClickEditButton(): void
	{
		this.handleRequestEditRights();
	}

	handleClickSharing(): void
	{
		switch (this.sharingControlType)
		{
			case SharingControlType.WITH_CHANGE_RIGHTS:
				(new LegacyPopup()).showSharingDetailWithChangeRights({
					object: this.context.object
				});
				break;
			case SharingControlType.WITH_SHARING:
				(new LegacyPopup()).showSharingDetailWithChangeRights({
					object: this.context.object
				});
				break;
			case SharingControlType.WITHOUT_EDIT:
				(new LegacyPopup()).showSharingDetailWithoutEdit({
					object: this.context.object
				});
				break;
		}
	}

	handleClickSharingByExternalLink(): void
	{
		ExternalLink.showPopup(this.context.object.id);
	}

	handleClickEditSubItems(event, menuItem: MenuItem): void
	{
		let serviceCode = menuItem.getId();
		if(serviceCode === 'onlyoffice')
		{
			this.handleClickEditButton();

			return;
		}

		BX.Disk.Viewer.Actions.runActionEdit({
			objectId: this.context.object.id,
			attachedObjectId: this.context.attachedObject.id,
			serviceCode: serviceCode,
		});
	}

	handleSaveButtonClick(): void
	{
		PULL.subscribe({
			moduleId: 'disk',
			command: 'onlyoffice',
			callback: (data) => {
				if (data.hash === this.documentSession.hash)
				{
					this.emitEventOnSaved();

					window.BX.Disk.showModalWithStatusAction();
					BX.SidePanel.Instance.close();
				}
			}
		});
	}

	handleClose(): void
	{
		PULL.sendMessage([-1], 'disk', 'exitDocument', {
			fromUserId: this.context.currentUser.id,
		});

		this.sendTelemetryEvent('exit');

		this.emitEventOnClosed();

		if (this.dontEndCurrentDocumentSession)
		{
			return;
		}

		top.BX.Disk.endEditSession({
			id: this.documentSession.id,
			hash: this.documentSession.hash,
			documentWasChanged: this.documentWasChanged,
		});
	}

	handleDocumentStateChange(event): void
	{
		if (!this.caughtDocumentReady || !this.caughtInfoEvent)
		{
			return;
		}

		if (Date.now() - Math.max(this.caughtDocumentReady, this.caughtInfoEvent) < 500)
		{
			return;
		}

		this.documentWasChanged = true;
	}

	handleInfo(): void
	{
		this.caughtInfoEvent = Date.now();
	}

	handleRequestRename(event): void
	{
		const newName = event.data;
		Ajax.runAction('disk.api.onlyoffice.renameDocument', {
			mode: 'ajax',
			json: {
				documentSessionId: this.context.documentSession.id,
				documentSessionHash: this.context.documentSession.hash,
				newName: newName,
			}
		});
	}

	handleMetaChange(event): void
	{
	}

	handleDocumentReady(): void
	{
		this.sendTelemetryEvent('ready');

		this.caughtDocumentReady = Date.now();
	}

	handleRequestEditRights(): void
	{
		this.dontEndCurrentDocumentSession = true;

		let linkToEdit = BX.util.add_url_param(
			'/bitrix/services/main/ajax.php',
			{
				action: 'disk.api.documentService.goToEdit',
				serviceCode: 'onlyoffice',
				documentSessionId: this.documentSession.id,
				documentSessionHash: this.documentSession.hash,
			}
		);

		if (this.linkToEdit)
		{
			linkToEdit = this.linkToEdit;
		}

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (!currentSlider)
		{
			window.location = linkToEdit;

			return;
		}

		let customLeftBoundary = currentSlider.getCustomLeftBoundary();
		currentSlider.close();

		BX.SidePanel.Instance.open(
			linkToEdit, {
			width: '100%',
			customLeftBoundary: customLeftBoundary,
			cacheable: false,
			allowChangeHistory: false,
			data: {
				documentEditor: true
			}
		});
	}

	getEditor()
	{
		return this.editor;
	}

	getEditorNode(): HTMLElement
	{
		return this.editorNode;
	}

	getEditorWrapperNode(): HTMLElement
	{
		return this.editorWrapperNode;
	}

	getContainer(): HTMLElement
	{
		return this.targetNode;
	}
}
