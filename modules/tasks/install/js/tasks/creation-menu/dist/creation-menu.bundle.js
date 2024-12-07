/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_popup,ui_analytics,main_core) {
	'use strict';

	var Delimiter = /*#__PURE__*/function () {
	  function Delimiter() {
	    babelHelpers.classCallCheck(this, Delimiter);
	  }
	  babelHelpers.createClass(Delimiter, null, [{
	    key: "create",
	    value: function create() {
	      return {
	        tabId: CreationMenu.MENU_ID,
	        delimiter: true
	      };
	    }
	  }]);
	  return Delimiter;
	}();

	var Task = /*#__PURE__*/function () {
	  function Task() {
	    babelHelpers.classCallCheck(this, Task);
	  }
	  babelHelpers.createClass(Task, null, [{
	    key: "create",
	    value: function create() {
	      var link = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      return {
	        tabId: CreationMenu.MENU_ID,
	        text: main_core.Loc.getMessage('TASKS_CREATION_MENU_CREATE_TASK'),
	        href: link,
	        onclick: function onclick(event, menuItem) {
	          menuItem.getMenuWindow().close();
	        }
	      };
	    }
	  }]);
	  return Task;
	}();

	var Loading = /*#__PURE__*/function () {
	  function Loading() {
	    babelHelpers.classCallCheck(this, Loading);
	  }
	  babelHelpers.createClass(Loading, null, [{
	    key: "create",
	    value: function create() {
	      return {
	        id: Loading.ID,
	        text: main_core.Loc.getMessage('TASKS_CREATION_MENU_LOAD_TEMPLATE_LIST')
	      };
	    }
	  }]);
	  return Loading;
	}();
	babelHelpers.defineProperty(Loading, "ID", 'loading');

	var TaskByTemplate = /*#__PURE__*/function () {
	  function TaskByTemplate() {
	    babelHelpers.classCallCheck(this, TaskByTemplate);
	  }
	  babelHelpers.createClass(TaskByTemplate, [{
	    key: "getTemplates",
	    value: function getTemplates() {
	      return main_core.ajax.runComponentAction('bitrix:tasks.templates.list', 'getList', {
	        mode: 'class',
	        data: {
	          select: ['ID', 'TITLE'],
	          order: {
	            ID: 'DESC'
	          },
	          filter: {
	            ZOMBIE: 'N'
	          }
	        }
	      });
	    }
	  }, {
	    key: "addSubItems",
	    value: function addSubItems(menuItem, response, link) {
	      if (response.data.length > 0) {
	        response.data.forEach(function (item) {
	          menuItem.getSubMenu().addMenuItem({
	            text: BX.util.htmlspecialchars(item.TITLE),
	            href: link + '&TEMPLATE=' + item.ID,
	            onclick: function onclick() {
	              menuItem.getMenuWindow().close();
	            }
	          });
	        });
	      } else {
	        menuItem.getSubMenu().addMenuItem({
	          text: main_core.Loc.getMessage('TASKS_CREATION_MENU_EMPTY_TEMPLATE_LIST')
	        });
	      }
	      this.removeLoading(menuItem);
	    }
	  }, {
	    key: "addError",
	    value: function addError(menuItem) {
	      menuItem.getSubMenu().addMenuItem({
	        text: main_core.Loc.getMessage('TASKS_CREATION_MENU_ERROR_LOAD_TEMPLATE_LIST')
	      });
	      this.removeLoading(menuItem);
	    }
	  }, {
	    key: "removeLoading",
	    value: function removeLoading(menuItem) {
	      menuItem.getSubMenu().removeMenuItem(Loading.ID);
	    }
	  }], [{
	    key: "create",
	    value: function create() {
	      var _this = this;
	      var link = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      return {
	        tabId: CreationMenu.MENU_ID,
	        text: main_core.Loc.getMessage('TASKS_CREATION_MENU_CREATE_TASK_BY_TEMPLATE'),
	        cacheable: true,
	        items: [Loading.create()],
	        events: {
	          onSubMenuShow: function onSubMenuShow(event) {
	            var item = new TaskByTemplate();
	            item.getTemplates().then(function (response) {
	              if (_this.isTemplateListLoaded) {
	                return;
	              }
	              _this.isTemplateListLoaded = true;
	              item.addSubItems(event.getTarget(), response, link);
	            }, function () {
	              _this.isTemplateListLoaded = true;
	              item.addError(event.getTarget());
	            });
	          }
	        }
	      };
	    }
	  }]);
	  return TaskByTemplate;
	}();

	var TemplateList = /*#__PURE__*/function () {
	  function TemplateList() {
	    babelHelpers.classCallCheck(this, TemplateList);
	  }
	  babelHelpers.createClass(TemplateList, null, [{
	    key: "create",
	    value: function create() {
	      var link = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      return {
	        tabId: CreationMenu.MENU_ID,
	        text: main_core.Loc.getMessage('TASKS_CREATION_MENU_TEMPLATE_LIST'),
	        href: link,
	        target: '_top'
	      };
	    }
	  }]);
	  return TemplateList;
	}();

	var CreationMenu = /*#__PURE__*/function () {
	  babelHelpers.createClass(CreationMenu, null, [{
	    key: "toggle",
	    value: function toggle(options) {
	      var creationMenu = main_popup.MenuManager.getMenuById(CreationMenu.MENU_ID);
	      if (creationMenu) {
	        creationMenu.toggle();
	      } else {
	        new this(options).createMenu().toggle();
	      }
	    }
	  }]);
	  function CreationMenu(options) {
	    babelHelpers.classCallCheck(this, CreationMenu);
	    this.bindElement = options.bindElement;
	    this.createTaskLink = options.createTaskLink;
	    this.templatesListLink = options.templatesListLink;
	  }
	  babelHelpers.createClass(CreationMenu, [{
	    key: "createMenu",
	    value: function createMenu() {
	      this.menu = main_popup.MenuManager.create({
	        id: CreationMenu.MENU_ID,
	        bindElement: this.bindElement,
	        closeByEsc: true,
	        items: this.getCreationItems()
	      });
	      return this.menu;
	    }
	  }, {
	    key: "getCreationItems",
	    value: function getCreationItems() {
	      var createLink = main_core.Uri.addParam(this.createTaskLink, {
	        ta_sec: 'space',
	        ta_el: 'create_button'
	      });
	      return [Task.create(createLink), TaskByTemplate.create(createLink), Delimiter.create(), TemplateList.create(this.templatesListLink)];
	    }
	  }]);
	  return CreationMenu;
	}();
	babelHelpers.defineProperty(CreationMenu, "MENU_ID", 'tasks-creation-menu');

	exports.CreationMenu = CreationMenu;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.Main,BX.UI.Analytics,BX));
//# sourceMappingURL=creation-menu.bundle.js.map
