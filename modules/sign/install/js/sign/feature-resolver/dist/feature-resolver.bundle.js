/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var _canInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canInstance");
	var _instance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("instance");
	var _featureCodes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("featureCodes");
	class FeatureResolver {
	  constructor(featureCodes = []) {
	    Object.defineProperty(this, _featureCodes, {
	      writable: true,
	      value: void 0
	    });
	    if (!babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _canInstance)[_canInstance]) {
	      throw new Error('Use FeatureResolver.instance() method to get instance of FeatureResolver');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _featureCodes)[_featureCodes] = new Set(featureCodes);
	  }
	  static instance() {
	    if (main_core.Type.isNil(babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _instance)[_instance])) {
	      const settings = main_core.Extension.getSettings('sign.feature-resolver');
	      babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _canInstance)[_canInstance] = true;
	      babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _instance)[_instance] = new FeatureResolver(settings.get('featureCodes', []));
	      babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _canInstance)[_canInstance] = false;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(FeatureResolver, _instance)[_instance];
	  }
	  released(code) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _featureCodes)[_featureCodes].has(code);
	  }
	}
	Object.defineProperty(FeatureResolver, _canInstance, {
	  writable: true,
	  value: false
	});
	Object.defineProperty(FeatureResolver, _instance, {
	  writable: true,
	  value: void 0
	});

	exports.FeatureResolver = FeatureResolver;

}((this.BX.Sign = this.BX.Sign || {}),BX));
//# sourceMappingURL=feature-resolver.bundle.js.map
