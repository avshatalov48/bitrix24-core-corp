import {rest as Rest} from 'rest.client';
import {Type, Uri, ajax as Ajax, Event} from 'main.core';
import {BaseButton} from 'ui.buttons';

import 'clipboard';
import 'loadext';
import 'popup';
import 'sidepanel';

import './manager.css';

export class Manager
{
	static sessionId = null;
	static connectedSiteId = null;
	static addUrlPopup = null;
	static addUrlResolve = null;
	static popupNode = null;
	static siteTemplateCode = null;
	static isSitePublished = null;
	static isSiteExists = null;
	static isOrderPublicUrlAvailable = null;
	static isPullInited = false;
	static connectPath = null;
	static fieldsMap = null;

	static init(options)
	{
		options.connectedSiteId = parseInt(options.connectedSiteId);
		if(options.connectedSiteId > 0)
		{
			Manager.connectedSiteId = options.connectedSiteId;
		}
		options.sessionId = parseInt(options.sessionId);
		if(options.sessionId > 0)
		{
			Manager.sessionId = options.sessionId;
		}
		if(Type.isString(options.siteTemplateCode))
		{
			Manager.siteTemplateCode = options.siteTemplateCode;
		}
		if(Type.isString(options.connectPath))
		{
			Manager.connectPath = options.connectPath;
		}
		if(Type.isBoolean(options.isSitePublished))
		{
			Manager.isSitePublished = options.isSitePublished;
		}
		if(Type.isBoolean(options.isSiteExists))
		{
			Manager.isSiteExists = options.isSiteExists;
		}
		if(Type.isBoolean(options.isOrderPublicUrlAvailable))
		{
			Manager.isOrderPublicUrlAvailable = options.isOrderPublicUrlAvailable;
		}
		else
		{
			Manager.isOrderPublicUrlAvailable = false;
		}

		if(!Manager.isPullInited)
		{
			Event.ready(Manager.initPull);
		}

		// the crutch to load landings dynamically
		if(!top.BX.Landing)
		{
			top.BX.Landing = {
				PageObject: {},
				Main: {},
			}
		}
	}

	static loadConfig()
	{
		return new Promise((resolve, reject) =>
		{
			Rest.callMethod('salescenter.manager.getConfig').then((result) =>
			{
				Manager.init(result.answer.result);
				resolve(result.answer.result);
			}).catch((reason) =>
			{
				reject(reason);
			});
		});
	}

	/**
	 * Shows slider with module description and connect button.
	 *
	 * @returns {Promise<any>}
	 */
	static startConnection(params = {})
	{
		return new Promise((resolve, reject) =>
		{
			if(!Manager.connectPath)
			{
				reject('no connect path');
			}

			let url = new Uri(Manager.connectPath);
			if(!Type.isPlainObject(params))
			{
				params = {};
			}
			params.analyticsLabel = 'salescenterStartConnection';
			url.setQueryParams(params);
			Manager.openSlider(url.toString(), {width: 760}).then(() =>
			{
				resolve();
			}).catch((reason) =>
			{
				reject(reason);
			});
		});
	}

	/**
	 * Shows slider with landing template.
	 *
	 * @param {Object} params
	 * @returns {Promise<any>}
	 */
	static connect(params)
	{
		return new Promise((resolve) =>
		{
			if(Manager.connectedSiteId > 0 && Manager.isSiteExists)
			{
				resolve();
			}
			else
			{
				let url = new Uri('/shop/stores/site/edit/0/');
				if(!Type.isPlainObject(params))
				{
					params = {};
				}
				params.analyticsLabel = 'salescenterConnect';
				if(Manager.siteTemplateCode)
				{
					params.tpl = Manager.siteTemplateCode;
				}
				url.setQueryParams(params);
				Manager.openSlider(url.toString()).then(() =>
				{
					resolve();
				});
			}
		});
	}

	static publicConnectedSite()
	{
		return new Promise((resolve, reject) =>
		{
			if(Manager.connectedSiteId > 0 && !Manager.isSitePublished)
			{
				Rest.callMethod('landing.site.publication', {id: Manager.connectedSiteId}).then((result) =>
				{
					Manager.isSitePublished = true;
					Manager.firePublicConnectedSiteEvent();
					resolve(result);
				}).catch((reason) =>
				{
					reject(reason);
				});
			}
			else
			{
				resolve();
			}
		});
	}

	static firePublicConnectedSiteEvent()
	{
		top.BX.onCustomEvent('Salescenter.Manager:onPublicConnectedSite', {
			isSitePublished: true
		});
	}

	/**
	 * @returns BX.PopupWindow
	 */
	static getPopup({id = '', title = '', text = '', buttons = []})
	{
		let popup = BX.PopupWindowManager.getPopupById(id);

		const content = `<div class="salescenter-popup">
			<div class="salescenter-popup-title">${title}</div>
			<div class="salescenter-popup-text">${text}</div>
		</div>`;
		if(popup)
		{
			popup.setContent(content);
			popup.setButtons(buttons);
		}
		else
		{
			popup = new BX.PopupWindow(id, null, {
				zIndex: 200,
				className: "salescenter-connect-popup",
				autoHide: true,
				closeByEsc: true,
				padding: 0,
				closeIcon: true,
				content : content,
				width: 400,
				overlay: true,
				lightShadow: false,
				buttons: buttons,
			});
		}

		return popup;
	}

	static showAfterConnectPopup()
	{
		const popup = Manager.getPopup({
			id: 'salescenter-connect-popup',
			title: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_TITLE'),
			text: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_DESCRIPTION'),
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_GO_BUTTON'),
					className : "ui-btn ui-btn-md ui-btn-primary",
					events : {
						click : () =>
						{
							Manager.openConnectedSite();
							popup.close();
						}
					}
				})
			],
		});
		popup.show();
	}

	static copyUrl(url, event)
	{
		BX.clipboard.copy(url);
		if(event && event.target)
		{
			Manager.showCopyLinkPopup(event.target);
		}
	}

	static showCopyLinkPopup = function(node) {
		if(Manager.popupOuterLink)
		{
			Manager.popupOuterLink.destroy();
			Manager.popupOuterLink = null;
			if(Manager.hideCopyLinkTimeout > 0)
			{
				clearTimeout(Manager.hideCopyLinkTimeout);
				Manager.hideCopyLinkTimeout = 0;
			}
			if(Manager.destroyCopyLinkTimeout > 0)
			{
				clearTimeout(Manager.destroyCopyLinkTimeout);
				Manager.destroyCopyLinkTimeout = 0;
			}
		}

		Manager.popupOuterLink = new BX.PopupWindow('salescenter-popup-copy-link', node, {
			className: 'salescenter-popup-copy-link',
			darkMode: true,
			content: BX.message('SALESCENTER_MANAGER_COPY_URL_SUCCESS'),
			zIndex: 5000,
		});

		Manager.popupOuterLink.show();

		Manager.hideCopyLinkTimeout = setTimeout(() =>
		{
			BX.hide(BX(Manager.popupOuterLink.uniquePopupId));
			Manager.hideCopyLinkTimeout = 0;
		}, 2000);

		Manager.destroyCopyLinkTimeout = setTimeout(() =>
		{
			Manager.popupOuterLink.destroy();
			Manager.popupOuterLink = null;
			Manager.destroyCopyLinkTimeout = 0;
		}, 2200)
	};

	static addCustomPage(page)
	{
		return new Promise((resolve) =>
		{
			Manager.getAddUrlPopup().then((popup) => {
				Manager.templateEngine.$emit('onAddUrlPopupCreate', page);
				popup.show();
			});
			Manager.addUrlResolve = resolve;
			Manager.addingCustomPage = page;
		});
	}

	static resolveAddPopup(pageId, isSaved)
	{
		if(Manager.addUrlResolve && Type.isFunction(Manager.addUrlResolve))
		{
			Manager.addUrlResolve(pageId);
			Manager.addUrlResolve = null;
		}
		if(isSaved && pageId > 0)
		{
			if(Manager.addingCustomPage && Manager.addingCustomPage.id && parseInt(Manager.addingCustomPage.id) === parseInt(pageId))
			{
				Manager.showNotification(BX.message('SALESCENTER_MANAGER_UPDATE_URL_SUCCESS'));
			}
			else
			{
				Manager.showNotification(BX.message('SALESCENTER_MANAGER_ADD_URL_SUCCESS'));
			}
		}
	}

	static initPopupTemplate()
	{
		return new Promise(resolve =>
		{
			BX.loadExt('salescenter.url_popup').then(() =>
			{
				Manager.templateEngine = BX.Vue.create({
					el: document.createElement('div'),
					template: '<bx-salescenter-url-popup/>',
					mounted()
					{
						Manager.popupNode = this.$el;
						this.$app = Manager;
						resolve();
					},
				});
			});
		});
	}

	static handleAddUrlPopupAutoHide(event)
	{
		if(!Manager.addUrlPopup)
		{
			return true;
		}
		if(event.target !== Manager.addUrlPopup.getPopupContainer() && !Manager.addUrlPopup.getPopupContainer().contains(event.target))
		{
			let urlFieldsPopupWindow = null;
			const urlFieldsPopupMenu = BX.PopupMenu.getMenuById('salescenter-url-fields-popup');
			if(urlFieldsPopupMenu)
			{
				urlFieldsPopupWindow = urlFieldsPopupMenu.popupWindow;
			}
			if(!urlFieldsPopupWindow)
			{
				return true;
			}
			else
			{
				if(event.target.dataset['rootMenu'] === 'salescenter-url-fields-popup' || event.target.parentNode.dataset['rootMenu'] === 'salescenter-url-fields-popup')
				{
					if(!event.target.classList.contains('menu-popup-item-submenu') && !event.target.parentNode.classList.contains('menu-popup-item-submenu'))
					{
						urlFieldsPopupWindow.close();
					}
					return false;
				}
				else
				{
					return true;
				}
			}
		}

		return false;
	}

	static getAddUrlPopup()
	{
		return new Promise((resolve) =>
		{
			if(!Manager.addUrlPopup)
			{
				Manager.initPopupTemplate().then(() =>
				{
					Manager.addUrlPopup = new BX.PopupWindow({
						id: 'salescenter-app-add-url',
						zIndex: 200,
						autoHide: true,
						closeByEsc: true,
						closeIcon: true,
						padding: 0,
						contentPadding: 0,
						content : Manager.popupNode,
						titleBar: BX.message('SALESCENTER_ACTION_ADD_CUSTOM_TITLE'),
						contentColor: 'white',
						width: 600,
						autoHideHandler: Manager.handleAddUrlPopupAutoHide,
						events : {
							onPopupClose: () =>
							{
								let newPageId = document.getElementById('salescenter-app-add-custom-url-id');
								const isSaved = document.getElementById('salescenter-app-add-custom-url-is-saved').value === 'y';
								if(newPageId)
								{
									newPageId = newPageId.value;
								}
								else
								{
									newPageId = false;
								}
								Manager.resolveAddPopup(newPageId, isSaved);
							},
							onPopupDestroy : () =>
							{
								Manager.addUrlPopup = null;
							}
						},
					});

					resolve(Manager.addUrlPopup);
				});
			}
			else
			{
				resolve(Manager.addUrlPopup);
			}
		});
	}

	static addPage(fields)
	{
		return new Promise((resolve, reject) =>
		{
			let method, analyticsLabel;
			if(fields.analyticsLabel)
			{
				analyticsLabel = fields.analyticsLabel;
				delete(fields.analyticsLabel);
			}
			if(fields.id > 0)
			{
				method = Rest.callMethod('salescenter.page.update', {
					id: fields.id,
					fields: fields,
				});
				if(!analyticsLabel)
				{
					analyticsLabel = 'salescenterUpdatePage';
				}
			}
			else
			{
				method = Rest.callMethod('salescenter.page.add', {
					fields: fields,
				});
				if(!analyticsLabel)
				{
					analyticsLabel = 'salescenterAddPage';
				}
			}
			method.then((result) =>
			{
				if(result.answer.result.page)
				{
					let page = result.answer.result.page;
					let source = 'other';
					if(page.landingId > 0)
					{
						if(parseInt(page.siteId) === parseInt(Manager.connectedSiteId))
						{
							source = 'landing_store_chat';
						}
						else
						{
							source = 'landing_other';
						}
					}
					Manager.addAnalyticAction({
						analyticsLabel: analyticsLabel,
						source: source,
						type: page.isWebform ? 'forms' : 'info',
						code: page.code,
					}).then(() =>
					{
						resolve(result);
					});
				}
				else
				{
					resolve(result);
				}
			}).catch((reason) =>
			{
				reject(reason);
			})
		});
	}

	static checkUrl(url)
	{
		return Rest.callMethod('salescenter.page.geturldata', {
			url: url
		});
	}

	static addSitePage(isWebform = false)
	{
		return new Promise((resolve) =>
		{
			const siteId = Manager.connectedSiteId;
			if (siteId > 0)
			{
				BX.loadExt('landing.master').then(() =>
				{
					BX.Landing.Env.getInstance().setOptions({site_id: siteId});
					BX.Landing.UI.Panel.URLList
						.getInstance()
						.show('landing', {siteId: siteId})
						.then((result) =>
						{
							Manager.addPage({
								hidden: false,
								landingId: result.id,
								isWebform: isWebform,
							}).then((result) =>
							{
								resolve(result);
								Manager.showNotification(BX.message('SALESCENTER_MANAGER_ADD_URL_SUCCESS'));
							});
						});
				});
			}
			else
			{
				Manager.openSlider('/bitrix/components/bitrix/salescenter.connect/slider.php').then(() =>
				{
					resolve();
				});
			}
		});
	}

	static showNotification(message)
	{
		if(!message)
		{
			return;
		}
		BX.loadExt('ui.notification').then(() =>
		{
			BX.UI.Notification.Center.notify({
				content: message
			});
		});
	}

	static hidePage(page)
	{
		const method = 'salescenter.page.hide';
		let source = 'other';
		if(page.landingId > 0)
		{
			if(parseInt(page.siteId) === parseInt(Manager.connectedSiteId))
			{
				source = 'landing_store_chat';
			}
			else
			{
				source = 'landing_other';
			}
		}
		const data = {
			id: page.id,
			fields: {
				hidden: true,
			},
			analyticsLabel: 'salescenterDeletePage',
			source: source,
			type: page.isWebform ? 'form' : 'info',
			code: page.code,
		};

		return new Promise((resolve, reject) =>
		{
			Rest.callMethod(method, data).then((result) =>
			{
				resolve(result);
				Manager.showNotification(BX.message('SALESCENTER_MANAGER_HIDE_URL_SUCCESS'));
			}).catch((result) =>
			{
				reject(result);
			});
		});
	}

	static deleteUrl(page)
	{
		const method = 'salescenter.page.delete';
		const data = {
			id: page.id,
			analyticsLabel: 'salescenterDeletePage',
			source: 'other',
			type: page.isWebform ? 'form' : 'info',
		};

		return new Promise((resolve, reject) =>
		{
			Rest.callMethod(method, data).then((result) =>
			{
				resolve(result);
				Manager.showNotification(BX.message('SALESCENTER_MANAGER_DELETE_URL_SUCCESS'));
			}).catch((result) =>
			{
				reject(result);
			});
		});
	}

	static editLandingPage(pageId, siteId = Manager.connectedSiteId)
	{
		window.open(`/shop/stores/site/${siteId}/view/${pageId}/`, '_blank');
	}

	static openSlider(url, options)
	{
		if(!Type.isPlainObject(options))
		{
			options = {};
		}
		options = {...{cacheable: false, allowChangeHistory: false, events: {}}, ...options};
		return new Promise((resolve) =>
		{
			if(Type.isString(url) && url.length > 1)
			{
				options.events.onClose = function(event)
				{
					resolve(event.getSlider());
				};
				BX.SidePanel.Instance.open(url, options);
			}
			else
			{
				resolve();
			}
		});
	}

	static getOrdersListUrl(params)
	{
		if(!Type.isPlainObject(params))
		{
			params = {};
		}
		if(Manager.sessionId > 0)
		{
			params['sessionId'] = Manager.sessionId;
		}
		return (new Uri('/saleshub/orders/')).setQueryParams(params).toString();
	}

	static getPaymentsListUrl(params)
	{
		if(!Type.isPlainObject(params))
		{
			params = {};
		}
		if(Manager.sessionId > 0)
		{
			params['sessionId'] = Manager.sessionId;
		}
		return (new Uri('/saleshub/payments/')).setQueryParams(params).toString();
	}

	static showOrdersList(params)
	{
		return Manager.openSlider(Manager.getOrdersListUrl(params));
	}

	static showPaymentsList(params)
	{
		return Manager.openSlider(Manager.getPaymentsListUrl(params));
	}

	static getOrderAddUrl(params)
	{
		if(!Type.isPlainObject(params))
		{
			params = {};
		}
		if(Manager.sessionId > 0)
		{
			params['sessionId'] = Manager.sessionId;
		}
		return (new Uri('/saleshub/orders/order/')).setQueryParams(params).toString();
	}

	static showOrderAdd(params)
	{
		return Manager.openSlider(Manager.getOrderAddUrl(params));
	}

	static showOrdersListAfterCreate(orderId)
	{
		let ordersListUrl = Manager.getOrdersListUrl({orderId: orderId});
		let listSlider = BX.SidePanel.Instance.getSlider(ordersListUrl);
		if(!listSlider)
		{
			ordersListUrl = Manager.getOrdersListUrl({orderId: orderId});
			listSlider = BX.SidePanel.Instance.getSlider(ordersListUrl);
		}
		let orderAddUrl = Manager.getOrderAddUrl();
		let addSlider = BX.SidePanel.Instance.getSlider(orderAddUrl);
		if(addSlider)
		{
			addSlider.destroy();
		}
		if(!listSlider)
		{
			Manager.showOrdersList({orderId: orderId});
		}
		else
		{
			top.BX.onCustomEvent(listSlider.getFrameWindow(), 'salescenter-order-create', [
				{
					orderId: orderId
				}
			]);
		}
	}

	static initPull()
	{
		if(BX.PULL)
		{
			Manager.isPullInited = true;
			BX.PULL.subscribe({
				moduleId: 'salescenter',
				command: 'SETCONNECTEDSITE',
				callback: (params) =>
				{
					Manager.init(params);
				},
			});
		}
	}

	static openControlPanel()
	{
		window.open('/saleshub/', '_blank');
	}

	static getFormAddUrl(formId = 0)
	{
		return (new Uri(`/crm/webform/edit/${parseInt(formId)}/`)).setQueryParams({ACTIVE: 'Y', RELOAD_LIST: 'N'}).toString();
	}

	static addNewForm()
	{
		return new Promise((resolve, reject) =>
		{
			Manager.openSlider(Manager.getFormAddUrl()).then((slider) =>
			{
				const formId = slider.getData().get('formId');
				if(formId > 0)
				{
					Manager.addNewFormPage(formId).then((result) =>
					{
						resolve(result);
					}).catch((reason) =>
					{
						reject(reason);
					});
				}
			});
		});
	}

	static addNewFormPage(formId)
	{
		return new Promise((resolve, reject) =>
		{
			const popupId = 'salescenter-add-new-page-popup';
			Manager.getPopup({
				id: popupId,
				title: BX.message('SALESCENTER_MANAGER_NEW_PAGE_POPUP_TITLE'),
				text: BX.message('SALESCENTER_MANAGER_NEW_PAGE_WAIT'),
			}).show();

			Rest.callMethod('salescenter.page.addformpage', {
				formId: formId,
			}).then((result) =>
			{
				if(result.answer.result.page)
				{
					resolve(result.answer.result.page);
					let landingId = result.answer.result.page.landingId;
					const popup = Manager.getPopup({
						id: popupId,
						title: BX.message('SALESCENTER_MANAGER_NEW_PAGE_POPUP_TITLE'),
						text: BX.message('SALESCENTER_MANAGER_NEW_PAGE_COMPLETE'),
						buttons: [
							new BX.PopupWindowButton({
								text : BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_GO_BUTTON'),
								className : "ui-btn ui-btn-md ui-btn-primary",
								events : {
									click : () =>
									{
										Manager.editLandingPage(landingId);
										popup.close();
									}
								}
							})
						]
					});
					popup.show();
				}
				else
				{
					Manager.getPopup({
						id: popupId,
						title: BX.message('SALESCENTER_MANAGER_ERROR_POPUP'),
					})
					.show();
					reject();
				}
			}).catch((error) =>
			{
				Manager.getPopup({
					id: popupId,
					title: BX.message('SALESCENTER_MANAGER_ERROR_POPUP'),
					text: error,
				})
				.show();
				reject(error);
			});
		});
	}

	static openConnectedSite(isRecycle = false)
	{
		if(Manager.connectedSiteId > 0)
		{
			let url = new Uri(`/shop/stores/site/${Manager.connectedSiteId}/`);
			let params = {
				apply_filter: 'y',
			};
			if(isRecycle)
			{
				params.DELETED = 'Y';
			}
			else
			{
				params.clear_filter = 'y';
			}
			url.setQueryParams(params);
			window.open(url.toString(), '_blank');
		}
	}

	static openHowItWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=9289135', 'chat_connect');
	}

	static openHowCrmStoreWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=13651476', 'crmstore_how_works');
	}

	static openHowCrmFormsWorks(event, url)
	{
		url = url || 'redirect=detail&code=13774372';
		Manager.openHelper(event, url, 'crmforms_how_works');
	}

	static openHowSmsWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=9680407', 'sms_connect');
	}

	static openHowToConfigOpenLines(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=7872935', 'openlines_connect');
	}

	static openHowToConfigDefaultPaySystem(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=10460164', 'pay_system_connect');
	}

	static openHowToConfigPaySystem(event, code)
	{
		Manager.openHelper(event, 'redirect=detail&code=' + code, 'pay_system_connect');
	}

	static openHowToConfigCashboxPaySystem(event, code)
	{
		Manager.openHelper(event, 'redirect=detail&code=' + code, 'pay_system_cashbox_connect');
	}

	static openHowToUseOfflineCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=11271760', 'cashbox_connect');
	}

	static openHowToConfigCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=11120562', 'cashbox_connect');
	}

	static openHowToConfigCheckboxCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=12306679', 'cashbox_connect');
	}

	static openHowToConfigBusinessRuCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=12806492', 'cashbox_connect');
	}

	static openHowToConfigRobokassaCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=12849128', 'cashbox_connect');
	}

	static openHowToConfigYooKassaCashBox(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=17776800', 'cashbox_connect');
	}

	static openHowToSetupCheckboxCashBoxAndKeys(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=12334663', 'cashbox_connect');
	}

	static openHowToSell(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=17615318', 'crmstore_connect');
	}

	static openHowToWork(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=11553526', 'companycontacts_connect');
	}

	static openWhatClientSee(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=11278264', 'client_view');
	}

	static openHowPayDealWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=17615318', 'pay_deal');
	}

	static openHowPaySmartInvoiceWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=17615318', 'pay_smart_invoice');
	}

	static openFormPagesHelp(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=9606749', 'forms');
	}

	static openCommonPagesHelp(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=9604717', 'common_pages');
	}

	static openBitrix24NotificationsHelp(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=17615266', 'bitrix24_notifications');
	}

	static openBitrix24NotificationsWorks(event)
	{
		Manager.openHelper(event, 'redirect=detail&code=13655934', 'bitrix24_notifications_work');
	}

	static openHelper(event = null, url = '', analyticsArticle = '')
	{
		if(event)
		{
			event.preventDefault();
		}
		if(analyticsArticle)
		{
			Manager.addAnalyticAction({
				analyticsLabel: 'salescenterOpenHelp',
				article: analyticsArticle
			}).then(() =>
			{
				if(top.BX.Helper)
				{
					top.BX.Helper.show(url);
				}
			});
		}
		else if(top.BX.Helper)
		{
			top.BX.Helper.show(url);
		}
	}

	static openFeedbackForm(event)
	{
		if(event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}
		return Manager.openSlider('/bitrix/components/bitrix/salescenter.feedback/slider.php', {width: 735});
	}

	static openFeedbackFormParams(event, params, options={})
	{
		if(event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}

		if(!Type.isPlainObject(params))
		{
			params = {};
		}

		let url = (new Uri('/bitrix/components/bitrix/salescenter.feedback/slider.php')).setQueryParams(params).toString();
		return Manager.openSlider(url, options);
	}

	static openFeedbackPayOrderForm(event)
	{
		if(event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}
		return Manager.openSlider('/bitrix/components/bitrix/salescenter.feedback/slider.php?feedback_type=pay_order', {width: 735});
	}

	static openFeedbackDeliveryOfferForm(event)
	{
		if(event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}
		return Manager.openSlider('/bitrix/components/bitrix/salescenter.feedback/slider.php?feedback_type=delivery_offer', {width: 735});
	}

	static openIntegrationRequestForm(event)
	{
		let params = Manager.#getDataSettingFromEventDomNode(event)

		if (event && Type.isFunction(event.preventDefault))
		{
			event.preventDefault();
		}

		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		let url = (new Uri('/bitrix/components/bitrix/salescenter.feedback/slider.php'));

		url.setQueryParams({feedback_type: 'integration_request'});
		url.setQueryParams(params);

		return Manager.openSlider(url.toString(), {width: 735});
	}

	static #parseParamsDataSetting(settings): Object
	{
		const result = {};

		if (Type.isStringFilled(settings))
		{
			let fields = settings.split(',');

			try
			{
				for (let inx in fields)
				{
					if (!fields.hasOwnProperty(inx))
					{
						continue;
					}

					let [name, value] = fields[inx].split(':');

					if (Type.isStringFilled(name))
					{
						result[name] = value;
					}
				}
			}
			catch (e) {}
		}

		return result;
	}

	static #getDataSettingFromEventDomNode(event): ?Object
	{
		let node = null;
		if (Type.isDomNode(event.button))
		{
			node = event.button;
		}
		else if (Type.isDomNode(event.target))
		{
			node = event.target;
		}

		if (Type.isObject(node))
		{
			let dataset = node.dataset ? node.dataset : {};
			let settings = dataset.hasOwnProperty('managerOpenintegrationrequestformParams') ? dataset.managerOpenintegrationrequestformParams : '';

			return this.#parseParamsDataSetting(settings);
		}

		return null;
	}

	static openApplication(params = {})
	{
		let url = new Uri('/saleshub/app/');
		if(Type.isPlainObject(params))
		{
			url.setQueryParams(params);
		}

		let sliderOptions = params.hasOwnProperty('sliderOptions') ? params.sliderOptions : {};
		if (!sliderOptions.hasOwnProperty('width'))
		{
			sliderOptions.width = 1140;
		}

		return new Promise((resolve, reject) =>
		{
			Manager.openSlider(url.toString(), sliderOptions).then((slider) =>
			{
				resolve(slider.getData());
			}).catch((reason) =>
			{

			});
		});
	}

	static addAnalyticAction(params)
	{
		return new Promise((resolve, reject) =>
		{
			if(!Type.isPlainObject(params) || !params.analyticsLabel)
			{
				reject('wrong params');
			}
			params = {...params, ...{action: 'salescenter.manager.addAnalytic', sessid: BX.bitrix_sessid()}};
			let request = new XMLHttpRequest();
			let url = new Uri('/bitrix/services/main/ajax.php');
			url.setQueryParams(params);
			request.open('GET', url.toString());
			request.onload = () =>
			{
				resolve();
			};
			request.onerror = () =>
			{
				reject();
			};
			request.send();
		});
	}

	static getFieldsMap()
	{
		return new Promise((resolve, reject) =>
		{
			if(Manager.fieldsMap !== null)
			{
				resolve(Manager.fieldsMap);
				return;
			}
			Ajax.runAction('salescenter.manager.getFieldsMap', {
				analyticsLabel: 'salescenterFieldsMapLoading',
			}).then((response) =>
			{
				Manager.fieldsMap = response.data.fields;
				resolve(response.data.fields);
			}).catch((response) =>
			{
				reject(response.errors);
			});
		});
	}

	static getPageUrl(pageId, entities, context = null): ?String
	{
		return new Promise((resolve, reject) =>
		{
			if(!Type.isInteger(pageId))
			{
				resolve(null);
			}
			if(!Type.isPlainObject(entities) || entities.length <= 0)
			{
				resolve(null);
			}

			Ajax.runAction('salescenter.manager.getPageUrl', {
				data: {
					pageId: pageId,
					entities: entities
				},
				analyticsLabel: 'salescenterGetPageUrlWithParameters',
				getParameters: {
					context: context
				}
			}).then((response) =>
			{
				resolve(response.data.pageUrl);
			}).catch((response) =>
			{
				reject(response.errors);
			});
		});
	}
}
