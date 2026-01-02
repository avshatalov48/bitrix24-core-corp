/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
    'use strict';

    /**
     * @abstract
     * @memberOf BX.Crm
     */
    class Model {
      constructor(data, params) {
        this.deleted = false;
        this.progress = false;
        this.data = {};
        if (main_core.Type.isPlainObject(data)) {
          this.data = data;
        }
        this.getParameters = {
          add: {},
          get: {},
          update: {},
          delete: {}
        };
        if (main_core.Type.isPlainObject(params)) {
          this.getParameters = params.getParameters;
        }
      }
      get actions() {
        return {
          get: this.compileActionString('get'),
          add: this.compileActionString('add'),
          update: this.compileActionString('update'),
          delete: this.compileActionString('delete')
        };
      }

      /**
       * @protected
       * @param action
       */
      compileActionString(action) {
        return 'crm.api.' + this.getModelName() + '.' + action;
      }
      getId() {
        const id = this.data.id;
        return main_core.Type.isNil(id) ? null : main_core.Text.toInteger(id);
      }
      getEntityTypeId() {
        return this.data.entityTypeId;
      }
      isSaved() {
        return this.getId() > 0;
      }
      isDeleted() {
        return this.deleted;
      }
      setData(data) {
        this.data = data;
        return this;
      }
      getData() {
        return this.data;
      }
      setGetParameters(action, parameters) {
        this.getParameters[action] = parameters;
      }
      getGetParameters(action) {
        return {
          ...{
            analyticsLabel: 'crmModel' + this.getModelName() + action
          },
          ...this.getParameters[action]
        };
      }

      /**
       * @abstract
       */
      getModelName() {
        throw new Error('Method "getModelName" should be overridden');
      }
      setDataFromResponse(response) {
        this.setData(response.data[this.getModelName()]);
      }
      load() {
        return new Promise((resolve, reject) => {
          const errors = [];
          if (this.progress) {
            errors.push('Another action is in progress');
            reject(errors);
            return;
          }
          if (!this.isSaved()) {
            errors.push('Cant load ' + this.getModelName() + ' without id');
            reject(errors);
            return;
          }
          const action = this.actions.get;
          if (!main_core.Type.isString(action) || action.length <= 0) {
            errors.push('Load action is not specified');
            reject(errors);
            return;
          }
          this.progress = true;
          main_core.ajax.runAction(action, {
            data: {
              id: this.getId()
            },
            getParameters: this.getGetParameters('get')
          }).then(response => {
            this.progress = false;
            this.setDataFromResponse(response);
            resolve(response);
          }).catch(response => {
            this.progress = false;
            response.errors.forEach(({
              message
            }) => {
              errors.push(message);
            });
            reject(errors);
          });
        });
      }
      save() {
        return new Promise((resolve, reject) => {
          let errors = [];
          if (this.progress) {
            errors.push('Another action is in progress');
            reject(errors);
            return;
          }
          let action;
          let data;
          let getParameters;
          if (this.isSaved()) {
            action = this.actions.update;
            data = {
              id: this.getId(),
              fields: this.getData()
            };
            getParameters = this.getGetParameters('update');
          } else {
            action = this.actions.add;
            data = {
              fields: this.getData()
            };
            getParameters = this.getGetParameters('add');
          }
          if (!main_core.Type.isString(action) || action.length <= 0) {
            errors.push('Save action is not specified');
            reject(errors);
            return;
          }
          this.progress = true;
          main_core.ajax.runAction(action, {
            data,
            getParameters
          }).then(response => {
            this.progress = false;
            this.setDataFromResponse(response);
            resolve(response);
          }).catch(response => {
            this.progress = false;
            errors = [...errors, ...this.extractErrorMessages(response)];
            reject(errors);
          });
        });
      }

      /**
       * @protected
       * @param errors
       */
      extractErrorMessages({
        errors
      }) {
        const errorMessages = [];
        errors.forEach(({
          message
        }) => {
          if (main_core.Type.isPlainObject(message) && message.text) {
            errorMessages.push(message.text);
          } else {
            errorMessages.push(message);
          }
        });
        return errorMessages;
      }
      delete() {
        return new Promise((resolve, reject) => {
          const errors = [];
          if (this.progress) {
            errors.push('Another action is in progress');
            reject(errors);
            return;
          }
          if (!this.isSaved()) {
            errors.push('Cant delete ' + this.getModelName() + ' without id');
            reject(errors);
            return;
          }
          const action = this.actions.delete;
          if (!main_core.Type.isString(action) || action.length <= 0) {
            errors.push('Delete action is not specified');
            reject(errors);
            return;
          }
          this.progress = true;
          main_core.ajax.runAction(action, {
            data: {
              id: this.getId()
            },
            getParameters: this.getGetParameters('delete')
          }).then(response => {
            this.deleted = true;
            this.progress = false;
            resolve(response);
          }).catch(response => {
            this.progress = false;
            response.errors.forEach(({
              message
            }) => {
              errors.push(message);
            });
            reject(errors);
          });
        });
      }
    }

    exports.Model = Model;

}((this.BX.Crm = this.BX.Crm || {}),BX));
//# sourceMappingURL=model.bundle.js.map
