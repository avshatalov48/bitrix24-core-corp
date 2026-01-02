import { Dom, ajax as Ajax, Event, Tag, Loc, Runtime, Type } from 'main.core';
import { Loader } from 'main.loader';
import { EventEmitter, type BaseEvent } from 'main.core.events';

export type ImportantMessagesWidgetOptions = {
	widgetContainerId: string;
	messages: ImportantMessagesWidgetMessage[];
	options: Object;
	postInfo: Object;
	url: string;
	pageSettings: ImportantMessagesWidgetPageSettings;
}

type ImportantMessagesWidgetPageSettings = {
	NavPageCount: number;
	NavPageNomer: number;
	NavPageSize: number;
	NavRecordCount: number;
	bDescPageNumbering: boolean;
	nPageSize: number;
	iNumPage?: number;
}

export type ImportantMessagesWidgetMessage = {
	id: string;
	author_avatar: string;
	author_url: string;
	author_name: string;
	post_url: string;
	post_text: string;
}

type FetchNewMessagesResponse = {
	pageSettings: ImportantMessagesWidgetPageSettings;
	messages: ImportantMessagesWidgetMessage[];
}

export class ImportantMessagesWidget
{
	#widgetContainerId: string;
	#messages: ImportantMessagesWidgetMessage[] = [];
	#options = {};
	#postInfo = {};
	#url: string = '';
	#pageSettings: ImportantMessagesWidgetPageSettings = {};
	#activeMessageIndex = 0;
	#loader: Loader;
	#loaderOverlay: HTMLElement;
	#lockButtons: boolean = false;

	constructor(options: ImportantMessagesWidgetOptions)
	{
		this.#widgetContainerId = options.widgetContainerId;
		this.#messages = options.messages;
		this.#options = options.options;
		this.#postInfo = options.postInfo;
		this.#url = options.url;
		this.#pageSettings = options.pageSettings;

		this.#postInfo.AJAX_POST = 'Y';

		this.init();
	}

	init(): void
	{
		this.#renderMessages();

		Event.bind(this.#getNextMessageButton(), 'click', () => {
			if (this.#lockButtons)
			{
				return;
			}

			this.#lockButtons = true;

			this.#handleClickOnNextBtn()
				.then(() => {
					this.#lockButtons = false;
					this.updateNavigation();
				})
				.catch(() => {
					this.#lockButtons = false;
				})
			;
		});

		Event.bind(this.#getPrevMessageButton(), 'click', () => {
			if (this.#lockButtons)
			{
				return;
			}

			this.#lockButtons = true;

			this.#showPrevMessage()
				.then(() => {
					this.#lockButtons = false;
					this.updateNavigation();
				})
				.catch(() => {
					this.#lockButtons = false;
				})
			;
		});

		Event.bind(this.#getReadMessageButton(), 'click', () => {
			const activeMessage = this.getActiveMessage();
			this.#readMessage(activeMessage.id);
		});

		EventEmitter.subscribe('onImportantPostRead', (event) => {
			const [messageId] = event.getData();
			this.#removeMessageFromList(messageId);
		});

		const loadNewMessages = Runtime.debounce(() => {
			this.#loadNewMessages()
				.then(() => {
					this.updateNavigation();
					this.show();
				})
				.catch(() => {
					// fail silently
				})
			;
		}, 6000);

		EventEmitter.subscribe('onPullEvent-main', (event: BaseEvent) => {
			if (this.#messages.length > 0)
			{
				return;
			}

			const [command, params] = event.getCompatData();
			if (
				command === 'user_counter'
				&& params[Loc.getMessage('SITE_ID')]
				&& params[Loc.getMessage('SITE_ID')]['BLOG_POST_IMPORTANT']
			)
			{
				loadNewMessages();
			}
		});

		this.updateNavigation();
	}

	show(): void
	{
		if (this.#messages.length === 0)
		{
			return;
		}

		const activeMessage = this.getActiveMessage();
		if (!activeMessage)
		{
			const message = this.#messages[this.#activeMessageIndex] || null;
			const element = this.#getMessagesListContainer().querySelector(`[data-message-id="${message?.id}"]`);
			if (element)
			{
				Dom.addClass(element, '--active');
			}
		}

		Dom.removeClass(this.#getWidgetContainer(), ['--hidden', '--hiding']);
	}

	hide(): void
	{
		Dom.addClass(this.#getWidgetContainer(), '--hiding');
		Event.bindOnce(this.#getWidgetContainer(), 'transitionend', () => {
			Dom.removeClass(this.#getWidgetContainer(), '--hiding');
			Dom.addClass(this.#getWidgetContainer(), '--hidden');
		});
	}

	#needLoadNewMessages(): boolean
	{
		return (
			(this.#pageSettings.bDescPageNumbering === true && this.#pageSettings.NavPageNomer > 1)
			|| this.#messages.length < this.#pageSettings.NavRecordCount
		);
	}

	async #loadNewMessages(): Promise
	{
		const data = await this.#fetchNewMessages();
		const messages = data.messages;

		this.#setPageSettings(data.pageSettings);

		for (const message of messages)
		{
			if (this.#getMessageById(message.id))
			{
				continue;
			}

			this.#messages.push(message);

			Dom.append(
				this.#renderMessageElement(message, false),
				this.#getMessagesListContainer(),
			);
		}
	}

	#fetchNewMessages(): Promise<FetchNewMessagesResponse>
	{
		return new Promise((resolve, reject) => {
			const request = this.#postInfo;
			if (this.#pageSettings.bDescPageNumbering)
			{
				this.#pageSettings.iNumPage = this.#pageSettings.NavPageNomer - 1;
			}
			else
			{
				this.#pageSettings.iNumPage = this.#pageSettings.NavPageNomer + 1;
			}

			request.page_settings = this.#pageSettings;
			request.sessid = BX.bitrix_sessid();

			Ajax({
				method: 'POST',
				processData: true,
				url: this.#url,
				data: request,
				onsuccess: (response: string) => {
					const data = JSON.parse(response);

					resolve({
						messages: data.data,
						pageSettings: data.page_settings,
					});
				},
				onfailure: (error) => {
					reject(error);
				},
			});
		});
	}

	#renderMessages(): void
	{
		this.#messages.forEach((message, index) => {
			Dom.append(
				this.#renderMessageElement(message, index === 0),
				this.#getMessagesListContainer(),
			);
		});
	}

	#renderMessageElement(messageData: ImportantMessagesWidgetMessage, isActive: boolean): HTMLElement
	{
		return Tag.render`
			<div class="sidebar-imp-mess ${isActive ? '--active' : ''}" data-message-id="${messageData.id}">
				<a href="${messageData.post_url}" class="sidebar-imp-mess-wrap">
					<div class="sidebar-imp-mess-avatar-block">
						<div class="sidebar-imp-mess-author-avatar ui-icon ui-icon-common-user"><i ${messageData.author_avatar}></i></div>
					</div>
					<div class="sidebar-imp-mess-info">
						<div class="sidebar-imp-mess-title">${messageData.author_name}</div>
						<div class="sidebar-imp-mess-text">${messageData.post_text}</div>
					</div>
				</a>
			</div>
		`;
	}

	async #handleClickOnNextBtn(): Promise<void>
	{
		if (!this.#hasNextMessage() && this.#needLoadNewMessages())
		{
			this.#showLoader();
			await this.#loadNewMessages();
			this.#hideLoader();
		}

		return this.#showNextMessage()
			.then(() => {
				this.updateNavigation();
			})
			.catch(() => {})
		;
	}

	async #showNextMessage(): Promise<void>
	{
		if (!this.#hasNextMessage())
		{
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const activeMessage = this.#getMessagesListContainer().querySelector('.sidebar-imp-mess.--active');
			const nextMessage = activeMessage.nextElementSibling || this.#getMessagesListContainer().firstElementChild;

			this.#activeMessageIndex = this.#getNextMessageIndex();

			Dom.addClass(activeMessage, '--slide-out-left');
			Dom.addClass(nextMessage, '--active');
			Dom.addClass(nextMessage, '--slide-in-right');
			Event.bindOnce(activeMessage, 'animationend', () => {
				Dom.removeClass(activeMessage, '--active');
				Dom.removeClass(activeMessage, '--slide-out-left');
				Dom.removeClass(nextMessage, '--slide-in-right');
				resolve();
			});
		});
	}

	#showPrevMessage(): Promise<void>
	{
		if (!this.#hasPrevMessage())
		{
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			const activeMessage = this.#getMessagesListContainer().querySelector('.sidebar-imp-mess.--active');
			const prevMessage = activeMessage.previousElementSibling || this.#getMessagesListContainer().lastElementChild;

			this.#activeMessageIndex = this.#getPrevMessageIndex();

			Dom.addClass(activeMessage, '--slide-out-right');
			Dom.addClass(prevMessage, '--active');
			Dom.addClass(prevMessage, '--slide-in-left');
			Event.bindOnce(activeMessage, 'animationend', () => {
				Dom.removeClass(activeMessage, '--active');
				Dom.removeClass(activeMessage, '--slide-out-right');
				Dom.removeClass(prevMessage, '--slide-in-left');
				resolve();
			});
		});
	}

	async #readMessage(messageId: string): void
	{
		const data = this.#getMessageById(messageId);
		const options = [];
		for (const option of this.#options)
		{
			options.push({
				post_id: data.id,
				name: option.name,
				value: option.value,
			});
		}

		let request = this.#postInfo;
		request.options = options;
		request.page_settings = this.#pageSettings;
		request.sessid = BX.bitrix_sessid();
		request = Ajax.prepareData(request);

		this.#showLoader();

		if (!this.#hasNextMessage() && this.#needLoadNewMessages())
		{
			await this.#loadNewMessages();
		}

		Ajax({
			method: 'GET',
			url: this.#url + (this.#url.includes('?') ? '&' : '?') + request,
			onsuccess: (response) => {
				const data = JSON.parse(response);
				this.#removeMessageFromList(messageId, data.page_settings);
				this.#hideLoader();
			},
			onfailure: () => {
				this.#hideLoader();
			},
		});
	}

	#setPageSettings(pageSettings: ImportantMessagesWidgetPageSettings): void
	{
		this.#pageSettings = pageSettings;
	}

	#hasNextMessage(): boolean
	{
		return this.#activeMessageIndex + 1 <= this.#messages.length - 1;
	}

	#getNextMessageIndex(): number
	{
		return this.#activeMessageIndex + 1 > this.#messages.length - 1 ? 0 : this.#activeMessageIndex + 1;
	}

	#hasPrevMessage(): boolean
	{
		return this.#activeMessageIndex - 1 >= 0;
	}

	#getPrevMessageIndex(): number
	{
		return this.#activeMessageIndex - 1 < 0 ? this.#messages.length - 1 : this.#activeMessageIndex - 1;
	}

	#removeMessageFromList(messageId: string, pageSettings = null): void
	{
		const finalize = () => {
			const activeMessage = this.getActiveMessage();
			Dom.remove(messageElement);
			this.#messages = this.#messages.filter((message) => message.id !== messageId);

			this.#activeMessageIndex = Math.max(
				0,
				this.#messages.findIndex((message) => message.id === activeMessage.id),
			);

			if (pageSettings === null)
			{
				this.#setPageSettings({
					...this.#pageSettings,
					NavRecordCount: Math.max(0, this.#pageSettings.NavRecordCount - 1),
				});
			}
			else
			{
				this.#setPageSettings(pageSettings);
			}

			this.updateNavigation();
			if (this.#messages.length === 0)
			{
				this.hide();
			}
		};

		const messageElement = this.#getMessagesListContainer().querySelector(`[data-message-id="${messageId}"]`);
		const activeMessageElement = this.#getActiveMessageElement();
		if (messageElement === activeMessageElement)
		{
			if (this.#hasNextMessage())
			{
				this.#showNextMessage().then(finalize).catch(() => {});
			}
			else if (this.#hasPrevMessage())
			{
				this.#showPrevMessage().then(finalize).catch(() => {});
			}
			else
			{
				finalize();
			}
		}
		else
		{
			finalize();
		}
	}

	#getActiveMessageElement(): HTMLElement
	{
		return this.#getMessagesListContainer().querySelector('.--active');
	}

	#getMessageById(messageId: string): ?ImportantMessagesWidgetMessage
	{
		return this.#messages.find((message) => message.id === messageId);
	}

	#getMessagesListContainer(): HTMLElement
	{
		return document.getElementById('sidebar-imp-mess-list');
	}

	#getReadMessageButton(): HTMLButtonElement
	{
		return document.getElementById('sidebar-imp-mess-read-button');
	}

	#getWidgetContainer(): HTMLElement
	{
		return document.getElementById(this.#widgetContainerId);
	}

	#updateCurrentMessageNumber(): void
	{
		const currentMessageNumberContainer = document.getElementById('sidebar-imp-mess-current-mess-number');

		currentMessageNumberContainer.textContent = this.#activeMessageIndex + 1;
	}

	#updateTotalMessagesNumber(): void
	{
		const totalMessagesNumberContainer = this.#getTotalMessagesNumberContainer();
		totalMessagesNumberContainer.textContent = this.#pageSettings.NavRecordCount;
	}

	#getTotalMessagesNumberContainer(): HTMLElement
	{
		return document.getElementById('sidebar-imp-mess-total');
	}

	#getActiveMessageIndex(): number
	{
		return [...this.#getMessagesListContainer().children]
			.indexOf(this.#getMessagesListContainer().querySelector('.--active'))
		;
	}

	getActiveMessage(): ImportantMessagesWidgetMessage | null
	{
		return this.#messages[this.#getActiveMessageIndex()] || null;
	}

	#getPrevMessageButton(): HTMLButtonElement
	{
		return document.getElementById('sidebar-imp-mess-prev');
	}

	#getNextMessageButton(): HTMLButtonElement
	{
		return document.getElementById('sidebar-imp-mess-next');
	}

	#showLoader(): void
	{
		this.#loaderOverlay = Tag.render`
			<div class="sidebar-widget-content-overlay"></div>
		`;

		Dom.style(this.#loaderOverlay, {
			position: 'absolute',
			top: 0,
			left: 0,
			width: '100%',
			height: '100%',
			background: 'rgba(255, 255, 255, 0.7)',
		});

		Dom.append(this.#loaderOverlay, this.#getWidgetContainer());

		if (!this.#loader)
		{
			this.#loader = new Loader({
				size: 60,
				target: this.#loaderOverlay,
				color: '#0154C8',
			});
		}

		this.#loader.show();
	}

	#hideLoader(): void
	{
		this.#loader?.hide();
		Dom.remove(this.#loaderOverlay);
	}

	updateNavigation(): void
	{
		if (this.#activeMessageIndex >= this.#pageSettings.NavRecordCount - 1)
		{
			this.#disableNextButton();
		}
		else if (this.#pageSettings.NavRecordCount > 1)
		{
			this.#enableNextButton();
		}
		else
		{
			this.#disableNextButton();
		}

		if (this.#activeMessageIndex === 0)
		{
			this.#disablePrevButton();
		}
		else
		{
			this.#enablePrevButton();
		}

		this.#updateCurrentMessageNumber();
		this.#updateTotalMessagesNumber();
	}

	#enableNextButton()
	{
		this.#getNextMessageButton().removeAttribute('disabled');
	}

	#disableNextButton()
	{
		this.#getNextMessageButton().setAttribute('disabled', true);
	}

	#enablePrevButton()
	{
		this.#getPrevMessageButton().removeAttribute('disabled');
	}

	#disablePrevButton()
	{
		this.#getPrevMessageButton().setAttribute('disabled', true);
	}
}
