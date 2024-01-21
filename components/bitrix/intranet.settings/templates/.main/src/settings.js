import { ajax, Http, Event, Runtime } from 'main.core';
import { Analytic } from './analytic';
import { ToolsPage } from './pages/tools-page';
import { EmployeePage } from './pages/employee-page';
import { RequisitePage } from './pages/requisite-page';
import { CommunicationPage } from './pages/communication-page';
import { PortalPage } from './pages/portal-page';
import { ConfigurationPage } from './pages/configuration-page';
import { SchedulePage } from './pages/schedule-page';
import { GdprPage } from './pages/gdpr-page';
import { SecurityPage } from './pages/security-page';
import { ExternalTemporaryPage } from './pages/external-temporary-page';
import { Type, Dom, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { PageManager } from './page-manager';
import { BaseSettingsElement, ErrorCollection, BaseSettingVisitor,
	RecursiveFilteringVisitor, AscendingOpeningVisitor, BaseSettingsPage } from 'ui.form-elements.field';
import { Searcher } from './searcher';

import './css/style.css';
import './css/main_search_field.css';

export class Settings extends BaseSettingsElement
{
	#basePage: string;
	#currentPage: ?BaseSettingsPage;
	isChanged: boolean = false;
	#menuNode: ?HTMLElement;
	#settingsNode: ?HTMLElement;
	#contentNode: ?HTMLElement;
	#searcher: Searcher;
	#pageManager: ?PageManager;
	#cancelMessageBox: ?MessageBox;
	#analytic: Analytic;

	constructor(params)
	{
		super(params);
		this.#analytic = new Analytic({
			isAdmin: true,
			locationName: 'settings',
			isBitrix24: params.isBitrix24 === true,
			analyticContext: Type.isStringFilled(params.analyticContext) ? params.analyticContext : null
		});
		this.#analytic.addEventOpenSettings();
		this.#analytic.addEventStartPagePage(params.startPage);
		this.setEventNamespace('BX.Intranet.Settings');
		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			'button-click',
			(event) => {
				const [clickedBtn] = event.data;
				if (clickedBtn.TYPE === 'save')
				{
					this.#onClickSaveBtn(event);
				}
			},
		);

		EventEmitter.subscribe(
			EventEmitter.GLOBAL_TARGET,
			'SidePanel.Slider:onClose',
			this.#onSliderCloseHandler.bind(this),
		);
		this.#menuNode = Type.isDomNode(params.menuNode) ? params.menuNode : null;
		this.#settingsNode = Type.isDomNode(params.settingsNode) ? params.settingsNode : null;
		this.#contentNode = Type.isDomNode(params.contentNode) ? params.contentNode : null;
		this.#basePage = Type.isString(params.basePage) ? params.basePage : '';

		if (this.#menuNode)
		{
			this.#menuNode.querySelectorAll('li.ui-sidepanel-menu-item a')
				.forEach(item => {
					item.addEventListener('click', (event) => {
						this.show(item.dataset.type);
					});
				});
		}

		if (params.searchNode)
		{
			Event.bind(params.searchNode, 'focus', this.#onClickSearchInput.bind(this, params.searchNode));
		}

		if (this.#settingsNode)
		{
			this.#settingsNode.querySelector('.ui-button-panel input[name="cancel"]')
				.addEventListener('click', this.#onClickCancelBtn);
		}

		params.pages
			.concat(
				Object
					.values(params.externalPages)
					.map(({type, extensions}) => new ExternalTemporaryPage(type, extensions))
			)
			.forEach((page: BaseSettingsPage) => this.registerPage(page).expandPage(params.subPages[page.getType()]))
		;

		const toolsMenuItem = BX.UI.DropdownMenuItem.getItemByNode(this.#menuNode.querySelector('[data-type="tools"]'));
		if (toolsMenuItem.subItems && toolsMenuItem.subItems.length > 0)
		{
			toolsMenuItem.hideSubmenu();
			toolsMenuItem.setDefaultToggleButtonName();
		}
	}

	registerPage(page: BaseSettingsPage): BaseSettingsPage
	{
		page.setParentElement(this);
		page.subscribe('change', this.#onEventChangeData.bind(this))
			.subscribe('fetch', this.#onEventFetchPage.bind(this))
		;
		page.setAnalytic(this.#analytic);

		return page;
	}

	getPageByType(type: string): ?BaseSettingsPage
	{
		return this.getChildrenElements().find((page: BaseSettingsPage) => {
			return page.getType() === type;
		});
	}

	show(type: string): void
	{
		if (!Type.isDomNode(this.#contentNode))
		{
			console.log('Not found settings container');
			return;
		}

		const nextPage = this.getPageByType(type);
		if (!(nextPage instanceof BaseSettingsPage))
		{
			console.log('Not found "' + type + '" page');
			return;
		}

		if (nextPage === this.#currentPage)
		{
			return;
		}

		Dom.hide(this.#currentPage?.getPage());
		if (Type.isNil(nextPage.getPage().parentNode))
		{
			Dom.append(nextPage.getPage(), this.#contentNode);
		}
		else
		{
			Dom.show(nextPage.getPage());
		}
		this.#currentPage = nextPage;
		this.#analytic.addEventChangePage(type);
		this.#updatePageTypeToAddressBar();
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			'BX.Intranet.Settings:onAfterShowPage', {
				source: this,
				page: nextPage,
			},
		);
	}

	#getPageManager(): PageManager
	{
		if (!this.#pageManager)
		{
			this.#pageManager = (new PageManager(this.getChildrenElements()))
		}
		return this.#pageManager;
	}

	#onEventFetchPage(event: BaseEvent): Promise
	{
		return this.#getPageManager().fetchPage(event.getTarget());
	}

	#updatePageTypeToAddressBar()
	{
		let url = new URL(window.location.href);
		url.searchParams.set('page', this.#currentPage?.getType());
		url.searchParams.delete('IFRAME');
		url.searchParams.delete('IFRAME_TYPE');
		top.window.history.replaceState(null, '', url.toString());
	}

	#onSliderCloseHandler(event: BaseEvent)
	{
		const [panelEvent] = event.getCompatData();
		if (this.#cancelMessageBox instanceof MessageBox)
		{
			panelEvent.denyAction();
			return false;
		}

		if (this.isChanged && panelEvent.slider.getData()?.get('ignoreChanges') !== true)
		{
			panelEvent.denyAction();
			this.#cancelMessageBox = MessageBox.create({
				message: Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_DESC'),
				modal: true,
				buttons: [
					new BX.UI.Button({
						text: Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_OK'),
						color: BX.UI.Button.Color.SUCCESS,
						events: {
							click: () => {
								EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onCancel', {});
								panelEvent.slider.getData().set('ignoreChanges', true);
								this.isChanged = false;
								BX.UI.ButtonPanel.hide();
								this.#cancelMessageBox.close();
								this.#cancelMessageBox = null;
								panelEvent.slider.close();
								panelEvent.slider.destroy();

								if (this.#basePage.includes('/configs/')) {
									this.#reload('/index.php');
								}
							},
						}
					}),
					new BX.UI.CancelButton({
						text: Loc.getMessage('INTRANET_SETTINGS_CONFIRM_ACTION_CANCEL'),
						events: {
							click: () => {
								this.#cancelMessageBox.close();
								this.#cancelMessageBox = null;
							},
						}
					}),
				],
			});
			return this.#cancelMessageBox.show();
		}

		if (this.#basePage.includes('/configs/') || this.reloadAfterClose)
		{
			this.#reload('/index.php');
		}
	}

	#reload(url: ?string = null)
	{
		const loader =  document.querySelector('#ui-sidepanel-wrapper-loader');
		if (loader)
		{
			loader.style.display = '';
		}
		if (Type.isString(url))
		{
			top.window.location.href = url;
		}
		else
		{
			top.window.location.href = this.#basePage;
		}
	}

	#onEventChangeData(event)
	{
		this.isChanged = true;
		BX.UI.ButtonPanel.show();
	}

	#onClickSaveBtn(event)
	{
		let data = this.#getPageManager().collectData();
		EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onBeforeSave', {
			data: data,
		});
		this.#analytic.send();
		ajax.runComponentAction(
			'bitrix:intranet.settings',
			'set',
			{
				mode: 'class',
				data: Http.Data.convertObjectToFormData(data),
			},
		).then(this.#successSaveHandler.bind(this), this.#failSaveHandler.bind(this));
	}

	#successSaveHandler(response: Object)
	{
		this.isChanged = false;
		this.#hideWaitIcon();
		BX.UI.ButtonPanel.hide();
		// EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Settings:onSuccessSave', {});
		this.reloadAfterClose = true;
	}

	#failSaveHandler(response: Object)
	{
		let errorCollection = this.#prepareErrorCollection(response.errors);
		this.#hideWaitIcon();

		EventEmitter.emit('BX.UI.FormElement.Field:onFailedSave', {
			errors: errorCollection,
		});

		let pageType = this.#selectPageForError(errorCollection);
		this.#activeMenuItem(pageType);
	}

	#activeMenuItem(type: string)
	{
		let itemNode = document.querySelector('li a[data-type="' + type + '"]');
		if (itemNode)
		{
			itemNode.dispatchEvent(new window.Event('click'));
		}
	}

	#prepareErrorCollection(rawErrors): Object
	{
		let errorCollection = {};
		for (let error of rawErrors)
		{
			let type = error.customData?.page;
			let field = error.customData?.field;
			if (Type.isNil(type) || Type.isNil(field))
			{
				ErrorCollection.showSystemError(Loc.getMessage('INTRANET_SETTINGS_ERROR_FETCH_DATA'));
				break;
			}
			if (Type.isNil(errorCollection[type]))
			{
				errorCollection[type] = {};
			}
			if (Type.isNil(errorCollection[type][field]))
			{
				errorCollection[type][field] = [];
			}
			errorCollection[type][field].push(error.message);
		}

		return errorCollection;
	}

	#onClickCancelBtn(event)
	{
		top.BX.SidePanel.Instance.close();
	}

	#hideWaitIcon()
	{
		let saveBtnNode = document.querySelector('#intranet-settings-page #ui-button-panel-save');
		Dom.removeClass(saveBtnNode, 'ui-btn-wait');
	}

	#selectPageForError(errors): string
	{
		for (let pageType in errors)
		{
			return pageType;
		}
	}

	#onClickSearchInput(node: HTMLInputElement, event: Event)
	{
		Event.unbindAll(node);

		if (!this.#searcher)
		{
			this.#searcher = new Searcher({node});
			this.openFoundSections = Runtime.debounce(this.openFoundSections, 1000, this);

			this
				.#getPageManager()
				.fetchUnfetchedPages()
				.then(() => {
					this.#searcher.subscribe('fastSearch', this.#markFoundText.bind(this));
					this.#searcher.subscribe('clearSearch', this.#clearFoundText.bind(this));
					if (this.#searcher.getValue().length > 0)
					{
						this.#markFoundText(new BaseEvent({data: {current: this.#searcher.getValue()}}))
					}
				}, () => {
				})
				.finally(() => {
				})
			;

		}
	}

	#markFoundText(event: BaseEvent)
	{
		const searchText = event.getData().current.toLowerCase();

		const foundPages = this.getChildrenElements().filter((page: BaseSettingsPage) => {
			const menuNode = this.#menuNode
				.querySelector('li.ui-sidepanel-menu-item a[data-type="' + page.getType() + '"]')
				.closest('li.ui-sidepanel-menu-item')
			;

			removeMarkTag(page.getPage());

			if (page.getPage().innerText.toLowerCase().indexOf(searchText) >= 0)
			{
				Dom.addClass(menuNode, '--found');
				addMarkTag(page.getPage(), searchText);
				return true;
			}

			Dom.removeClass(menuNode, '--found');

			return false;
		});

		if (foundPages.length > 0)
		{
			this.openFoundSections(foundPages);
		}
	}

	#clearFoundText(event: BaseEvent)
	{
		this.getChildrenElements().forEach((page: BaseSettingsPage) => {
			const menuNode = this.#menuNode
				.querySelector('li.ui-sidepanel-menu-item a[data-type="' + page.getType() + '"]')
				.closest('li.ui-sidepanel-menu-item')
			;
			removeMarkTag(page.getPage());
			Dom.removeClass(menuNode, '--found');
		});
	}

	openFoundSections(pages)
	{
		pages.forEach(
			(baseSettingsElement) =>
			{
				RecursiveFilteringVisitor.startFrom(
					baseSettingsElement,
					(element) => element.render().querySelector('mark') instanceof HTMLElement
				).forEach((element) => AscendingOpeningVisitor.startFrom(element));
			})
		;
	}
}

function revertMarkTag(properlyMark: HTMLElement)
{
	if (properlyMark.sourceNode)
	{
		properlyMark.beforeMark && properlyMark.beforeMark.parentNode ? properlyMark.beforeMark.parentNode.removeChild(properlyMark.beforeMark) : '';
		properlyMark.afterMark && properlyMark.afterMark.parentNode ? properlyMark.afterMark.parentNode.removeChild(properlyMark.afterMark) : '';

		properlyMark.parentNode.replaceChild(properlyMark.sourceNode, properlyMark);

		delete properlyMark.beforeMark;
		delete properlyMark.afterMark;
		delete properlyMark.sourceNode;
	}
}

function removeMarkTag(node)
{
	node.querySelectorAll('mark')
		.forEach((markNode) => {
			revertMarkTag(markNode);
		})
	;
}

function addMarkTag(node, searchText): void
{
	if (!(node instanceof HTMLElement))
	{
		if (node instanceof Text)
		{
			const startIndex = node.data.toLowerCase().indexOf(searchText);
			if (startIndex >= 0)
			{
				const value = node.data;
				const nextSibling = node.nextSibling;
				const finishIndex = startIndex + searchText.length;
				const parentNode = node.parentNode;

				parentNode.removeChild(node);

				const properlyMark = document.createElement('MARK');
				properlyMark.innerText = value.substring(startIndex, finishIndex);
				properlyMark.sourceNode = node;
				properlyMark.beforeMark = null;
				properlyMark.afterMark = null;

				if (startIndex > 0)
				{
					const beforeMark = new window.Text(value.substring(0, startIndex));
					nextSibling ? parentNode.insertBefore(beforeMark, nextSibling) : parentNode.appendChild(beforeMark);
					properlyMark.beforeMark = beforeMark;
				}

				nextSibling ? parentNode.insertBefore(properlyMark, nextSibling) : parentNode.appendChild(properlyMark);

				if (finishIndex < value.length)
				{
					const afterMark =  new window.Text(value.substring(finishIndex, value.length));
					nextSibling ? parentNode.insertBefore(afterMark, nextSibling) : parentNode.appendChild(afterMark);
					properlyMark.afterMark = afterMark;
				}
			}
		}

		return;
	}

	node.childNodes.forEach((child)  => {
		if (
			child instanceof HTMLElement && child.innerText.toLowerCase().indexOf(searchText) >= 0
			|| child.data && child.data.toLowerCase().indexOf(searchText) >= 0
		)
		{
			addMarkTag(child, searchText);
		}
	});
}

export {
	ToolsPage,
	EmployeePage,
	PortalPage,
	CommunicationPage,
	RequisitePage,
	ConfigurationPage,
	SchedulePage,
	GdprPage,
	SecurityPage
};