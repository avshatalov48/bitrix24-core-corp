/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_popup,main_core_events) {
	'use strict';

	var UI = /*#__PURE__*/function () {
	  function UI() {
	    babelHelpers.classCallCheck(this, UI);
	  }
	  babelHelpers.createClass(UI, null, [{
	    key: "bindSimpleMenu",
	    /**
	     * Binds Menu (simple structure) to the element.
	     * @param {SimpleMenuOptions} options
	     */
	    value: function bindSimpleMenu(options) {
	      var bindElement = options.bindElement,
	        items = options.items,
	        actionHandler = options.actionHandler,
	        currentValue = options.currentValue;
	      var menuItems = [];
	      var menuId = options.menuId || 'menu-' + Math.random();
	      var currentActiveNode = null;
	      if (UI.simpleMenu[menuId]) {
	        return;
	      }

	      // build from plain array
	      if (main_core.Type.isArray(items)) {
	        items.map(function (item) {
	          menuItems.push({
	            text: item,
	            className: currentValue === parseInt(item) ? 'sign-popupmenu-item --sign-popupmenu-active' : 'sign-popupmenu-item',
	            onclick: function onclick(ev) {
	              actionHandler(item);
	              main_popup.MenuManager.getMenuById(menuId).close();
	              main_core_events.EventEmitter.emit(BX.findParent(bindElement, {
	                className: 'sign-document__block-wrapper'
	              }), 'BX.Sign:setFontSize', {
	                menuId: menuId,
	                fontSize: parseInt(item)
	              });
	              var popupNode = UI.simpleMenu[menuId].getPopupWindow().getPopupContainer();
	              if (popupNode) {
	                var activeItemNode = popupNode.querySelector('.--sign-popupmenu-active');
	                if (activeItemNode) {
	                  activeItemNode.classList.remove('--sign-popupmenu-active');
	                }
	              }
	              //
	              var currentNode = ev.target.closest('.sign-popupmenu-item');
	              //
	              if (currentNode) {
	                currentActiveNode = currentNode;
	                currentActiveNode.classList.add('--sign-popupmenu-active');
	              }
	            }
	          });
	        });
	      }
	      // or Object
	      else {
	        babelHelpers.toConsumableArray(Object.keys(items)).map(function (key) {
	          menuItems.push({
	            html: items[key],
	            onclick: function onclick() {
	              actionHandler(key);
	              main_popup.MenuManager.getMenuById(menuId).close();
	            }
	          });
	        });
	      }
	      var documentContainer = document.body.querySelector('[data-role="sign-editor__content"]');
	      var adjustMenu = function adjustMenu() {
	        UI.simpleMenu[menuId].close();
	      };
	      UI.simpleMenu[menuId] = main_popup.MenuManager.create({
	        id: menuId,
	        bindElement: bindElement,
	        items: menuItems,
	        autoHide: true,
	        offsetLeft: -35,
	        events: {
	          onClose: function onClose() {
	            if (documentContainer) {
	              main_core.Event.unbind(documentContainer, 'scroll', adjustMenu);
	            }
	            main_core_events.EventEmitter.unsubscribe('BX.Sign:resizeStart', adjustMenu);
	            main_core_events.EventEmitter.unsubscribe('BX.Sign:moveStart', adjustMenu);
	          },
	          onShow: function onShow() {
	            if (documentContainer) {
	              main_core.Event.bind(documentContainer, 'scroll', adjustMenu);
	            }
	            main_core_events.EventEmitter.subscribe('BX.Sign:moveStart', adjustMenu);
	            main_core_events.EventEmitter.subscribe('BX.Sign:resizeStart', adjustMenu);
	          },
	          onPopupShow: function onPopupShow() {
	            if (!currentActiveNode) {
	              var popupNode = UI.simpleMenu[menuId].getPopupWindow().getPopupContainer();
	              currentActiveNode = popupNode.querySelector('.--sign-popupmenu-active');
	            }
	            if (currentActiveNode) {
	              var menuNode = UI.simpleMenu[menuId].getPopupWindow().getContentContainer();
	              var menuHeight = menuNode.offsetHeight;
	              var scrollOffset = currentActiveNode.offsetTop;
	              scrollOffset = scrollOffset + currentActiveNode.offsetHeight / 2 - menuHeight / 2;
	              menuNode.scrollTop = scrollOffset;
	            }
	          }
	        }
	      });
	      main_core.Event.bind(bindElement, 'click', function () {
	        var pos = bindElement.getBoundingClientRect();
	        var fixHeight = 233;
	        var maxHeight = window.innerHeight - pos.top - pos.height * 2;
	        UI.simpleMenu[menuId].popupWindow.setMaxHeight(maxHeight < fixHeight ? maxHeight : fixHeight);
	        UI.simpleMenu[menuId].show();
	      });
	    }
	  }]);
	  return UI;
	}();
	babelHelpers.defineProperty(UI, "simpleMenu", {});

	exports.UI = UI;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX.Main,BX.Event));
//# sourceMappingURL=index.bundle.js.map
