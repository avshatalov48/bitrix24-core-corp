this.BX = this.BX || {};
(function (exports,main_core,main_core_events,ui_timeline,calendar_planner,calendar_util) {
	'use strict';

	function _templateObject14() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"zoom-error-message ui-alert ui-alert-danger ui-alert-icon-danger\">\n\t\t\t\t<span class=\"ui-alert-message\">", "</span>\n\t\t\t</div>"]);

	  _templateObject14 = function _templateObject14() {
	    return data;
	  };

	  return data;
	}

	function _templateObject13() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-timeline-zoom-editor\">\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject13 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span onclick=\"", "\" class=\"ui-btn ui-btn-xs ui-btn-light-border\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"]);

	  _templateObject12 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button onclick=\"", "\" class=\"ui-btn ui-btn-xs ui-btn-primary\">\n\t\t\t\t\t", "\n\t\t\t\t</button>\n\t\t\t"]);

	  _templateObject11 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-entity-stream-content-zoom-btn-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-entity-stream-content-zoom-planner-container\"></div>\n\t\t\t"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-inner\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field\">\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field-inner\">\n\t\t\t\t\t\t\t\t<label for=\"\" class=\"crm-entity-stream-content-new-zoom-field-label\">", "</label>\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-sm ui-ctl-w100 ui-ctl-textbox\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field\">\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field-block\">\n\t\t\t\t\t\t\t\t<label for=\"\" class=\"crm-entity-stream-content-new-zoom-field-label\">", "</label>\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field-control\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field-block\">\n\t\t\t\t\t\t\t\t<label for=\"\" class=\"crm-entity-stream-content-new-zoom-field-label\">", "</label>\n\t\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-new-zoom-field-control\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-sm crm-entity-stream-content-new-zoom-field-xs\">\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown crm-entity-stream-content-new-zoom-field-sm\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<br>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<select class=\"ui-ctl-element\">\n\t\t\t\t\t<option value=\"m\">", "</option>\n\t\t\t\t\t<option value=\"h\">", "</option>\n\t\t\t\t</select>\n\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" value=\"30\">\n\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\" value=\"", "\">\n\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-ctl-element\">12:00</div>\n\t\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-dropdown crm-entity-stream-content-new-zoom-field-sm\">\n\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input type=\"text\" class=\"ui-ctl-element\">\n\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-ctl ui-ctl-sm ui-ctl-after-icon ui-ctl-date\">\n\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-calendar\"></div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	/**
	 * @memberOf BX.UI.Timeline
	 * @mixes EventEmitter
	 */

	var Zoom = /*#__PURE__*/function (_Timeline$Editor) {
	  babelHelpers.inherits(Zoom, _Timeline$Editor);

	  function Zoom(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, Zoom);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Zoom).call(this, params));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "TITLE", 'Zoom');
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "error", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "errorMessages", []);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());
	    _this.containerId = params.container;
	    _this.manager = params.manager;
	    _this.userId = +params.userId;

	    _this.setEventNamespace('BX.UI.Timeline.ZoomEditor');

	    main_core.Dom.append(_this.getFormContainer(), BX(_this.containerId));
	    main_core.Event.bind(_this.getDateContainer(), 'click', function (e) {
	      _this.onDateFieldClick(e);
	    });
	    main_core.Event.bind(_this.getTimeContainer(), 'click', function () {
	      _this.onTimeSwitchClick(_this.getTimeInputField());
	    });
	    main_core.Event.bind(_this.getDateContainer(), 'change', function () {
	      _this.onUpdateDateTime();
	    });
	    main_core.Event.bind(_this.getTimeContainer(), 'change', function () {
	      _this.onUpdateDateTime();
	    });
	    main_core.Event.bind(_this.getDurationInputField(), 'change', function () {
	      _this.onUpdateDateTime();
	    });
	    main_core.Event.bind(_this.getDurationTypeInputField(), 'change', function () {
	      _this.onUpdateDateTime();
	    });
	    return _this;
	  }

	  babelHelpers.createClass(Zoom, [{
	    key: "getTitle",
	    value: function getTitle() {
	      return this.TITLE;
	    }
	  }, {
	    key: "getStartDateTime",
	    value: function getStartDateTime() {
	      var ts = BX.parseDate(this.getDateInputField().value).getTime() + this.unFormatTime(this.getTimeInputField().textContent) * 1000;
	      return new Date(ts);
	    }
	  }, {
	    key: "getEndDateTime",
	    value: function getEndDateTime() {
	      var duration = +this.getDurationInputField().value;
	      var durationType = this.getDurationTypeInputField().value;

	      if (durationType === 'h') {
	        duration *= 60 * 60 * 1000;
	      } else {
	        duration *= 60 * 1000;
	      }

	      var endDateTime = new Date();
	      endDateTime.setTime(this.getStartDateTime().getTime() + duration);
	      return endDateTime;
	    }
	  }, {
	    key: "onUpdateDateTime",
	    value: function onUpdateDateTime() {
	      this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);
	    }
	  }, {
	    key: "onDateFieldClick",
	    value: function onDateFieldClick(e) {
	      BX.calendar({
	        node: e.currentTarget,
	        field: this.getDateInputField(),
	        bTime: false
	      });
	      return false;
	    }
	  }, {
	    key: "onTimeSwitchClick",
	    value: function onTimeSwitchClick(element) {
	      var _this2 = this;

	      if (!this.clockInstance) {
	        this.clockInstance = new BX.CClockSelector({
	          start_time: this.unFormatTime(element.textContent),
	          node: element,
	          callback: BX.doNothing
	        });
	      }

	      this.clockInstance.setNode(element);
	      this.clockInstance.setTime(this.unFormatTime(element.textContent));
	      this.clockInstance.setCallback(function (v) {
	        element.textContent = v;
	        BX.fireEvent(element, 'change');

	        _this2.clockInstance.closeWnd();
	      });
	      this.clockInstance.Show();
	    }
	  }, {
	    key: "formatTime",
	    value: function formatTime(date) {
	      var dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')).replace(/:?\s*s/, ''),
	          timeFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')).replace(/:?\s*s/, ''),
	          str1 = BX.date.format(dateFormat, date),
	          str2 = BX.date.format(timeFormat, date);
	      return BX.util.trim(str2.replace(str1, ''));
	    }
	  }, {
	    key: "unFormatTime",
	    value: function unFormatTime(time) {
	      var q = time.split(/[\s:]+/);

	      if (q.length == 3) {
	        var mt = q[2];
	        if (mt == 'pm' && q[0] < 12) q[0] = parseInt(q[0], 10) + 12;
	        if (mt == 'am' && q[0] == 12) q[0] = 0;
	      }

	      return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
	    }
	  }, {
	    key: "getDateContainer",
	    value: function getDateContainer() {
	      var _this3 = this;

	      return this.cache.remember('startDateContainer', function () {
	        return main_core.Tag.render(_templateObject(), _this3.getDateInputField());
	      });
	    }
	  }, {
	    key: "getDateInputField",
	    value: function getDateInputField() {
	      return this.cache.remember('startDateInputField', function () {
	        return main_core.Tag.render(_templateObject2());
	      });
	    }
	  }, {
	    key: "getTimeContainer",
	    value: function getTimeContainer() {
	      var _this4 = this;

	      return this.cache.remember('startTimeContainer', function () {
	        return main_core.Tag.render(_templateObject3(), _this4.getTimeInputField());
	      });
	    }
	  }, {
	    key: "getTimeInputField",
	    value: function getTimeInputField() {
	      return this.cache.remember('startTimeInputField', function () {
	        return main_core.Tag.render(_templateObject4());
	      });
	    }
	  }, {
	    key: "getTitleInputField",
	    value: function getTitleInputField() {
	      return this.cache.remember('titleInputField', function () {
	        return main_core.Tag.render(_templateObject5(), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE_PLACEHOLDER'));
	      });
	    }
	  }, {
	    key: "getDurationInputField",
	    value: function getDurationInputField() {
	      return this.cache.remember('durationInputField', function () {
	        return main_core.Tag.render(_templateObject6());
	      });
	    }
	  }, {
	    key: "getDurationTypeInputField",
	    value: function getDurationTypeInputField() {
	      return this.cache.remember('durationTypeInputField', function () {
	        return main_core.Tag.render(_templateObject7(), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_MINUTES'), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_HOURS'));
	      });
	    }
	  }, {
	    key: "getFormContainer",
	    value: function getFormContainer() {
	      var _this5 = this;

	      return this.cache.remember('formContainer', function () {
	        return main_core.Tag.render(_templateObject8(), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE'), _this5.getTitleInputField(), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DATE_CAPTION'), _this5.getDateContainer(), _this5.getTimeContainer(), main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_DURATION_CAPTION'), _this5.getDurationInputField(), _this5.getDurationTypeInputField(), _this5.renderPlanner());
	      });
	    }
	  }, {
	    key: "renderPlanner",
	    value: function renderPlanner() {
	      return this.cache.remember('plannerContainer', function () {
	        return main_core.Tag.render(_templateObject9());
	      });
	    }
	  }, {
	    key: "renderButtons",
	    value: function renderButtons() {
	      var _this6 = this;

	      return this.cache.remember('buttonsContainer', function () {
	        return main_core.Tag.render(_templateObject10(), _this6.renderSaveButton(), _this6.renderCancelButton());
	      });
	    }
	  }, {
	    key: "renderSaveButton",
	    value: function renderSaveButton() {
	      var _this7 = this;

	      return this.cache.remember('saveButton', function () {
	        return main_core.Tag.render(_templateObject11(), _this7.save.bind(_this7), main_core.Loc.getMessage('UI_BUTTONS_CREATE_BTN_TEXT'));
	      });
	    }
	  }, {
	    key: "refreshStartTimeView",
	    value: function refreshStartTimeView() {
	      var dt = new Date();
	      var minutes = dt.getMinutes();
	      var mod = minutes % 5;

	      if (mod > 0) {
	        dt.setMinutes(minutes - mod + (mod > 2 ? 5 : 0));
	      }

	      this.getDateInputField().value = BX.formatDate(dt, BX.message('FORMAT_DATE'));
	      this.getTimeInputField().innerHTML = this.formatTime(dt);
	    }
	  }, {
	    key: "renderCancelButton",
	    value: function renderCancelButton() {
	      var _this8 = this;

	      return this.cache.remember('cancelButton', function () {
	        return main_core.Tag.render(_templateObject12(), _this8.cancel.bind(_this8), main_core.Loc.getMessage('UI_TIMELINE_EDITOR_COMMENT_CANCEL'));
	      });
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      this.refreshStartTimeView();
	      this.initPlanner();
	      this.layout.container = main_core.Tag.render(_templateObject13(), this.renderButtons());
	      return this.getContainer();
	    }
	  }, {
	    key: "initPlanner",
	    value: function initPlanner() {
	      this.planner = new calendar_planner.Planner({
	        wrap: this.renderPlanner(),
	        showEntryName: false,
	        showEntiesHeader: false,
	        entriesListWidth: 70
	      });
	      this.planner.show();
	      this.loadPlannerData({
	        codes: ["U" + this.userId],
	        from: calendar_util.Util.formatDate(this.getStartDateTime().getTime() - calendar_util.Util.getDayLength() * 3),
	        to: calendar_util.Util.formatDate(this.getStartDateTime().getTime() + calendar_util.Util.getDayLength() * 10)
	      });
	      this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);
	      this.planner.subscribe('onDateChange', this.handlePlannerSelectorChanges.bind(this));
	    }
	  }, {
	    key: "handlePlannerSelectorChanges",
	    value: function handlePlannerSelectorChanges(event) {
	      if (event instanceof main_core_events.BaseEvent) {
	        var data = event.getData();
	        var startDateTime = data.dateFrom;
	        var duration = (data.dateTo - data.dateFrom) / 1000 / 60; //duration in minutes

	        var durationType = this.getDurationTypeInputField().value;
	        this.getDateInputField().value = BX.formatDate(startDateTime, BX.message('FORMAT_DATE'));
	        this.getTimeInputField().innerHTML = this.formatTime(startDateTime);

	        if (durationType === 'h' && duration % 60 === 0) {
	          this.getDurationInputField().value = duration / 60;
	          this.getDurationTypeInputField().value = 'h';
	        } else {
	          this.getDurationInputField().value = duration;
	          this.getDurationTypeInputField().value = 'm';
	        }
	      }
	    }
	  }, {
	    key: "loadPlannerData",
	    value: function loadPlannerData() {
	      var _this9 = this;

	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      this.planner.showLoader();
	      BX.ajax.runAction('calendar.api.calendarajax.updatePlanner', {
	        data: {
	          codes: params.codes || [],
	          dateFrom: params.from || '',
	          dateTo: params.to || ''
	        }
	      }).then(function (response) {
	        _this9.planner.hideLoader();

	        _this9.planner.update(response.data.entries, response.data.accessibility);
	      }, function (response) {
	        console.error(response.errors);
	      });
	    }
	  }, {
	    key: "onFocus",
	    value: function onFocus() {
	      var container = this.getContainer();

	      if (container) {
	        main_core.Dom.addClass(container, "focus");
	      }
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      this.cleanError();
	      main_core.Dom.addClass(this.renderSaveButton(), "ui-btn-wait");
	      var entityInfo = this.manager.getOwnerInfo();
	      var entityId = entityInfo['ENTITY_ID'];
	      var entityType = entityInfo['ENTITY_TYPE_NAME'];
	      var dateStart = this.getDateInputField().value;
	      var timeStart = this.getTimeInputField().textContent;
	      var timestampStart = this.getStartDateTime().getTime();
	      var dateTimeStart = dateStart + " " + timeStart;
	      var conferenceTitle = this.getTitleInputField().value;
	      var duration = +this.getDurationInputField().value;
	      var durationType = this.getDurationTypeInputField().value;

	      if (!main_core.Type.isString(conferenceTitle) || conferenceTitle === '') {
	        this.errorMessages.push("".concat(main_core.Loc.getMessage('CRM_ZOOM_ERROR_EMPTY_TITLE')));
	        this.showError();
	      }

	      if (!main_core.Type.isInteger(timestampStart) || timestampStart < Date.now()) {
	        this.errorMessages.push("".concat(main_core.Loc.getMessage('CRM_ZOOM_ERROR_INCORRECT_DATETIME')));
	        this.showError();
	      }

	      if (!main_core.Type.isInteger(duration) || duration <= 0 || !['h', 'm'].includes(durationType)) {
	        this.errorMessages.push("".concat(main_core.Loc.getMessage('CRM_ZOOM_ERROR_INCORRECT_DURATION')));
	        this.showError();
	      }

	      if (!this.error) {
	        BX.ajax.runAction('crm.api.zoomUser.createConference', {
	          data: {
	            conferenceParams: {
	              conferenceTitle: conferenceTitle,
	              dateTimeStart: dateTimeStart,
	              timestampStart: timestampStart,
	              duration: duration,
	              durationType: durationType
	            },
	            entityId: entityId,
	            entityType: entityType
	          },
	          analyticsLabel: {}
	        }).then(function (response) {
	          main_core.Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
	          this.cancel();
	        }.bind(this), function (response) {
	          main_core.Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
	          this.errorMessages.push("".concat(main_core.Loc.getMessage('CRM_ZOOM_CREATE_MEETING_SERVER_RETURNS_ERROR')));
	          this.errorMessages.push(response.errors[0].message);
	          this.showError();
	          console.error(response.errors);
	        }.bind(this));
	      }
	    }
	  }, {
	    key: "cancel",
	    value: function cancel() {
	      this.refreshTitle();
	      this.refreshStartTimeView();
	      this.refreshDuration();
	      this.planner.updateSelector(this.getStartDateTime(), this.getEndDateTime(), false);
	      this.setVisible(false);

	      this.manager._commentEditor.setVisible(true);

	      this.manager._menuBar.setActiveItemById("comment");
	    }
	  }, {
	    key: "setVisible",
	    value: function setVisible(show) {
	      var container = this.getContainer();

	      if (!show) {
	        if (container) {
	          BX.hide(container);
	        }
	      } else {
	        if (!container) {
	          container = this.renderInside();
	        }

	        if (container) {
	          BX.show(container);
	        }

	        this.onFocus();
	      }
	    }
	  }, {
	    key: "renderInside",
	    value: function renderInside() {
	      if (main_core.Type.isStringFilled(this.containerId)) {
	        var container = document.querySelector("#" + this.containerId);
	        this.render();

	        if (main_core.Type.isElementNode(container)) {
	          var node = this.layout.container.firstElementChild;

	          while (node) {
	            container.appendChild(node);
	            node = node.nextSibling;
	          }

	          main_core.Dom.remove(this.layout.container);
	          main_core.Dom.addClass(container, "ui-timeline-zoom-editor");
	          this.layout.container = container;
	        }
	      }

	      this.containerId = null;
	      return this.getContainer();
	    }
	  }, {
	    key: "showError",
	    value: function showError() {
	      var errorText = '';
	      this.errorMessages.forEach(function (message) {
	        errorText += message + "\n";
	      });

	      if (!this.error && errorText !== '') {
	        this.errorElement = main_core.Tag.render(_templateObject14(), errorText);
	        main_core.Dom.append(this.errorElement, this.layout.container.firstElementChild);
	        this.error = true;
	      }

	      main_core.Dom.removeClass(this.renderSaveButton(), 'ui-btn-wait');
	    }
	  }, {
	    key: "cleanError",
	    value: function cleanError() {
	      if (this.error) {
	        if (main_core.Type.isDomNode(this.errorElement)) {
	          main_core.Dom.remove(this.errorElement);
	          this.error = false;
	          this.errorMessages = [];
	        }
	      }
	    }
	  }, {
	    key: "refreshTitle",
	    value: function refreshTitle() {
	      this.getTitleInputField().value = main_core.Loc.getMessage('CRM_ZOOM_NEW_CONFERENCE_TITLE_PLACEHOLDER');
	    }
	  }, {
	    key: "refreshDuration",
	    value: function refreshDuration() {
	      this.getDurationInputField().value = 30;
	      this.getDurationTypeInputField().value = 'm';
	    }
	  }], [{
	    key: "onNotConnectedHandler",
	    value: function onNotConnectedHandler(userId) {
	      var url = document.location.href;
	      var userProfileUri = '/company/personal/user/' + userId + '/social_services/';
	      BX.SidePanel.Instance.open(userProfileUri, {
	        events: {
	          allowChangeHistory: false,
	          onClose: function onClose() {
	            top.location.href = url;
	          }
	        }
	      });
	    }
	  }, {
	    key: "onNotAvailableHandler",
	    value: function onNotAvailableHandler() {
	      BX.UI.InfoHelper.show('limit_video_conference_zoom_crm');
	    }
	  }]);
	  return Zoom;
	}(ui_timeline.Timeline.Editor);

	exports.Zoom = Zoom;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event,BX.UI,BX.Calendar,BX.Calendar));
//# sourceMappingURL=zoom.bundle.js.map
