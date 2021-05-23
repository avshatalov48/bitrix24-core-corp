(function (exports,main_core,rpa_manager,main_popup,ui_dialogs_messagebox) {
	'use strict';

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item-text\">", "</div>\n\t\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span id=\"rpa-type-list-", "-counter\" class=\"rpa-tile-item-counter\" ", ">", "</span>\n\t\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item-status\"></div>\n\t\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item-image-block\">\n\t\t\t\t\t\t<div class=\"rpa-tile-item-image rpa-tile-item-icon-", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item-button\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"rpa-tile-item-button-inner\">", "</div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"rpa-tile-item-content\">\n\t\t\t\t\t\t\t<div class=\"rpa-tile-item-subject\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"rpa-tile-item rpa-tile-item-add-new\" onclick=\"", "\">\n\t\t\t\t\t\t<div class=\"rpa-tile-item-content\">\n\t\t\t\t\t\t\t<span class=\"rpa-tile-item-add-new-inner\">\n\t\t\t\t\t\t\t\t<span class=\"rpa-tile-item-add-icon\"></span>\n\t\t\t\t\t\t\t\t<span class=\"rpa-tile-item-add-text\">", "</span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var PanelItem =
	/*#__PURE__*/
	function (_BX$TileGrid$Item) {
	  babelHelpers.inherits(PanelItem, _BX$TileGrid$Item);

	  function PanelItem(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, PanelItem);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PanelItem).call(this, options));
	    _this.typeId = options.typeId;
	    _this.title = options.title;
	    _this.image = options.image;
	    _this.listUrl = options.listUrl;
	    _this.canDelete = options.canDelete === true;
	    _this.tasksCounter = options.tasksCounter;
	    _this.isSettingsRestricted = options.isSettingsRestricted === true;
	    return _this;
	  }

	  babelHelpers.createClass(PanelItem, [{
	    key: "isNew",
	    value: function isNew() {
	      return this.id === 'rpa-type-new';
	    }
	  }, {
	    key: "getContent",
	    value: function getContent() {
	      if (!this.layout.container) {
	        if (this.isNew()) {
	          this.layout.container = main_core.Tag.render(_templateObject(), this.onClick.bind(this), main_core.Loc.getMessage('RPA_COMMON_NEW_PROCESS'));
	        } else {
	          this.layout.container = main_core.Tag.render(_templateObject2(), this.onClick.bind(this), this.getTitle(), this.getButton(), this.getImage(), this.getStatus(), this.getCounter());
	        }
	      }

	      return this.layout.container;
	    }
	  }, {
	    key: "removeNode",
	    value: function removeNode() {
	      if (this.layout.container && this.layout.container.parentNode) {
	        this.layout.container.parentNode.removeChild(this.layout.container);
	      }
	    }
	  }, {
	    key: "getButton",
	    value: function getButton() {
	      if (!this.layout.button) {
	        this.layout.button = main_core.Tag.render(_templateObject3(), this.showActions.bind(this), main_core.Loc.getMessage('RPA_COMMON_BUTTON_ACTIONS'));
	      }

	      return this.layout.button;
	    }
	  }, {
	    key: "getImage",
	    value: function getImage() {
	      if (!this.layout.image) {
	        this.layout.image =
	        /*Tag.render`
	        	<div class="rpa-tile-item-image-block">
	        		<span class="rpa-tile-item-image fa fa-plane"></span>
	        		<span class="rpa-tile-item-image" style="background-image: url(&quot;https://cdn.bitrix24.site/bitrix/images/landing/business/1920x1080/img6.jpg&quot;);"></span>
	        	</div>
	        `;*/
	        main_core.Tag.render(_templateObject4(), main_core.Text.encode(this.image));
	      }

	      return this.layout.image;
	    }
	  }, {
	    key: "getStatus",
	    value: function getStatus() {
	      if (!this.layout.status) {
	        this.layout.status = main_core.Tag.render(_templateObject5());
	      }

	      return this.layout.status;
	    }
	  }, {
	    key: "getCounter",
	    value: function getCounter() {
	      if (!this.layout.counter) {
	        this.layout.counter = main_core.Tag.render(_templateObject6(), this.typeId, this.tasksCounter <= 0 ? 'style="display: none;"' : '', this.tasksCounter);
	      }

	      return this.layout.counter;
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      if (!this.layout.title) {
	        this.layout.title = main_core.Tag.render(_templateObject7(), main_core.Text.encode(this.title));
	      }

	      return this.layout.title;
	    }
	  }, {
	    key: "updateLayout",
	    value: function updateLayout() {
	      var _this2 = this;

	      this.getTitle().innerText = this.title;
	      var imageNode = this.getImage().querySelector('.rpa-tile-item-image');

	      if (!imageNode) {
	        return;
	      }

	      imageNode.classList.forEach(function (className) {
	        if (className.match('rpa-tile-item-icon-')) {
	          imageNode.classList.remove(className);
	        }

	        imageNode.classList.add('rpa-tile-item-icon-' + main_core.Text.encode(_this2.image));
	      });
	    }
	  }, {
	    key: "onClick",
	    value: function onClick() {
	      if (this.isNew()) {
	        if (this.gridTile.options.isCreateTypeRestricted) {
	          rpa_manager.Manager.Instance.showFeatureSlider();
	          return;
	        }

	        this.openSettings().then(function (slider) {
	          var sliderData = slider.getData();
	          var response = sliderData.get('response');

	          if (response && response.status === 'success') {
	            rpa_manager.Manager.Instance.openKanban(response.data.type.id);
	          } else {
	            var data = sliderData.get('type');

	            if (main_core.Type.isPlainObject(data) && data.typeId && data.typeId > 0) {
	              //this.gridTile.appendItem(data);
	              main_core.ajax.runAction('rpa.type.delete', {
	                data: {
	                  id: data.typeId
	                }
	              });
	            }
	          }
	        });
	      } else {
	        this.goToList();
	      }
	    }
	  }, {
	    key: "showActions",
	    value: function showActions(event) {
	      event.preventDefault();
	      event.stopPropagation();
	      main_popup.PopupMenu.show({
	        id: this.id,
	        bindElement: this.getButton(),
	        items: this.getActions(),
	        offsetLeft: 0,
	        offsetTop: 0,
	        closeByEsc: true,
	        className: 'rpa-item-actions',
	        cacheable: false
	      });
	    }
	  }, {
	    key: "closeActions",
	    value: function closeActions() {
	      main_popup.PopupMenu.destroy(this.id);
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this3 = this;

	      var self = this;
	      var actions = [{
	        text: main_core.Loc.getMessage('RPA_COMMON_LIST'),
	        onclick: function onclick() {
	          self.goToList();

	          _this3.closeActions();
	        }
	      }, {
	        text: main_core.Loc.getMessage('RPA_COMMON_ACTION_SETTINGS'),
	        onclick: function onclick() {
	          _this3.closeActions();

	          if (_this3.isSettingsRestricted) {
	            rpa_manager.Manager.Instance.showFeatureSlider();
	            return;
	          }

	          _this3.openSettings().then(function (slider) {
	            if (!slider) {
	              return;
	            }

	            var response = slider.getData().get('response');

	            if (response && response.data && main_core.Type.isPlainObject(response.data.type)) {
	              _this3.image = response.data.type.image;
	              _this3.title = response.data.type.title;

	              _this3.updateLayout();
	            }
	          });
	        }
	      }, {
	        text: main_core.Loc.getMessage('RPA_COMMON_STAGES'),
	        onclick: function onclick() {
	          if (_this3.isSettingsRestricted) {
	            rpa_manager.Manager.Instance.showFeatureSlider();
	          } else {
	            rpa_manager.Manager.Instance.openStageList(_this3.typeId);
	          }
	        }
	      }, {
	        text: main_core.Loc.getMessage('RPA_COMMON_FIELDS_SETTINGS'),
	        onclick: function onclick() {
	          if (_this3.isSettingsRestricted) {
	            rpa_manager.Manager.Instance.showFeatureSlider();
	          } else {
	            rpa_manager.Manager.Instance.openFieldsList(_this3.typeId);
	          }
	        }
	      }];

	      if (this.canDelete) {
	        actions.push({
	          text: main_core.Loc.getMessage('RPA_COMMON_ACTION_DELETE'),
	          onclick: function onclick() {
	            _this3.closeActions();

	            if (_this3.gridTile.getLoader().isShown()) {
	              return;
	            }

	            ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('RPA_PANEL_DELETE_CONFIRM_TEXT'), main_core.Loc.getMessage('RPA_PANEL_DELETE_CONFIRM_TITLE'), function () {
	              return self.delete();
	            });
	          }
	        });
	      }

	      return actions;
	    }
	  }, {
	    key: "openSettings",
	    value: function openSettings() {
	      if (this.isNew()) {
	        return rpa_manager.Manager.Instance.openTypeDetail(0, {
	          allowChangeHistory: false
	        });
	      }

	      return rpa_manager.Manager.Instance.openTypeDetail(this.typeId);
	    }
	  }, {
	    key: "openStages",
	    value: function openStages() {
	      return rpa_manager.Manager.Instance.openStageList(this.typeId);
	    }
	  }, {
	    key: "delete",
	    value: function _delete() {
	      var _this4 = this;

	      return new Promise(function (resolve) {
	        if (_this4.gridTile.getLoader().isShown()) {
	          resolve();
	        }

	        _this4.gridTile.getLoader().show();

	        main_core.ajax.runAction('rpa.type.delete', {
	          analyticsLabel: 'rpaPanelDeleteType',
	          data: {
	            id: _this4.typeId
	          }
	        }).then(function (response) {
	          _this4.gridTile.getLoader().hide();

	          _this4.gridTile.removeItem(_this4);

	          resolve();
	        }).catch(function (response) {
	          _this4.gridTile.getLoader().hide();

	          var message = '';
	          response.errors.forEach(function (error) {
	            message += error.message;
	          });
	          PanelItem.showError(message);
	          resolve();
	        });
	      });
	    }
	  }, {
	    key: "goToList",
	    value: function goToList() {
	      if (this.listUrl) {
	        location.href = this.listUrl;
	      }
	    }
	  }], [{
	    key: "getErrorNode",
	    value: function getErrorNode() {
	      return document.getElementById('rpa-panel-error-container');
	    }
	  }, {
	    key: "showError",
	    value: function showError(message) {
	      PanelItem.getErrorNode().innerText = message;

	      if (message.length > 0) {
	        PanelItem.getErrorNode().parentNode.style.display = 'block';
	      } else {
	        PanelItem.getErrorNode().parentNode.style.display = 'none';
	      }
	    }
	  }]);
	  return PanelItem;
	}(BX.TileGrid.Item);

	namespace.PanelItem = PanelItem;

}((this.window = this.window || {}),BX,BX.Rpa,BX.Main,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map
