/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	var _code = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("code");
	var _available = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("available");
	var _active = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("active");
	class Service extends main_core_events.EventEmitter {
	  constructor(code) {
	    super();
	    Object.defineProperty(this, _code, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _available, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _active, {
	      writable: true,
	      value: false
	    });
	    this.setEventNamespace('BX.Baas');
	    babelHelpers.classPrivateFieldLooseBase(this, _code)[_code] = code;
	    const props = main_core.Extension.getSettings('baas.service').services[babelHelpers.classPrivateFieldLooseBase(this, _code)[_code]];
	    if (main_core.Type.isPlainObject(props)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _available)[_available] = props.isAvailable;
	      babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = props.isActive;
	    }
	    if (BX.PULL && main_core.Extension.getSettings('baas.service').pull) {
	      BX.PULL.extendWatch(main_core.Extension.getSettings('baas.service').pull.channelName);
	      main_core_events.EventEmitter.subscribe('onPullEvent-baas', event => {
	        const [command, params] = event.getData();
	        if (command === 'updateService' && babelHelpers.classPrivateFieldLooseBase(this, _code)[_code] === params.service.code) {
	          if (babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] !== params.service.isActive) {
	            babelHelpers.classPrivateFieldLooseBase(this, _active)[_active] = params.service.isActive;
	            this.emit('onServiceActivityChanged', new main_core_events.BaseEvent({
	              data: {
	                code: babelHelpers.classPrivateFieldLooseBase(this, _code)[_code],
	                activity: babelHelpers.classPrivateFieldLooseBase(this, _active)[_active]
	              }
	            }));
	          }
	          if (babelHelpers.classPrivateFieldLooseBase(this, _available)[_available] !== params.service.isAvailable) {
	            babelHelpers.classPrivateFieldLooseBase(this, _available)[_available] = params.service.isAvailable;
	            this.emit('onServiceAvailabilityChanged', new main_core_events.BaseEvent({
	              data: {
	                code: babelHelpers.classPrivateFieldLooseBase(this, _code)[_code],
	                availability: babelHelpers.classPrivateFieldLooseBase(this, _available)[_available]
	              }
	            }));
	          }
	        }
	      });
	    }
	  }
	  isAvailable() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _available)[_available];
	  }
	  isActive() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _active)[_active];
	  }
	}

	var _services = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("services");
	class ServiceManager {
	  static getByCode(code) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _services)[_services][code]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _services)[_services][code] = new Service(code);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _services)[_services][code];
	  }
	}
	Object.defineProperty(ServiceManager, _services, {
	  writable: true,
	  value: []
	});

	exports.Service = Service;
	exports.ServiceManager = ServiceManager;

}((this.BX.Baas = this.BX.Baas || {}),BX,BX.Event));
//# sourceMappingURL=service.bundle.js.map
