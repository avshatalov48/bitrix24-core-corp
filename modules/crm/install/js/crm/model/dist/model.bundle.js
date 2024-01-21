this.BX = this.BX || {};
(function (exports,main_core) {
    'use strict';

    function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
    function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
    /**
     * @abstract
     * @memberOf BX.Crm
     */
    var Model = /*#__PURE__*/function () {
      function Model(data, params) {
        babelHelpers.classCallCheck(this, Model);
        babelHelpers.defineProperty(this, "deleted", false);
        babelHelpers.defineProperty(this, "progress", false);
        this.data = {};
        if (main_core.Type.isPlainObject(data)) {
          this.data = data;
        }
        this.getParameters = {
          add: {},
          get: {},
          update: {},
          "delete": {}
        };
        if (main_core.Type.isPlainObject(params)) {
          this.getParameters = params.getParameters;
        }
      }
      babelHelpers.createClass(Model, [{
        key: "compileActionString",
        /**
         * @protected
         * @param action
         */
        value: function compileActionString(action) {
          return 'crm.api.' + this.getModelName() + '.' + action;
        }
      }, {
        key: "getId",
        value: function getId() {
          return this.data.id;
        }
      }, {
        key: "getEntityTypeId",
        value: function getEntityTypeId() {
          return this.data.entityTypeId;
        }
      }, {
        key: "isSaved",
        value: function isSaved() {
          return this.getId() > 0;
        }
      }, {
        key: "isDeleted",
        value: function isDeleted() {
          return this.deleted;
        }
      }, {
        key: "setData",
        value: function setData(data) {
          this.data = data;
          return this;
        }
      }, {
        key: "getData",
        value: function getData() {
          return this.data;
        }
      }, {
        key: "setGetParameters",
        value: function setGetParameters(action, parameters) {
          this.getParameters[action] = parameters;
        }
      }, {
        key: "getGetParameters",
        value: function getGetParameters(action) {
          return _objectSpread(_objectSpread({}, {
            analyticsLabel: 'crmModel' + this.getModelName() + action
          }), this.getParameters[action]);
        }
        /**
         * @abstract
         */
      }, {
        key: "getModelName",
        value: function getModelName() {
          throw new Error('Method "getModelName" should be overridden');
        }
      }, {
        key: "setDataFromResponse",
        value: function setDataFromResponse(response) {
          this.setData(response.data[this.getModelName()]);
        }
      }, {
        key: "load",
        value: function load() {
          var _this = this;
          return new Promise(function (resolve, reject) {
            var errors = [];
            if (_this.progress) {
              errors.push('Another action is in progress');
              reject(errors);
              return;
            }
            if (!_this.isSaved()) {
              errors.push('Cant load ' + _this.getModelName() + ' without id');
              reject(errors);
              return;
            }
            var action = _this.actions.get;
            if (!main_core.Type.isString(action) || action.length <= 0) {
              errors.push('Load action is not specified');
              reject(errors);
              return;
            }
            _this.progress = true;
            main_core.ajax.runAction(action, {
              data: {
                id: _this.getId()
              },
              getParameters: _this.getGetParameters('get')
            }).then(function (response) {
              _this.progress = false;
              _this.setDataFromResponse(response);
              resolve(response);
            })["catch"](function (response) {
              _this.progress = false;
              response.errors.forEach(function (_ref) {
                var message = _ref.message;
                errors.push(message);
              });
              reject(errors);
            });
          });
        }
      }, {
        key: "save",
        value: function save() {
          var _this2 = this;
          return new Promise(function (resolve, reject) {
            var errors = [];
            if (_this2.progress) {
              errors.push('Another action is in progress');
              reject(errors);
              return;
            }
            var action;
            var data;
            var getParameters;
            if (_this2.isSaved()) {
              action = _this2.actions.update;
              data = {
                id: _this2.getId(),
                fields: _this2.getData()
              };
              getParameters = _this2.getGetParameters('update');
            } else {
              action = _this2.actions.add;
              data = {
                fields: _this2.getData()
              };
              getParameters = _this2.getGetParameters('add');
            }
            if (!main_core.Type.isString(action) || action.length <= 0) {
              errors.push('Save action is not specified');
              reject(errors);
              return;
            }
            _this2.progress = true;
            main_core.ajax.runAction(action, {
              data: data,
              getParameters: getParameters
            }).then(function (response) {
              _this2.progress = false;
              _this2.setDataFromResponse(response);
              resolve(response);
            })["catch"](function (response) {
              _this2.progress = false;
              errors = [].concat(babelHelpers.toConsumableArray(errors), babelHelpers.toConsumableArray(_this2.extractErrorMessages(response)));
              reject(errors);
            });
          });
        }
        /**
         * @protected
         * @param errors
         */
      }, {
        key: "extractErrorMessages",
        value: function extractErrorMessages(_ref2) {
          var errors = _ref2.errors;
          var errorMessages = [];
          errors.forEach(function (_ref3) {
            var message = _ref3.message;
            if (main_core.Type.isPlainObject(message) && message.text) {
              errorMessages.push(message.text);
            } else {
              errorMessages.push(message);
            }
          });
          return errorMessages;
        }
      }, {
        key: "delete",
        value: function _delete() {
          var _this3 = this;
          return new Promise(function (resolve, reject) {
            var errors = [];
            if (_this3.progress) {
              errors.push('Another action is in progress');
              reject(errors);
              return;
            }
            if (!_this3.isSaved()) {
              errors.push('Cant delete ' + _this3.getModelName() + ' without id');
              reject(errors);
              return;
            }
            var action = _this3.actions["delete"];
            if (!main_core.Type.isString(action) || action.length <= 0) {
              errors.push('Delete action is not specified');
              reject(errors);
              return;
            }
            _this3.progress = true;
            main_core.ajax.runAction(action, {
              data: {
                id: _this3.getId()
              },
              getParameters: _this3.getGetParameters('delete')
            }).then(function (response) {
              _this3.deleted = true;
              _this3.progress = false;
              resolve(response);
            })["catch"](function (response) {
              _this3.progress = false;
              response.errors.forEach(function (_ref4) {
                var message = _ref4.message;
                errors.push(message);
              });
              reject(errors);
            });
          });
        }
      }, {
        key: "actions",
        get: function get() {
          return {
            get: this.compileActionString('get'),
            add: this.compileActionString('add'),
            update: this.compileActionString('update'),
            "delete": this.compileActionString('delete')
          };
        }
      }]);
      return Model;
    }();

    exports.Model = Model;

}((this.BX.Crm = this.BX.Crm || {}),BX));
//# sourceMappingURL=model.bundle.js.map
