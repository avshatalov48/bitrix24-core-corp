this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,main_core,ui_buttons) {
	'use strict';

	var LegacyPopup = /*#__PURE__*/function () {
	  function LegacyPopup() {
	    babelHelpers.classCallCheck(this, LegacyPopup);
	    babelHelpers.defineProperty(this, "userBoxNode", null);
	    babelHelpers.defineProperty(this, "isChangedRights", false);
	    babelHelpers.defineProperty(this, "storageNewRights", {});
	    babelHelpers.defineProperty(this, "originalRights", {});
	    babelHelpers.defineProperty(this, "detachedRights", {});
	    babelHelpers.defineProperty(this, "moduleTasks", {});
	    babelHelpers.defineProperty(this, "entityToNewShared", {});
	    babelHelpers.defineProperty(this, "loadedReadOnlyEntityToNewShared", {});
	    babelHelpers.defineProperty(this, "entityToNewSharedMaxTaskName", '');
	    babelHelpers.defineProperty(this, "ajaxUrl", '/bitrix/components/bitrix/disk.folder.list/ajax.php');
	    babelHelpers.defineProperty(this, "destFormName", 'folder-list-destFormName');
	  }

	  babelHelpers.createClass(LegacyPopup, [{
	    key: "showSharingDetailWithChangeRights",
	    value: function showSharingDetailWithChangeRights(params) {
	      var _this = this;

	      this.entityToNewShared = {};
	      this.loadedReadOnlyEntityToNewShared = {};
	      params = params || {};
	      var objectId = params.object.id;
	      BX.Disk.modalWindowLoader(BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailChangeRights'), {
	        id: 'folder_list_sharing_detail_object_' + objectId,
	        responseType: 'json',
	        postData: {
	          objectId: objectId
	        },
	        afterSuccessLoad: function afterSuccessLoad(response) {
	          if (response.status !== 'success') {
	            response.errors = response.errors || [{}];
	            BX.Disk.showModalWithStatusAction({
	              status: 'error',
	              message: response.errors.pop().message
	            });
	          }

	          var objectOwner = {
	            name: response.owner.name,
	            avatar: response.owner.avatar,
	            link: response.owner.link
	          };
	          BX.Disk.modalWindow({
	            modalId: 'bx-disk-detail-sharing-folder-change-right',
	            title: BX.message('JS_DISK_SHARING_LEGACY_POPUP_TITLE_MODAL_3'),
	            contentClassName: '',
	            contentStyle: {},
	            events: {
	              onAfterPopupShow: function onAfterPopupShow() {
	                BX.addCustomEvent('onChangeRightOfSharing', _this.onChangeRightOfSharing.bind(_this));

	                for (var i in response.members) {
	                  if (!response.members.hasOwnProperty(i)) {
	                    continue;
	                  }

	                  _this.entityToNewShared[response.members[i].entityId] = {
	                    item: {
	                      id: response.members[i].entityId,
	                      name: response.members[i].name,
	                      avatar: response.members[i].avatar
	                    },
	                    type: response.members[i].type,
	                    right: response.members[i].right
	                  };
	                }

	                BX.SocNetLogDestination.init({
	                  name: _this.destFormName,
	                  searchInput: BX('feed-add-post-destination-input'),
	                  bindMainPopup: {
	                    'node': BX('feed-add-post-destination-container'),
	                    'offsetTop': '5px',
	                    'offsetLeft': '15px'
	                  },
	                  bindSearchPopup: {
	                    'node': BX('feed-add-post-destination-container'),
	                    'offsetTop': '5px',
	                    'offsetLeft': '15px'
	                  },
	                  callback: {
	                    select: _this.onSelectDestination.bind(_this),
	                    unSelect: _this.onUnSelectDestination.bind(_this),
	                    openDialog: _this.onOpenDialogDestination.bind(_this),
	                    closeDialog: _this.onCloseDialogDestination.bind(_this),
	                    openSearch: _this.onOpenSearchDestination.bind(_this),
	                    closeSearch: _this.onCloseSearchDestination.bind(_this)
	                  },
	                  items: response.destination.items,
	                  itemsLast: response.destination.itemsLast,
	                  itemsSelected: response.destination.itemsSelected
	                });
	                var BXSocNetLogDestinationFormName = _this.destFormName;
	                BX.bind(BX('feed-add-post-destination-container'), 'click', function (e) {
	                  BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);
	                  BX.PreventDefault(e);
	                });
	                BX.bind(BX('feed-add-post-destination-input'), 'keyup', _this.onKeyUpDestination.bind(_this));
	                BX.bind(BX('feed-add-post-destination-input'), 'keydown', _this.onKeyDownDestination.bind(_this));
	              },
	              onPopupClose: function onPopupClose() {
	                if (BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog()) {
	                  BX.SocNetLogDestination.closeDialog();
	                }

	                BX.removeCustomEvent('onChangeRightOfSharing', _this.onChangeRightOfSharing.bind(_this));
	              }
	            },
	            content: [BX.create('div', {
	              props: {
	                className: 'bx-disk-popup-content'
	              },
	              children: [BX.create('table', {
	                props: {
	                  className: 'bx-disk-popup-shared-people-list'
	                },
	                children: [BX.create('thead', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER') + '</td>' + '</tr>'
	                }), BX.create('tr', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + main_core.Text.encode(objectOwner.name) + '</a></td>' + '</tr>'
	                })]
	              }), BX.create('table', {
	                props: {
	                  id: 'bx-disk-popup-shared-people-list',
	                  className: 'bx-disk-popup-shared-people-list'
	                },
	                children: [BX.create('thead', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER') + '</td>' + '<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS') + '</td>' + '<td class="bx-disk-popup-shared-people-list-head-col3"></td>' + '</tr>'
	                })]
	              }), BX.create('div', {
	                props: {
	                  id: 'feed-add-post-destination-container',
	                  className: 'feed-add-post-destination-wrap'
	                },
	                children: [BX.create('span', {
	                  props: {
	                    className: 'feed-add-post-destination-item'
	                  }
	                }), BX.create('span', {
	                  props: {
	                    id: 'feed-add-post-destination-input-box',
	                    className: 'feed-add-destination-input-box'
	                  },
	                  style: {
	                    background: 'transparent'
	                  },
	                  children: [BX.create('input', {
	                    props: {
	                      type: 'text',
	                      value: '',
	                      id: 'feed-add-post-destination-input',
	                      className: 'feed-add-destination-inp'
	                    }
	                  })]
	                }), BX.create('a', {
	                  props: {
	                    href: '#',
	                    id: 'bx-destination-tag',
	                    className: 'feed-add-destination-link'
	                  },
	                  style: {
	                    background: 'transparent'
	                  },
	                  text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
	                  events: {
	                    click: function click() {}
	                  }
	                })]
	              })]
	            })],
	            buttons: [new ui_buttons.SaveButton({
	              events: {
	                click: function click() {
	                  BX.Disk.ajax({
	                    method: 'POST',
	                    dataType: 'json',
	                    url: BX.Disk.addToLinkParam(_this.ajaxUrl, 'action', 'changeSharingAndRights'),
	                    data: {
	                      objectId: objectId,
	                      entityToNewShared: _this.entityToNewShared
	                    },
	                    onsuccess: function onsuccess(response) {
	                      if (!response) {
	                        return;
	                      }

	                      response.message = BX.message('JS_DISK_SHARING_LEGACY_POPUP_OK_FILE_SHARE_MODIFIED').replace('#FILE#', params.object.name);
	                      BX.Disk.showModalWithStatusAction(response);
	                    }
	                  });
	                  BX.PopupWindowManager.getCurrentPopup().close();
	                }
	              }
	            }), new BX.UI.CloseButton({
	              events: {
	                click: function click() {
	                  BX.PopupWindowManager.getCurrentPopup().close();
	                }
	              }
	            })]
	          });
	        }
	      });
	    }
	  }, {
	    key: "showSharingDetailWithSharing",
	    value: function showSharingDetailWithSharing(params) {
	      var _this2 = this;

	      this.entityToNewShared = {};
	      this.loadedReadOnlyEntityToNewShared = {};
	      params = params || {};
	      var objectId = params.object.id;
	      BX.Disk.modalWindowLoader(BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailAppendSharing'), {
	        id: 'folder_list_sharing_detail_object_' + objectId,
	        responseType: 'json',
	        postData: {
	          objectId: objectId
	        },
	        afterSuccessLoad: function afterSuccessLoad(response) {
	          if (response.status !== 'success') {
	            response.errors = response.errors || [{}];
	            BX.Disk.showModalWithStatusAction({
	              status: 'error',
	              message: response.errors.pop().message
	            });
	          }

	          var objectOwner = {
	            name: response.owner.name,
	            avatar: response.owner.avatar,
	            link: response.owner.link
	          };
	          _this2.entityToNewSharedMaxTaskName = response.owner.maxTaskName;
	          BX.Disk.modalWindow({
	            modalId: 'bx-disk-detail-sharing-folder-change-right',
	            title: BX.message('JS_DISK_SHARING_LEGACY_POPUP_TITLE_MODAL_3'),
	            contentClassName: '',
	            contentStyle: {},
	            events: {
	              onAfterPopupShow: function onAfterPopupShow() {
	                BX.addCustomEvent('onChangeRightOfSharing', _this2.onChangeRightOfSharing.bind(_this2));

	                for (var i in response.members) {
	                  if (!response.members.hasOwnProperty(i)) {
	                    continue;
	                  }

	                  _this2.entityToNewShared[response.members[i].entityId] = {
	                    item: {
	                      id: response.members[i].entityId,
	                      name: response.members[i].name,
	                      avatar: response.members[i].avatar
	                    },
	                    type: response.members[i].type,
	                    right: response.members[i].right
	                  };
	                }

	                _this2.loadedReadOnlyEntityToNewShared = BX.clone(_this2.entityToNewShared, true);
	                BX.SocNetLogDestination.init({
	                  name: _this2.destFormName,
	                  searchInput: BX('feed-add-post-destination-input'),
	                  bindMainPopup: {
	                    'node': BX('feed-add-post-destination-container'),
	                    'offsetTop': '5px',
	                    'offsetLeft': '15px'
	                  },
	                  bindSearchPopup: {
	                    'node': BX('feed-add-post-destination-container'),
	                    'offsetTop': '5px',
	                    'offsetLeft': '15px'
	                  },
	                  callback: {
	                    select: _this2.onSelectDestination.bind(_this2),
	                    unSelect: _this2.onUnSelectDestination.bind(_this2),
	                    openDialog: _this2.onOpenDialogDestination.bind(_this2),
	                    closeDialog: _this2.onCloseDialogDestination.bind(_this2),
	                    openSearch: _this2.onOpenSearchDestination.bind(_this2),
	                    closeSearch: _this2.onCloseSearchDestination.bind(_this2)
	                  },
	                  items: response.destination.items,
	                  itemsLast: response.destination.itemsLast,
	                  itemsSelected: response.destination.itemsSelected
	                });
	                var BXSocNetLogDestinationFormName = _this2.destFormName;
	                BX.bind(BX('feed-add-post-destination-container'), 'click', function (e) {
	                  BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);
	                  BX.PreventDefault(e);
	                });
	                BX.bind(BX('feed-add-post-destination-input'), 'keyup', _this2.onKeyUpDestination.bind(_this2));
	                BX.bind(BX('feed-add-post-destination-input'), 'keydown', _this2.onKeyDownDestination.bind(_this2));
	              },
	              onPopupClose: function onPopupClose() {
	                if (BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog()) {
	                  BX.SocNetLogDestination.closeDialog();
	                }

	                BX.removeCustomEvent('onChangeRightOfSharing', _this2.onChangeRightOfSharing.bind(_this2));
	              }
	            },
	            content: [BX.create('div', {
	              props: {
	                className: 'bx-disk-popup-content'
	              },
	              children: [BX.create('table', {
	                props: {
	                  className: 'bx-disk-popup-shared-people-list'
	                },
	                children: [BX.create('thead', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_OWNER') + '</td>' + '</tr>'
	                }), BX.create('tr', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + main_core.Text.encode(objectOwner.name) + '</a></td>' + '</tr>'
	                })]
	              }), BX.create('table', {
	                props: {
	                  id: 'bx-disk-popup-shared-people-list',
	                  className: 'bx-disk-popup-shared-people-list'
	                },
	                children: [BX.create('thead', {
	                  html: '<tr>' + '<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS_USER') + '</td>' + '<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_RIGHTS') + '</td>' + '<td class="bx-disk-popup-shared-people-list-head-col3"></td>' + '</tr>'
	                })]
	              }), BX.create('div', {
	                props: {
	                  id: 'feed-add-post-destination-container',
	                  className: 'feed-add-post-destination-wrap'
	                },
	                children: [BX.create('span', {
	                  props: {
	                    className: 'feed-add-post-destination-item'
	                  }
	                }), BX.create('span', {
	                  props: {
	                    id: 'feed-add-post-destination-input-box',
	                    className: 'feed-add-destination-input-box'
	                  },
	                  style: {
	                    background: 'transparent'
	                  },
	                  children: [BX.create('input', {
	                    props: {
	                      type: 'text',
	                      value: '',
	                      id: 'feed-add-post-destination-input',
	                      className: 'feed-add-destination-inp'
	                    }
	                  })]
	                }), BX.create('a', {
	                  props: {
	                    href: '#',
	                    id: 'bx-destination-tag',
	                    className: 'feed-add-destination-link'
	                  },
	                  style: {
	                    background: 'transparent'
	                  },
	                  text: BX.message('JS_DISK_SHARING_LEGACY_POPUP_LABEL_NAME_ADD_RIGHTS_USER'),
	                  events: {
	                    click: function click() {}
	                  }
	                })]
	              })]
	            })],
	            buttons: [new ui_buttons.SaveButton({
	              events: {
	                click: function click() {
	                  BX.Disk.ajax({
	                    method: 'POST',
	                    dataType: 'json',
	                    url: BX.Disk.addToLinkParam(_this2.ajaxUrl, 'action', 'appendSharing'),
	                    data: {
	                      objectId: objectId,
	                      entityToNewShared: _this2.entityToNewShared
	                    },
	                    onsuccess: function onsuccess(response) {
	                      if (!response) {
	                        return;
	                      }

	                      BX.Disk.showModalWithStatusAction(response);
	                    }
	                  });
	                  BX.PopupWindowManager.getCurrentPopup().close();
	                }
	              }
	            }), new BX.UI.CloseButton({
	              events: {
	                click: function click() {
	                  BX.PopupWindowManager.getCurrentPopup().close();
	                }
	              }
	            })]
	          });
	        }
	      });
	    }
	  }, {
	    key: "showSharingDetailWithoutEdit",
	    value: function showSharingDetailWithoutEdit(params) {
	      params = params || {};
	      BX.Disk.showSharingDetailWithoutEdit({
	        ajaxUrl: '/bitrix/components/bitrix/disk.folder.list/ajax.php',
	        object: params.object
	      });
	    }
	  }, {
	    key: "onSelectDestination",
	    value: function onSelectDestination(item, type, search) {
	      this.entityToNewShared[item.id] = this.entityToNewShared[item.id] || {};
	      BX.Disk.appendNewShared({
	        maxTaskName: this.entityToNewSharedMaxTaskName,
	        readOnly: !!this.loadedReadOnlyEntityToNewShared[item.id],
	        destFormName: this.destFormName,
	        item: item,
	        type: type,
	        right: this.entityToNewShared[item.id].right || 'disk_access_edit'
	      });
	      this.entityToNewShared[item.id] = {
	        item: item,
	        type: type,
	        right: this.entityToNewShared[item.id].right || 'disk_access_edit'
	      };
	    }
	  }, {
	    key: "onUnSelectDestination",
	    value: function onUnSelectDestination(item, type, search) {
	      var entityId = item.id;

	      if (!!this.loadedReadOnlyEntityToNewShared[entityId]) {
	        return false;
	      }

	      delete this.entityToNewShared[entityId];
	      var child = BX.findChild(BX('bx-disk-popup-shared-people-list'), {
	        attribute: {
	          'data-dest-id': '' + entityId + ''
	        }
	      }, true);

	      if (child) {
	        BX.remove(child);
	      }
	    }
	  }, {
	    key: "onChangeRightOfSharing",
	    value: function onChangeRightOfSharing(entityId, taskName) {
	      if (this.entityToNewShared[entityId]) {
	        this.entityToNewShared[entityId].right = taskName;
	      }
	    }
	  }, {
	    key: "onOpenDialogDestination",
	    value: function onOpenDialogDestination() {
	      BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
	      BX.style(BX('bx-destination-tag'), 'display', 'none');
	      BX.focus(BX('feed-add-post-destination-input'));

	      if (BX.SocNetLogDestination.popupWindow) {
	        BX.SocNetLogDestination.popupWindow.adjustPosition({
	          forceTop: true
	        });
	      }
	    }
	  }, {
	    key: "onCloseDialogDestination",
	    value: function onCloseDialogDestination() {
	      var input = BX('feed-add-post-destination-input');

	      if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length <= 0) {
	        BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
	        BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
	      }
	    }
	  }, {
	    key: "onOpenSearchDestination",
	    value: function onOpenSearchDestination() {
	      if (BX.SocNetLogDestination.popupSearchWindow) {
	        BX.SocNetLogDestination.popupSearchWindow.adjustPosition({
	          forceTop: true
	        });
	      }
	    }
	  }, {
	    key: "onCloseSearchDestination",
	    value: function onCloseSearchDestination() {
	      var input = BX('feed-add-post-destination-input');

	      if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length > 0) {
	        BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
	        BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
	        BX('feed-add-post-destination-input').value = '';
	      }
	    }
	  }, {
	    key: "onKeyDownDestination",
	    value: function onKeyDownDestination(event) {
	      var BXSocNetLogDestinationFormName = this.destFormName;

	      if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0) {
	        BX.SocNetLogDestination.sendEvent = false;
	        BX.SocNetLogDestination.deleteLastItem(BXSocNetLogDestinationFormName);
	      }

	      return true;
	    }
	  }, {
	    key: "onKeyUpDestination",
	    value: function onKeyUpDestination(event) {
	      var BXSocNetLogDestinationFormName = this.destFormName;

	      if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91) {
	        return false;
	      }

	      if (event.keyCode == 13) {
	        BX.SocNetLogDestination.selectFirstSearchItem(BXSocNetLogDestinationFormName);
	        return BX.PreventDefault(event);
	      }

	      if (event.keyCode == 27) {
	        BX('feed-add-post-destination-input').value = '';
	      } else {
	        BX.SocNetLogDestination.search(BX('feed-add-post-destination-input').value, true, BXSocNetLogDestinationFormName);
	      }

	      if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog()) {
	        BX.SocNetLogDestination.closeDialog();
	      }

	      if (event.keyCode == 8) {
	        BX.SocNetLogDestination.sendEvent = true;
	      }

	      return BX.PreventDefault(event);
	    }
	  }]);
	  return LegacyPopup;
	}();

	var SharingControlType = function SharingControlType() {
	  babelHelpers.classCallCheck(this, SharingControlType);
	};

	babelHelpers.defineProperty(SharingControlType, "WITHOUT_EDIT", 'without-edit');
	babelHelpers.defineProperty(SharingControlType, "WITH_CHANGE_RIGHTS", 'with-change-rights');
	babelHelpers.defineProperty(SharingControlType, "WITH_SHARING", 'with-sharing');
	babelHelpers.defineProperty(SharingControlType, "BLOCKED_BY_FEATURE", 'blocked-by-feature');

	exports.LegacyPopup = LegacyPopup;
	exports.SharingControlType = SharingControlType;

}((this.BX.Disk.Sharing = this.BX.Disk.Sharing || {}),BX,BX.UI));
//# sourceMappingURL=disk.sharing-legacy-popup.bundle.js.map
