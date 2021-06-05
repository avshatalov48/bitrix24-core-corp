import {Type, ajax as Ajax} from "main.core";
import { EventEmitter } from 'main.core.events';
import {PULL} from "pull.client";
import type {EditorOptions, DocumentSession} from "./types";

export default class OnlyOffice
{
	editor: any = null;
	editorJson: any = null;
	editorNode: HTMLElement = null;
	editorWrapperNode: HTMLElement = null;
	targetNode: HTMLElement = null;
	documentSession: DocumentSession = null;
	object: BaseObject = null;
	documentWasChanged: boolean = false;
	dontEndCurrentDocumentSession: boolean = false;

	constructor(editorOptions: EditorOptions)
	{
		const options = Type.isPlainObject(editorOptions) ? editorOptions : {};

		this.documentSession = options.documentSession;
		this.object = options.object;
		this.targetNode = options.targetNode;
		this.editorNode = options.editorNode;
		this.editorWrapperNode = options.editorWrapperNode;

		this.initializeEditor(options.editorJson);

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		currentSlider.getData().set('documentSession', this.documentSession);
		this.bindEvents();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe("SidePanel.Slider:onClose", this.handleClose.bind(this));
	}

	initializeEditor(options): void
	{
		this.adjustEditorHeight(options);
		options.events = {
			onDocumentStateChange: this.handleDocumentStateChange.bind(this),
			onDocumentReady: this.handleDocumentReady.bind(this),
			onInfo: this.handleInfo.bind(this),
			// onRequestClose: this.handleClose.bind(this),
		}

		if (options.document.permissions.edit === true)
		{
			//in that case we will show Edit button
			options.events.onRequestEditRights = this.handleRequestEditRights.bind(this);
		}

		this.editorJson = options;
		this.editor = new DocsAPI.DocEditor(this.editorNode.id, options);
	}

	adjustEditorHeight(options): void
	{
		options.height = (document.body.clientHeight) + 'px';
	}

	emitEventOnSaved(): void
	{
		const sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
		if (sliderByWindow)
		{
			BX.SidePanel.Instance.postMessageAll(window, 'Disk.OnlyOffice:onSaved', {
				documentSession: this.documentSession,
				object: this.object,
			});
		}

		EventEmitter.emit('Disk.OnlyOffice:onSaved', {
			documentSession: this.documentSession,
			object: this.object,
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
				object: this.object,
				process: process,
			});
		}

		EventEmitter.emit('Disk.OnlyOffice:onClosed', {
			documentSession: this.documentSession,
			object: this.object,
			process: process,
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

	handleDocumentReady(): void
	{
		this.caughtDocumentReady = Date.now();
	}

	handleRequestEditRights(): void
	{
		this.dontEndCurrentDocumentSession = true;

		const currentSlider = BX.SidePanel.Instance.getSliderByWindow(window);
		let customLeftBoundary = currentSlider.getCustomLeftBoundary();
		currentSlider.close();

		BX.SidePanel.Instance.open(
			BX.util.add_url_param(
				'/bitrix/services/main/ajax.php',
				{
					action: 'disk.api.documentService.goToEdit',
					serviceCode: 'onlyoffice',
					documentSessionId: this.documentSession.id,
					documentSessionHash: this.documentSession.hash,
				}
		), {
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