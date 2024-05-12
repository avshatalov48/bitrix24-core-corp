import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Event, Tag, Dom, Loc, Type } from 'main.core';
import { Searcher } from './searcher';
import { Loader } from 'main.loader';

export type renderOptions = {
	searcher: Searcher,
	inputNode: HTMLElement,
	iconContainer: HTMLElement,
	timeout: number
}

export class Renderer
{
	#searcher: Searcher;
	#inputNode: HTMLElement;
	#iconContainer: HTMLElement;
	#popup: Popup;
	#timeoutId: number;
	#timeout: number;
	#nav: SearchNavigation;

	static EXTERNAL_LINK = 'EXTERNAL_LINKS';

	constructor(options: renderOptions)
	{
		this.#searcher = options.searcher;
		this.#inputNode = options.inputNode;
		this.#iconContainer = options.iconContainer;
		this.#timeout = options.timeout;
		this.#nav = new SearchNavigation();

		this.#popup = new Popup('settings-search-popup', this.#inputNode, {
			closeByEsc: true,
			angle: false,
			overlay: false,
			width: 332,//470,//this.#inputNode.offsetWidth,
			offsetTop: 4,
			background: '#fff',
			contentBackground: '#fff',
			contentPadding: 0,
			autoHide: true,
			borderRadius: 6,
			autoHideHandler: (event) => {
				return event.target !== this.#inputNode;
			},

		});
		this.#popup.setContent(this.renderContent());

		Event.bind(this.#inputNode, 'focus', () => {
			if (!this.#popup.isShown())
			{
				this.#popup.show();
			}
		});

		EventEmitter.subscribe('BX.Intranet.Settings:searchChangeState', (event) => {
			const {state} = event.data;
			this.#nav.clean();
			this.#popup.setContent(this.renderContent(state));
		});

		Event.bind(this.#iconContainer.querySelector('#intranet-settings-icon-delete'), 'click', () => {
			Dom.removeClass(this.#iconContainer, 'main-ui-show');
			this.#inputNode.value = '';
		})

		Event.bind(this.#inputNode, 'keyup', (event) => {
			if (
				event.keyCode === 37
				|| event.keyCode === 39
			)
			{
				return;
			}

			if (event.keyCode === 13) //enter
			{
				this.#nav.current().dispatchEvent(new MouseEvent('click'));
				this.#nav.unHighlightAll();

				return;
			}

			if (event.keyCode === 38) //up
			{
				this.#nav.prev().highlight();
				if (!Type.isNil(this.#nav.current()))
				{
					this.updateScroll(this.#nav.current());
				}

				return;
			}

			if (event.keyCode === 40) //down
			{
				this.#nav.next().highlight();

				if (!Type.isNil(this.#nav.current()))
				{
					this.updateScroll(this.#nav.current());
				}

				return;
			}

			if (this.getQuery().length > 0)
			{
				Dom.addClass(this.#iconContainer, 'main-ui-show');
			}
			else
			{
				Dom.removeClass(this.#iconContainer, 'main-ui-show');
			}

			if (!this.#popup.isShown() && this.getQuery().length > 0)
			{
				this.#popup.show();
			}
			this.find();
		});
	}

	updateScroll(element): void
	{
		const rect = element.getBoundingClientRect();
		const container = this.#popup.getContentContainer().firstElementChild;
		const relTop = rect.top - container.getBoundingClientRect().top;
		const relBot = rect.bottom - container.getBoundingClientRect().bottom;
		const padding = 10;
		if (relTop < 0 && relBot <= 0) //invisible top
		{
			container.scrollTo(0, relTop + container.scrollTop - padding);
		}
		else if (relTop >= 0 && relBot > 0) //invisible bottom
		{
			container.scrollTo(0, relBot + container.scrollTop + padding);
		}
	}

	renderWait(): HTMLElement
	{
		const loaderContainer = Tag.render`<span class="title-search-waiter-img"></span>`;
		const loader = new Loader({
			target: loaderContainer,
			size: 20,
			mode: 'inline',
		});
		loader.show();

		return Tag.render`
			<div class="title-search-waiter">
				${loaderContainer}
				<span class="title-search-waiter-text">${Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCHING')}</span>
			</div>
		`;
	}

	find(): void
	{
		clearTimeout(this.#timeoutId);
		this.#timeoutId = setTimeout(() => {
			this.#searcher.find(this.getQuery());
		}, this.#timeout);

	}

	getQuery(): string
	{
		return BX.util.trim(this.#inputNode.value);
	}

	createLinkOption(option): HTMLElement
	{
		const link =  Dom.create('a', {
			props: {
				className: 'search-title-top-item-link',
			},
			events: {
				mouseenter: (event) => {
					this.#nav.unHighlightAll();
					this.#nav.cursorTo(event.target);
					SearchNavigation.highlight(event.target);
				},
				mouseleave: (event) => {
					SearchNavigation.unHighlight(event.target);
				}
			},
			attrs: {
				title: option.title,
				href: Type.isStringFilled(option.url) ? option.url : '#',
				target: '_blank'
			},
			children: [
				Tag.render`<span class="search-title-top-item-text"><span>${option.title}</span></span>`,
			],
		});
		this.#nav.add(link);
		return link;
	}

	createBtnOption(page: string, option): HTMLElement
	{
		const link = Dom.create('a', {
			props: {
				className: 'search-title-top-item-link',
			},
			events: {
				click: (event) => {
					EventEmitter.emit(
						EventEmitter.GLOBAL_TARGET,
						'BX.Intranet.SettingsNavigation:onMove',
						{
							page: page,
							fieldName: option.code,
						}
					);
					this.#inputNode.blur();
					this.#popup.close();

					event.preventDefault();
				},
				mouseenter: (event) => {
					this.#nav.unHighlightAll();
					this.#nav.cursorTo(event.target);
					SearchNavigation.highlight(event.target);
				},
				mouseleave: (event) => {
					SearchNavigation.unHighlight(event.target);
				}
			},
			attrs: {
				title: option.title,
				href: "#",
			},
			children: [
				Tag.render`<span class="search-title-top-item-text"><span>${option.title}</span></span>`,
			],
		});
		this.#nav.add(link);
		return Tag.render`<div class="search-title-top-item search-title-top-item-js">${link}</div>`;
	}

	renderOption(page: string, option): HTMLElement
	{
		let link;
		if (page === Renderer.EXTERNAL_LINK)
		{
			link = this.createLinkOption(option);
		}
		else
		{
			link = this.createBtnOption(page, option)
		}

		return Tag.render`<div class="search-title-top-item search-title-top-item-js">${link}</div>`;
	}

	renderGroup(group): HTMLElement
	{
		const optionsContainer = Tag.render`<div class="search-title-top-list search-title-top-list-js"></div>`;
		group.options.forEach((option) => {
			Dom.append(this.renderOption(group.page, option), optionsContainer);
		});
		return Tag.render`
			<div class="search-title-top-block search-title-top-block-sonetgroups">
				<div class="search-title-top-subtitle">
					<div class="search-title-top-subtitle-text">${group.title}</div>
				</div>
				<div class="search-title-top-list-wrap">
					${optionsContainer}
				</div>
			</div>
		`;
	}

	renderContent(state: 'wait'|'ready'|'not_found' = 'ready'): HTMLElement
	{
		const optionsContainer = Tag.render`<div class="search-title-top-result"></div>`;

		switch (state)
		{
			case 'ready':
				Dom.append(this.renderSearchResult(this.#searcher.getResult()), optionsContainer);
				break;
			case 'wait':
				Dom.append(this.renderWait(), optionsContainer);
				break;
			case 'not_found':
				Dom.append(this.renderNotFound(), optionsContainer);
				break;
		}
		Dom.append(this.renderOthers(this.#searcher.getOthers()), optionsContainer);

		return optionsContainer;
	}

	renderNotFound(): HTMLElement
	{
		return Tag.render`
			<div class="title-search-waiter">
				<span class="title-search-waiter-text">${Loc.getMessage('INTRANET_SETTINGS_SEARCH_NOT_FOUND')}</span>
			</div>
		`;
	}

	renderSearchResult(result): HTMLElement
	{
		const container = Tag.render`<div class="search-title-content-result"></div>`;
		result.forEach((item) => {
			Dom.append(this.renderGroup(item), container);
		});

		return container;
	}

	renderOthers(links): HTMLElement
	{
		const wraper = Tag.render`<div class="search-title-top-list search-title-top-list-js"></div>`;
		const other = Tag.render`
		<div class="search-title-top-block search-title-top-block-tools">
			<div class="search-title-top-subtitle">
				<div class="search-title-top-subtitle-text">${Loc.getMessage('INTRANET_SETTINGS_TITLE_SEARCH_IN')}</div>
			</div>
			<div class="search-title-top-list-height-wrap">
					<div class="search-title-top-list-wrap">${wraper}</div>
				</div>
		</div>
		`;

		links.forEach((link) => {
			Dom.append(this.renderOtherLink(link), wraper);
		});

		return other
	}

	renderOtherLink(link): HTMLElement
	{
		const linkTag = Dom.create('a', {
			props: {
				className: 'search-title-top-item-link',

			},
			events: {
				mouseenter: (event) => {
					this.#nav.unHighlightAll();
					this.#nav.cursorTo(event.target);
					SearchNavigation.highlight(event.target);
				},
				mouseleave: (event) => {
					SearchNavigation.unHighlight(event.target);
				}
			},
			attrs: {
				title: link.title,
				href: link.link,
				target: 'blank_'
			},
			children: [
				Tag.render`<span class="search-title-top-item-text"><span>${link.title}</span></span>`,
			],
		});
		this.#nav.add(linkTag);

		return Tag.render`
		<div class="search-title-top-item search-title-top-item-js">
			${linkTag}
		</div>`;
	}
}

class SearchNavigation
{
	#index: ?number = null;
	#elementList: Array<HTMLElement>;

	constructor(nodeList: Array<HTMLElement> = [])
	{
		this.#elementList = nodeList;
	}

	add(element: HTMLElement): void
	{
		this.#elementList.push(element);
	}

	clean(): void
	{
		this.#index = null;
		this.#elementList = [];
	}

	next(): SearchNavigation
	{
		if (Type.isNil(this.#index))
		{
			this.#index = 0;

			return this;
		}

		if (this.#elementList.length - 1 > this.#index)
		{
			this.#index++;
		}

		return this;
	}

	current(): ?HTMLElement
	{
		if (Type.isNil(this.#index))
		{
			return null;
		}

		return this.#elementList[this.#index];
	}

	prev(): SearchNavigation
	{
		if (Type.isNil(this.#index))
		{
			this.#index = this.#elementList.length - 1;

			return this;
		}

		if (this.#index > 0)
		{
			this.#index -= 1;
		}

		return this;
	}

	highlight(): void
	{
		this.unHighlightAll();
		if (!Dom.hasClass(this.current(), 'active'))
		{
			Dom.addClass(this.current(), 'active');
		}

		return this;
	}

	unHighlight(): void
	{
		if (Dom.hasClass(this.current(), 'active'))
		{
			Dom.removeClass(this.current(), 'active');
		}

		return this;
	}

	static highlight(element: HTMLElement): void
	{
		element.classList.add('active');
	}

	static unHighlight(element: HTMLElement): void
	{
		element.classList.remove('active');
	}

	cursorTo(element: HTMLElement): void
	{
		this.#elementList.forEach((item, index) => {
			if (item === element)
			{
				this.#index = index;
				return;
			}
		});
	}

	unHighlightAll(): void
	{
		this.#elementList.forEach((item) => {
			SearchNavigation.unHighlight(item);
		});
	}
}
