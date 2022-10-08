this.BX = this.BX || {};
this.BX.Salescenter = this.BX.Salescenter || {};
this.BX.Salescenter.Component = this.BX.Salescenter.Component || {};
(function (exports,main_core,main_popup) {
	'use strict';

	var SelectArrow = {
	  template: "\n\t\t\t<span class=\"salescenter-app-payment-by-sms-item-container-select-arrow\" /> \n"
	};

	var _templateObject, _templateObject2;
	var StageList = {
	  props: {
	    stages: {
	      type: Array,
	      required: true
	    },
	    editable: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'select-arrow-block': SelectArrow
	  },
	  computed: {
	    classesObject: function classesObject() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-select-inner': true
	      };
	    }
	  },
	  methods: {
	    styleObject: function styleObject(stage) {
	      return {
	        background: stage.color
	      };
	    },
	    showSelectPopup: function showSelectPopup(target, options) {
	      var _this = this;

	      if (!target || !this.editable) {
	        return;
	      }

	      this.selectPopup = new main_popup.Popup(null, target, {
	        closeByEsc: true,
	        autoHide: true,
	        width: 250,
	        offsetTop: 5,
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this.selectPopup.destroy();
	          }
	        },
	        content: this.getSelectPopupContent(options)
	      });
	      this.selectPopup.show();
	    },
	    getSelectPopupContent: function getSelectPopupContent(options) {
	      var _this2 = this;

	      if (!this.selectPopupContent) {
	        this.selectPopupContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"salescenter-app-payment-by-sms-select-popup\"></div>"])));

	        var onClickOptionHandler = function onClickOptionHandler(event) {
	          _this2.onChooseSelectOption(event);
	        };

	        for (var i = 0; i < options.length; i++) {
	          var option = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div data-item-value=\"", "\" class=\"salescenter-app-payment-by-sms-select-popup-option\" style=\"background-color:", ";\" onclick=\"", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"])), options[i].id, options[i].color ? options[i].color : '', onClickOptionHandler.bind(this), main_core.Text.encode(options[i].name));

	          if (options[i].colorText === 'light') {
	            option.style.color = '#fff';
	          }

	          main_core.Dom.append(option, this.selectPopupContent);
	        }
	      }

	      return this.selectPopupContent;
	    },
	    onChooseSelectOption: function onChooseSelectOption(event) {
	      var currentOption = this.$refs['selectedOptions'][0];
	      currentOption.textContent = event.currentTarget.textContent;
	      currentOption.style.color = event.currentTarget.style.color;
	      currentOption.nextElementSibling.style.borderColor = event.currentTarget.style.color;
	      currentOption.parentNode.style.background = event.currentTarget.style.backgroundColor;
	      this.$emit('on-choose-select-option', {
	        data: event.currentTarget.getAttribute('data-item-value')
	      });
	      this.selectPopup.destroy();
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-select\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-select-text\">\n\t\t\t\t<slot name=\"stage-list-text\"/>\n\t\t\t</div>\n\t\t\t<template v-for=\"stage in stages\">\n\t\t\t\t<div \n\t\t\t\t\tv-if=\"stage.selected\" \n\t\t\t\t\t:class=\"classesObject\" \n\t\t\t\t\t:style=\"styleObject(stage)\" \n\t\t\t\t\tv-on:click=\"showSelectPopup($event.currentTarget, stages)\"\n\t\t\t\t>\n\t\t\t\t\t<div ref=\"selectedOptions\" class=\"salescenter-app-payment-by-sms-item-container-select-item\">{{stage.name}}</div>\n\t\t\t\t\t<select-arrow-block/>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	};

	exports.StageList = StageList;

}((this.BX.Salescenter.Component.StageBlock = this.BX.Salescenter.Component.StageBlock || {}),BX,BX.Main));
//# sourceMappingURL=automation.bundle.js.map
