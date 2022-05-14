this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_model) {
    'use strict';

    /**
     * @memberOf BX.Crm.Models
     */
    var CategoryModel = /*#__PURE__*/function (_Model) {
      babelHelpers.inherits(CategoryModel, _Model);

      function CategoryModel(data, params) {
        babelHelpers.classCallCheck(this, CategoryModel);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CategoryModel).call(this, data, params));
      }

      babelHelpers.createClass(CategoryModel, [{
        key: "getModelName",
        value: function getModelName() {
          return 'category';
        }
      }, {
        key: "getName",
        value: function getName() {
          return this.data.name;
        }
      }, {
        key: "setName",
        value: function setName(name) {
          this.data.name = name;
        }
      }, {
        key: "getSort",
        value: function getSort() {
          return this.data.sort;
        }
      }, {
        key: "setSort",
        value: function setSort(sort) {
          this.data.sort = sort;
        }
      }, {
        key: "isDefault",
        value: function isDefault() {
          return this.data.isDefault;
        }
      }, {
        key: "setDefault",
        value: function setDefault(isDefault) {
          this.data.isDefault = isDefault;
        }
      }, {
        key: "getGetParameters",
        value: function getGetParameters(action) {
          return babelHelpers.objectSpread({}, babelHelpers.get(babelHelpers.getPrototypeOf(CategoryModel.prototype), "getGetParameters", this).call(this, action), {
            entityTypeId: this.getEntityTypeId()
          });
        }
      }]);
      return CategoryModel;
    }(crm_model.Model);

    exports.CategoryModel = CategoryModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX.Crm));
//# sourceMappingURL=category-model.bundle.js.map
