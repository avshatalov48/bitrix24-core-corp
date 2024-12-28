/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup) {
	'use strict';

	let instance = null;
	class ListViewTypes {}
	ListViewTypes.KANBAN = 'KANBAN';
	ListViewTypes.LIST = 'LIST';
	/**
	 * @memberOf BX.Crm
	 */
	class Router {
	  constructor() {
	    this.defaultRootUrlTemplates = {};
	    this.customRootUrlTemplates = {};
	    this.currentViews = {};
	  }
	  static get Instance() {
	    if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.Router')) {
	      return window.top.BX.Crm.Router.Instance;
	    }
	    if (instance === null) {
	      instance = new Router();
	    }
	    return instance;
	  }

	  /**
	   * @public
	   * @param params
	   * @return {BX.Crm.Router}
	   */
	  setUrlTemplates(params) {
	    if (main_core.Type.isPlainObject(params.defaultRootUrlTemplates)) {
	      this.defaultRootUrlTemplates = params.defaultRootUrlTemplates;
	    }
	    if (main_core.Type.isPlainObject(params.customRootUrlTemplates)) {
	      this.customRootUrlTemplates = params.customRootUrlTemplates;
	    }
	    return this;
	  }
	  setCurrentListView(entityTypeId, view) {
	    this.currentViews[entityTypeId] = view;
	    return this;
	  }
	  getCurrentListView(entityTypeId) {
	    return this.currentViews[entityTypeId] || ListViewTypes.LIST;
	  }
	  static openSlider(url, options = null) {
	    const preparedUrl = String(url);
	    if (!main_core.Type.isStringFilled(preparedUrl)) {
	      return Promise.resolve();
	    }
	    let preparedOptions = main_core.Type.isPlainObject(options) ? options : {};
	    preparedOptions = {
	      cacheable: false,
	      allowChangeHistory: true,
	      events: {},
	      ...preparedOptions
	    };
	    return new Promise(resolve => {
	      preparedOptions.events.onClose = event => resolve(event.getSlider());
	      BX.SidePanel.Instance.open(preparedUrl, preparedOptions);
	    });
	  }
	  openTypeDetail(typeId, options, queryParams) {
	    const preparedOptions = main_core.Type.isPlainObject(options) ? options : {};
	    preparedOptions.width = 876;
	    preparedOptions.allowChangeHistory = false;
	    preparedOptions.cacheable = false;
	    const uri = this.getTypeDetailUrl(typeId);
	    if (uri) {
	      if (main_core.Type.isPlainObject(queryParams)) {
	        uri.setQueryParams(queryParams);
	      }
	      return Router.openSlider(uri.toString(), preparedOptions);
	    }
	    return null;
	  }
	  openAutomatedSolutionDetail(automatedSolutionId = 0, options = {}) {
	    const preparedOptions = main_core.Type.isPlainObject(options) ? options : {};
	    preparedOptions.width = 876;
	    preparedOptions.allowChangeHistory = false;
	    preparedOptions.cacheable = false;
	    const uri = this.getAutomatedSolutionDetailUrl(automatedSolutionId);
	    if (uri) {
	      return Router.openSlider(uri, preparedOptions);
	    }
	    return null;
	  }

	  /**
	   * @protected
	   * @param component
	   * @param entityTypeId
	   * @return {string|null}
	   */
	  getTemplate(component, entityTypeId = 0) {
	    var _this$defaultRootUrlT;
	    if (entityTypeId > 0 && Object.hasOwn(this.customRootUrlTemplates, entityTypeId)) {
	      var _this$customRootUrlTe;
	      return (_this$customRootUrlTe = this.customRootUrlTemplates[entityTypeId][component]) != null ? _this$customRootUrlTe : null;
	    }
	    return (_this$defaultRootUrlT = this.defaultRootUrlTemplates[component]) != null ? _this$defaultRootUrlT : null;
	  }
	  getTypeDetailUrl(entityTypeId = 0) {
	    const template = this.getTemplate('bitrix:crm.type.detail', entityTypeId);
	    if (template) {
	      return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId));
	    }
	    return null;
	  }
	  getTypeListUrl() {
	    const template = this.getTemplate('bitrix:crm.type.list');
	    if (template) {
	      return new main_core.Uri(template);
	    }
	    return null;
	  }
	  openTypeHelpPage() {
	    Router.openHelper(null, 13315798);
	  }
	  static openHelper(event = null, code = null) {
	    if (event && main_core.Type.isFunction(event.preventDefault)) {
	      event.preventDefault();
	    }
	    if (top.BX.Helper && code > 0) {
	      top.BX.Helper.show(`redirect=detail&code=${code}`);
	    }
	  }
	  showFeatureSlider(event, item, sliderCode = 'limit_smart_process_automation') {
	    Router.Instance.closeSettingsMenu(event, item);
	    if (main_core.Reflection.getClass('BX.UI.InfoHelper.show')) {
	      BX.UI.InfoHelper.show(sliderCode);
	    }
	  }

	  /**
	   * For dynamic entities only.
	   * Does not support knowledge about whether kanban available or not.
	   *
	   * @param entityTypeId
	   * @param categoryId
	   */
	  getItemListUrlInCurrentView(entityTypeId, categoryId = 0) {
	    const currentListView = this.getCurrentListView(entityTypeId);
	    let template = null;
	    if (currentListView === ListViewTypes.KANBAN) {
	      template = this.getTemplate('bitrix:crm.kanban', entityTypeId);
	    } else {
	      template = this.getTemplate('bitrix:crm.item.list', entityTypeId);
	    }
	    if (template) {
	      return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	    }
	    return null;
	  }

	  /**
	   * For factory based entities only.
	   * Does not support knowledge about whether kanban available or not.
	   *
	   * @public
	   * @param entityTypeId
	   * @param categoryId
	   * @return {null|BX.Uri}
	   */
	  getKanbanUrl(entityTypeId, categoryId = 0) {
	    const template = this.getTemplate('bitrix:crm.item.kanban', entityTypeId);
	    if (template) {
	      return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	    }
	    return null;
	  }

	  /**
	   * For factory based entities only
	   *
	   * @public
	   * @param entityTypeId
	   * @param categoryId
	   * @return {null|BX.Uri}
	   */
	  getItemListUrl(entityTypeId, categoryId = 0) {
	    const template = this.getTemplate('bitrix:crm.item.list', entityTypeId);
	    if (template) {
	      return new main_core.Uri(template.replace('#entityTypeId#', entityTypeId).replace('#categoryId#', categoryId));
	    }
	    return null;
	  }
	  openDocumentSlider(documentId) {
	    return Router.openSlider(`/bitrix/components/bitrix/crm.document.view/slider.php?documentId=${documentId}`, {
	      width: 1060,
	      loader: '/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg'
	    });
	  }
	  openSignDocumentSlider(documentId, memberHash) {
	    // todo make a url template
	    return Router.openSlider(`/bitrix/components/bitrix/crm.signdocument.view/slider.php?documentId=${documentId}&memberHash=${memberHash}`, {
	      width: 1060
	    });
	  }
	  openSignDocumentModifySlider(documentId) {
	    return Router.openSlider(`/sign/doc/0/?docId=${documentId}&stepId=changePartner&noRedirect=Y`, {
	      width: 1250
	    });
	  }
	  openCalendarEventSlider(eventId, isSharing) {
	    const sliderId = `crm-calendar-slider-${eventId}-${Math.floor(Math.random() * 1000)}`;
	    return new (window.top.BX || window.BX).Calendar.SliderLoader(eventId, {
	      sliderId,
	      isSharing
	    }).show();
	  }
	  closeSettingsMenu(event, item) {
	    if (item && main_core.Type.isFunction(item.getMenuWindow)) {
	      const window = item.getMenuWindow();
	      if (window) {
	        window.close();
	        return;
	      }
	    }
	    // eslint-disable-next-line unicorn/no-this-assignment
	    const menu = this;
	    if (menu && main_core.Type.isFunction(menu.close)) {
	      menu.close();
	    }
	  }
	  closeToolbarSettingsMenuRecursively(event, menuItem) {
	    let menuWindow = menuItem == null ? void 0 : menuItem.getMenuWindow();
	    if (!menuWindow) {
	      return;
	    }
	    while (menuWindow) {
	      menuWindow.close();
	      menuWindow = menuWindow.getParentMenuWindow();
	    }
	  }
	  closeSliderOrRedirect(redirectTo, currentWindow = null) {
	    var _BX$SidePanel, _BX$SidePanel$Instanc;
	    const slider = (_BX$SidePanel = BX.SidePanel) == null ? void 0 : (_BX$SidePanel$Instanc = _BX$SidePanel.Instance) == null ? void 0 : _BX$SidePanel$Instanc.getSliderByWindow(currentWindow != null ? currentWindow : window);
	    if (slider) {
	      slider.close();
	      return;
	    }
	    if (redirectTo instanceof main_core.Uri) {
	      window.location.href = redirectTo.toString();
	    } else {
	      window.location.href = redirectTo;
	    }
	  }
	  getAutomatedSolutionListUrl() {
	    return new main_core.Uri('/automation/type/automated_solution/list/');
	  }
	  getAutomatedSolutionDetailUrl(id) {
	    let normalizedId = main_core.Text.toInteger(id);
	    normalizedId = normalizedId > 0 ? normalizedId : 0;
	    return new main_core.Uri(`/automation/type/automated_solution/details/${normalizedId}/`);
	  }
	}

	exports.Router = Router;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Main));
//# sourceMappingURL=router.bundle.js.map
