import { ajax, Http, Event, Runtime } from 'main.core';
import { HelpMessage } from 'ui.section';
import { Analytic } from './analytic';
import { Navigation } from './navigation';
import { ToolsPage } from './pages/tools-page';
import { EmployeePage } from './pages/employee-page';
import { RequisitePage } from './pages/requisite-page';
import { CommunicationPage } from './pages/communication-page';
import { PortalPage } from './pages/portal-page';
import { ConfigurationPage } from './pages/configuration-page';
import { SchedulePage } from './pages/schedule-page';
import { GdprPage } from './pages/gdpr-page';
import { SecurityPage } from './pages/security-page';
import { MainpagePage } from './pages/mainpage-page';
import { ExternalTemporaryPage } from './pages/external-temporary-page';
import { Type, Dom, Loc } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { PageManager } from './page-manager';
import { BaseSettingsElement, ErrorCollection, BaseSettingsPage } from 'ui.form-elements.field';
import { Searcher } from './search-engine/searcher';
import { Renderer } from './search-engine/renderer';
import { ServerDataSource } from './search-engine/server-data-source';
import { Permission } from './permission';

import './css/style.css';
import './css/main_search_field.css';

export class Settings extends BaseSettingsElement
{
	#basePage: string;
	isChanged: boolean = false;
	#menuNode: ?HTMLElement;
	#settingsNode: ?HTMLElement;
	#contentNode: ?HTMLElement;
	#pageManager: ?PageManager;
	#cancelMessageBox: ?MessageBox;
	#analytic: Analytic;
	#navigator: Navigation;
	#permission: Permission;
	#pagesPermission;
	#extraSettings = {
		reloadAfterClose: false,
	};

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
		this.#permission = params.permission instanceof Permission ? params.permission : new Permission();
		this.#pagesPermission = params.pagesPermission;

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

		this.#navigator = new Navigation(this);

		if (this.#menuNode)
		{
			this.#menuNode.querySelectorAll('li.ui-sidepanel-menu-item a.ui-sidepanel-menu-link')
				.forEach(item => {
					const helpPopup = new HelpMessage(
						item.dataset.type + '_help-msg',
						item,
						Loc.getMessage('INTRANET_SETTINGS_PERMISSION_MSG')
					);
					helpPopup.getPopup().setWidth(275);
					const page = this.getNavigator().getPageByType(item.dataset.type);
					item.addEventListener('click', (event) => {
						if (page?.getPermission()?.canRead())
						{
							this.show(item.dataset.type);
						}
						else
						{
							helpPopup.show();
						}
					});
				});
		}
	}

	registerPage(page: BaseSettingsPage): BaseSettingsPage
	{
		page.setParentElement(this);
		page.setPermission(new Permission(this.#pagesPermission[page.getType()] ?? null))
		page.subscribe('change', this.#onEventChangeData.bind(this))
			.subscribe('fetch', this.#onEventFetchPage.bind(this))
		;
		page.setAnalytic(this.#analytic);

		return page;
	}

	getCurrentPage(): ?BaseSettingsPage
	{
		return this.getNavigator().getCurrentPage();
	}

	getNavigator(): Navigation
	{
		return this.#navigator
	}

	show(type: string, option?: string): void
	{
		if (!Type.isDomNode(this.#contentNode))
		{
			console.log('Not found settings container');
			return;
		}
		if (!this.#permission.canRead())
		{
			return;
		}
		const nextPage = this.getNavigator().getPageByType(type);

		if (this.getCurrentPage() === nextPage)
		{
			return;
		}

		this.getNavigator().changePage(nextPage);
		Dom.hide(this.getNavigator().getPrevPage()?.getPage());
		if (Type.isNil(this.getNavigator().getCurrentPage().getPage().parentNode))
		{
			Dom.append(this.getNavigator().getCurrentPage().getPage(), this.#contentNode);
		}
		else
		{
			Dom.show(this.getNavigator().getCurrentPage().getPage());
		}
		this.activateMenuItem(type);

		this.#analytic.addEventChangePage(type);
		this.getNavigator().updateAddressBar();
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			'BX.Intranet.Settings:onAfterShowPage', {
				source: this,
				page: nextPage,
			},
		);

		if (Type.isString(option) && option !== '')
		{
			EventEmitter.subscribeOnce(
				EventEmitter.GLOBAL_TARGET,
				'BX.Intranet.Settings:onPageComplete',
				() => {
					console.log(option);
					this.getNavigator().moveTo(nextPage, option);
				}
			);
		}
	}

	activateMenuItem(type: string)
	{
		const menuItem = BX.UI.DropdownMenuItem.getItemByNode(
			this.#menuNode.querySelector(`a.ui-sidepanel-menu-link[data-type="${type}"]`)
		);
		menuItem && menuItem.setActiveHandler();
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

		if (
			this.#basePage.includes('/configs/')
			|| this.#extraSettings.reloadAfterClose === true
		)
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
		if (!this.#permission.canEdit())
		{
			return;
		}
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
		this.#extraSettings.reloadAfterClose = true;

		this.isChanged = false;
		this.#hideWaitIcon();
		BX.UI.ButtonPanel.hide();

		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			'BX.Intranet.Settings:onSuccessSave',
			this.#extraSettings,
		);
	}

	#failSaveHandler(response: Object)
	{
		let errorCollection = this.#prepareErrorCollection(response.errors);
		this.#hideWaitIcon();

		EventEmitter.emit('BX.UI.FormElement.Field:onFailedSave', {
			errors: errorCollection,
		});

		let pageType = this.#selectPageForError(errorCollection);
		this.show(pageType);
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
}

export {
	ToolsPage,
	EmployeePage,
	PortalPage,
	MainpagePage,
	CommunicationPage,
	RequisitePage,
	ConfigurationPage,
	SchedulePage,
	GdprPage,
	SecurityPage,
	Renderer,
	Searcher,
	ServerDataSource,
	Permission,
};