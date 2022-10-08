(function (exports) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	(function () {

	  var namespace = BX.namespace('BX.Salescenter.DeliveryInstallation');

	  if (namespace.Wizard) {
	    return;
	  }
	  /**
	   * Wizard.
	   *
	   */


	  function Wizard() {}

	  Wizard.prototype.init = function (params) {
	    this.id = params.id;
	    this.code = params.code;
	    this.form = BX(params.formId);
	    this.saveButton = BX(params.saveButtonId);
	    this.confirmDeleteMessage = params.confirmDeleteMessage;
	    this.errorMessageNode = BX(params.errorMessageId);
	    this.buttonWaitClass = 'ui-btn-wait';
	    BX.bind(this.saveButton, 'click', this.onSave.bind(this));
	    BX.bind(this.form, 'submit', this.onSubmit.bind(this));
	  };

	  Wizard.prototype.onSave = function () {
	    if (BX.hasClass(this.saveButton, this.buttonWaitClass)) {
	      BX.removeClass(this.saveButton, this.buttonWaitClass);
	    }
	  };

	  Wizard.prototype["delete"] = function (event) {
	    event.preventDefault();
	    var deleteButton = event.target;

	    if (this.id > 0 && confirm(this.confirmDeleteMessage)) {
	      BX.ajax.runComponentAction('bitrix:salescenter.delivery.wizard', 'delete', {
	        data: {
	          id: this.id,
	          code: this.code
	        }
	      }).then(function () {
	        deleteButton.classList.remove('ui-btn-wait');
	        BX.SidePanel.Instance.close();
	      }.bind(this), function (result) {
	        deleteButton.classList.remove('ui-btn-wait');
	        this.showError(result.errors);
	      }.bind(this));
	    } else {
	      setTimeout(function () {
	        return BX.removeClass(deleteButton, 'ui-btn-wait');
	      }, 100);
	    }
	  };

	  Wizard.prototype.onSubmit = function (event) {
	    var _this = this;

	    var settings = {};
	    var formData = new FormData(this.form);

	    var _iterator = _createForOfIteratorHelper(formData.entries()),
	        _step;

	    try {
	      for (_iterator.s(); !(_step = _iterator.n()).done;) {
	        var pair = _step.value;
	        settings[pair[0]] = pair[1];
	      }
	    } catch (err) {
	      _iterator.e(err);
	    } finally {
	      _iterator.f();
	    }

	    this.saveButton.classList.add('ui-btn-wait');

	    var finallyCallback = function finallyCallback() {
	      _this.saveButton.classList.remove('ui-btn-wait');
	    };

	    var action = formData.has('id') ? 'update' : 'install';
	    BX.ajax.runComponentAction('bitrix:salescenter.delivery.wizard', action, {
	      json: settings
	    }).then(function (response) {
	      finallyCallback();
	      BX.SidePanel.Instance.close();
	    })["catch"](function (result) {
	      finallyCallback();

	      _this.showError(result.errors);
	    });
	    event.preventDefault();
	  };

	  Wizard.prototype.showError = function (errors) {
	    var text = '';
	    errors.forEach(function (error) {
	      text += error.message + '<br>';
	    });

	    if (this.errorMessageNode && text) {
	      this.errorMessageNode.parentNode.style.display = 'block';
	      this.errorMessageNode.innerHTML = text;
	    }
	  };

	  namespace.Wizard = new Wizard();
	})();

}((this.window = this.window || {})));
//# sourceMappingURL=script.js.map
