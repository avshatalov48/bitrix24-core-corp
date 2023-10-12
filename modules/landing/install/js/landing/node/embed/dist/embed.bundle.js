this.BX = this.BX || {};
this.BX.Landing = this.BX.Landing || {};
(function (exports,landing_node) {
	'use strict';

	class Embed extends landing_node.Node {
	  constructor(options) {
	    super(options);
	    this.data = BX.Landing.Utils.data;
	    this.type = 'embed';
	    this.attribute = ['data-src', 'data-source', 'data-preview'];
	    this.onAttributeChangeHandler = options.onAttributeChange || function () {};
	    this.lastValue = this.getValue();
	    this.nodeContainer = this.node.closest(BX.Landing.Node.Embed.CONTAINER_SELECTOR);
	  }
	  onChange(preventHistory) {
	    this.lastValue = this.getValue();
	    this.onAttributeChangeHandler(this);
	    this.onChangeHandler(this, preventHistory);
	  }
	  isChanged() {
	    return JSON.stringify(this.getValue()) !== JSON.stringify(this.lastValue);
	  }
	  getValue() {
	    var _this$node$src;
	    const ratio = this.nodeContainer ? BX.Landing.Node.Embed.RATIO_CLASSES.find(item => BX.Dom.hasClass(this.nodeContainer, item)) : '';
	    return {
	      src: (_this$node$src = this.node.src) != null ? _this$node$src : this.data(this.node, 'data-src'),
	      source: this.data(this.node, 'data-source'),
	      preview: this.data(this.node, 'data-preview'),
	      ratio: ratio || ''
	    };
	  }

	  /**
	   * Sets node value
	   * @abstract
	   * @param {*} value
	   * @param {?boolean} [preventSave = false]
	   * @param {?boolean} [preventHistory = false]
	   * @return void
	   */
	  setValue(value, preventSave, preventHistory) {
	    // if iframe or preview-div
	    if (this.node.src) {
	      this.node.src = value.src;
	    } else {
	      this.data(this.node, 'data-src', value.src);
	    }
	    this.data(this.node, 'data-source', value.source);
	    if (value.preview) {
	      this.data(this.node, 'data-preview', value.preview);
	      BX.Dom.style(this.node, 'background-image', `url("${value.preview}")`);
	    } else {
	      this.data(this.node, 'data-preview', null);
	      BX.Dom.style(this.node, 'background-image', '');
	    }
	    if (value.src && value.ratio && this.lastValue.src !== value.src && BX.Landing.Node.Embed.RATIO_CLASSES.includes(value.ratio) && this.nodeContainer) {
	      BX.Landing.Node.Embed.RATIO_CLASSES.forEach(ratioClass => {
	        if (value.ratio === ratioClass) {
	          BX.Dom.addClass(this.nodeContainer, ratioClass);
	        } else {
	          BX.Dom.removeClass(this.nodeContainer, ratioClass);
	        }
	      });
	    }
	    if (this.isChanged()) {
	      if (!preventHistory) {
	        BX.Landing.History.getInstance().push();
	      }
	      this.onChange(preventHistory);
	    }
	  }
	  getField() {
	    const fieldData = {
	      title: this.manifest.name,
	      selector: this.selector,
	      content: this.getValue()
	    };
	    if (BX.Dom.hasClass(this.node.parentNode, 'bg-video__inner')) {
	      return new BX.Landing.UI.Field.EmbedBg(fieldData);
	    }
	    return new BX.Landing.UI.Field.Embed(fieldData);
	  }
	}
	BX.Landing.Node.Embed = Embed;
	BX.Landing.Node.Embed.CONTAINER_SELECTOR = '.embed-responsive';
	BX.Landing.Node.Embed.RATIO_CLASSES = ['embed-responsive-16by9', 'embed-responsive-9by16', 'embed-responsive-4by3', 'embed-responsive-3by4', 'embed-responsive-21by9', 'embed-responsive-9by21', 'embed-responsive-1by1'];
	BX.Landing.Node.Embed.DEFAULT_RATIO_V = 'embed-responsive-9by16';
	BX.Landing.Node.Embed.DEFAULT_RATIO_H = 'embed-responsive-16by9';

	exports.Embed = Embed;

}((this.BX.Landing.Node = this.BX.Landing.Node || {}),BX.Landing));
//# sourceMappingURL=embed.bundle.js.map
