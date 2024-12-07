import { bind, Tag, Dom } from 'main.core';
import { Popup } from 'main.popup';
import { Loader } from 'main.loader';
import { ListRenderer } from './list-renderer';

export type PopupWithLoaderOptions = {
	bindElement: HTMLElement;
	listRenderer: ListRenderer;
	events?: Object;
	filter?: Function;
	useSearch?: boolean;
}

export class PopupWithLoader
{
	#bindElement: HTMLElement = null;
	#popupContent: {root: HTMLElement, listContainer: HTMLElement};
	#isLoading: boolean = false;
	#popup: Popup = null;
	#list: string[] = [];
	#listRenderer: ListRenderer;
	#events: Object;
	#useSearch: boolean;
	#searchValue: string = '';
	#filter: Function | null;

	constructor(options: PopupWithLoaderOptions)
	{
		this.#bindElement = options.bindElement;
		this.#listRenderer = options.listRenderer;
		this.#events = options.events || {};
		this.#filter = options.filter || null;
		this.#useSearch = options.useSearch === true;
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#initPopup();
		}

		this.#popup.show();

		if (this.#isLoading)
		{
			const copilotColor = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');

			const loader = new Loader({
				target: this.#popupContent,
				size: 30,
				color: copilotColor,
			});

			loader.show(this.#popupContent.root);
		}
	}

	hide(): void
	{
		this.#popup?.destroy();
		this.#popup = null;
		this.#popupContent = null;
	}

	isShown(): boolean
	{
		return Boolean(this.#popup?.isShown());
	}

	setLoading(isLoading: boolean): void
	{
		this.#isLoading = isLoading;
	}

	setList(list: string[])
	{
		this.#list = list;

		if (this.#popup)
		{
			this.#popup.setContent(this.#renderPopupContent());
		}
	}

	#initPopup()
	{
		this.#popup = new Popup({
			bindElement: this.#bindElement,
			cacheable: false,
			className: 'ai__share-prompt-library-grid_popup-with-more-info',
			angle: {
				position: 'top',
			},
			autoHide: true,
			closeByEsc: true,
			content: this.#renderPopupContent(),
			width: 285,
			minHeight: 190,
			maxHeight: 300,
			padding: 16,
			contentPadding: 0,
			events: {
				...this.#events,
			},
		});
	}

	#renderPopupContent(): HTMLElement
	{
		const listWithSearchClassnameModifier = this.#useSearch ? '--with-search' : '';

		this.#popupContent = Tag.render`
			<div class="ai__prompt-library_info-popup">
				${this.#renderSearch()}
				<div class="ai__prompt-library_info-popup_list ${listWithSearchClassnameModifier}" ref="listContainer">
					${this.#renderList()}
				</div>
			<div>
		`;

		return this.#popupContent.root;
	}

	#renderList(): HTMLElement
	{
		const list = this.#list.filter((item) => {
			if (this.#filter)
			{
				return this.#filter(item, this.#searchValue);
			}

			return true;
		});

		return this.#listRenderer.render(list, this.#searchValue);
	}

	#updateList(): void
	{
		if (this.#popupContent.listContainer)
		{
			this.#popupContent.listContainer.innerHTML = '';
			Dom.append(this.#renderList(), this.#popupContent.listContainer);
		}
	}

	#renderSearch(): HTMLElement
	{
		if (this.#useSearch === false)
		{
			return null;
		}

		const container = Tag.render`
			<div class="ai__prompt-library_info-popup_search">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-before-icon ui-ctl-after-icon">
					<div class="ui-ctl-before ui-ctl-icon-search"></div>
					<button ref="clear" class="ui-ctl-after ui-ctl-icon-clear"></button>
					<input ref="input" type="text" class="ui-ctl-element">
				</div>
			</div>
		`;

		bind(container.clear, 'click', () => {
			container.input.value = '';
			this.#searchValue = '';
			this.#updateList();
		});

		bind(container.input, 'input', (e) => {
			this.#searchValue = e.target.value;
			this.#updateList();
		});

		return container.root;
	}
}
