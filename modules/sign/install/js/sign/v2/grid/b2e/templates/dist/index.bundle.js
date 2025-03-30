/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
this.BX.Sign.V2.Grid = this.BX.Sign.V2.Grid || {};
(function (exports,main_core,sign_v2_analytics,sign_v2_api,ui_dialogs_messagebox,ui_switcher) {
	'use strict';

	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _changeVisibility = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeVisibility");
	var _sendActionStateAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendActionStateAnalytics");
	var _downloadStringLikeFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("downloadStringLikeFile");
	class Templates {
	  constructor() {
	    Object.defineProperty(this, _downloadStringLikeFile, {
	      value: _downloadStringLikeFile2
	    });
	    Object.defineProperty(this, _sendActionStateAnalytics, {
	      value: _sendActionStateAnalytics2
	    });
	    Object.defineProperty(this, _changeVisibility, {
	      value: _changeVisibility2
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: new sign_v2_analytics.Analytics()
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	  }
	  async deleteTemplate(templateId) {
	    const messageContent = document.createElement('div');
	    messageContent.innerHTML = main_core.Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_MESSAGE');
	    main_core.Dom.style(messageContent, 'margin-top', '5%');
	    main_core.Dom.style(messageContent, 'color', '#535c69');
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_TITLE'),
	      message: messageContent.outerHTML,
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_YES'),
	        color: BX.UI.Button.Color.PRIMARY,
	        onclick: async button => {
	          try {
	            const api = babelHelpers.classPrivateFieldLooseBase(this, _api)[_api];
	            await api.deleteTemplate(templateId);
	            window.top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_SUCCESS')
	            });
	          } catch {
	            window.top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_FAIL')
	            });
	          }
	          await this.reload();
	          button.getContext().close();
	        }
	      }), new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_NO'),
	        color: BX.UI.Button.Color.LINK,
	        onclick: button => {
	          button.getContext().close();
	        }
	      })]
	    });
	  }
	  async renderSwitcher(templateId, isChecked, isDisabled, hasEditTemplateAccess) {
	    const switcherNode = document.getElementById(`switcher_b2e_template_grid_${templateId}`);
	    const switcher = new ui_switcher.Switcher({
	      node: switcherNode,
	      checked: isChecked,
	      size: ui_switcher.SwitcherSize.medium,
	      disabled: isDisabled,
	      handlers: {
	        toggled: async () => {
	          switcher.setLoading(true);
	          const checked = switcher.isChecked();
	          const visibility = checked ? 'visible' : 'invisible';
	          try {
	            await babelHelpers.classPrivateFieldLooseBase(this, _changeVisibility)[_changeVisibility](templateId, visibility);
	          } catch {
	            switcher.setLoading(false);
	            switcher.check(!checked, false);
	          } finally {
	            babelHelpers.classPrivateFieldLooseBase(this, _sendActionStateAnalytics)[_sendActionStateAnalytics](checked, templateId);
	            switcher.setLoading(false);
	          }
	        }
	      }
	    });
	    if (!isDisabled) {
	      return;
	    }
	    const title = hasEditTemplateAccess ? main_core.Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT') : main_core.Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT_NO_ACCESS');
	    switcherNode.setAttribute('title', title);
	  }
	  reload() {
	    main_core.Event.ready(() => {
	      var _BX$Main$gridManager$;
	      const grid = (_BX$Main$gridManager$ = BX.Main.gridManager.getById('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_GRID')) == null ? void 0 : _BX$Main$gridManager$.instance;
	      if (main_core.Type.isObject(grid)) {
	        grid.reload();
	      }
	    });
	  }
	  reloadAfterSliderClose(addNewTemplateLink) {
	    BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', async event => {
	      const baseUrl = '/sign/b2e/doc/0/';
	      const closedSliderUrl = event.getData()[0].getSlider().getUrl();
	      const uri = new main_core.Uri(closedSliderUrl);
	      const path = uri.getPath();
	      if (closedSliderUrl === addNewTemplateLink || path.startsWith(baseUrl) || closedSliderUrl === 'sign-settings-template-created') {
	        await this.reload();
	      }
	    });
	  }
	  async exportBlank(templateId) {
	    try {
	      const {
	        json,
	        filename
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].template.exportBlank(templateId);
	      const mimeType = 'application/json';
	      babelHelpers.classPrivateFieldLooseBase(this, _downloadStringLikeFile)[_downloadStringLikeFile](json, filename, mimeType);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_SUCCESS')
	      });
	    } catch (e) {
	      console.error(e);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_EXPORT_BLANK_FAILURE')
	      });
	    }
	  }
	  async copyTemplate(templateId) {
	    try {
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].copyTemplate(templateId);
	      await this.reload();
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_SUCCESS')
	      });
	    } catch (error) {
	      console.error('Error copying template:', error);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_FAIL')
	      });
	    }
	  }
	}
	function _changeVisibility2(templateId, visibility) {
	  const api = babelHelpers.classPrivateFieldLooseBase(this, _api)[_api];
	  return api.changeTemplateVisibility(templateId, visibility);
	}
	function _sendActionStateAnalytics2(checked, templateId) {
	  babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	    category: 'templates',
	    event: 'turn_on_off_template',
	    type: 'manual',
	    c_section: 'sign',
	    c_sub_section: 'templates',
	    c_element: checked ? 'on' : 'off',
	    p5: `templateid_${templateId}`
	  });
	}
	function _downloadStringLikeFile2(data, filename, mimeType) {
	  const blob = new Blob([data], {
	    type: mimeType
	  });
	  const url = window.URL.createObjectURL(blob);
	  const a = document.createElement('a');
	  main_core.Dom.style(a, 'display', 'none');
	  a.href = url;
	  a.download = filename;
	  main_core.Dom.append(a, document.body);
	  a.click();
	  window.URL.revokeObjectURL(url);
	  main_core.Dom.remove(a);
	}

	exports.Templates = Templates;

}((this.BX.Sign.V2.Grid.B2e = this.BX.Sign.V2.Grid.B2e || {}),BX,BX.Sign.V2,BX.Sign.V2,BX.UI.Dialogs,BX.UI));
//# sourceMappingURL=index.bundle.js.map
