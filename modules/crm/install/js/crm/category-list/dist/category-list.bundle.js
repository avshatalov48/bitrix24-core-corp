this.BX = this.BX || {};
(function (exports,main_core,crm_categoryModel) {
	'use strict';

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var instance = null;
	/**
	 * @memberOf BX.Crm
	 */

	var _items = new WeakMap();

	var _isProgress = new WeakMap();

	var _loadItems = new WeakSet();

	var CategoryList = /*#__PURE__*/function () {
	  function CategoryList() {
	    babelHelpers.classCallCheck(this, CategoryList);

	    _loadItems.add(this);

	    _items.set(this, {
	      writable: true,
	      value: {}
	    });

	    _isProgress.set(this, {
	      writable: true,
	      value: false
	    });
	  }

	  babelHelpers.createClass(CategoryList, [{
	    key: "getItems",
	    value: function getItems(entityTypeId) {
	      var _this = this;

	      return new Promise(function (resolve, reject) {
	        if (babelHelpers.classPrivateFieldGet(_this, _items).hasOwnProperty(entityTypeId)) {
	          resolve(babelHelpers.classPrivateFieldGet(_this, _items)[entityTypeId]);
	          return;
	        }

	        _classPrivateMethodGet(_this, _loadItems, _loadItems2).call(_this, entityTypeId).then(function (categories) {
	          babelHelpers.classPrivateFieldGet(_this, _items)[entityTypeId] = categories;
	          resolve(categories);
	        }).catch(function (error) {
	          babelHelpers.classPrivateFieldGet(_this, _items)[entityTypeId] = [];
	          reject(error);
	        });
	      });
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(entityTypeId, items) {
	      babelHelpers.classPrivateFieldGet(this, _items)[entityTypeId] = items;
	      return this;
	    }
	  }], [{
	    key: "Instance",
	    get: function get() {
	      if (window.top !== window && main_core.Reflection.getClass('top.BX.Crm.CategoryList')) {
	        return window.top.BX.Crm.CategoryList.Instance;
	      }

	      if (instance === null) {
	        instance = new CategoryList();
	      }

	      return instance;
	    }
	  }]);
	  return CategoryList;
	}();

	var _loadItems2 = function _loadItems2(entityTypeId) {
	  var _this2 = this;

	  return new Promise(function (resolve, reject) {
	    if (babelHelpers.classPrivateFieldGet(_this2, _isProgress)) {
	      reject('CategoryList is already loading');
	      return;
	    }

	    babelHelpers.classPrivateFieldSet(_this2, _isProgress, true);
	    main_core.ajax.runAction('crm.category.list', {
	      data: {
	        entityTypeId: entityTypeId
	      }
	    }).then(function (response) {
	      babelHelpers.classPrivateFieldSet(_this2, _isProgress, false);
	      var categories = [];
	      response.data.categories.forEach(function (category) {
	        categories.push(new crm_categoryModel.CategoryModel(category));
	      });
	      resolve(categories);
	    }).catch(function (response) {
	      babelHelpers.classPrivateFieldSet(_this2, _isProgress, false);
	      reject("CategoryList error: " + response.errors.map(function (_ref) {
	        var message = _ref.message;
	        return message;
	      }).join("; "));
	    });
	  });
	};

	exports.CategoryList = CategoryList;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Crm.Models));
//# sourceMappingURL=category-list.bundle.js.map
