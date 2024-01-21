/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
this.BX.BIConnector.EntityEditor = this.BX.BIConnector.EntityEditor || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	class SettingsDateFilterField extends BX.UI.EntityEditorList {
	  constructor(id, settings) {
	    super();
	    this.dateSelectorBlock = null;
	    this.toInput = null;
	    this.startInput = null;
	  }
	  static create(id, settings) {
	    const self = new this(id, settings);
	    self.initialize(id, settings);
	    return self;
	  }
	  static layout(options = {}) {
	    super.layout();
	  }
	  createTitleNode() {
	    return main_core.Tag.render(_t || (_t = _`<span></span>`));
	  }
	  layout(options) {
	    super.layout(options);
	    this.layoutRangeField(this.getValue() === SettingsDateFilterField.RANGE_VALUE);
	  }
	  onItemSelect(e, item) {
	    this.layoutRangeField(item.value === SettingsDateFilterField.RANGE_VALUE);
	    super.onItemSelect(e, item);
	  }
	  layoutRangeField($isRangeSelected) {
	    if ($isRangeSelected) {
	      main_core.Dom.removeClass(this._selectContainer, 'ui-ctl-w100');
	      main_core.Dom.addClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
	      main_core.Dom.addClass(this._selectContainer, 'ui-ctl-date-range');
	      this.dateSelectorBlock = main_core.Tag.render(_t2 || (_t2 = _`<div class="ui-ctl-dropdown-range-group"></div>`));
	      const dateStartValue = main_core.Text.encode(this.getModel().getField(this.getDateStartFieldName()));
	      this.startInput = main_core.Tag.render(_t3 || (_t3 = _`<input class="ui-ctl-element" type="text" value="${0}" name="${0}">`), dateStartValue, this.getDateStartFieldName());
	      main_core.Event.bind(this.startInput, 'click', () => {
	        SettingsDateFilterField.showCalendar(this.startInput);
	      });
	      main_core.Event.bind(this.startInput, 'change', () => {
	        this.markAsChanged();
	      });
	      main_core.Event.bind(this.startInput, 'input', () => {
	        this.markAsChanged();
	      });
	      main_core.Dom.append(main_core.Tag.render(_t4 || (_t4 = _`
					<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
						<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
						${0}				
					</div>
				`), this.startInput), this.dateSelectorBlock);
	      main_core.Dom.append(main_core.Tag.render(_t5 || (_t5 = _`
					<div class="ui-ctl-dropdown-range-line">
						<span class="ui-ctl-dropdown-range-line-item"></span>
					</div>
				`)), this.dateSelectorBlock);
	      const dateEndValue = main_core.Text.encode(this.getModel().getField(this.getDateEndFieldName()));
	      this.endInput = main_core.Tag.render(_t6 || (_t6 = _`<input class="ui-ctl-element" type="text" value="${0}" name="${0}">`), dateEndValue, this.getDateEndFieldName());
	      main_core.Event.bind(this.endInput, 'click', () => {
	        SettingsDateFilterField.showCalendar(this.endInput);
	      });
	      main_core.Event.bind(this.endInput, 'change', () => {
	        this.markAsChanged();
	      });
	      main_core.Event.bind(this.endInput, 'input', () => {
	        this.markAsChanged();
	      });
	      main_core.Dom.append(main_core.Tag.render(_t7 || (_t7 = _`
					<div class="ui-ctl ui-ctl-before-icon ui-ctl-datetime">
						<div class="ui-ctl-before ui-ctl-icon-calendar"></div>
						${0}
					</div>
				`), this.endInput), this.dateSelectorBlock);
	      main_core.Dom.append(this.dateSelectorBlock, this._innerWrapper);
	    } else {
	      if (this.dateSelectorBlock !== null) {
	        main_core.Dom.remove(this.dateSelectorBlock);
	        this.dateSelectorBlock = null;
	        this.startInput = null;
	        this.endInput = null;
	      }
	      main_core.Dom.addClass(this._selectContainer, 'ui-ctl-w100');
	      main_core.Dom.removeClass(this._innerWrapper, 'ui-entity-editor-content-block__range');
	      main_core.Dom.removeClass(this._selectContainer, 'ui-ctl-date-range');
	    }
	  }
	  getDateStartFieldName() {
	    var _this$_schemeElement$;
	    return (_this$_schemeElement$ = this._schemeElement.getData().dateStartFieldName) != null ? _this$_schemeElement$ : 'DATE_FILTER_START';
	  }
	  getDateEndFieldName() {
	    var _this$_schemeElement$2;
	    return (_this$_schemeElement$2 = this._schemeElement.getData().dateEndFieldName) != null ? _this$_schemeElement$2 : 'DATE_FILTER_END';
	  }
	  save() {
	    super.save();
	    this._model.setField(this.getDateStartFieldName(), null);
	    this._model.setField(this.getDateEndFieldName(), null);
	    if (main_core.Type.isDomNode(this.endInput)) {
	      this._model.setField(this.getDateEndFieldName(), this.endInput.value);
	    }
	    if (main_core.Type.isDomNode(this.startInput)) {
	      this._model.setField(this.getDateStartFieldName(), this.startInput.value);
	    }
	  }
	  static showCalendar(input) {
	    BX.calendar({
	      node: input,
	      field: input,
	      bTime: false,
	      bSetFocus: false
	    });
	  }
	}
	SettingsDateFilterField.RANGE_VALUE = 'range';

	class SettingsDateFilterFieldFactory {
	  constructor(entityEditorControlFactory = 'BX.UI.EntityEditorControlFactory') {
	    main_core_events.EventEmitter.subscribe(entityEditorControlFactory + ':onInitialize', event => {
	      const [, eventArgs] = event.getCompatData();
	      eventArgs.methods['dashboardSettings'] = this.factory.bind(this);
	    });
	  }
	  factory(type, controlId, settings) {
	    if (type === 'timePeriod') {
	      return SettingsDateFilterField.create(controlId, settings);
	    }
	    return null;
	  }
	}

	exports.SettingsDateFilterField = SettingsDateFilterField;
	exports.SettingsDateFilterFieldFactory = SettingsDateFilterFieldFactory;

}((this.BX.BIConnector.EntityEditor.Field = this.BX.BIConnector.EntityEditor.Field || {}),BX,BX.Event));
//# sourceMappingURL=settings-date-filter.bundle.js.map
