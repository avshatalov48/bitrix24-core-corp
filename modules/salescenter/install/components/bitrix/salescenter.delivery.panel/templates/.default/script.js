/* eslint-disable */
(function (exports) {
	'use strict';

	(function () {

	  BX.namespace('BX.SaleCenterDelivery');
	  BX.SaleCenterDelivery = {
	    delivery: null,
	    signedParameters: null,
	    init: function init(config) {
	      this.deliveryParams = config.deliveryParams;
	      this.signedParameters = config.signedParameters;
	      this.delivery = new BX.TileGrid.Grid(this.deliveryParams);
	      this.delivery.draw();
	    },
	    reloadSlider: function reloadSlider(responseData) {
	      if (responseData.deliveryPanelParams) {
	        this.delivery.redraw(responseData.deliveryPanelParams);
	      }
	    }
	  };

	  /**
	   *
	   * @param options
	   * @extends {BX.TileGrid.Item}
	   * @constructor
	   */
	  BX.SaleCenterDelivery.TileGrid = function (options) {
	    BX.TileGrid.Item.apply(this, arguments);
	    this.title = options.title;
	    this.image = options.image;
	    this.itemSelected = options.itemSelected;
	    this.itemSelectedColor = options.itemSelectedColor;
	    this.itemSelectedImage = options.itemSelectedImage;
	    this.outerImage = options.outerImage || false;
	    this.layout = {
	      container: null,
	      image: null,
	      title: null,
	      clipTitle: null,
	      company: null,
	      controls: null,
	      buttonAction: null,
	      price: null
	    };
	    this.data = options.data || {};
	  };
	  BX.SaleCenterDelivery.TileGrid.prototype = {
	    __proto__: BX.TileGrid.Item.prototype,
	    constructor: BX.TileGrid.Item,
	    getContent: function getContent() {
	      if (!this.layout.wrapper) {
	        this.layout.wrapper = BX.create('div', {
	          props: {
	            className: 'salescenter-delivery-item'
	          },
	          children: [BX.create('div', {
	            props: {
	              className: 'salescenter-delivery-item-content'
	            },
	            children: [this.getImage(), this.getTitle(), this.getStatus()]
	          })],
	          events: {
	            click: function () {
	              this.onClick();
	            }.bind(this)
	          }
	        });
	      }
	      if (this.itemSelected || this.data.type === 'actionbox') {
	        this.setSelected();
	      }
	      return this.layout.wrapper;
	    },
	    getImage: function getImage() {
	      if (!this.layout.image) {
	        var className = this.data.hasOwnProperty('hasOwnIcon') && this.data.hasOwnIcon ? 'salescenter-delivery-marketplace-item-image' : 'salescenter-delivery-item-image';
	        var logo = BX.create('div', {
	          props: {
	            className: className
	          },
	          style: {
	            backgroundSize: this.outerImage ? '50px' : '',
	            backgroundImage: this.image ? 'url(' + encodeURI(this.image) + ')' : null
	          }
	        });
	        this.layout.image = logo;
	      }
	      return this.layout.image;
	    },
	    getStatus: function getStatus() {
	      if (!this.itemSelected) return;
	      this.layout.itemSelected = BX.create('div', {
	        props: {
	          className: 'salescenter-delivery-item-status-selected'
	        }
	      });
	      return this.layout.itemSelected;
	    },
	    setSelected: function setSelected() {
	      BX.addClass(this.layout.wrapper, 'salescenter-delivery-item-selected');
	      if (this.itemSelectedImage) {
	        this.layout.image.style.backgroundImage = 'url(' + this.itemSelectedImage + ')';
	      }
	      if (this.itemSelectedColor) {
	        this.layout.wrapper.style.backgroundColor = this.itemSelectedColor;
	      }
	    },
	    setUnselected: function setUnselected() {
	      if (this.itemSelected) {
	        return;
	      }
	      BX.removeClass(this.layout.wrapper, 'salescenter-delivery-item-selected');
	      if (this.image) {
	        this.layout.image.style.backgroundImage = 'url(' + this.image + ')';
	      }
	      this.layout.wrapper.style.backgroundColor = '';
	      var itemSelected = content.querySelector('.salescenter-delivery-item-status-selected');
	      if (itemSelected) {
	        itemSelected.parentNode.removeChild(itemSelected);
	      }
	    },
	    getTitle: function getTitle() {
	      if (!this.layout.title) {
	        this.layout.title = BX.create('div', {
	          props: {
	            className: this.data.type === 'marketplaceApp' ? 'salescenter-delivery-marketplace-app-item-title' : 'salescenter-delivery-item-title'
	          },
	          text: this.title
	        });
	      }
	      return this.layout.title;
	    },
	    openRestAppLayout: function openRestAppLayout(applicationId, appCode) {
	      BX.ajax.runComponentAction("bitrix:salescenter.delivery.panel", "getRestApp", {
	        data: {
	          code: appCode
	        }
	      }).then(function (response) {
	        var app = response.data;
	        if (app.TYPE === "A") {
	          this.showRestApplication(appCode);
	        } else {
	          BX.rest.AppLayout.openApplication(applicationId);
	        }
	      }.bind(this))["catch"](function (response) {
	        this.restAppErrorPopup(" ", response.errors.pop().message);
	      }.bind(this));
	    },
	    restAppErrorPopup: function restAppErrorPopup(title, text) {
	      BX.UI.Dialogs.MessageBox.alert(text, title, function (messageBox) {
	        return messageBox.close();
	      }, BX.Loc.getMessage('SDP_SALESCENTER_JS_POPUP_CLOSE'));
	    },
	    onClick: function onClick() {
	      var _this = this;
	      if (this.data.type === "delivery") {
	        var sliderOptions = {
	          allowChangeHistory: false,
	          events: {
	            onLoad: function (e) {
	              var slider = e.getSlider();
	              if (slider.isOpen() && slider.url.indexOf('CREATE') > -1) {
	                this.prepareDeliveryForm(slider);
	              } else {
	                var url = this.data.connectPath;
	                //this.setDeliveryListAddButton(slider, url);
	              }
	            }.bind(this),
	            onClose: function (e) {
	              var slider = e.getSlider();
	              this.setDeliveryItemHandler(slider);
	            }.bind(this),
	            onDestroy: function (e) {
	              var slider = e.getSlider();
	              this.setDeliveryItemHandler(slider);
	            }.bind(this)
	          }
	        };
	        if (!this.itemSelected && !this.data.showMenu) {
	          BX.Salescenter.Manager.openSlider(this.data.connectPath, {
	            width: 835
	          }).then(function () {
	            _this.reload(BX.SaleCenterDelivery.signedParameters);
	          });
	        } else {
	          this.showItemMenu(this, {
	            sliderOptions: sliderOptions
	          });
	        }
	      } else if (this.data.type === "marketplaceApp") {
	        if (this.itemSelected) {
	          this.openRestAppLayout(this.id, this.data.code);
	        } else {
	          this.showRestApplication(this.data.code);
	        }
	      } else if (this.data.type === 'recommend') {
	        BX.SidePanel.Instance.open(this.data.connectPath, {
	          width: 735
	        });
	      } else if (this.data.type === 'actionbox') {
	        if (this.data.handler === 'anchor') {
	          window.open(this.data.move);
	        } else if (this.data.handler === 'marketplace') {
	          BX.rest.Marketplace.open({
	            PLACEMENT: this.data.move
	          });
	        } else if (this.data.handler === 'landing') {
	          var dataMove = this.data.move;
	          BX.SidePanel.Instance.open('salecenter', {
	            contentCallback: function contentCallback() {
	              return "<iframe src='" + dataMove + "'" + " style='width: 100%; height:" + " -webkit-calc(100vh - 20px); height:" + " calc(100vh - 20px);'></iframe>";
	            }
	          });
	        }
	      }
	    },
	    showItemMenu: function showItemMenu(item, options) {
	      var _this2 = this;
	      var menu = [],
	        menuItemIndex,
	        itemNode = item.layout.container,
	        menuitemId = 'salescenter-item-menu-' + BX.util.getRandomString(),
	        filter;
	      item.sliderOptions = {};
	      if (options.sliderOptions) {
	        item.sliderOptions = options.sliderOptions;
	      }
	      for (menuItemIndex in item.data.menuItems) {
	        if (item.data.menuItems.hasOwnProperty(menuItemIndex)) {
	          if (item.data.menuItems[menuItemIndex].DELIMITER) {
	            menu.push({
	              delimiter: true
	            });
	          } else if (item.data.menuItems[menuItemIndex].FILTER) {
	            filter = item.data.menuItems[menuItemIndex].FILTER;
	            menu.push({
	              text: item.data.menuItems[menuItemIndex].NAME,
	              link: item.data.menuItems[menuItemIndex].LINK,
	              onclick: function onclick(e, tile) {
	                item.moreTabsMenu.close();
	                BX.ajax.runComponentAction('bitrix:salescenter.delivery.panel', 'setDeliveryListFilter', {
	                  mode: 'class',
	                  data: {
	                    filter: filter
	                  }
	                }).then(function (response) {
	                  BX.SidePanel.Instance.open(tile.options.link, item.sliderOptions);
	                });
	              }
	            });
	          } else {
	            menu.push({
	              text: item.data.menuItems[menuItemIndex].NAME,
	              link: item.data.menuItems[menuItemIndex].LINK,
	              onclick: function onclick(e, tile) {
	                item.moreTabsMenu.close();
	                BX.Salescenter.Manager.openSlider(tile.options.link, {
	                  width: 835
	                }).then(function () {
	                  _this2.reload(BX.SaleCenterDelivery.signedParameters);
	                });
	              }
	            });
	          }
	        }
	      }
	      item.moreTabsMenu = BX.PopupMenu.create(menuitemId, itemNode, menu, {
	        autoHide: true,
	        offsetLeft: 0,
	        offsetTop: 0,
	        closeByEsc: true,
	        events: {
	          onPopupClose: function onPopupClose() {
	            item.moreTabsMenu.popupWindow.destroy();
	            BX.PopupMenu.destroy(menuitemId);
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            item.moreTabsMenu = null;
	          }
	        }
	      });
	      item.moreTabsMenu.popupWindow.show();
	    },
	    prepareDeliveryForm: function prepareDeliveryForm(slider) {
	      var sliderIframe, innerDoc, deliveryName, deliveryStores;
	      sliderIframe = slider.iframe;
	      innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;
	      deliveryName = innerDoc.getElementsByName('NAME')[0];
	      if (deliveryName) {
	        deliveryName.value = this.title;
	      }
	      if (this.id === 'pickup') {
	        deliveryStores = innerDoc.getElementsByName('STORES_SHOW')[0];
	        if (deliveryStores) {
	          deliveryStores.checked = true;
	          var eventChange = new Event('change');
	          deliveryStores.dispatchEvent(eventChange);
	        }
	      }
	    },
	    setDeliveryListAddButton: function setDeliveryListAddButton(slider, url) {
	      var sliderIframe, innerDoc, addButtonWrapper, addButtonMenu, addButton, addButtonText;
	      sliderIframe = slider.iframe;
	      innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;
	      addButtonWrapper = innerDoc.getElementsByClassName('ui-btn-split ui-btn-primary');
	      if (addButtonWrapper) {
	        addButtonMenu = addButtonWrapper[0].getElementsByClassName('ui-btn-main')[0];
	        if (addButtonMenu) {
	          addButtonText = addButtonMenu.innerText;
	          addButton = BX.create('a', {
	            props: {
	              className: 'ui-btn-main',
	              href: url
	            },
	            text: addButtonText
	          });
	          addButtonWrapper[0].replaceChild(addButton, addButtonMenu);
	        }
	      }
	    },
	    setDeliveryItemHandler: function setDeliveryItemHandler(slider) {
	      var sliderIframe, innerDoc, className, serviceType, url;
	      sliderIframe = slider.iframe;
	      innerDoc = sliderIframe.contentDocument || sliderIframe.contentWindow.document;
	      url = new URL(window.location.href + slider.url);
	      className = url.searchParams.get("CLASS_NAME");
	      serviceType = url.searchParams.get("SERVICE_TYPE");
	      this.reloadDeliveryItem(className, serviceType);
	    },
	    reloadDeliveryItem: function reloadDeliveryItem(className, serviceType) {
	      var self = this;
	      BX.ajax.runComponentAction('bitrix:salescenter.control_panel', 'reloadDeliveryItem', {
	        mode: 'class',
	        data: {
	          className: className,
	          serviceType: serviceType
	        }
	      }).then(function (response) {
	        if (response.data.menuItems && response.data.menuItems.length > 0) {
	          self.itemSelected = response.data.itemSelected;
	          if (self.itemSelected) {
	            self.setSelected();
	          } else {
	            self.setUnselected();
	          }
	          self.data.menuItems = response.data.menuItems;
	          self.data.showMenu = response.data.showMenu;
	        }
	      });
	    },
	    showRestApplication: function showRestApplication(appCode) {
	      var applicationUrlTemplate = "/marketplace/detail/#app#/";
	      var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(appCode));
	      BX.SidePanel.Instance.open(url, {
	        allowChangeHistory: false,
	        events: {
	          onClose: this.reload.bind(this, BX.SaleCenterDelivery.signedParameters)
	        }
	      });
	    },
	    reload: function reload(signedParameters) {
	      BX.ajax.runComponentAction("bitrix:salescenter.delivery.panel", "getComponentResult", {
	        mode: "ajax",
	        data: {
	          signedParameters: signedParameters
	        }
	      }).then(function (response) {
	        BX.SaleCenterDelivery.reloadSlider(response.data);
	      });
	    }
	  };
	})();

}((this.window = this.window || {})));
//# sourceMappingURL=script.js.map
