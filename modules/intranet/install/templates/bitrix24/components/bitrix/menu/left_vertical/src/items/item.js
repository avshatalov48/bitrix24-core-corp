import {Event, Uri, Type, Loc, Text, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupManager, Menu, MenuItem} from 'main.popup';
import Options from "../options";
import Utils from "../utils";
import {SaveButton, CancelButton} from 'ui.buttons';

export default class Item
{
	static code = 'abstract';

	parentContainer: Element;
	container: Element;
	links = [];
	isDraggable = true;
	storage: Array = [];

	constructor(parentContainer, container)
	{
		this.parentContainer = parentContainer;
		this.container = container;
		this.init();

		this.onDeleteAsFavorites = this.onDeleteAsFavorites.bind(this);
		setTimeout(() => {
			EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
			EventEmitter.incrementMaxListeners(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
			EventEmitter.subscribe(this, Options.eventName('onItemDelete'), this.destroy.bind(this));
		}, 0);
		this.showError = this.showError.bind(this);
		this.showMessage = this.showMessage.bind(this);
	}

	getId(): string
	{
		return this.container.dataset.id;
	}

	getCode(): string
	{
		return this.constructor.code;
	}

	getName()
	{
		return this.container.querySelector("[data-role='item-text']").textContent;
	}

	canDelete(): boolean
	{
		return false;
	}

	delete()
	{
		// Just do it.
	}

	init()
	{
		this.links = [];
		if (
			this.container.hasAttribute('data-link')
			&& Type.isStringFilled(this.container.getAttribute("data-link"))
		)
		{
			this.links.push(
				this.container.getAttribute("data-link")
			);
		}

		if (this.container
			.hasAttribute("data-all-links"))
		{
			this.container
				.getAttribute("data-all-links")
				.split(",")
				.forEach((link) => {
					link = String(link).trim();
					if (Type.isStringFilled(link))
					{
						this.links.push(
							link
						);
					}
				})
			;
		}
		this.makeTextIcons();
		this.storage = this.container.dataset.storage.split(',');
	}

	update({link, openInNewPage, text})
	{
		openInNewPage = (openInNewPage === 'Y' ? 'Y' : 'N');

		if (this.container.hasAttribute('data-link'))
		{
			this.container.setAttribute('data-link', Text.encode(link));
			this.container.setAttribute('data-new-page', openInNewPage);
		}

		const linkNode = this.container.querySelector('a');
		if (linkNode)
		{
			if (Type.isString(link))
			{
				linkNode.setAttribute('href', Text.encode(link));
			}

			linkNode.setAttribute('target', openInNewPage === 'Y' ? '_blank' : '_self');
		}
		this.container.querySelector("[data-role='item-text']").innerHTML = Text.encode(text);
		this.init();
	}

	destroy()
	{
		EventEmitter.unsubscribe(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
		EventEmitter.decrementMaxListeners(EventEmitter.GLOBAL_TARGET, 'onItemDeleteAsFavorites');
	}

	getSimilarToUrl(currentUri: Uri): Array
	{
		const result = [];

		this.links.forEach((link, index) => {
			if (areUrlsEqual(link, currentUri))
			{
				result.push({
					priority: index > 0 ? 0 : 1, // main link is in higher priority
					url: link
				})
			}
		});
		return result;
	}

	makeTextIcons()
	{
		if (!this.container.classList.contains("menu-item-no-icon-state"))
		{
			return;
		}

		const icon = this.container.querySelector(".menu-item-icon");
		const text = this.container.querySelector(".menu-item-link-text");

		if (icon && text)
		{
			icon.textContent = getShortName(text.textContent);
		}
	}

	getCounterValue(): ?number
	{
		const node = this.container.querySelector('[data-role="counter"]');
		if (!node)
		{
			return null;
		}
		return parseInt(node.dataset.counterValue);
	}

	updateCounter(counterValue)
	{
		const node = this.container.querySelector('[data-role="counter"]');
		if (!node)
		{
			return;
		}
		const oldValue = parseInt(node.dataset.counterValue) || 0;
		node.dataset.counterValue = counterValue;

		if (counterValue > 0)
		{
			node.innerHTML = (counterValue > 99 ? '99+' : counterValue);
			this.container.classList.add('menu-item-with-index');
		}
		else
		{
			node.innerHTML = '';
			this.container.classList.remove('menu-item-with-index');
			if (counterValue < 0) // TODO need to know what it means
			{
				const warning = BX('menu-counter-warning-' + this.getId());
				if (warning)
				{
					warning.style.display = 'inline-block';
				}
			}
		}
		return {oldValue, newValue: counterValue};
	}

	markAsActive()
	{
		console.error('This action is only for the group');
	}

	showWarning(title, events)
	{
		this.removeWarning();
		const link = this.container.querySelector("a.menu-item-link");
		if (!link)
		{
			return
		}
		title = title ? Text.encode(title) : '';
		const node = Tag.render`<a class="menu-post-warn-icon" title="${title}"></a>`;
		if (events)
		{
			Object.keys(events).forEach((key) => {
				Event.bind(node, key, events[key]);
			});
		}
		this.container.classList.add("menu-item-warning-state");
		link.appendChild(node);
	}

	removeWarning()
	{
		if (!this.container.classList.contains('menu-item-warning-state'))
		{
			return;
		}
		this.container.classList.remove('menu-item-warning-state');
		let node;
		while (node = this.container.querySelector("a.menu-post-warn-icon"))
		{
			node.parentNode.removeChild(node);
		}
	}

	showMessagePopup;
	showMessage(message)
	{
		if (this.showMessagePopup)
		{
			this.showMessagePopup.close();
		}
		this.showMessagePopup = PopupManager.create("left-menu-message", this.container,
			{
				content: '<div class="left-menu-message-popup">' + message + '</div>',
				darkMode: true,
				offsetTop: 2,
				offsetLeft: 0,
				angle: true,
				events: {
					onClose: () => { this.showMessagePopup = null;}
				},
				autoHide: true
			});

		this.showMessagePopup.show();

		setTimeout(() => { if (this.showMessagePopup) {this.showMessagePopup.close()} }, 3000);
	}

	showError(response)
	{
		const errors = [];
		if (response.errors)
		{
			errors.push(response.errors[0].message);
		}
		else if (response instanceof TypeError)
		{
			errors.push(response.message);
		}

		const message = [Loc.getMessage("MENU_ERROR_OCCURRED"), ...errors].join(' ');
		this.showMessage(message);
	}

	getDropDownActions(): Array
	{
		return [];
	}

	getEditFields(): Object
	{
		return {id: this.getId(), text: this.getName()};
	}

	onDeleteAsFavorites({data})
	{
		if (String(data.id) === String(this.getId()))
		{
			if (this.getCode() === 'standard'/* instanceof ItemUserFavorites*/)
			{
				EventEmitter.emit(this, Options.eventName('onItemDelete'), {
					item: this,
					animate: true
				});
			}
			else
			{
				this.storage = [...this.storage].filter((v) => {return v !== 'standard'});
				this.container.dataset.storage = this.storage.join(',');
			}
			EventEmitter.unsubscribe(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
			EventEmitter.decrementMaxListeners(EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
			}
	}

	static detect(node)
	{
		return node.getAttribute("data-role") !== 'group' &&
			node.getAttribute("data-type") === this.code;
	}

	static createNode({id, text, link, openInNewPage, counterId, counterValue, topMenuId})
	{
		id = Text.encode(id);
		text = Text.encode(text);
		link = Text.encode(link);
		counterId = counterId ? Text.encode(counterId) : '';
		counterValue = counterValue ? parseInt(counterValue) : 0;
		openInNewPage = openInNewPage === 'Y' ? 'Y' : 'N';
		return Tag.render`<li 
			id="bx_left_menu_${id}" 
			data-status="show" 
			data-id="${id}" 
			data-role="item"
			data-storage="" 
			data-counter-id="${counterId}" 
			data-link="${link}" 
			data-all-links="" 
			data-type="${this.code}" 
			data-delete-perm="Y" 
			${topMenuId ? `data-top-menu-id="${Text.encode(topMenuId)}"` : ""}
			data-new-page="${openInNewPage}" 
			class="menu-item-block menu-item-no-icon-state">
				<span class="menu-favorites-btn menu-favorites-draggable">
					<span class="menu-fav-draggable-icon"></span>
				</span>
				<a class="menu-item-link" data-slider-ignore-autobinding="true" href="${link}" target="${openInNewPage === 'Y' ? '_blank' : '_self'}">
					<span class="menu-item-icon-box">
						<span class="menu-item-icon">W</span>
					</span>
					<span class="menu-item-link-text " data-role="item-text">${text}</span>
					${counterId ? 
					`<span class="menu-item-index-wrap">
						<span data-role="counter"
							data-counter-value="${counterValue}" class="menu-item-index" id="menu-counter-${counterId}">${counterValue}</span>
					</span>` : '' }
				</a>
				<span data-role="item-edit-control" class="menu-fav-editable-btn menu-favorites-btn">
					<span class="menu-favorites-btn-icon"></span>
				</span>
			</li>`;
	}

	//region Edition for siblings
	static backendSaveItem(itemInfo): Promise
	{
		throw 'Function backendSaveItem must be replaced';
	}
	static popup: Popup;
	static showUpdate(item: Item)
	{
		return new Promise((resolve, reject) => {
			this.showForm(item.container, item.getEditFields(), resolve, reject);
		});
	}
	static checkForm(form: HTMLFormElement): boolean
	{
		if (String(form.elements["text"].value).trim().length <= 0)
		{
			form.elements["text"].classList.add('menu-form-input-error');
			form.elements["text"].focus();
			return false;
		}
		if (form.elements["link"])
		{
			if (String(form.elements["link"].value).trim().length <= 0 ||
				Utils.refineUrl(form.elements["link"].value).length <= 0)
			{
				form.elements["link"].classList.add('menu-form-input-error');
				form.elements["link"].focus();
				return false;
			}
			else
			{
				form.elements["link"].value = Utils.refineUrl(form.elements["link"].value);
			}
		}
		return true;
	}
	static showForm(bindElement, itemInfo, resolve, reject)
	{
		if (this.popup)
		{
			this.popup.close();
		}

		const isEditMode = itemInfo.id !== '';
		const form = Tag.render`
<form name="menuAddToFavoriteForm">
	<input type="hidden" name="id" value="${Text.encode(itemInfo.id || '')}">
	<label for="menuPageToFavoriteName" class="menu-form-label">${Loc.getMessage("MENU_ITEM_NAME")}</label>
	<input name="text" type="text" id="menuPageToFavoriteName" class="menu-form-input" value="${Text.encode(itemInfo.text || '')}" >
	${itemInfo['link'] !== undefined ? `<br><br>
	<label for="menuPageToFavoriteLink" class="menu-form-label">${Loc.getMessage("MENU_ITEM_LINK")}</label>
	<input name="link" id="menuPageToFavoriteLink" type="text" class="menu-form-input" value="${Text.encode(itemInfo.link)}" >` : ''}
	${itemInfo['openInNewPage'] !== undefined ? `<br><br>
	<input name="openInNewPage" id="menuOpenInNewPage" type="checkbox" value="Y" ${itemInfo.openInNewPage === 'Y' ? 'checked' : ''} >
	<label for="menuOpenInNewPage" class="menu-form-label">${Loc.getMessage("MENU_OPEN_IN_NEW_PAGE")}</label>` : ''}
</form>`;
		Object.keys(itemInfo)
			.forEach((key) => {
				if (['id', 'text', 'link', 'openInNewPage'].indexOf(key) < 0)
				{
					const name = Text.encode(key);
					const value = Text.encode(itemInfo[key]);
					form.appendChild(
						Tag.render`<input type="hidden" name="${name}" value="${value}">`
					)
				}
			})
		;

		this.popup = PopupManager.create('menu-self-item-popup', bindElement, {
			className: 'menu-self-item-popup',
			titleBar: (itemInfo['link'] === undefined ? Loc.getMessage("MENU_RENAME_ITEM") : (
				isEditMode ? Loc.getMessage("MENU_EDIT_SELF_PAGE") : Loc.getMessage("MENU_ADD_SELF_PAGE")
			)),
			offsetTop: 1,
			offsetLeft: 20,
			cacheable: false,
			closeIcon: true,
			lightShadow: true,
			draggable: {restrict: true},
			closeByEsc: true,
			content: form,
			buttons: [
				new SaveButton({ onclick: () => {
						if (this.checkForm(form))
						{
							const itemInfoToSave = {};
							[...form.elements].forEach((node) => {
								itemInfoToSave[node.name] = node.value;
							});
							if (form.elements['openInNewPage'])
							{
								itemInfoToSave['openInNewPage'] = form.elements["openInNewPage"].checked ? 'Y' : 'N';
							}

							this
								.backendSaveItem(itemInfoToSave)
								.then(() => {
									resolve(itemInfoToSave);
									this.popup.close();
								})
								.catch(Utils.catchError);
						}
					} } ),
				new CancelButton({ onclick: () => { this.popup.close();} }),
			]
		});
		this.popup.show();
	}
	//endregion
}

function areUrlsEqual(url, currentUri: Uri)
{
	const checkedUri = new Uri(url);
	const checkedUrlBrief = checkedUri.getPath().replace('/index.php', '').replace('/index.html', '');

	const currentUrlBrief = currentUri.getPath().replace('/index.php', '').replace('/index.html', '');

	if (
		checkedUri.getHost() !== ''
		&&
		checkedUri.getHost() !== currentUri.getHost()
	)
	{
		return false;
	}
	if (currentUrlBrief.indexOf(checkedUrlBrief) !== 0)
	{
		return false;
	}
	return true;
}

function getShortName(name)
{
	if (!Type.isStringFilled(name))
	{
		return "...";
	}

	name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
	if (name.length <= 0)
	{
		return '...';
	}

	let shortName;
	let words = name.split(/[\s,]+/);
	if (words.length <= 1)
	{
		shortName = name.substring(0, 1);
	}
	else if (words.length === 2)
	{
		shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
	}
	else
	{
		let firstWord = words[0];
		let secondWord = words[1];

		for (let i = 1; i < words.length; i++)
		{
			if (words[i].length > 3)
			{
				secondWord = words[i];
				break;
			}
		}

		shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
	}

	return shortName.toUpperCase();
}
