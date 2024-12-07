/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
(function (exports,ui_entitySelector,main_core) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	var TagFooter = /*#__PURE__*/function (_DefaultFooter) {
	  babelHelpers.inherits(TagFooter, _DefaultFooter);
	  function TagFooter() {
	    babelHelpers.classCallCheck(this, TagFooter);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(TagFooter).apply(this, arguments));
	  }
	  babelHelpers.createClass(TagFooter, [{
	    key: "getContent",
	    value: function getContent() {
	      var _this = this;
	      return this.cache.remember('tag-footer-content', function () {
	        var createButton = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-selector-footer-link ui-selector-footer-link-add\"  \n\t\t\t\t\tid=\"tags-widget-custom-footer-add-new\" hidden>\n\t\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"])), main_core.Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_CREATE'));
	        main_core.Event.bind(createButton, 'click', function () {
	          return _this.createItem();
	        });
	        var openTagListButton = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a class=\"ui-selector-footer-link\">\n\t\t\t\t\t", "\n\t\t\t\t</a>\n\t\t\t"])), main_core.Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_GET_TAG_SLIDER'));
	        main_core.Event.bind(openTagListButton, 'click', function () {
	          var sliderLink = new main_core.Uri('/bitrix/components/bitrix/biconnector.apachesuperset.dashboard.tag.list/slider.php');
	          top.BX.SidePanel.Instance.open(sliderLink.toString(), {
	            width: 970,
	            allowChangeHistory: false,
	            cacheable: false
	          });
	        });
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"tags-widget-custom-footer\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span class=\"ui-selector-footer-conjunction\" \n\t\t\t\t\t\tid=\"tags-widget-custom-footer-conjunction\" hidden>\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), createButton, main_core.Loc.getMessage('BICONNECTOR_ENTITY_SELECTOR_TAG_FOOTER_OR'), openTagListButton);
	      });
	    }
	  }, {
	    key: "createItem",
	    value: function createItem() {
	      var _this2 = this;
	      if (!this.canCreateTag()) {
	        return;
	      }
	      var tagSelector = this.getDialog().getTagSelector();
	      if (tagSelector && tagSelector.isLocked()) {
	        return;
	      }
	      var finalize = function finalize() {
	        if (_this2.getDialog().getTagSelector()) {
	          _this2.getDialog().getTagSelector().unlock();
	          _this2.getDialog().focusSearch();
	        }
	      };
	      if (tagSelector) {
	        tagSelector.lock();
	      }
	      this.getDialog().emitAsync('Search:onItemCreateAsync', {
	        searchQuery: this.getDialog().getSearchTab().getLastSearchQuery()
	      }).then(function () {
	        _this2.getDialog().getSearchTab().clearResults();
	        _this2.getDialog().clearSearch();
	        if (_this2.getDialog().getActiveTab() === _this2.getTab()) {
	          _this2.getDialog().selectFirstTab();
	        }
	        finalize();
	      })["catch"](function () {
	        finalize();
	      });
	    }
	  }, {
	    key: "canCreateTag",
	    value: function canCreateTag() {
	      var _this$options$canCrea, _this$options;
	      return (_this$options$canCrea = (_this$options = this.options) === null || _this$options === void 0 ? void 0 : _this$options.canCreateTag) !== null && _this$options$canCrea !== void 0 ? _this$options$canCrea : false;
	    }
	  }]);
	  return TagFooter;
	}(ui_entitySelector.DefaultFooter);

	exports.TagFooter = TagFooter;

}((this.BX.BIConnector.EntitySelector = this.BX.BIConnector.EntitySelector || {}),BX.UI.EntitySelector,BX));
//# sourceMappingURL=biconnector-entity-selector.bundle.js.map
