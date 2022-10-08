import {ajax as Ajax, Runtime, Type} from "main.core";
import {BaseEvent, EventEmitter} from "main.core.events";
import { MenuItem } from "main.popup";
import {PULL, PullClient} from "pull.client";
import type {EditorOptions, DocumentSession, Context} from "./types";
import {ButtonManager, Button, SplitButton} from "ui.buttons";
import ClientCommandHandler from "./client-command-handler";
import ServerCommandHandler from "./server-command-handler";
import UserManager from "./user-manager";
import {LegacyPopup, SharingControlType} from "disk.sharing-legacy-popup";
import {ExternalLink} from "disk.external-link";
import {PromoPopup} from "disk.onlyoffice-promo-popup";
import CustomErrorControl from "./custom-error-controls";

const SECONDS_TO_MARK_AS_STILL_WORKING = 60;

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
	linkToView: string = null;
	linkToDownload: string = null;
	pullConfig: any = null;
	editButton: SplitButton = null;
	setupSharingButton: Button = null;
	documentWasChanged: boolean = false;
	dontEndCurrentDocumentSession: boolean = false;
	context: Context = null;
	usersInDocument: UserManager = null;
	sharingControlType: ?SharingControlType = null;
	brokenDocumentOpened: boolean = false;

	constructor(editorOptions: EditorOptions)
	{
		const options = Type.isPlainObject(editorOptions) ? editorOptions : {};

		this.pullConfig = options.pullConfig;
		this.documentSession = options.documentSession;
		this.linkToEdit = options.linkToEdit;
		this.linkToView = options.linkToView;
		this.linkToDownload = options.linkToDownload;
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
		this.context.object.publicChannel = options.publicChannel;
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
		}

		this.loadDiskExtensionInTopWindow();

		this.initPull();
		this.bindEvents();
		if (this.isEditMode())
		{
			this.registerTimerToTrackWork();
		}

		if (PromoPopup.shouldShowViewPromo())
		{
			PromoPopup.showViewPromo();
		}
	}

	registerTimerToTrackWork(): void
	{
		setInterval(this.#trackWork.bind(this), SECONDS_TO_MARK_AS_STILL_WORKING*1000);
	}

	#trackWork(): void
	{
			Ajax.runComponentAction('bitrix:disk.file.editor-onlyoffice', 'markAsStillWorkingSession', {
				mode: 'ajax',
				json: {
					documentSessionId: this.context.documentSession.id,
					documentSessionHash: this.context.documentSession.hash,
				}
			});
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
		EventEmitter.subscribe("SidePanel.Slider:onClose", this.handleSliderClose.bind(this));
		window.addEventListener("beforeunload", this.handleClose.bind(this));

		if (window.top !== window)
		{
			window.addEventListener("message", (event: MessageEvent) => {
				if (event.data === 'closeIframe')
				{
					this.handleClose();
				}
			});
		}

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
			onlyOffice: this,
			context: this.context,
			userManager: this.usersInDocument,
		}));
		PULL.subscribe(new ServerCommandHandler({
			onlyOffice: this,
			context: this.context,
			userManager: this.usersInDocument,
		}));
	}

	initializeEditor(options): void
	{
		options.events = {
			onDocumentStateChange: this.handleDocumentStateChange.bind(this),
			onDocumentReady: this.handleDocumentReady.bind(this),
			onMetaChange: this.handleMetaChange.bind(this),
			onInfo: this.handleInfo.bind(this),
			onWarning: this.handleWarning.bind(this),
			onError: this.handleError.bind(this),
			onRequestClose: this.handleRequestClose.bind(this),
		}

		if (options.document.permissions.rename)
		{
			options.events.onRequestRename = this.handleRequestRename.bind(this);
		}

		this.editorJson = options;
		this.editor = new DocsAPI.DocEditor(this.editorNode.id, options);
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
		if (PromoPopup.shouldShowEditPromo())
		{
			PromoPopup.showEditPromo();

			return;
		}

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

	handleClickSharingByExternalLink(event, menuItem: MenuItem): void
	{
		if (menuItem.dataset.shouldBlockExternalLinkFeature)
		{
			eval(menuItem.dataset.blockerExternalLinkFeature);

			return;
		}

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
			name: this.context.object.name,
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

	handleRequestClose(): void
	{
		console.log('handleRequestClose');
		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (!currentSlider)
		{
			return;
		}

		currentSlider.getData().set('dontInvokeRequestClose', true);
		this.handleClose();
		currentSlider.close();
	}

	isDocumentReadyToEdit(): boolean
	{
		if (this.brokenDocumentOpened)
		{
			return false;
		}

		if (!this.caughtDocumentReady)
		{
			return false;
		}

		return true;
	}

	handleSliderClose(event: BaseEvent): void
	{
		console.log('handleSliderClose');

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		if (!currentSlider)
		{
			return;
		}

		const currentSliderData = currentSlider.getData();
		const uid = currentSliderData.get('uid');

		/** @type {BX.SidePanel.Event} */
		const [sliderEvent] = event.getData();
		if (sliderEvent.getSlider().getData().get('uid') !== uid)
		{
			return;
		}

		if (this.isViewMode() || !this.isDocumentReadyToEdit())
		{
			this.handleClose();

			return;
		}

		if (this.editor.hasOwnProperty('requestClose'))
		{
			if (currentSliderData.get('dontInvokeRequestClose'))
			{
				return;
			}

			this.editor.requestClose();
			sliderEvent.denyAction();
		}
		else
		{
			this.handleClose();
		}
	}

	handleClose(): void
	{
		console.log('handleClose');

		PULL.sendMessageToChannels([this.context.object.publicChannel], 'disk', 'exitDocument', {
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

	wasDocumentChanged(): boolean
	{
		return this.documentWasChanged;
	}

	isEditMode(): boolean
	{
		return this.editorJson.editorConfig.mode === 'edit';
	}

	isViewMode(): boolean
	{
		return !this.isEditMode();
	}

	reloadView(): void
	{
		if (this.isViewMode())
		{
			document.location = this.linkToView;
		}
	}

	handleInfo(): void
	{
		this.caughtInfoEvent = Date.now();
	}

	handleWarning(d): void
	{
		console.log('onlyoffice warning:', d.data);
	}

	handleError(d): void
	{
		console.log('onlyoffice error:', d.data);

		if (d.data.errorCode === -82)
		{
			this.brokenDocumentOpened = true;
		}
		else if (d.data.errorCode === -84)
		{
			setTimeout(() => {
				(new CustomErrorControl()).showWhenTooLarge(
					this.context.object.name,
					this.getEditorWrapperNode(),
					this.getContainer(),
					this.linkToDownload,
				);

			}, 100);
		}
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
