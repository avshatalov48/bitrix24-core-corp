/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
this.BX.Sign.V2.Grid = this.BX.Sign.V2.Grid || {};
(function (exports,sign_v2_api,ui_dialogs_messagebox,main_core,ui_buttons,main_popup) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	var _handleSave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSave");
	var _showError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showError");
	class CreateListPopup {
	  constructor() {
	    Object.defineProperty(this, _showError, {
	      value: _showError2
	    });
	    Object.defineProperty(this, _handleSave, {
	      value: _handleSave2
	    });
	  }
	  async show(inputText = null) {
	    return new Promise(resolve => {
	      const inputId = `listNameInput_${Date.now()}`;
	      const input = main_core.Tag.render(_t || (_t = _`
				<input
					type="text"
					id="${0}"
					class="ui-ctl-element"
					placeholder="${0}"
					value="${0}"
				>
			`), inputId, main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_POPUP_INPUT_PLACEHOLDER'), inputText === null ? '' : main_core.Text.encode(inputText));
	      const popup = new main_popup.Popup(`listNamePopup_${inputId}`, null, {
	        draggable: false,
	        overlay: true,
	        width: 500,
	        height: 280,
	        padding: 0,
	        closeByEsc: true,
	        closeIcon: true,
	        className: 'sign-signers-grid-create-list-popup',
	        content: main_core.Tag.render(_t2 || (_t2 = _`
					<div class="sign-create-list-popup-item-container-wrapper">
						<span class="sign-create-list-title-titlebar">${0}</span>
						<div class="sign-create-list-popup-item-container">
							<div class="sign-create-list-title-input-container">
								${0}
							</div>
							<span style="text-align: left; width: 100%">${0}</span>
						</div>
					</div>
				`), main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_POPUP_TITLE'), input, main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_DESCRIPTION')),
	        buttons: [new ui_buttons.CreateButton({
	          text: inputText === null ? main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_CREATE_BUTTON_TEXT') : main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_SAVE_BUTTON_TEXT'),
	          round: true,
	          events: {
	            click: async () => {
	              try {
	                const listName = await babelHelpers.classPrivateFieldLooseBase(this, _handleSave)[_handleSave](input);
	                resolve(listName);
	                popup.close();
	              } catch (error) {
	                babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](error);
	              }
	            }
	          }
	        }), new ui_buttons.CancelButton({
	          text: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_CANCEL_BUTTON_TEXT'),
	          events: {
	            click() {
	              popup.close();
	            }
	          }
	        })],
	        events: {
	          onPopupShow() {
	            main_core.Dom.style(this.popupContainer, 'backgroundColor', 'rgba(255, 255, 255)');
	          },
	          onAfterShow: () => {
	            input.focus();
	            if (inputText !== null && inputText.length > 1) {
	              input.setSelectionRange(input.value.length, input.value.length);
	            }
	            main_core.Event.bind(input, 'keydown', async event => {
	              if (event.key === 'Enter') {
	                try {
	                  const listName = await babelHelpers.classPrivateFieldLooseBase(this, _handleSave)[_handleSave](input);
	                  resolve(listName);
	                  popup.close();
	                } catch (error) {
	                  babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](error);
	                }
	              }
	            });
	          }
	        }
	      });
	      popup.show();
	    });
	  }
	}
	async function _handleSave2(input) {
	  const listName = input ? input.value : '';
	  if (!listName) {
	    throw new Error(main_core.Loc.getMessage('SIGN_SIGNERS_GRID_CREATE_LIST_HINT_TITLE_NOT_EMPTY'));
	  }
	  return listName;
	}
	function _showError2(error) {
	  BX.UI.Notification.Center.notify({
	    content: error.message
	  });
	}

	let _$1 = t => t,
	  _t$1;
	const GRID_SIGNERS_LISTS = 'SIGN_B2E_SIGNERS_LIST_GRID';
	const GRID_SIGNERS = 'SIGN_B2E_SIGNERS_LIST_GRID_EDIT';
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _getGridManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getGridManager");
	class Signers {
	  constructor() {
	    Object.defineProperty(this, _getGridManager, {
	      value: _getGridManager2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	  }
	  reloadSigners() {
	    const gridManager = babelHelpers.classPrivateFieldLooseBase(this, _getGridManager)[_getGridManager]();
	    main_core.Event.ready(() => {
	      var _gridManager$getById;
	      const grid = gridManager == null ? void 0 : (_gridManager$getById = gridManager.getById(GRID_SIGNERS)) == null ? void 0 : _gridManager$getById.instance;
	      if (main_core.Type.isObject(grid)) {
	        grid.reload();
	      }
	    });
	  }
	  reloadLists() {
	    const gridManager = babelHelpers.classPrivateFieldLooseBase(this, _getGridManager)[_getGridManager]();
	    main_core.Event.ready(() => {
	      var _gridManager$getById2;
	      const grid = gridManager == null ? void 0 : (_gridManager$getById2 = gridManager.getById(GRID_SIGNERS_LISTS)) == null ? void 0 : _gridManager$getById2.instance;
	      if (main_core.Type.isObject(grid)) {
	        grid.reload();
	      }
	    });
	  }
	  async deleteList(listId) {
	    const messageContent = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div>
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_SIGNERS_DELETE_CONFIRMATION_MESSAGE'));
	    main_core.Dom.style(messageContent, 'margin-top', '5%');
	    main_core.Dom.style(messageContent, 'color', '#535c69');
	    ui_dialogs_messagebox.MessageBox.show({
	      title: main_core.Loc.getMessage('SIGN_SIGNERS_DELETE_CONFIRMATION_TITLE'),
	      message: messageContent.outerHTML,
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_YES'),
	        color: BX.UI.Button.Color.PRIMARY,
	        onclick: async button => {
	          button.setDisabled(true);
	          button.setState(BX.UI.Button.State.WAITING);
	          try {
	            const api = babelHelpers.classPrivateFieldLooseBase(this, _api)[_api];
	            await api.signersList.deleteSignersList(listId);
	            window.top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_HINT_SUCCESS')
	            });
	          } catch {
	            window.top.BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_HINT_FAIL')
	            });
	          }
	          await this.reloadLists();
	          button.getContext().close();
	        }
	      }), new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_NO'),
	        color: BX.UI.Button.Color.LINK,
	        onclick: button => {
	          button.getContext().close();
	        }
	      })]
	    });
	  }
	  async copyList(listId) {
	    try {
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].signersList.copySignersList(listId);
	      await this.reloadLists();
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_COPY_HINT_SUCCESS')
	      });
	    } catch (error) {
	      console.error('Error copying list:', error);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_COPY_HINT_FAIL')
	      });
	    }
	  }
	  async deleteSelectedSigners(listId) {
	    var _gridManager$getById3;
	    const gridManager = babelHelpers.classPrivateFieldLooseBase(this, _getGridManager)[_getGridManager]();
	    const grid = gridManager == null ? void 0 : (_gridManager$getById3 = gridManager.getById(GRID_SIGNERS)) == null ? void 0 : _gridManager$getById3.instance;
	    if (!grid) {
	      return;
	    }
	    const selectedIds = grid.getRows().getSelectedIds();
	    if (selectedIds.length === 0) {
	      return;
	    }
	    await this.deleteSigners(listId, selectedIds);
	  }
	  async deleteSigners(listId, userIds) {
	    BX.UI.Dialogs.MessageBox.show({
	      message: main_core.Loc.getMessage('SIGN_SIGNERS_SIGNER_DELETE_CONFIRMATION_TITLE'),
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_YES'),
	        color: BX.UI.Button.Color.PRIMARY,
	        onclick: async button => {
	          button.setDisabled(true);
	          button.setState(BX.UI.Button.State.WAITING);
	          const isSingle = userIds.length === 1;
	          const successMsg = isSingle ? main_core.Loc.getMessage('SIGN_SIGNERS_SIGNER_GRID_DELETE_HINT_SUCCESS') : main_core.Loc.getMessage('SIGN_SIGNERS_SIGNERS_GRID_DELETE_HINT_SUCCESS');
	          const failMsg = isSingle ? main_core.Loc.getMessage('SIGN_SIGNERS_SIGNER_GRID_DELETE_HINT_FAIL') : main_core.Loc.getMessage('SIGN_SIGNERS_SIGNERS_GRID_DELETE_HINT_FAIL');
	          try {
	            await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].signersList.deleteSignersFromList(listId, userIds);
	            window.top.BX.UI.Notification.Center.notify({
	              content: successMsg
	            });
	          } catch {
	            window.top.BX.UI.Notification.Center.notify({
	              content: failMsg
	            });
	          }
	          await this.reloadSigners();
	          button.getContext().close();
	        }
	      }), new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_DELETE_POPUP_NO'),
	        color: BX.UI.Button.Color.LINK,
	        onclick: button => button.getContext().close()
	      })]
	    });
	  }
	  async createList() {
	    try {
	      const createListPopup = new CreateListPopup();
	      const title = await createListPopup.show();
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].signersList.createList(title);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_SUCCESS')
	      });
	    } catch {
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_FAIL')
	      });
	    }
	    await this.reloadLists();
	  }
	  async renameList(listId, title) {
	    try {
	      const createListPopup = new CreateListPopup();
	      const newTitle = await createListPopup.show(title);
	      await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].signersList.renameList(listId, newTitle);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_SUCCESS')
	      });
	    } catch {
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_SIGNERS_GRID_LIST_CREATE_FAIL')
	      });
	    }
	    await this.reloadLists();
	  }
	  async addSigners(listId, entities, excludeRejected = true) {
	    const members = entities.map(entity => ({
	      ...entity,
	      party: 2
	    }));
	    await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].signersList.addSignersToList(listId, members, excludeRejected);
	    BX.SidePanel.Instance.close();
	    await this.reloadSigners();
	  }
	  async handleAddSignersButtonClick(listId, userParty) {
	    if (userParty.validate()) {
	      const listGrid = new BX.Sign.V2.Grid.B2e.Signers();
	      await listGrid.addSigners(listId, userParty.getEntities(), userParty.isRejectExcludedEnabled());
	    }
	  }
	}
	function _getGridManager2() {
	  if (BX.Main.gridManager) {
	    return BX.Main.gridManager;
	  }
	  const previousSlider = BX.SidePanel.Instance.getPreviousSlider(BX.SidePanel.Instance.getSliderByWindow(window));
	  const gridWindow = previousSlider ? previousSlider.getWindow() : window.top;
	  return gridWindow == null ? void 0 : gridWindow.BX.Main.gridManager;
	}

	exports.Signers = Signers;

}((this.BX.Sign.V2.Grid.B2e = this.BX.Sign.V2.Grid.B2e || {}),BX.Sign.V2,BX.UI.Dialogs,BX,BX.UI,BX.Main));
//# sourceMappingURL=signers.bundle.js.map
