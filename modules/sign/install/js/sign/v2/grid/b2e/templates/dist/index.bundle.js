/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
this.BX.Sign.V2.Grid = this.BX.Sign.V2.Grid || {};
(function (exports,sign_v2_analytics,ui_switcher,ui_dialogs_messagebox,main_core,main_core_events,sign_v2_api,sign_featureStorage,sign_type) {
	'use strict';

	let moveBtnOnClick = null;
	function toggleActionButton(options) {
	  const button = document.getElementById(options.id);
	  if (!main_core.Type.isElementNode(button)) {
	    return;
	  }
	  if (options.disabled && !main_core.Dom.hasClass(button, 'ui-action-panel-item-is-disabled')) {
	    main_core.Dom.addClass(button, 'ui-action-panel-item-is-disabled');
	    main_core.Dom.attr(button, 'title', options.title);
	    main_core.Dom.style(button, 'user-select', 'none');
	    moveBtnOnClick = button.onclick;
	    button.onclick = () => 0;
	  } else if (!options.disabled && main_core.Dom.hasClass(button, 'ui-action-panel-item-is-disabled')) {
	    main_core.Dom.removeClass(button, 'ui-action-panel-item-is-disabled');
	    main_core.Dom.attr(button, 'title', '');
	    main_core.Dom.style(button, 'user-select', 'auto');
	    button.onclick = moveBtnOnClick;
	  }
	}

	class CreateFolderPopup {
	  async show(inputText = null) {
	    return new Promise(resolve => {
	      const uniqueId = `folderNameInput_${Date.now()}`;
	      const handleSubmit = () => {
	        const folderName = document.getElementById(uniqueId).value;
	        if (folderName) {
	          resolve(folderName);
	          popup.close();
	        } else {
	          BX.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_TITLE_NOT_EMPTY')
	          });
	        }
	      };
	      const popup = new BX.PopupWindow(`folderNamePopup_${uniqueId}`, null, {
	        className: 'sign-b2e-grid-templates-popup',
	        content: `
					<div class="sign-create-folder-popup-item-container">
						<span class="sign-create-folder-popup-icon"></span>
						<div class="sign-create-folder-title-input-container">
							<input
							type="text"
							id="${uniqueId}"
							class="ui-ctl-element"
							placeholder="${main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_POPUP_INPUT_PLACEHOLDER')}"
							value="${inputText === null ? '' : BX.util.htmlspecialchars(inputText)}"
							>
						</div>
					</div>
				`,
	        buttons: [new BX.PopupWindowButton({
	          text: inputText === null ? main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_CREATE_BUTTON_TEXT') : main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_SAVE_BUTTON_TEXT'),
	          className: 'popup-window-button-blue',
	          events: {
	            click: handleSubmit
	          }
	        }), new BX.PopupWindowButton({
	          text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_CANCEL_BUTTON_TEXT'),
	          events: {
	            click() {
	              popup.close();
	            }
	          }
	        })],
	        draggable: true,
	        overlay: true,
	        width: 500,
	        height: 420,
	        events: {
	          onPopupShow() {
	            main_core.Dom.style(this.popupContainer, 'backgroundColor', 'rgba(255, 255, 255)');
	            const input = document.getElementById(uniqueId);
	            input.focus();
	            input.setSelectionRange(input.value.length, input.value.length);
	            main_core.Event.bind(input, 'keydown', event => {
	              if (event.key === 'Enter') {
	                event.preventDefault();
	                handleSubmit();
	              }
	            });
	          }
	        }
	      });
	      popup.show();
	    });
	  }
	}

	var _getMessageContentForDeleteMessageBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMessageContentForDeleteMessageBox");
	var _getTitleForDeleteMessageBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTitleForDeleteMessageBox");
	var _getSuccessNotificationForDeleteMessageBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSuccessNotificationForDeleteMessageBox");
	var _getFailNotificationForDeleteMessageBox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFailNotificationForDeleteMessageBox");
	class DeleteConfirmationPopup {
	  constructor() {
	    Object.defineProperty(this, _getFailNotificationForDeleteMessageBox, {
	      value: _getFailNotificationForDeleteMessageBox2
	    });
	    Object.defineProperty(this, _getSuccessNotificationForDeleteMessageBox, {
	      value: _getSuccessNotificationForDeleteMessageBox2
	    });
	    Object.defineProperty(this, _getTitleForDeleteMessageBox, {
	      value: _getTitleForDeleteMessageBox2
	    });
	    Object.defineProperty(this, _getMessageContentForDeleteMessageBox, {
	      value: _getMessageContentForDeleteMessageBox2
	    });
	  }
	  async show(entityType, onConfirm) {
	    const messageContent = document.createElement('div');
	    messageContent.innerHTML = babelHelpers.classPrivateFieldLooseBase(this, _getMessageContentForDeleteMessageBox)[_getMessageContentForDeleteMessageBox](entityType);
	    main_core.Dom.style(messageContent, 'margin-top', '5%');
	    main_core.Dom.style(messageContent, 'color', '#535c69');
	    ui_dialogs_messagebox.MessageBox.show({
	      title: babelHelpers.classPrivateFieldLooseBase(this, _getTitleForDeleteMessageBox)[_getTitleForDeleteMessageBox](entityType),
	      message: messageContent.outerHTML,
	      modal: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_POPUP_YES'),
	        color: BX.UI.Button.Color.PRIMARY,
	        onclick: async button => {
	          try {
	            await onConfirm();
	            window.top.BX.UI.Notification.Center.notify({
	              content: babelHelpers.classPrivateFieldLooseBase(this, _getSuccessNotificationForDeleteMessageBox)[_getSuccessNotificationForDeleteMessageBox](entityType)
	            });
	          } catch {
	            if (entityType !== sign_type.TemplateEntity.folder) {
	              window.top.BX.UI.Notification.Center.notify({
	                content: babelHelpers.classPrivateFieldLooseBase(this, _getFailNotificationForDeleteMessageBox)[_getFailNotificationForDeleteMessageBox](entityType)
	              });
	            }
	          }
	          const templateGrid = new Templates();
	          await templateGrid.reload();
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
	}
	function _getMessageContentForDeleteMessageBox2(entityType) {
	  switch (entityType) {
	    case sign_type.TemplateEntity.template:
	      return main_core.Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_MESSAGE');
	    case sign_type.TemplateEntity.folder:
	      return main_core.Loc.getMessage('SIGN_FOLDER_DELETE_CONFIRMATION_MESSAGE');
	    case sign_type.TemplateEntity.multiple:
	      return sign_featureStorage.FeatureStorage.isTemplateFolderGroupingAllowed() ? main_core.Loc.getMessage('SIGN_MULTIPLE_DELETE_CONFIRMATION_MESSAGE') : main_core.Loc.getMessage('SIGN_MULTIPLE_DELETE_TEMPLATES_CONFIRMATION_MESSAGE');
	    default:
	      return null;
	  }
	}
	function _getTitleForDeleteMessageBox2(entityType) {
	  switch (entityType) {
	    case sign_type.TemplateEntity.template:
	      return main_core.Loc.getMessage('SIGN_TEMPLATE_DELETE_CONFIRMATION_TITLE');
	    case sign_type.TemplateEntity.folder:
	      return main_core.Loc.getMessage('SIGN_FOLDER_DELETE_CONFIRMATION_TITLE');
	    case sign_type.TemplateEntity.multiple:
	      return main_core.Loc.getMessage('SIGN_MULTIPLE_DELETE_CONFIRMATION_TITLE');
	    default:
	      return null;
	  }
	}
	function _getSuccessNotificationForDeleteMessageBox2(entityType) {
	  switch (entityType) {
	    case sign_type.TemplateEntity.template:
	      return main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_SUCCESS');
	    case sign_type.TemplateEntity.folder:
	      return main_core.Loc.getMessage('SIGN_FOLDER_GRID_DELETE_HINT_SUCCESS');
	    case sign_type.TemplateEntity.multiple:
	      return sign_featureStorage.FeatureStorage.isTemplateFolderGroupingAllowed() ? main_core.Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_SUCCESS') : main_core.Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_TEMPLATES_SUCCESS');
	    default:
	      return null;
	  }
	}
	function _getFailNotificationForDeleteMessageBox2(entityType) {
	  switch (entityType) {
	    case sign_type.TemplateEntity.template:
	      return main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_HINT_FAIL');
	    case sign_type.TemplateEntity.folder:
	      return main_core.Loc.getMessage('SIGN_FOLDER_GRID_DELETE_HINT_FAIL');
	    case sign_type.TemplateEntity.multiple:
	      return sign_featureStorage.FeatureStorage.isTemplateFolderGroupingAllowed() ? main_core.Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_FAIL') : main_core.Loc.getMessage('SIGN_MULTIPLE_GRID_DELETE_HINT_TEMPLATES_FAIL');
	    default:
	      return null;
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _createFolderListContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createFolderListContainer");
	var _createSubFolderContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createSubFolderContainer");
	var _createFolderItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createFolderItem");
	var _selectAndHighlightFolder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectAndHighlightFolder");
	class FolderSelectionPopup extends main_core_events.EventEmitter {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _selectAndHighlightFolder, {
	      value: _selectAndHighlightFolder2
	    });
	    Object.defineProperty(this, _createFolderItem, {
	      value: _createFolderItem2
	    });
	    Object.defineProperty(this, _createSubFolderContainer, {
	      value: _createSubFolderContainer2
	    });
	    Object.defineProperty(this, _createFolderListContainer, {
	      value: _createFolderListContainer2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	  }
	  async show() {
	    const firstLevelDeepFolders = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].templateFolder.getListByDepthLevel(0);
	    const folderList = babelHelpers.classPrivateFieldLooseBase(this, _createFolderListContainer)[_createFolderListContainer]();
	    const rootFolderData = {
	      title: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_ROOT_LEVEL_ITEM'),
	      id: 0
	    };
	    const rootFolder = babelHelpers.classPrivateFieldLooseBase(this, _createFolderItem)[_createFolderItem](rootFolderData);
	    main_core.Dom.addClass(rootFolder, 'sign-b2e-grid-templates-popup__selected-folder');
	    main_core.Dom.append(rootFolder, folderList);
	    this.emit('folderSelected', rootFolderData);
	    const subFolderContainer = babelHelpers.classPrivateFieldLooseBase(this, _createSubFolderContainer)[_createSubFolderContainer]();
	    main_core.Dom.append(subFolderContainer, folderList);
	    firstLevelDeepFolders.forEach(folder => {
	      const listItem = babelHelpers.classPrivateFieldLooseBase(this, _createFolderItem)[_createFolderItem](folder);
	      main_core.Dom.append(listItem, subFolderContainer);
	    });
	    return folderList;
	  }
	}
	function _createFolderListContainer2() {
	  return main_core.Tag.render(_t || (_t = _`<div class="sign-b2e-grid-templates-popup__folder-list-container"></div>`));
	}
	function _createSubFolderContainer2() {
	  return main_core.Tag.render(_t2 || (_t2 = _`<div class="sign-b2e-grid-templates-popup__sub-folder-container"></div>`));
	}
	function _createFolderItem2(folder) {
	  const listItem = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="sign-b2e-grid-templates-popup__folder-item">
				<span class="sign-b2e-grid-templates-popup__folder-icon-folder-list"></span>
				<span>${0}</span>
			</div>
		`), main_core.Text.encode(folder.title));
	  main_core.Event.bind(listItem, 'click', event => {
	    event.stopPropagation();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectAndHighlightFolder)[_selectAndHighlightFolder](listItem, folder);
	  });
	  return listItem;
	}
	function _selectAndHighlightFolder2(selectedItem, folder) {
	  const folderListContainer = selectedItem.closest('.sign-b2e-grid-templates-popup__folder-list-container');
	  if (folderListContainer) {
	    folderListContainer.querySelectorAll('.sign-b2e-grid-templates-popup__folder-item').forEach(child => {
	      main_core.Dom.removeClass(child, 'sign-b2e-grid-templates-popup__selected-folder');
	    });
	    main_core.Dom.addClass(selectedItem, 'sign-b2e-grid-templates-popup__selected-folder');
	    this.emit('folderSelected', folder);
	  }
	}

	function buildMetadataFromElement(element) {
	  return {
	    id: Number(element.dataset.id),
	    entityType: element.dataset.entityType,
	    initiatedByType: element.dataset.initiatedByType,
	    canEdit: element.dataset.canEdit,
	    canDelete: element.dataset.canDelete,
	    isFolderEntityType() {
	      return this.entityType === sign_type.TemplateEntity.folder;
	    },
	    isInitiatedByTypeisEmployee() {
	      return this.initiatedByType === sign_type.DocumentInitiated.employee;
	    },
	    canEditAccess() {
	      return this.canEdit;
	    },
	    canDeleteAccess() {
	      return this.canDelete;
	    }
	  };
	}
	function extractMetadataFromRow(row) {
	  const cellWithMetadataElement = [...row.getCells()].map(cell => cell.querySelector('.sign-grid-template__cell-metadata')).find(element => element);
	  if (!cellWithMetadataElement) {
	    return null;
	  }
	  return buildMetadataFromElement(cellWithMetadataElement);
	}

	let _$1 = t => t,
	  _t$1;
	var _gridId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridId");
	var _addNewTemplateLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addNewTemplateLink");
	var _urlsForReload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urlsForReload");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _api$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _changeVisibilityForTemplate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeVisibilityForTemplate");
	var _changeVisibilityForFolder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("changeVisibilityForFolder");
	var _sendActionStateAnalytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendActionStateAnalytics");
	var _getFolderGrid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFolderGrid");
	var _getTemplateListGrid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTemplateListGrid");
	var _shouldReloadAfterClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("shouldReloadAfterClose");
	var _downloadStringLikeFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("downloadStringLikeFile");
	var _getSelectedItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedItems");
	var _onGridTreeAttributesMutate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onGridTreeAttributesMutate");
	class Templates {
	  constructor(gridId, addNewTemplateLink, urlsForReload) {
	    Object.defineProperty(this, _onGridTreeAttributesMutate, {
	      value: _onGridTreeAttributesMutate2
	    });
	    Object.defineProperty(this, _getSelectedItems, {
	      value: _getSelectedItems2
	    });
	    Object.defineProperty(this, _downloadStringLikeFile, {
	      value: _downloadStringLikeFile2
	    });
	    Object.defineProperty(this, _shouldReloadAfterClose, {
	      value: _shouldReloadAfterClose2
	    });
	    Object.defineProperty(this, _getTemplateListGrid, {
	      value: _getTemplateListGrid2
	    });
	    Object.defineProperty(this, _getFolderGrid, {
	      value: _getFolderGrid2
	    });
	    Object.defineProperty(this, _sendActionStateAnalytics, {
	      value: _sendActionStateAnalytics2
	    });
	    Object.defineProperty(this, _changeVisibilityForFolder, {
	      value: _changeVisibilityForFolder2
	    });
	    Object.defineProperty(this, _changeVisibilityForTemplate, {
	      value: _changeVisibilityForTemplate2
	    });
	    Object.defineProperty(this, _gridId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _addNewTemplateLink, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _urlsForReload, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: new sign_v2_analytics.Analytics()
	    });
	    Object.defineProperty(this, _api$1, {
	      writable: true,
	      value: new sign_v2_api.Api()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId] = gridId;
	    babelHelpers.classPrivateFieldLooseBase(this, _addNewTemplateLink)[_addNewTemplateLink] = addNewTemplateLink;
	    babelHelpers.classPrivateFieldLooseBase(this, _urlsForReload)[_urlsForReload] = urlsForReload;
	  }
	  renderSendButton(entityId, entityType, templateIds, blockParams) {
	    if (entityId <= 0) {
	      throw new Error('Invalid entityId must be greater than 0');
	    }
	    const validEntityTypes = ['template', 'folder'];
	    if (!validEntityTypes.includes(entityType)) {
	      throw new Error(`Invalid entityType must be one of ${validEntityTypes.join(', ')}`);
	    }
	    const button = new BX.UI.Button({
	      text: main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_LAUNCH_SIGNING'),
	      color: BX.UI.Button.Color.SUCCESS,
	      size: BX.UI.Button.Size.SMALL,
	      round: true,
	      disabled: blockParams.isBlocked
	    });
	    const buttonElement = button.render();
	    if (blockParams.isNoCompaniesInFolder) {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_EMPTY_FOLDER'));
	    } else if (blockParams.isTemplateDisabled) {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_TEMPLATE_DISABLED'));
	    } else if (blockParams.isMultipleCompaniesInFolder) {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_MULTIPLE_COMPANIES_IN_FOLDER'));
	    } else if (blockParams.isInvisible && entityType === 'template') {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_TEMPLATE'));
	    } else if (blockParams.isInvisible && entityType === 'folder') {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_FOLDER'));
	    } else if (blockParams.hasAnyInvisibleTemplates) {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_INVISIBLE_TEMPLATES_IN_FOLDER'));
	    } else if (blockParams.hasNoReadAccess) {
	      buttonElement.setAttribute('title', main_core.Loc.getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_BUTTON_BLOCKED_HINT_NO_READ_ACCESS'));
	    } else {
	      BX.Event.bind(buttonElement, 'click', event => {
	        event.preventDefault();
	        event.stopPropagation();
	        const sliderUrl = `sign-b2e-templates-settings-${entityId}-${entityType}`;
	        BX.SidePanel.Instance.open(sliderUrl, {
	          width: 900,
	          cacheable: false,
	          contentCallback: () => {
	            return top.BX.Runtime.loadExtension(['sign.v2.b2e.sign-settings-templates']).then(exports => {
	              const {
	                B2ETemplatesSignSettings
	              } = exports;
	              const container = BX.Tag.render(_t$1 || (_t$1 = _$1`<div id="sign-b2e-templates-settings-container-${0}-${0}"></div>`), entityId, entityType);
	              const templatesSignSettings = new B2ETemplatesSignSettings(templateIds, sliderUrl);
	              templatesSignSettings.renderToContainer(container);
	              return container;
	            });
	          }
	        });
	      });
	    }
	    return buttonElement;
	  }
	  async renderSwitcher(entityId, entityType, isChecked, isDisabled, hasEditTemplateAccess) {
	    const switcherNode = document.getElementById(`switcher_b2e_template_grid_${entityId}_${entityType}`);
	    const switcher = new ui_switcher.Switcher({
	      node: switcherNode,
	      checked: isChecked,
	      size: ui_switcher.SwitcherSize.medium,
	      disabled: isDisabled,
	      handlers: {
	        toggled: async event => {
	          event.stopPropagation();
	          switcher.setLoading(true);
	          const checked = switcher.isChecked();
	          const visibility = checked ? 'visible' : 'invisible';
	          try {
	            switch (entityType) {
	              case sign_type.TemplateEntity.template:
	                await babelHelpers.classPrivateFieldLooseBase(this, _changeVisibilityForTemplate)[_changeVisibilityForTemplate](entityId, visibility);
	                break;
	              case sign_type.TemplateEntity.folder:
	                await babelHelpers.classPrivateFieldLooseBase(this, _changeVisibilityForFolder)[_changeVisibilityForFolder](entityId, visibility);
	                break;
	              default:
	                await console.error(`Unknown entity type: ${entityType}`);
	            }
	            await this.reload();
	          } catch {
	            switcher.setLoading(false);
	            switcher.check(!checked, false);
	          } finally {
	            babelHelpers.classPrivateFieldLooseBase(this, _sendActionStateAnalytics)[_sendActionStateAnalytics](checked, entityId);
	            switcher.setLoading(false);
	          }
	        }
	      }
	    });
	    if (!isDisabled) {
	      return;
	    }
	    let title = '';
	    switch (entityType) {
	      case sign_type.TemplateEntity.template:
	        title = hasEditTemplateAccess ? main_core.Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT') : main_core.Loc.getMessage('SIGN_TEMPLATE_BLOCKED_SWITCHER_HINT_NO_ACCESS');
	        break;
	      case sign_type.TemplateEntity.folder:
	        title = hasEditTemplateAccess ? main_core.Loc.getMessage('SIGN_TEMPLATE_FOLDER_BLOCKED_SWITCHER_HINT') : main_core.Loc.getMessage('SIGN_TEMPLATE_FOLDER_BLOCKED_SWITCHER_HINT_NO_ACCESS');
	        break;
	      default:
	        title = '';
	        break;
	    }
	    switcherNode.setAttribute('title', title);
	  }
	  async createFolder() {
	    try {
	      const createFolderPopup = new CreateFolderPopup();
	      const title = await createFolderPopup.show();
	      const api = babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].templateFolder;
	      await api.create(title);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_SUCCESS')
	      });
	    } catch {
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_FAIL')
	      });
	    }
	    await this.reload();
	  }
	  async renameFolder(entityId, oldTitle) {
	    try {
	      const createFolderPopup = new CreateFolderPopup();
	      const newTitle = await createFolderPopup.show(oldTitle);
	      const api = babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].templateFolder;
	      await api.rename(entityId, newTitle);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_RENAME_SUCCESS')
	      });
	    } catch {
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_CREATE_FOLDER_HINT_RENAME_FAIL')
	      });
	    }
	    await this.reload();
	  }
	  reload() {
	    main_core.Event.ready(() => {
	      var _babelHelpers$classPr;
	      const grid = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getFolderGrid)[_getFolderGrid]()) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _getTemplateListGrid)[_getTemplateListGrid]();
	      if (main_core.Type.isObject(grid)) {
	        grid.reload();
	      }
	    });
	  }
	  reloadAfterSliderClose() {
	    const context = window === top ? window : top;
	    context.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onCloseComplete', async event => {
	      const closedSliderUrl = event.getData()[0].getSlider().getUrl();
	      if (babelHelpers.classPrivateFieldLooseBase(this, _shouldReloadAfterClose)[_shouldReloadAfterClose](closedSliderUrl)) {
	        await this.reload();
	      }
	    });
	  }
	  async exportBlank(templateId) {
	    try {
	      const {
	        json,
	        filename
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].template.exportBlank(templateId);
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
	  async copyTemplate(templateId, folderId) {
	    try {
	      const response = await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].template.copy(templateId, folderId);
	      const copyTemplateId = response.template.id;
	      await this.reload();
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_SUCCESS')
	      });
	      if (window.top.BX.SidePanel && babelHelpers.classPrivateFieldLooseBase(this, _addNewTemplateLink)[_addNewTemplateLink] && copyTemplateId) {
	        window.top.BX.SidePanel.Instance.open(`${babelHelpers.classPrivateFieldLooseBase(this, _addNewTemplateLink)[_addNewTemplateLink]}&templateId=${copyTemplateId}&stepId=changePartner&noRedirect=Y`);
	      }
	    } catch (error) {
	      console.error('Error copying template:', error);
	      window.top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_COPY_HINT_FAIL')
	      });
	    }
	  }
	  async delete(entityId, entityType) {
	    const deleteConfirmationPopup = new DeleteConfirmationPopup();
	    await deleteConfirmationPopup.show(entityType, async () => {
	      const api = babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1];
	      switch (entityType) {
	        case sign_type.TemplateEntity.template:
	          await api.template.delete(entityId);
	          break;
	        case sign_type.TemplateEntity.folder:
	          await api.templateFolder.delete(entityId);
	          break;
	        default:
	          await console.error(`Unknown entity type: ${entityType}`);
	      }
	      await this.reload();
	    });
	  }
	  async deleteSelectedItems() {
	    const selectedItems = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedItems)[_getSelectedItems]();
	    if (selectedItems.length > 0) {
	      const deleteConfirmationPopup = new DeleteConfirmationPopup();
	      await deleteConfirmationPopup.show(sign_type.TemplateEntity.multiple, async () => {
	        try {
	          await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].template.deleteEntities(selectedItems);
	          await this.reload();
	        } catch (error) {
	          await console.error('Error deleting template entities:', error);
	        }
	      });
	    }
	  }
	  async moveTemplatesToFolder(templateId = null) {
	    const selectedItems = babelHelpers.classPrivateFieldLooseBase(this, _getSelectedItems)[_getSelectedItems]();
	    if (selectedItems.length > 0 || templateId !== null) {
	      const folderSelectionPopup = new FolderSelectionPopup();
	      folderSelectionPopup.subscribe('folderSelected', event => {
	        this.selectedFolder = event.getData();
	      });
	      const folderList = await folderSelectionPopup.show();
	      ui_dialogs_messagebox.MessageBox.show({
	        title: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_TITLE'),
	        message: folderList,
	        modal: true,
	        minWidth: 500,
	        minHeight: 370,
	        buttons: [new BX.UI.Button({
	          text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_OK_BUTTON_TEXT'),
	          color: BX.UI.Button.Color.SUCCESS,
	          onclick: async button => {
	            if (this.selectedFolder) {
	              const selectedItem = {
	                id: templateId,
	                entityType: sign_type.TemplateEntity.template
	              };
	              const selectedTemplates = templateId === null ? selectedItems : [selectedItem];
	              await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].template.moveToFolder(selectedTemplates, Number(this.selectedFolder.id));
	              button.getContext().close();
	              await this.reload();
	            }
	          },
	          className: 'sign-b2e-grid-templates-popup__move-to-button'
	        }), new BX.UI.Button({
	          text: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_POPUP_CANCEL_BUTTON_TEXT'),
	          color: BX.UI.Button.Color.LINK,
	          onclick: button => {
	            button.getContext().close();
	          }
	        })]
	      });
	    }
	  }
	  openSliderTemplateFolderContent(folderId = 0) {
	    window.event.stopPropagation();
	    if (BX.SidePanel && BX.SidePanel.Instance) {
	      const basePath = folderId === 0 ? 'employee/templates/folder/' : 'templates/folder/';
	      const queryParams = new URLSearchParams(window.location.search);
	      const url = folderId !== 0 && queryParams.has('folderId') ? `?folderId=${encodeURIComponent(folderId)}` : `${basePath}?folderId=${encodeURIComponent(folderId)}`;
	      BX.SidePanel.Instance.open(url, {
	        width: 1650,
	        cacheable: false,
	        allowChangeHistory: true
	      });
	    }
	  }
	  subscribeOnGridEvents() {
	    main_core.Event.ready(() => {
	      var _babelHelpers$classPr2;
	      const grid = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getFolderGrid)[_getFolderGrid]()) != null ? _babelHelpers$classPr2 : babelHelpers.classPrivateFieldLooseBase(this, _getTemplateListGrid)[_getTemplateListGrid]();
	      if (!main_core.Type.isObject(grid)) {
	        return;
	      }
	      const gridTreeAttributesChangesMutationObserver = new MutationObserver(() => babelHelpers.classPrivateFieldLooseBase(this, _onGridTreeAttributesMutate)[_onGridTreeAttributesMutate](grid));
	      gridTreeAttributesChangesMutationObserver.observe(grid.getContainer(), {
	        subtree: true,
	        attributes: true
	      });
	    });
	  }
	}
	function _changeVisibilityForTemplate2(templateId, visibility) {
	  const api = babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].template;
	  return api.changeVisibility(templateId, visibility);
	}
	function _changeVisibilityForFolder2(folderId, visibility) {
	  const api = babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].templateFolder;
	  return api.changeVisibility(folderId, visibility);
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
	function _getFolderGrid2() {
	  var _BX$SidePanel$Instanc, _BX$SidePanel$Instanc2;
	  return (_BX$SidePanel$Instanc = BX.SidePanel.Instance.getTopSlider()) == null ? void 0 : (_BX$SidePanel$Instanc2 = _BX$SidePanel$Instanc.getFrameWindow().BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId])) == null ? void 0 : _BX$SidePanel$Instanc2.instance;
	}
	function _getTemplateListGrid2() {
	  var _BX$Main$gridManager$;
	  return (_BX$Main$gridManager$ = BX.Main.gridManager.getById(babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId])) == null ? void 0 : _BX$Main$gridManager$.instance;
	}
	function _shouldReloadAfterClose2(closedSliderUrl) {
	  const uri = new main_core.Uri(closedSliderUrl);
	  const path = uri.getPath();
	  return closedSliderUrl === babelHelpers.classPrivateFieldLooseBase(this, _addNewTemplateLink)[_addNewTemplateLink] || closedSliderUrl === 'sign-settings-template-created' || babelHelpers.classPrivateFieldLooseBase(this, _urlsForReload)[_urlsForReload].some(url => path.startsWith(new main_core.Uri(url).getPath()));
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
	function _getSelectedItems2() {
	  const items = [];
	  const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
	  checkboxes.forEach(checkbox => {
	    const id = checkbox.value;
	    if (Number.isInteger(Number(id))) {
	      const rowElement = checkbox.closest('tr');
	      const entityElement = rowElement ? rowElement.querySelector('.sign-template-title, .sign-template-title-without-link') : null;
	      const entityType = entityElement ? entityElement.getAttribute('data-entity-type') : null;
	      items.push({
	        id,
	        entityType
	      });
	    }
	  });
	  return items;
	}
	function _onGridTreeAttributesMutate2(grid) {
	  var _document$querySelect, _document$querySelect2;
	  const rows = grid.getRows().getRows();
	  const isRowUnmovable = row => {
	    var _row$getCheckbox;
	    const metadata = extractMetadataFromRow(row);
	    if (main_core.Type.isNull(metadata)) {
	      return true;
	    }
	    const isChecked = (_row$getCheckbox = row.getCheckbox()) == null ? void 0 : _row$getCheckbox.checked;
	    const isRestricted = metadata.isFolderEntityType() || metadata.isInitiatedByTypeisEmployee() || !metadata.canEditAccess();
	    return isRestricted && isChecked;
	  };
	  const allSelectedCellsAmount = parseInt((_document$querySelect = (_document$querySelect2 = document.querySelector('[data-role="action-panel-total-param"]')) == null ? void 0 : _document$querySelect2.textContent) != null ? _document$querySelect : '0', 10);
	  const moveActionButton = {
	    id: 'sign-template-list-move-to-folder-button',
	    disabled: false,
	    title: main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_MOVE_TO_FOLDER_GROUP_ACTION_BUTTON_DISABLED_HINT')
	  };
	  const rowsWithoutHeaderAndHiddenRow = rows.slice(2);
	  moveActionButton.disabled = rowsWithoutHeaderAndHiddenRow.some(row => isRowUnmovable(row));
	  toggleActionButton(moveActionButton);
	  const isRowNonDeletable = row => {
	    var _row$getCheckbox2;
	    const metadata = extractMetadataFromRow(row);
	    if (main_core.Type.isNull(metadata)) {
	      return true;
	    }
	    const isChecked = (_row$getCheckbox2 = row.getCheckbox()) == null ? void 0 : _row$getCheckbox2.checked;
	    return !metadata.canDeleteAccess() && isChecked;
	  };
	  const deleteActionButton = {
	    id: 'sign-template-list-delete-button',
	    disabled: false,
	    title: sign_featureStorage.FeatureStorage.isTemplateFolderGroupingAllowed() ? main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_GROUP_ACTION_BUTTON_DISABLED_HINT') : main_core.Loc.getMessage('SIGN_TEMPLATE_GRID_DELETE_TEMPLATES_GROUP_ACTION_BUTTON_DISABLED_HINT')
	  };
	  if (allSelectedCellsAmount <= 1) {
	    toggleActionButton(moveActionButton);
	    return;
	  }
	  deleteActionButton.disabled = rowsWithoutHeaderAndHiddenRow.some(row => isRowNonDeletable(row));
	  toggleActionButton(deleteActionButton);
	}

	exports.Templates = Templates;

}((this.BX.Sign.V2.Grid.B2e = this.BX.Sign.V2.Grid.B2e || {}),BX.Sign.V2,BX.UI,BX.UI.Dialogs,BX,BX.Event,BX.Sign.V2,BX.Sign,BX.Sign));
//# sourceMappingURL=index.bundle.js.map
