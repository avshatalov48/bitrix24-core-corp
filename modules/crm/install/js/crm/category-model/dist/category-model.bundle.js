/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_model) {
    'use strict';

    /**
     * @memberOf BX.Crm.Models
     */
    class CategoryModel extends crm_model.Model {
      constructor(data, params) {
        super(data, params);
      }
      getModelName() {
        return 'category';
      }
      getName() {
        return this.data.name;
      }
      setName(name) {
        this.data.name = name;
      }
      getSort() {
        return this.data.sort;
      }
      setSort(sort) {
        this.data.sort = sort;
      }
      isDefault() {
        return this.data.isDefault;
      }
      setDefault(isDefault) {
        this.data.isDefault = isDefault;
      }
      getGetParameters(action) {
        return {
          ...super.getGetParameters(action),
          ...{
            entityTypeId: this.getEntityTypeId()
          }
        };
      }
    }

    exports.CategoryModel = CategoryModel;

}((this.BX.Crm.Models = this.BX.Crm.Models || {}),BX.Crm));
//# sourceMappingURL=category-model.bundle.js.map
