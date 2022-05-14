this.BX = this.BX || {};
(function (exports,main_core_events,calendar_util,main_core,main_popup) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3;
	var Selector = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Selector, _EventEmitter);

	  function Selector() {
	    var _this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Selector);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Selector).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "DOM", {});
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "selectMode", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "currentDateFrom", new Date());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "currentDateTo", new Date());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "currentFullDay", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "useAnimation", true);

	    _this.setEventNamespace('BX.Calendar.Planner.Selector');

	    _this.selectMode = params.selectMode;
	    _this.getPosByDate = params.getPosByDate;
	    _this.getDateByPos = params.getDateByPos;
	    _this.getPosDateMap = params.getPosDateMap;
	    _this.getTimelineWidth = params.getTimelineWidth;
	    _this.getScaleInfo = params.getScaleInfo;
	    _this.solidStatus = params.solidStatus;
	    _this.useAnimation = params.useAnimation !== false;
	    _this.DOM.timelineWrap = params.timelineWrap;

	    _this.render();

	    return _this;
	  }

	  babelHelpers.createClass(Selector, [{
	    key: "render",
	    value: function render() {
	      this.DOM.wrap = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-timeline-selector\" data-bx-planner-meta=\"selector\">\n\t\t\t\t<span data-bx-planner-meta=\"selector-resize-left\" class=\"calendar-planner-timeline-drag-left\"></span>\n\t\t\t\t<span class=\"calendar-planner-timeline-selector-grip\"></span>\n\t\t\t\t<span data-bx-planner-meta=\"selector-resize-right\" class=\"calendar-planner-timeline-drag-right\"></span>\n\t\t\t</div>"]))); // prefent draging selector and activating uploader controll in livefeed

	      this.DOM.wrap.ondrag = BX.False;
	      this.DOM.wrap.ondragstart = BX.False;
	      this.DOM.titleNode = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"calendar-planner-selector-notice\" style=\"display: none\"></div>"])));

	      if (this.selectMode) {
	        result.controlWrap = this.DOM.wrap.appendChild(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"calendar-planner-selector-control\"></div>"]))));
	      }
	    }
	  }, {
	    key: "getWrap",
	    value: function getWrap() {
	      return this.DOM.wrap;
	    }
	  }, {
	    key: "getTitleNode",
	    value: function getTitleNode() {
	      return this.DOM.titleNode;
	    }
	    /**
	     *
	     *
	     * @params array array of parameters
	     * @params[from]
	     * @params[to]
	     * @params[updateScaleType] bool
	     * @params[updateScaleLimits] bool
	     *
	     * @return null
	     */

	  }, {
	    key: "update",
	    value: function update() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      params.updateScaleType = !!params.updateScaleType;
	      params.updateScaleLimits = !!params.updateScaleLimits;
	      params.animation = !!params.animation;
	      var from = main_core.Type.isDate(params.from) ? params.from : BX.parseDate(params.from) || this.currentDateFrom;
	      var to = main_core.Type.isDate(params.to) ? params.to : BX.parseDate(params.to) || this.currentDateTo;
	      this.fullDayMode = params.fullDay !== undefined ? params.fullDay : this.currentFullDay;

	      if (main_core.Type.isDate(from) && main_core.Type.isDate(to)) {
	        this.currentDateFrom = from;
	        this.currentDateTo = to;
	        this.currentFullDay = this.fullDayMode;

	        if (this.fullDayMode) {
	          to = new Date(to.getTime() + calendar_util.Util.getDayLength());
	          from.setHours(0, 0, 0, 0);
	          to.setHours(0, 0, 0, 0);
	        } // Update selector


	        this.show(from, to, {
	          animation: params.animation,
	          focus: params.focus
	        });
	      }
	    }
	  }, {
	    key: "show",
	    value: function show(from, to, params) {
	      var animation = params.animation && this.useAnimation !== false;
	      var focus = params.focus !== false;
	      var alignCenter = params.alignCenter !== false;
	      this.DOM.wrap.style.display = 'block';

	      if (main_core.Type.isDate(from) && main_core.Type.isDate(to)) {
	        var fromPos = this.getPosByDate(from),
	            toPos = this.getPosByDate(to);
	        this.DOM.wrap.style.width = toPos - fromPos + 'px';

	        if (animation && this.DOM.wrap.style.left && !this.currentFullDay) {
	          this.transit({
	            toX: fromPos,
	            triggerChangeEvents: false,
	            focus: focus
	          });
	        } else {
	          this.DOM.wrap.style.left = fromPos + 'px';
	          this.DOM.wrap.style.width = toPos - fromPos + 'px';

	          if (focus) {
	            this.focus(false, 200, alignCenter);
	          }

	          this.checkStatus(fromPos, true);
	        }
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.DOM.wrap.style.display = 'none';
	    }
	  }, {
	    key: "startMove",
	    value: function startMove() {
	      this.selectorIsDraged = true;
	      this.selectorRoundedPos = false;
	      this.selectorStartLeft = parseInt(this.DOM.wrap.style.left);
	      this.selectorStartScrollLeft = this.DOM.timelineWrap.scrollLeft;
	      main_core.Dom.addClass(document.body, 'calendar-planner-unselectable');
	    }
	  }, {
	    key: "move",
	    value: function move(x) {
	      if (this.selectorIsDraged) {
	        var selectorWidth = parseInt(this.DOM.wrap.style.width),
	            pos = this.selectorStartLeft + x; // Correct cursor position acording to changes of scrollleft

	        pos -= this.selectorStartScrollLeft - this.DOM.timelineWrap.scrollLeft;

	        if (this.getPosDateMap()[pos]) {
	          this.selectorRoundedPos = pos;
	        } else {
	          var roundedPos = Selector.roundPos(pos);

	          if (this.getPosDateMap()[roundedPos]) {
	            this.selectorRoundedPos = roundedPos;
	          }
	        }

	        var checkedPos = this.checkPosition(this.selectorRoundedPos);

	        if (checkedPos !== this.selectorRoundedPos) {
	          this.selectorRoundedPos = checkedPos;
	          pos = checkedPos;
	        }

	        this.DOM.wrap.style.left = pos + 'px';
	        this.showTitle({
	          fromPos: pos,
	          toPos: this.selectorRoundedPos + selectorWidth
	        });
	        this.checkStatus(this.selectorRoundedPos, true);
	      }
	    }
	  }, {
	    key: "endMove",
	    value: function endMove() {
	      if (this.selectorIsDraged && this.selectorRoundedPos) {
	        this.DOM.wrap.style.left = this.selectorRoundedPos + 'px';
	        this.selectorRoundedPos = false;
	        this.hideTitle();
	        this.setValue(this.selectorRoundedPos);
	      }

	      this.selectorIsDraged = false;
	    }
	  }, {
	    key: "startResize",
	    value: function startResize() {
	      this.selectorIsResized = true;
	      this.selectorRoundedPos = false;
	      this.selectorStartLeft = parseInt(this.DOM.wrap.style.left);
	      this.selectorStartWidth = parseInt(this.DOM.wrap.style.width);
	      this.selectorStartScrollLeft = this.DOM.timelineWrap.scrollLeft;
	    }
	  }, {
	    key: "resize",
	    value: function resize(x) {
	      if (this.selectorIsResized) {
	        var toDate,
	            timeTo,
	            width = this.selectorStartWidth + x; // Correct cursor position according to changes of scrollLeft

	        width -= this.selectorStartScrollLeft - this.DOM.timelineWrap.scrollLeft;
	        var rightPos = Math.min(this.selectorStartLeft + width, this.getTimelineWidth());
	        toDate = this.getDateByPos(rightPos, true);

	        if (this.fullDayMode) {
	          timeTo = parseInt(toDate.getHours()) + Math.round(toDate.getMinutes() / 60 * 10) / 10;
	          toDate.setHours(0, 0, 0, 0);

	          if (timeTo > 12) {
	            toDate = new Date(toDate.getTime() + calendar_util.Util.getDayLength());
	            toDate.setHours(0, 0, 0, 0);
	          }

	          rightPos = this.getPosByDate(toDate);
	          width = rightPos - this.selectorStartLeft;

	          if (width <= 10) {
	            toDate = this.getDateByPos(this.selectorStartLeft);
	            toDate = new Date(toDate.getTime() + calendar_util.Util.getDayLength());
	            toDate.setHours(0, 0, 0, 0);
	            width = this.getPosByDate(toDate) - this.selectorStartLeft;
	            rightPos = this.selectorStartLeft + width;
	          }
	        } else if (this.shownScaleTimeFrom !== 0 || this.shownScaleTimeTo !== 24) {
	          var fromDate = this.getDateByPos(this.selectorStartLeft);

	          if (toDate && fromDate && calendar_util.Util.formatDate(fromDate) !== calendar_util.Util.formatDate(toDate)) {
	            toDate = new Date(fromDate.getTime());
	            toDate.setHours(this.shownScaleTimeTo, 0, 0, 0);
	            rightPos = this.getPosByDate(toDate);
	            width = rightPos - this.selectorStartLeft;
	          }
	        }

	        if (this.getPosDateMap()[rightPos]) {
	          this.selectorRoundedRightPos = rightPos;
	        } else {
	          var roundedPos = Selector.roundPos(rightPos);

	          if (this.getPosDateMap()[roundedPos]) {
	            this.selectorRoundedRightPos = roundedPos;
	          }
	        }

	        this.DOM.wrap.style.width = width + 'px';
	        this.showTitle({
	          fromPos: this.selectorStartLeft,
	          toPos: this.selectorRoundedRightPos
	        });
	        this.checkStatus(this.selectorStartLeft, true);
	      }
	    }
	  }, {
	    key: "endResize",
	    value: function endResize() {
	      if (this.selectorIsResized && this.selectorRoundedRightPos) {
	        this.DOM.wrap.style.width = this.selectorRoundedPos - parseInt(this.DOM.wrap.style.left) + 'px';
	        this.selectorRoundedRightPos = false;
	        this.hideTitle();
	        this.setValue();
	      }

	      this.selectorIsResized = false;
	    }
	  }, {
	    key: "isDragged",
	    value: function isDragged() {
	      return this.selectorIsResized || this.selectorIsDraged;
	    }
	  }, {
	    key: "checkStatus",
	    value: function checkStatus(selectorPos, checkPosition) {
	      if (this.solidStatus) {
	        main_core.Dom.removeClass(this.DOM.wrap, 'calendar-planner-timeline-selector-warning');
	        main_core.Dom.removeClass(this.mainContWrap, 'calendar-planner-selector-warning');
	        main_core.Dom.addClass(this.DOM.wrap, 'solid');
	      } else {
	        if (!selectorPos) {
	          selectorPos = Selector.roundPos(this.DOM.wrap.style.left);
	        }

	        var fromDate, toDate;

	        if (checkPosition === true || !this.currentDateFrom) {
	          var selectorWidth = parseInt(this.DOM.wrap.style.width),
	              fromPos = selectorPos,
	              toPos = fromPos + selectorWidth;

	          if (!fromPos && !toPos && !selectorWidth && this.lastFromDate) {
	            fromDate = this.lastFromDate;
	            toDate = this.lastToDate;
	          } else {
	            fromDate = this.getDateByPos(fromPos);
	            toDate = this.getDateByPos(toPos, true);
	            this.lastFromDate = fromDate;
	            this.lastToDate = toDate;
	          }
	        } else {
	          fromDate = this.currentDateFrom;
	          toDate = this.currentDateTo;
	        }

	        this.emit('doCheckStatus', new main_core_events.BaseEvent({
	          data: {
	            dateFrom: fromDate,
	            dateTo: toDate
	          }
	        }));
	      }
	    }
	  }, {
	    key: "setSelectorStatus",
	    value: function setSelectorStatus(status) {
	      this.selectorIsFree = status;

	      if (this.selectorIsFree) {
	        main_core.Dom.removeClass(this.DOM.wrap, 'calendar-planner-timeline-selector-warning');
	      } else {
	        main_core.Dom.addClass(this.DOM.wrap, 'calendar-planner-timeline-selector-warning');
	      }
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(selectorPos, selectorWidth) {
	      if (!selectorPos) {
	        selectorPos = parseInt(this.DOM.wrap.style.left);
	      }

	      selectorPos = Math.max(0, selectorPos);

	      if (!selectorWidth) {
	        selectorWidth = parseInt(this.DOM.wrap.style.width);
	      }

	      if (selectorPos + selectorWidth > parseInt(this.getTimelineWidth())) {
	        selectorPos = parseInt(this.getTimelineWidth()) - selectorWidth;
	      }

	      var dateFrom = this.getDateByPos(selectorPos);
	      var dateTo = this.getDateByPos(selectorPos + selectorWidth, true);

	      if (dateFrom && dateTo) {
	        this.currentDateFrom = dateFrom;
	        this.currentDateTo = dateTo;
	        this.currentFullDay = this.fullDayMode;

	        if (this.fullDayMode) {
	          var dateToTime = dateTo.getTime();
	          this.currentDateTo = new Date(dateToTime - 1000);
	          dateTo = new Date(dateToTime - calendar_util.Util.getDayLength());
	        }

	        this.emit('onChange', new main_core_events.BaseEvent({
	          data: {
	            dateFrom: dateFrom,
	            dateTo: dateTo,
	            fullDay: this.fullDayMode
	          }
	        }));
	      }
	    }
	  }, {
	    key: "checkPosition",
	    value: function checkPosition(fromPos, selectorWidth, toPos) {
	      var scaleInfo = this.getScaleInfo();

	      if ((scaleInfo.shownTimeFrom !== 0 || scaleInfo.shownTimeTo !== 24) && (scaleInfo.type !== '1day' || this.fullDayMode)) {
	        fromPos = fromPos || parseInt(this.DOM.wrap.style.left);
	        selectorWidth = selectorWidth || parseInt(this.DOM.wrap.style.width);
	        toPos = toPos || fromPos + selectorWidth;

	        if (toPos > parseInt(this.getTimelineWidth())) {
	          fromPos = parseInt(this.getTimelineWidth()) - selectorWidth;
	        } else {
	          var fromDate = this.getDateByPos(fromPos),
	              toDate = this.getDateByPos(toPos, true),
	              timeFrom,
	              timeTo,
	              scaleTimeFrom = parseInt(scaleInfo.shownTimeFrom),
	              scaleTimeTo = parseInt(scaleInfo.shownTimeTo);

	          if (fromDate && toDate) {
	            if (this.fullDayMode) {
	              timeFrom = parseInt(fromDate.getHours()) + Math.round(fromDate.getMinutes() / 60 * 10) / 10;
	              fromDate.setHours(0, 0, 0, 0);

	              if (timeFrom > 12) {
	                fromDate = new Date(fromDate.getTime() + calendar_util.Util.getDayLength());
	                fromDate.setHours(0, 0, 0, 0);
	              }

	              fromPos = this.getPosByDate(fromDate);
	            } else if (fromDate.getDay() !== toDate.getDay()) {
	              timeFrom = parseInt(fromDate.getHours()) + Math.round(fromDate.getMinutes() / 60 * 10) / 10;
	              timeTo = parseInt(toDate.getHours()) + Math.round(toDate.getMinutes() / 60 * 10) / 10;

	              if (Math.abs(scaleTimeTo - timeFrom) > Math.abs(scaleTimeFrom - timeTo)) {
	                fromDate.setHours(scaleInfo.shownTimeTo, 0, 0, 0);
	                fromPos = this.getPosByDate(fromDate) - selectorWidth;
	              } else {
	                toDate.setHours(scaleInfo.shownTimeFrom, 0, 0, 0);
	                fromPos = this.getPosByDate(toDate);
	              }
	            }
	          }
	        }
	      }

	      return fromPos;
	    }
	  }, {
	    key: "transit",
	    value: function transit() {
	      var _this2 = this;

	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var fromX = params.fromX || parseInt(this.DOM.wrap.style.left),
	          toX = Selector.roundPos(params.toX || fromX),
	          triggerChangeEvents = params.triggerChangeEvents !== false,
	          focus = !!params.focus,
	          width = parseInt(this.DOM.wrap.offsetWidth); // triggerChangeEvents - it means that selector transition (animation caused from mouse ebents)

	      if (toX > fromX + width && triggerChangeEvents) {
	        toX -= width;
	      }

	      if (fromX !== toX) {
	        if (this.animation) {
	          this.animation.stop();
	        }

	        this.animation = new BX.easing({
	          duration: 300,
	          start: {
	            left: fromX
	          },
	          finish: {
	            left: toX
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            _this2.DOM.wrap.style.left = state.left + 'px';
	          },
	          complete: function complete() {
	            _this2.animation = null;

	            var fromPos = parseInt(_this2.DOM.wrap.style.left),
	                checkedPos = _this2.checkPosition(fromPos);

	            if (checkedPos !== fromPos) {
	              _this2.DOM.wrap.style.left = checkedPos + 'px';
	            }

	            if (triggerChangeEvents) {
	              _this2.setValue(checkedPos);
	            }

	            if (focus) {
	              _this2.focus(true, 300);
	            }

	            setTimeout(function () {
	              _this2.show(_this2.currentDateFrom, _this2.currentDateTo, {
	                animation: false,
	                focus: focus,
	                alignCenter: false
	              });
	            }, 200);

	            _this2.checkStatus(checkedPos);
	          }
	        });
	        this.animation.animate();
	      } else {
	        if (triggerChangeEvents) {
	          this.setValue();
	        }

	        if (focus === true) {
	          this.focus(true, 300);
	        }

	        this.checkStatus();
	      }
	    }
	  }, {
	    key: "showTitle",
	    value: function showTitle() {
	      var _this3 = this;

	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var fromPos = params.fromPos,
	          toPos = params.toPos,
	          selectorTitle = params.selectorTitle || this.getTitleNode(),
	          selector = params.selector || this.DOM.wrap,
	          timelineWidth = this.getTimelineWidth(),
	          fromDate,
	          toDate;

	      if (fromPos && toPos) {
	        if (toPos > timelineWidth) {
	          fromPos = timelineWidth - parseInt(selector.style.width);
	          toPos = timelineWidth;
	        }

	        fromDate = this.getDateByPos(fromPos);
	        toDate = this.getDateByPos(toPos, true);

	        if (fromDate && toDate) {
	          if (this.fullDayMode) {
	            if (Math.abs(toDate.getTime() - fromDate.getTime() - calendar_util.Util.getDayLength()) < 1000) {
	              selectorTitle.innerHTML = BX.date.format('d F, D', fromDate.getTime() / 1000);
	            } else {
	              selectorTitle.innerHTML = BX.date.format('d F', fromDate.getTime() / 1000) + ' - ' + BX.date.format('d F', toDate.getTime() / 1000);
	            }
	          } else {
	            selectorTitle.removeAttribute('style');
	            selectorTitle.innerHTML = calendar_util.Util.formatTime(fromDate) + ' - ' + calendar_util.Util.formatTime(toDate);
	          }

	          if (this.selectMode && this.lastTouchedEntry) {
	            var entriesListWidth = this.compactMode ? 0 : this.entriesListWidth,
	                selectorTitleLeft = parseInt(selector.style.left) - this.DOM.timelineWrap.scrollLeft + entriesListWidth + parseInt(selector.style.width) / 2,
	                selectorTitleTop = parseInt(this.timelineDataCont.offsetTop) + parseInt(this.lastTouchedEntry.style.top) - 12;
	            selectorTitle.style.top = selectorTitleTop + 'px';
	            selectorTitle.style.left = selectorTitleLeft + 'px';
	          } else {
	            selector.appendChild(selectorTitle);
	          }
	        }
	      }

	      if (selectorTitle === this.selectorTitle) {
	        if (selectorTitle.style.display === 'none' || this.selectorHideTimeout) {
	          this.selectorHideTimeout = clearTimeout(this.selectorHideTimeout); // Opacity animation

	          this.selectorTitle.style.display = '';
	          this.selectorTitle.style.opacity = 0;
	          new BX.easing({
	            duration: 400,
	            start: {
	              opacity: 0
	            },
	            finish: {
	              opacity: 100
	            },
	            transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
	            step: function step(state) {
	              _this3.selectorTitle.style.opacity = state.opacity / 100;
	            },
	            complete: function complete() {
	              _this3.selectorTitle.removeAttribute('style');
	            }
	          }).animate();
	        }
	      } else {
	        selectorTitle.removeAttribute('style');
	      }
	    }
	  }, {
	    key: "hideTitle",
	    value: function hideTitle() {
	      var _this4 = this;

	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      if (!main_core.Type.isPlainObject(params)) params = {};
	      var timeoutName = params.selectorIndex === undefined ? 'selectorHideTimeout' : 'selectorHideTimeout_' + params.selectorIndex,
	          selectorTitle = params.selectorTitle || this.getTitleNode();
	      if (this[timeoutName]) this[timeoutName] = clearTimeout(this[timeoutName]);

	      if (params.timeout !== false) {
	        this[timeoutName] = setTimeout(function () {
	          params.timeout = false;

	          _this4.hideTitle(params);
	        }, 700);
	      } else {
	        // Opacity animation
	        selectorTitle.style.display = '';
	        selectorTitle.style.opacity = 1;
	        new BX.easing({
	          duration: 400,
	          start: {
	            opacity: 100
	          },
	          finish: {
	            opacity: 0
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
	          step: function step(state) {
	            selectorTitle.style.opacity = state.opacity / 100;
	          },
	          complete: function complete() {
	            selectorTitle.removeAttribute('style');
	            selectorTitle.style.display = 'none';
	          }
	        }).animate();
	      }
	    }
	  }, {
	    key: "focus",
	    value: function focus() {
	      var _this5 = this;

	      var animation = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 300;
	      var alignCenter = arguments.length > 2 ? arguments[2] : undefined;
	      alignCenter = alignCenter === true;

	      if (this.focusTimeout) {
	        this.focusTimeout = !!clearTimeout(this.focusTimeout);
	      }

	      if (this.useAnimation === false) {
	        animation = false;
	      }

	      if (timeout) {
	        this.focusTimeout = setTimeout(function () {
	          _this5.focus(animation, false, alignCenter);
	        }, timeout);
	      } else {
	        var screenDelta = 10,
	            selectorLeft = parseInt(this.DOM.wrap.style.left),
	            selectorWidth = parseInt(this.DOM.wrap.style.width),
	            viewWidth = parseInt(this.DOM.timelineWrap.offsetWidth),
	            viewLeft = parseInt(this.DOM.timelineWrap.scrollLeft),
	            viewRight = viewLeft + viewWidth;
	        var newScrollLeft = viewLeft;

	        if (selectorLeft < viewLeft + screenDelta || selectorLeft > viewRight - screenDelta || alignCenter) {
	          // Selector is smaller than view - we puting it in the middle of the view
	          if (selectorWidth <= viewWidth) {
	            newScrollLeft = Math.max(Math.round(selectorLeft - (viewWidth - selectorWidth) / 2), screenDelta);
	          } else // Selector is wider, so we adjust by left side
	            {
	              newScrollLeft = Math.max(Math.round(selectorLeft - screenDelta), screenDelta);
	            }
	        }

	        if (newScrollLeft !== viewLeft) {
	          if (animation === false) {
	            this.DOM.timelineWrap.scrollLeft = newScrollLeft;
	          } else {
	            new BX.easing({
	              duration: 300,
	              start: {
	                scrollLeft: this.DOM.timelineWrap.scrollLeft
	              },
	              finish: {
	                scrollLeft: newScrollLeft
	              },
	              transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
	              step: function step(state) {
	                _this5.DOM.timelineWrap.scrollLeft = state.scrollLeft;
	              },
	              complete: function complete() {}
	            }).animate();
	          }
	        }
	      }
	    }
	  }, {
	    key: "getDuration",
	    value: function getDuration() {
	      var duration = Math.round((this.currentDateTo - this.currentDateFrom) / 1000) * 1000;

	      if (this.fullDayMode) {
	        duration += calendar_util.Util.getDayLength();
	      }

	      return duration;
	    }
	  }, {
	    key: "getDateFrom",
	    value: function getDateFrom() {
	      return this.currentDateFrom;
	    }
	  }, {
	    key: "getDateTo",
	    value: function getDateTo() {
	      return this.currentDateTo;
	    }
	  }], [{
	    key: "roundPos",
	    value: function roundPos(x) {
	      return Math.round(parseFloat(x));
	    }
	  }]);
	  return Selector;
	}(main_core_events.EventEmitter);

	var _templateObject$1, _templateObject2$1, _templateObject3$1, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18, _templateObject19, _templateObject20, _templateObject21, _templateObject22, _templateObject23, _templateObject24, _templateObject25, _templateObject26, _templateObject27, _templateObject28, _templateObject29, _templateObject30, _templateObject31, _templateObject32, _templateObject33, _templateObject34, _templateObject35, _templateObject36, _templateObject37, _templateObject38, _templateObject39, _templateObject40, _templateObject41, _templateObject42, _templateObject43, _templateObject44, _templateObject45, _templateObject46, _templateObject47, _templateObject48;
	var Planner = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Planner, _EventEmitter);

	  // in days
	  // in days
	  // in days
	  // ms
	  // in days
	  function Planner() {
	    var _this;

	    var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Planner);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Planner).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "DOM", {});
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "config", {});
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "entryStatusMap", {
	      h: 'user-status-h',
	      y: 'user-status-y',
	      q: 'user-status-q',
	      n: 'user-status-n'
	    });
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "scaleTypes", ['15min', '30min', '1hour', '2hour', '1day']);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "savedScaleType", null);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "SCALE_OFFSET_BEFORE", 3);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "SCALE_OFFSET_AFTER", 10);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "EXPAND_OFFSET", 3);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "EXPAND_DELAY", 2000);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "REBUILD_DELAY", 100);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "maxTimelineSize", 20);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "MIN_ENTRY_ROWS", 3);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "MAX_ENTRY_ROWS", 300);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "width", 700);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "height", 84);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "minWidth", 700);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "minHeight", 84);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "workTime", [9, 18]);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "scrollStep", 10);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "shown", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "built", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "locked", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "shownScaleTimeFrom", 24);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "shownScaleTimeTo", 0);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "timelineCellWidthOrig", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "proposeTimeLimit", 60);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "expandTimelineDelay", 600);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "limitScaleSizeMode", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "useAnimation", true);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "checkTimeCache", {});
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "entriesIndex", new Map());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "solidStatus", false);

	    _this.setEventNamespace('BX.Calendar.Planner');

	    _this.config = params;
	    _this.id = params.id;
	    _this.dayOfWeekMonthFormat = params.dayOfWeekMonthFormat || 'd F, l';
	    _this.userId = parseInt(params.userId || main_core.Loc.getMessage('USER_ID'));
	    _this.DOM.wrap = params.wrap;
	    _this.SCALE_TIME_FORMAT = BX.isAmPmMode() ? 'g a' : 'G';
	    _this.expandTimelineDebounce = main_core.Runtime.debounce(_this.expandTimeline, _this.EXPAND_DELAY, babelHelpers.assertThisInitialized(_this));

	    _this.setConfig(params);

	    return _this;
	  }

	  babelHelpers.createClass(Planner, [{
	    key: "show",
	    value: function show() {

	      if (this.hideAnimation) {
	        this.hideAnimation.stop();
	        this.hideAnimation = null;
	      }

	      if (!this.isBuilt()) {
	        this.build();
	        this.bindEventHandlers();
	      } else {
	        this.resizePlannerWidth(this.width);
	      }

	      this.buildTimeline();

	      if (this.adjustWidth) {
	        this.resizePlannerWidth(this.DOM.timelineInnerWrap.offsetWidth);
	      }

	      this.DOM.wrap.style.display = '';

	      if (this.readonly) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-readonly');
	      } else {
	        main_core.Dom.removeClass(this.DOM.mainWrap, 'calendar-planner-readonly');
	      }

	      if (this.compactMode) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-compact');
	      } else {
	        main_core.Dom.removeClass(this.DOM.mainWrap, 'calendar-planner-compact');
	      }

	      this.DOM.entriesOuterWrap.style.display = this.compactMode ? 'none' : '';

	      {
	        if (parseInt(this.DOM.wrap.style.height) < this.height) {
	          this.DOM.wrap.style.height = this.height + 'px';
	        }

	        this.adjustHeight();
	      }

	      this.shown = true;
	    }
	  }, {
	    key: "setConfig",
	    value: function setConfig(params) {
	      this.setScaleType(params.scaleType); // showTimelineDayTitle

	      if (params.showTimelineDayTitle !== undefined) {
	        this.showTimelineDayTitle = !!params.showTimelineDayTitle;
	      } else if (this.showTimelineDayTitle === undefined) {
	        this.showTimelineDayTitle = true;
	      } // compactMode


	      if (params.compactMode !== undefined) {
	        this.compactMode = !!params.compactMode;
	      } else if (this.compactMode === undefined) {
	        this.compactMode = false;
	      } // readonly


	      if (params.readonly !== undefined) {
	        this.readonly = !!params.readonly;
	      } else if (this.readonly === undefined) {
	        this.readonly = false;
	      }

	      if (this.compactMode) {
	        var compactHeight = 50;
	        if (this.showTimelineDayTitle && !this.isOneDayScale()) compactHeight += 20;
	        this.height = this.minHeight = compactHeight;
	      } // Select mode


	      if (params.selectEntriesMode !== undefined) {
	        this.selectMode = !!params.selectEntriesMode;
	      } else if (this.selectMode === undefined) {
	        this.selectMode = false;
	      }

	      if (main_core.Type.isInteger(params.SCALE_OFFSET_BEFORE)) {
	        this.SCALE_OFFSET_BEFORE = parseInt(params.SCALE_OFFSET_BEFORE);
	      }

	      if (main_core.Type.isInteger(params.SCALE_OFFSET_AFTER)) {
	        this.SCALE_OFFSET_AFTER = parseInt(params.SCALE_OFFSET_AFTER);
	      }

	      if (main_core.Type.isInteger(params.maxTimelineSize)) {
	        this.maxTimelineSize = parseInt(params.maxTimelineSize);
	      }

	      if (main_core.Type.isInteger(params.minEntryRows)) {
	        this.MIN_ENTRY_ROWS = parseInt(params.minEntryRows);
	      }

	      if (main_core.Type.isInteger(params.maxEntryRows)) {
	        this.MAX_ENTRY_ROWS = parseInt(params.maxEntryRows);
	      }

	      if (main_core.Type.isInteger(params.width)) {
	        this.width = parseInt(params.width);
	      }

	      if (main_core.Type.isInteger(params.height)) {
	        this.height = parseInt(params.height);
	      }

	      if (main_core.Type.isInteger(params.minWidth)) {
	        this.minWidth = parseInt(params.minWidth);
	      }

	      if (main_core.Type.isInteger(params.minHeight)) {
	        this.minHeight = parseInt(params.minHeight);
	      }

	      this.width = Math.max(this.minWidth, this.width);
	      this.height = Math.max(this.minHeight, this.height);

	      if (main_core.Type.isArray(params.workTime)) {
	        this.workTime = params.workTime;
	      }

	      this.extendScaleTime(this.workTime[0], this.workTime[1]);
	      this.weekHolidays = params.weekHolidays || this.weekHolidays || [];
	      this.yearHolidays = params.yearHolidays || this.yearHolidays || [];
	      this.accuracy = params.accuracy || this.accuracy || 300; // 5 min

	      this.clickSelectorScaleAccuracy = params.clickSelectorScaleAccuracy || this.accuracy; // 5 min

	      this.selectorAccuracy = parseInt(params.selectorAccuracy) || this.selectorAccuracy || 300; // 5 min

	      this.entriesListWidth = parseInt(params.entriesListWidth) || this.entriesListWidth || 200;
	      this.timelineCellWidth = params.timelineCellWidth || this.timelineCellWidth || 40;
	      this.solidStatus = params.solidStatus === true;
	      this.showEntiesHeader = params.showEntiesHeader === undefined ? true : !!params.showEntiesHeader;
	      this.showEntryName = params.showEntryName === undefined ? true : !!params.showEntryName;

	      if (this.isOneDayScale() && this.timelineCellWidth < 100) {
	        this.timelineCellWidthOrig = this.timelineCellWidth;
	        this.timelineCellWidth = 100;
	      } else if (this.timelineCellWidthOrig && !this.isOneDayScale()) {
	        this.timelineCellWidth = this.timelineCellWidthOrig;
	        this.timelineCellWidthOrig = false;
	      }

	      if (this.allowAdjustCellWidth === undefined || params.allowAdjustCellWidth !== undefined) {
	        this.allowAdjustCellWidth = this.readonly && this.compactMode && params.allowAdjustCellWidth !== false;
	      }

	      if (params.locked !== undefined) {
	        this.locked = params.locked;
	      }

	      this.adjustCellWidth(); // Scale params

	      this.setScaleLimits(params.scaleDateFrom, params.scaleDateTo);
	    }
	  }, {
	    key: "setScaleLimits",
	    value: function setScaleLimits(scaleDateFrom, scaleDateTo) {
	      if (scaleDateFrom !== undefined) {
	        this.scaleDateFrom = main_core.Type.isDate(scaleDateFrom) ? scaleDateFrom : calendar_util.Util.parseDate(scaleDateFrom);
	      }

	      if (!main_core.Type.isDate(this.scaleDateFrom)) {
	        if (this.compactMode && this.readonly) {
	          this.scaleDateFrom = new Date();
	        } else {
	          this.scaleDateFrom = new Date(new Date().getTime() - calendar_util.Util.getDayLength() * this.SCALE_OFFSET_BEFORE);
	        }
	      }

	      this.scaleDateFrom.setHours(this.isOneDayScale() ? 0 : this.shownScaleTimeFrom, 0, 0, 0);

	      if (scaleDateTo !== undefined) {
	        this.scaleDateTo = BX.type.isString(scaleDateTo) ? calendar_util.Util.parseDate(scaleDateTo) : scaleDateTo;
	      }

	      if (!main_core.Type.isDate(this.scaleDateTo)) {
	        if (this.compactMode && this.readonly) {
	          this.scaleDateTo = new Date();
	        } else {
	          this.scaleDateTo = new Date(new Date().getTime() + calendar_util.Util.getDayLength() * this.SCALE_OFFSET_AFTER);
	        }
	      }

	      this.scaleDateTo.setHours(this.isOneDayScale() ? 0 : this.shownScaleTimeTo, 0, 0, 0);
	    }
	  }, {
	    key: "SetLoadedDataLimits",
	    value: function SetLoadedDataLimits(from, to) {
	      if (from) {
	        this.loadedDataFrom = from.getTime ? from : calendar_util.Util.parseDate(from);
	      }

	      if (to) {
	        this.loadedDataTo = to.getTime ? to : calendar_util.Util.parseDate(to);
	      }
	    }
	  }, {
	    key: "extendScaleTime",
	    value: function extendScaleTime(fromTime, toTime) {
	      if (fromTime !== false && !isNaN(parseInt(fromTime))) {
	        this.shownScaleTimeFrom = Math.min(parseInt(fromTime), this.shownScaleTimeFrom, 23);
	        this.shownScaleTimeFrom = Math.max(this.shownScaleTimeFrom, 0);

	        if (this.scaleDateFrom) {
	          this.scaleDateFrom.setHours(this.shownScaleTimeFrom, 0, 0, 0);
	        }
	      }

	      if (toTime !== false && !isNaN(parseInt(toTime))) {
	        this.shownScaleTimeTo = Math.max(parseInt(toTime), this.shownScaleTimeTo, 1);
	        this.shownScaleTimeTo = Math.min(this.shownScaleTimeTo, 24);

	        if (this.scaleDateTo) {
	          this.scaleDateTo.setHours(this.shownScaleTimeTo, 0, 0, 0);
	        }
	      }

	      this.rebuildDebounce(); //this.checkSelectorPosition = this.shownScaleTimeFrom !== 0 || this.shownScaleTimeTo !== 24;
	    }
	  }, {
	    key: "adjustCellWidth",
	    value: function adjustCellWidth() {
	      if (this.allowAdjustCellWidth) {
	        this.timelineCellWidth = Math.round(this.width / ((this.shownScaleTimeTo - this.shownScaleTimeFrom) * 3600 / this.scaleSize));
	      }
	    }
	  }, {
	    key: "build",
	    value: function build() {
	      var _this3 = this;

	      if (!main_core.Type.isDomNode(this.DOM.wrap)) {
	        throw new TypeError("Wrap is not DOM node");
	      }

	      this.DOM.wrap.style.width = this.width + 'px'; // Left part - list of users and other resources

	      var entriesListWidth = this.compactMode ? 0 : this.entriesListWidth; // Timeline with accessibility information

	      this.DOM.mainWrap = this.DOM.wrap.appendChild(BX.create('DIV', {
	        props: {
	          className: 'calendar-planner-main-container calendar-planner-main-container-resource'
	        },
	        style: {
	          minHeight: this.minHeight + 'px',
	          height: this.height + 'px',
	          width: this.width + 'px'
	        }
	      }));

	      if (!this.showEntryName) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-entry-icons-only');
	      }

	      if (this.readonly) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-readonly');
	      }

	      this.DOM.entriesOuterWrap = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-user-container\" style=\"width: ", "px; height: ", "px;\"></div>\n\t\t"])), entriesListWidth, this.height));
	      calendar_util.Util.preventSelection(this.DOM.entriesOuterWrap);

	      if (this.compactMode) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-compact');
	        this.DOM.entriesOuterWrap.style.display = 'none';
	      }

	      if (this.isOneDayScale()) {
	        main_core.Dom.addClass(this.DOM.entriesOuterWrap, 'calendar-planner-no-daytitle');
	      } else {
	        main_core.Dom.removeClass(this.DOM.entriesOuterWrap, 'calendar-planner-no-daytitle');
	      }

	      if (this.showEntiesHeader !== false) {
	        this.DOM.entrieListHeader = this.DOM.entriesOuterWrap.appendChild(main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-header\"></div>\n\t\t\t"])))).appendChild(main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-general-info\"></div>\n\t\t\t"])))).appendChild(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-users-header\"></div>\n\t\t\t"]))));
	        this.entriesListTitleCounter = this.DOM.entrieListHeader.appendChild(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"calendar-planner-users-item\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), main_core.Loc.getMessage('EC_PL_ATTENDEES_TITLE') + ' ')).appendChild(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<span></span>"]))));
	      }

	      this.DOM.entrieListWrap = this.DOM.entriesOuterWrap.appendChild(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-user-container-inner\"></div>\n\t\t"])))); // Fixed cont with specific width and height

	      this.DOM.timelineFixedWrap = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-timeline-wrapper\" style=\"height: ", "px\"></div>\n\t\t"])), this.height));

	      if (this.isLocked()) {
	        this.lock();
	      } // Movable cont - used to move scale and data containers easy and at the same time


	      this.DOM.timelineInnerWrap = this.DOM.timelineFixedWrap.appendChild(main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-timeline-inner-wrapper\" data-bx-planner-meta=\"timeline\"></div>\n\t\t"])))); // Scale container

	      this.DOM.timelineScaleWrap = this.DOM.timelineInnerWrap.appendChild(main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-time\"></div>\n\t\t"]))));
	      calendar_util.Util.preventSelection(this.DOM.timelineScaleWrap); // Accessibility container

	      this.DOM.timelineDataWrap = this.DOM.timelineInnerWrap.appendChild(main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-timeline-container\" style=\"height: ", "px\"></div>\n\t\t"])), this.height)); // Container with accessibility entries elements

	      this.DOM.accessibilityWrap = this.DOM.timelineDataWrap.appendChild(main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-acc-wrap\"></div>\n\t\t"])))); // Selector

	      this.selector = new Selector({
	        selectMode: this.selectMode,
	        timelineWrap: this.DOM.timelineFixedWrap,
	        getPosByDate: this.getPosByDate.bind(this),
	        getDateByPos: this.getDateByPos.bind(this),
	        getPosDateMap: function getPosDateMap() {
	          return _this3.posDateMap;
	        },
	        useAnimation: this.useAnimation,
	        solidStatus: this.solidStatus,
	        getScaleInfo: function getScaleInfo() {
	          return {
	            scale: _this3.scaleType,
	            shownTimeFrom: _this3.shownScaleTimeFrom,
	            shownTimeTo: _this3.shownScaleTimeTo
	          };
	        },
	        getTimelineWidth: function getTimelineWidth() {
	          return parseInt(_this3.DOM.timelineInnerWrap.style.width);
	        }
	      });
	      this.DOM.timelineDataWrap.appendChild(this.selector.getWrap());
	      this.DOM.mainWrap.appendChild(this.selector.getTitleNode());
	      this.selector.subscribe('onChange', this.handleSelectorChanges.bind(this));
	      this.selector.subscribe('doCheckStatus', this.doCheckSelectorStatus.bind(this));

	      if (this.selectMode) {
	        this.selectedEntriesWrap = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-timeline-select-entries-wrap\"></div>\n\t\t\t"]))));
	        this.hoverRow = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-timeline-hover-row\" style=\"top: 0; width: ", "px\"></div>\n\t\t\t"])), parseInt(this.DOM.mainWrap.offsetWidth)));
	        main_core.Event.bind(document, 'mousemove', this.mouseMoveHandler.bind(this));
	      }

	      if (!this.compactMode) {
	        this.DOM.settingsButton = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["<div class=\"calendar-planner-settings-icon-container\" title=\"", "\"><span class=\"calendar-planner-settings-title\">", "</span><span class=\"calendar-planner-settings-icon\"></span></div>"])), main_core.Loc.getMessage('EC_PL_SETTINGS_SCALE'), main_core.Loc.getMessage('EC_PL_SETTINGS_SCALE')));
	        main_core.Event.bind(this.DOM.settingsButton, 'click', function () {
	          return _this3.showSettingsPopup();
	        });
	      }

	      this.built = true;
	    }
	  }, {
	    key: "buildTimeline",
	    value: function buildTimeline(clearCache) {
	      if (this.isBuilt() && (this.lastTimelineKey !== this.getTimelineShownKey() || clearCache === true)) {
	        if (this.DOM.timelineScaleWrap) {
	          main_core.Dom.clean(this.DOM.timelineScaleWrap);
	        }

	        this.scaleData = this.getScaleData();
	        var outerDayCont,
	            dayTitle,
	            cont = this.DOM.timelineScaleWrap;

	        for (var i = 0; i < this.scaleData.length; i++) {
	          if (this.showTimelineDayTitle && !this.isOneDayScale()) {
	            if (this.scaleDayTitles[this.scaleData[i].daystamp]) {
	              cont = this.scaleDayTitles[this.scaleData[i].daystamp];
	            } else {
	              outerDayCont = this.DOM.timelineScaleWrap.appendChild(main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"calendar-planner-time-day-outer\"></div>\n\t\t\t\t\t\t"])))); //F d, l

	              dayTitle = outerDayCont.appendChild(main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"calendar-planner-time-day-title\">\n\t\t\t\t\t\t\t\t<span>", "</span>\n\t\t\t\t\t\t\t\t<div class=\"calendar-planner-time-day-border\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t"])), BX.date.format(this.dayOfWeekMonthFormat, this.scaleData[i].timestamp / 1000)));
	              cont = outerDayCont.appendChild(main_core.Tag.render(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t<div class=\"calendar-planner-time-day\"></div>\n\t\t\t\t\t\t"]))));
	              this.scaleDayTitles[this.scaleData[i].daystamp] = cont;
	            }
	          }

	          var className = 'calendar-planner-time-hour-item' + (this.scaleData[i].dayStart ? ' calendar-planner-day-start' : '');

	          if ((this.scaleType === '15min' || this.scaleType === '30min') && this.scaleData[i].title !== '') {
	            className += ' calendar-planner-time-hour-bold';
	          }

	          this.scaleData[i].cell = cont.appendChild(BX.create('DIV', {
	            props: {
	              className: className
	            },
	            style: {
	              width: this.timelineCellWidth + 'px',
	              minWidth: this.timelineCellWidth + 'px'
	            },
	            html: this.scaleData[i].title ? '<i>' + this.scaleData[i].title + '</i>' : ''
	          }));

	          if (!this.isOneDayScale() && this.scaleData[i + 1] && this.scaleData[i + 1].dayStart) {
	            cont.appendChild(main_core.Tag.render(_templateObject19 || (_templateObject19 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div class=\"calendar-planner-timeline-border\"></div>\n\t\t\t\t\t"]))));
	          }
	        }

	        var mapDatePosRes = this.mapDatePos();
	        this.posDateMap = mapDatePosRes.posDateMap;
	        var timelineOffset = this.DOM.timelineScaleWrap.offsetWidth;
	        this.DOM.timelineInnerWrap.style.width = timelineOffset + 'px';
	        this.DOM.entrieListWrap.style.top = parseInt(this.DOM.timelineDataWrap.offsetTop) + 10 + 'px';
	        this.lastTimelineKey = this.getTimelineShownKey();
	        this.checkRebuildTimeout(timelineOffset);
	      }
	    }
	  }, {
	    key: "getTimelineShownKey",
	    value: function getTimelineShownKey() {
	      return 'tm_' + this.scaleDateFrom.getTime() + '_' + this.scaleDateTo.getTime();
	    }
	  }, {
	    key: "checkRebuildTimeout",
	    value: function checkRebuildTimeout(timelineOffset) {
	      var _this4 = this;

	      var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 300;

	      if (!this._checkRebuildTimeoutCount) {
	        this._checkRebuildTimeoutCount = 0;
	      }

	      if (this.rebuildTimeout) {
	        this.rebuildTimeout = !!clearTimeout(this.rebuildTimeout);
	      }

	      if (this._checkRebuildTimeoutCount <= 10 && main_core.Type.isElementNode(this.DOM.timelineScaleWrap) && main_core.Dom.isShown(this.DOM.timelineScaleWrap)) {
	        this._checkRebuildTimeoutCount++;
	        this.rebuildTimeout = setTimeout(function () {
	          if (timelineOffset !== _this4.DOM.timelineScaleWrap.offsetWidth) {
	            if (_this4.rebuildTimeout) {
	              _this4.rebuildTimeout = !!clearTimeout(_this4.rebuildTimeout);
	            }

	            _this4.rebuild();

	            if (_this4.selector) {
	              _this4.selector.focus(false, 300);
	            }
	          } else {
	            _this4.checkRebuildTimeout(timelineOffset, timeout);
	          }
	        }, timeout);
	      } else {
	        delete this._checkRebuildTimeoutCount;
	      }
	    }
	  }, {
	    key: "rebuildDebounce",
	    value: function rebuildDebounce() {
	      var timeout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : this.REBUILD_DELAY;
	      main_core.Runtime.debounce(this.rebuild, timeout, this)();
	    }
	  }, {
	    key: "rebuild",
	    value: function rebuild() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (this.isBuilt()) {
	        this.buildTimeline(true);
	        this.update(this.entries, this.accessibility);
	        this.adjustHeight();
	        this.resizePlannerWidth(this.width);

	        if (params.updateSelector !== false) {
	          this.selector.update(params.selectorParams);
	        }

	        this.clearCacheTime();
	      }
	    }
	  }, {
	    key: "getScaleData",
	    value: function getScaleData() {
	      this.scaleData = [];
	      this.scaleDayTitles = {};
	      var ts,
	          scaleFrom,
	          scaleTo,
	          time,
	          dayStamp,
	          title,
	          curDayStamp = false,
	          timeFrom = this.isOneDayScale() ? 0 : this.shownScaleTimeFrom,
	          timeTo = this.isOneDayScale() ? 0 : this.shownScaleTimeTo;
	      this.scaleDateFrom.setHours(timeFrom, 0, 0, 0);
	      this.scaleDateTo.setHours(timeTo, 0, 0, 0);
	      scaleFrom = this.scaleDateFrom.getTime();
	      scaleTo = this.scaleDateTo.getTime();

	      for (ts = scaleFrom; ts < scaleTo; ts += this.scaleSize * 1000) {
	        time = parseFloat(BX.date.format('H.i', ts / 1000));
	        if (this.isOneDayScale()) title = BX.date.format('d F, D', ts / 1000);else title = BX.date.format('i', ts / 1000) === '00' ? BX.date.format(this.SCALE_TIME_FORMAT, ts / 1000) : '';

	        if (this.isOneDayScale() || time >= timeFrom && time < timeTo) {
	          dayStamp = BX.date.format('d.m.Y', ts / 1000);
	          this.scaleData.push({
	            daystamp: dayStamp,
	            timestamp: ts,
	            value: ts,
	            title: title,
	            dayStart: curDayStamp !== dayStamp
	          });
	          curDayStamp = dayStamp;
	        }
	      }

	      return this.scaleData;
	    }
	  }, {
	    key: "isOneDayScale",
	    value: function isOneDayScale() {
	      return this.scaleType === '1day';
	    }
	  }, {
	    key: "addAccessibilityItem",
	    value: function addAccessibilityItem(entry, wrap) {
	      var timeFrom,
	          timeTo,
	          hidden = false,
	          fromTimestamp = entry.fromTimestamp,
	          toTimestamp = entry.toTimestampReal || entry.toTimestamp,
	          shownScaleTimeFrom = this.isOneDayScale() ? 0 : this.shownScaleTimeFrom,
	          shownScaleTimeTo = this.isOneDayScale() ? 24 : this.shownScaleTimeTo,
	          from = new Date(fromTimestamp),
	          to = new Date(toTimestamp);
	      timeFrom = parseInt(from.getHours()) + from.getMinutes() / 60;
	      timeTo = parseInt(to.getHours()) + to.getMinutes() / 60;

	      if (timeFrom > shownScaleTimeTo) {
	        from = new Date(from.getTime() + calendar_util.Util.getDayLength() - 1);
	        from.setHours(shownScaleTimeFrom, 0, 0, 0);

	        if (from.getTime() >= to.getTime()) {
	          hidden = true;
	        }
	      }

	      if (!hidden && timeFrom < shownScaleTimeFrom) {
	        from.setHours(shownScaleTimeFrom, 0, 0, 0);

	        if (from.getTime() >= to.getTime()) {
	          hidden = true;
	        }
	      }

	      if (!hidden && timeTo > shownScaleTimeTo) {
	        to.setHours(shownScaleTimeTo, 0, 0, 0);

	        if (from.getTime() >= to.getTime()) {
	          hidden = true;
	        }
	      }

	      if (!hidden && timeTo < shownScaleTimeFrom) {
	        to = new Date(to.getTime() - calendar_util.Util.getDayLength() + 1);
	        to.setHours(shownScaleTimeTo, 0, 0, 0);

	        if (from.getTime() >= to.getTime()) {
	          hidden = true;
	        }
	      }

	      if (!hidden) {
	        var fromPos = this.getPosByDate(from),
	            toPos = this.getPosByDate(to);
	        entry.node = wrap.appendChild(BX.create('DIV', {
	          props: {
	            className: 'calendar-planner-acc-entry' + (entry.type && entry.type === 'hr' ? ' calendar-planner-acc-entry-hr' : '')
	          },
	          style: {
	            left: fromPos + 'px',
	            width: Math.max(toPos - fromPos, 3) + 'px'
	          }
	        }));

	        if (entry.title || entry.name) {
	          entry.node.title = entry.title || entry.name;
	        }
	      }
	    }
	  }, {
	    key: "displayEntryRow",
	    value: function displayEntryRow(entry) {
	      var _this5 = this;

	      var accessibility = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
	      var rowWrap;

	      if (entry.type === 'moreLink') {
	        rowWrap = this.DOM.entrieListWrap.appendChild(main_core.Tag.render(_templateObject20 || (_templateObject20 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-user\"></div>\n\t\t\t"]))));

	        if (this.showEntryName) {
	          this.DOM.showMoreUsersLink = rowWrap.appendChild(main_core.Tag.render(_templateObject21 || (_templateObject21 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-all-users\" title=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), entry.title || '', entry.name));
	        } else {
	          this.DOM.showMoreUsersLink = rowWrap.appendChild(main_core.Tag.render(_templateObject22 || (_templateObject22 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-users-more\" title=\"", "\">\n\t\t\t\t\t\t<span class=\"calendar-planner-users-more-btn\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t"])), entry.name || ''));
	        }

	        main_core.Event.bind(this.DOM.showMoreUsersLink, 'click', function () {
	          return _this5.showMoreUsers();
	        });
	      } else if (entry.type === 'lastUsers') {
	        rowWrap = this.DOM.entrieListWrap.appendChild(main_core.Tag.render(_templateObject23 || (_templateObject23 = babelHelpers.taggedTemplateLiteral(["\t\n\t\t\t\t<div class=\"calendar-planner-user\"></div>\n\t\t\t"]))));

	        if (this.showEntryName) {
	          this.DOM.showMoreUsersLink = rowWrap.appendChild(main_core.Tag.render(_templateObject24 || (_templateObject24 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-all-users calendar-planner-last-users\" title=\"", "\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t"])), entry.title || '', entry.name));
	        } else {
	          this.DOM.showMoreUsersLink = rowWrap.appendChild(main_core.Tag.render(_templateObject25 || (_templateObject25 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-users-more\" title=\"", "\">\n\t\t\t\t\t\t<span class=\"calendar-planner-users-last-btn\"></span>\n\t\t\t\t\t</div>\n\t\t\t\t"])), entry.title || entry.name));
	        }
	      } else if (entry.id && entry.type === 'user') {
	        rowWrap = this.DOM.entrieListWrap.appendChild(BX.create('DIV', {
	          attrs: {
	            'data-bx-planner-entry': entry.uid,
	            className: 'calendar-planner-user' + (entry.emailUser ? ' calendar-planner-email-user' : '')
	          }
	        }));

	        if (entry.status && this.entryStatusMap[entry.status]) {
	          rowWrap.appendChild(BX.create('SPAN', {
	            props: {
	              className: 'calendar-planner-user-status-icon ' + this.entryStatusMap[entry.status],
	              title: main_core.Loc.getMessage('EC_PL_STATUS_' + entry.status.toUpperCase())
	            }
	          }));
	        }

	        rowWrap.appendChild(Planner.getEntryAvatarNode(entry));

	        if (this.showEntryName) {
	          rowWrap.appendChild(main_core.Tag.render(_templateObject26 || (_templateObject26 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"calendar-planner-user-name\"></span>\n\t\t\t\t"])))).appendChild(BX.create('SPAN', {
	            props: {
	              className: 'calendar-planner-entry-name'
	            },
	            attrs: {
	              'bx-tooltip-user-id': entry.id,
	              'bx-tooltip-classname': 'calendar-planner-user-tooltip'
	            },
	            style: {
	              width: this.entriesListWidth - 42 + 'px'
	            },
	            text: entry.name
	          }));
	        }
	      } else if (entry.id && entry.type === 'room') {
	        rowWrap = this.DOM.entrieListWrap.appendChild(main_core.Tag.render(_templateObject27 || (_templateObject27 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-user\"></div>\n\t\t\t"]))));

	        if (this.showEntryName) {
	          rowWrap.appendChild(main_core.Tag.render(_templateObject28 || (_templateObject28 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"calendar-planner-user-name\"></span>\n\t\t\t\t"])))).appendChild(main_core.Tag.render(_templateObject29 || (_templateObject29 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"calendar-planner-entry-name\" style=\"width: ", "px;\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t"])), this.entriesListWidth - 20, entry.name));
	        } else {
	          rowWrap.appendChild(main_core.Tag.render(_templateObject30 || (_templateObject30 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-location-image-icon\" title=\"", "\"></div>\n\t\t\t\t"])), entry.name));
	        }
	      } else if (entry.type === 'resource') {
	        if (!this.entriesResourceListWrap || !BX.isNodeInDom(this.entriesResourceListWrap)) {
	          this.entriesResourceListWrap = this.DOM.entrieListWrap.appendChild(main_core.Tag.render(_templateObject31 || (_templateObject31 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-container-resource\">\n\t\t\t\t\t\t<div class=\"calendar-planner-resource-header\">\n\t\t\t\t\t\t\t<span class=\"calendar-planner-users-item\">", "</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t"])), main_core.Loc.getMessage('EC_PL_RESOURCE_TITLE')));
	        }

	        rowWrap = this.entriesResourceListWrap.appendChild(main_core.Tag.render(_templateObject32 || (_templateObject32 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-user\" data-bx-planner-entry=\"", "\"></div>\n\t\t\t"])), entry.uid));

	        if (this.showEntryName) {
	          rowWrap.appendChild(main_core.Tag.render(_templateObject33 || (_templateObject33 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"calendar-planner-user-name\"></span>\n\t\t\t\t"])))).appendChild(main_core.Tag.render(_templateObject34 || (_templateObject34 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"calendar-planner-entry-name\" style=\"width: ", "px;\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t<span>\n\t\t\t\t"])), this.entriesListWidth - 20, entry.name));
	        } else {
	          rowWrap.appendChild(main_core.Tag.render(_templateObject35 || (_templateObject35 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-location-image-icon\" title=\"", "\"></div>\n\t\t\t\t"])), entry.name));
	        }
	      } else {
	        rowWrap = this.DOM.entrieListWrap.appendChild(main_core.Tag.render(_templateObject36 || (_templateObject36 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-user\"></div>\n\t\t\t"]))));
	        rowWrap.appendChild(main_core.Tag.render(_templateObject37 || (_templateObject37 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-all-users\">", "</div>\n\t\t\t"])), entry.name));
	      }

	      var top = rowWrap.offsetTop + 13;
	      var dataRowWrap = this.DOM.accessibilityWrap.appendChild(main_core.Tag.render(_templateObject38 || (_templateObject38 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-timeline-space\" style=\"top:", "px\" data-bx-planner-entry=\"", "\"></div>\n\t\t"])), top, entry.uid || 0));

	      if (this.selectMode) {
	        entry.selectorControlWrap = this.selector.controlWrap.appendChild(main_core.Tag.render(_templateObject39 || (_templateObject39 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-selector-control-row\" data-bx-planner-entry=\"", "\" style=\"top: ", "px;\"></div>\n\t\t\t"])), entry.uid, top - 4));

	        if (entry.selected) {
	          this.selectEntryRow(entry);
	        }
	      } //this.entriesRowMap.set(entry, rowWrap);


	      this.entriesDataRowMap.set(entry.uid, dataRowWrap);
	      accessibility.forEach(function (item) {
	        item = Planner.prepareAccessibilityItem(item);

	        if (item) {
	          _this5.addAccessibilityItem(item, dataRowWrap);
	        }
	      });
	    }
	  }, {
	    key: "selectEntryRow",
	    value: function selectEntryRow(entry) {
	      if (BX.type.isPlainObject(entry)) {
	        var top = parseInt(entry.dataRowWrap.offsetTop);

	        if (!entry.selectWrap || !BX.isParentForNode(this.selectedEntriesWrap, entry.selectWrap)) {
	          entry.selectWrap = this.selectedEntriesWrap.appendChild(main_core.Tag.render(_templateObject40 || (_templateObject40 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"calendar-planner-timeline-selected\"></div>\n\t\t\t\t"]))));
	        }

	        entry.selectWrap.style.display = '';
	        entry.selectWrap.style.top = top + 36 + 'px';
	        entry.selectWrap.style.width = parseInt(this.DOM.mainWrap.offsetWidth) + 5 + 'px';
	        main_core.Dom.addClass(entry.selectorControlWrap, 'active');
	        entry.selected = true;
	        this.clearCacheTime();
	      }
	    }
	  }, {
	    key: "isEntrySelected",
	    value: function isEntrySelected(entry) {
	      return entry && entry.selected;
	    }
	  }, {
	    key: "deSelectEntryRow",
	    value: function deSelectEntryRow(entry) {
	      if (BX.type.isPlainObject(entry)) {
	        if (entry.selectWrap) {
	          entry.selectWrap.style.display = 'none';
	        }

	        if (entry.selectorControlWrap) {
	          main_core.Dom.removeClass(entry.selectorControlWrap, 'active');
	        }

	        entry.selected = false;
	        this.clearCacheTime();
	      }
	    }
	  }, {
	    key: "getEntryByUniqueId",
	    value: function getEntryByUniqueId(entryUniqueId) {
	      if (BX.type.isArray(this.entries)) {
	        return this.entries.find(function (entry) {
	          return entry.uid == entryUniqueId;
	        });
	      }

	      return null;
	    }
	  }, {
	    key: "bindEventHandlers",
	    value: function bindEventHandlers() {
	      main_core.Event.bind(this.DOM.wrap, 'click', this.handleClick.bind(this));
	      main_core.Event.bind(this.DOM.wrap, 'mousedown', this.handleMousedown.bind(this));
	      main_core.Event.bind(document, 'mousemove', this.handleMousemove.bind(this));
	      main_core.Event.bind(document, 'mouseup', this.handleMouseup.bind(this));
	      main_core.Event.bind(this.DOM.timelineFixedWrap, 'onwheel' in document ? 'wheel' : 'mousewheel', this.mouseWheelTimelineHandler.bind(this));
	    }
	  }, {
	    key: "handleClick",
	    value: function handleClick(e) {
	      if (!e) {
	        e = window.event;
	      }

	      this.clickMousePos = this.getMousePos(e);
	      var nodeTarget = e.target || e.srcElement,
	          accuracyMouse = 5;

	      if (this.selectMode && main_core.Dom.hasClass(nodeTarget, 'calendar-planner-selector-control-row')) {
	        var entry = this.getEntryByUniqueId(nodeTarget.getAttribute('data-bx-planner-entry'));

	        if (entry) {
	          if (!this.isEntrySelected(entry)) {
	            this.selectEntryRow(entry);
	          } else {
	            this.deSelectEntryRow(entry);
	          }

	          this.selector.checkStatus();
	          BX.onCustomEvent('OnCalendarPlannerSelectedEntriesOnChange', [{
	            plannerId: this.id,
	            entries: this.entries
	          }]);
	        }

	        return;
	      }

	      if (!this.readonly) {
	        var timeline = this.findTarget(nodeTarget, 'timeline'),
	            selector = this.findTarget(nodeTarget, 'selector');

	        if (timeline && !selector && Math.abs(this.clickMousePos.x - this.mouseDownMousePos.x) < accuracyMouse && Math.abs(this.clickMousePos.y - this.mouseDownMousePos.y) < accuracyMouse) {
	          var left = this.clickMousePos.x - BX.pos(this.DOM.timelineFixedWrap).left + this.DOM.timelineFixedWrap.scrollLeft;

	          if (this.clickSelectorScaleAccuracy !== this.accuracy) {
	            var mapDatePosRes = this.mapDatePos(this.clickSelectorScaleAccuracy);
	            var dateFrom = this.getDateByPos(left, false, mapDatePosRes.posDateMap);
	            left = this.getPosByDate(dateFrom);
	          }

	          this.selector.transit({
	            toX: left
	          });
	        }
	      }
	    }
	  }, {
	    key: "handleMousedown",
	    value: function handleMousedown(e) {
	      if (!e) {
	        e = window.event;
	      }

	      var nodeTarget = e.target || e.srcElement;
	      this.mouseDownMousePos = this.getMousePos(e);
	      this.mouseDown = true;

	      if (!this.readonly) {
	        var selector = this.findTarget(nodeTarget, 'selector');
	        this.startMousePos = this.mouseDownMousePos;

	        if (selector) {
	          if (this.findTarget(nodeTarget, 'selector-resize-right')) {
	            this.selector.startResize();
	          } else {
	            this.selector.startMove();
	          }
	        } else if (this.findTarget(nodeTarget, 'timeline')) {
	          this.startScrollTimeline();
	        }
	      }
	    }
	  }, {
	    key: "handleMouseup",
	    value: function handleMouseup() {
	      if (this.selector.isDragged()) {
	        this.selector.endMove();
	        this.selector.endResize();
	      }

	      if (this.timelineIsDraged) {
	        this.endScrollTimeline();
	      }

	      if (this.shown && !this.readonly && this.mouseDown) {
	        this.checkTimelineScroll();
	      }

	      this.mouseDown = false;
	      main_core.Dom.removeClass(document.body, 'calendar-planner-unselectable');
	    }
	  }, {
	    key: "handleMousemove",
	    value: function handleMousemove(e) {
	      var mousePos,
	          target = e.target || e.srcElement;

	      if (this.selectMode && target && target.getAttribute && target.getAttribute('data-bx-planner-entry')) {
	        this.lastTouchedEntry = target;
	      }

	      if (this.selector.isDragged()) {
	        mousePos = this.getMousePos(e);
	        this.selector.move(mousePos.x - this.startMousePos.x);
	        this.selector.resize(mousePos.x - this.startMousePos.x);
	      }

	      if (this.timelineIsDraged) {
	        mousePos = this.getMousePos(e);
	        this.scrollTimeline(mousePos.x - this.startMousePos.x);
	      }
	    }
	  }, {
	    key: "mouseWheelTimelineHandler",
	    value: function mouseWheelTimelineHandler(e) {
	      e = e || window.event;

	      if (this.shown && !this.readonly) {
	        if (main_core.Browser.isMac()) {
	          this.checkTimelineScroll();
	        } else {
	          var delta = e.deltaY || e.detail || e.wheelDelta;

	          if (Math.abs(delta) > 0) {
	            this.DOM.timelineFixedWrap.scrollLeft = Math.max(this.DOM.timelineFixedWrap.scrollLeft + Math.round(delta / 3), 0);
	            this.checkTimelineScroll();
	            return BX.PreventDefault(e);
	          }
	        }
	      }
	    }
	  }, {
	    key: "checkTimelineScroll",
	    value: function checkTimelineScroll() {
	      var minScroll = this.scrollStep;
	      var maxScroll = this.DOM.timelineFixedWrap.scrollWidth - this.DOM.timelineFixedWrap.offsetWidth - this.scrollStep; // Check and expand only if it is visible

	      if (this.DOM.timelineFixedWrap.offsetWidth > 0) {
	        if (this.DOM.timelineFixedWrap.scrollLeft <= minScroll) {
	          this.expandTimelineDirection = 'past';
	        } else if (this.DOM.timelineFixedWrap.scrollLeft >= maxScroll) {
	          this.expandTimelineDirection = 'future';
	        }

	        if (this.expandTimelineDirection) {
	          if (!this.isLoaderShown()) {
	            this.showLoader();
	          }

	          this.expandTimelineDebounce();
	        }
	      }
	    }
	  }, {
	    key: "startScrollTimeline",
	    value: function startScrollTimeline() {
	      this.timelineIsDraged = true;
	      this.timelineStartScrollLeft = this.DOM.timelineFixedWrap.scrollLeft;
	    }
	  }, {
	    key: "scrollTimeline",
	    value: function scrollTimeline(x) {
	      this.DOM.timelineFixedWrap.scrollLeft = Math.max(this.timelineStartScrollLeft - x, 0);
	    }
	  }, {
	    key: "endScrollTimeline",
	    value: function endScrollTimeline() {
	      this.timelineIsDraged = false;
	    }
	  }, {
	    key: "findTarget",
	    value: function findTarget(node, nodeMetaType, parentCont) {
	      if (!parentCont) parentCont = this.DOM.mainWrap;
	      var type = node && node.getAttribute ? node.getAttribute('data-bx-planner-meta') : null;

	      if (type !== nodeMetaType) {
	        if (node) {
	          node = BX.findParent(node, function (n) {
	            return n.getAttribute && n.getAttribute('data-bx-planner-meta') === nodeMetaType;
	          }, parentCont);
	        } else {
	          node = null;
	        }
	      }

	      return node;
	    }
	  }, {
	    key: "getMousePos",
	    value: function getMousePos(e) {
	      if (!e) e = window.event;
	      var x = 0,
	          y = 0;

	      if (e.pageX || e.pageY) {
	        x = e.pageX;
	        y = e.pageY;
	      } else if (e.clientX || e.clientY) {
	        x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
	        y = e.clientY + (document.documentElement.scrollTop || document.body.scrollTop) - document.documentElement.clientTop;
	      }

	      return {
	        x: x,
	        y: y
	      };
	    }
	  }, {
	    key: "setScaleType",
	    value: function setScaleType(scaleType) {
	      if (!this.scaleTypes.includes(scaleType)) {
	        scaleType = '1hour';
	      }

	      this.scaleType = scaleType;
	      this.scaleSize = Planner.getScaleSize(scaleType);

	      if (this.isOneDayScale() && this.timelineCellWidth < 100) {
	        this.timelineCellWidthOrig = this.timelineCellWidth;
	        this.timelineCellWidth = 100;
	      } else if (!this.isOneDayScale() && this.timelineCellWidthOrig) {
	        this.timelineCellWidth = this.timelineCellWidthOrig;
	        this.timelineCellWidthOrig = false;
	      }

	      if (this.isOneDayScale()) {
	        main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-fulldaymode');

	        if (this.DOM.entriesOuterWrap) {
	          main_core.Dom.addClass(this.DOM.entriesOuterWrap, 'calendar-planner-no-daytitle');
	        }
	      } else {
	        main_core.Dom.removeClass(this.DOM.mainWrap, 'calendar-planner-fulldaymode');

	        if (this.DOM.entriesOuterWrap) {
	          main_core.Dom.removeClass(this.DOM.entriesOuterWrap, 'calendar-planner-no-daytitle');
	        }
	      }
	    }
	  }, {
	    key: "mapDatePos",
	    value: function mapDatePos(accuracy) {
	      if (!accuracy) {
	        accuracy = this.accuracy;
	      }

	      var datePosMap = {};
	      var posDateMap = {};
	      var i, j, tsi, xi, tsj, xj, cellWidth;
	      this.substeps = Math.round(this.scaleSize / accuracy);
	      this.posAccuracy = this.timelineCellWidth / this.substeps;
	      accuracy = accuracy * 1000;
	      var scaleSize = this.scaleData[1].timestamp - this.scaleData[0].timestamp;

	      for (i = 0; i < this.scaleData.length; i++) {
	        tsi = this.scaleData[i].timestamp;
	        xi = parseInt(this.scaleData[i].cell.offsetLeft);
	        cellWidth = parseInt(this.scaleData[i].cell.offsetWidth);

	        if (!datePosMap[tsi]) {
	          datePosMap[tsi] = xi;
	        }

	        posDateMap[xi] = tsi;

	        for (j = 1; j <= cellWidth; j++) {
	          tsj = tsi + Math.round(j * scaleSize / cellWidth / accuracy) * accuracy;
	          xj = xi + j;

	          if (!datePosMap[tsi]) {
	            datePosMap[tsj] = xj;
	          }

	          posDateMap[xj] = tsj;

	          if (j === cellWidth && (!this.scaleData[i + 1] || this.scaleData[i + 1].dayStart)) {
	            datePosMap[xj + '_end'] = tsj;
	          }
	        }
	      }

	      return {
	        datePosMap: datePosMap,
	        posDateMap: posDateMap
	      };
	    }
	  }, {
	    key: "getPosByDate",
	    value: function getPosByDate(date) {
	      var x = 0;

	      if (date && babelHelpers["typeof"](date) !== 'object') {
	        date = calendar_util.Util.parseDate(date);
	      }

	      if (date && babelHelpers["typeof"](date) === 'object') {
	        var curInd = 0;
	        var timestamp = date.getTime();

	        for (var i = 0; i < this.scaleData.length; i++) {
	          if (timestamp >= this.scaleData[i].timestamp) {
	            curInd = i;
	          } else {
	            break;
	          }
	        }

	        if (this.scaleData[curInd] && this.scaleData[curInd].cell) {
	          x = this.scaleData[curInd].cell.offsetLeft;
	          var cellWidth = this.scaleData[curInd].cell.offsetWidth;
	          var deltaTs = Math.round((timestamp - this.scaleData[curInd].timestamp) / 1000);

	          if (deltaTs > 0) {
	            x += Math.round(deltaTs * 10 / this.scaleSize * cellWidth) / 10;
	          }
	        }
	      }

	      return x;
	    }
	  }, {
	    key: "getDateByPos",
	    value: function getDateByPos(x, end, posDateMap) {
	      if (!posDateMap) {
	        posDateMap = this.posDateMap;
	      }

	      var date,
	          timestamp = end && posDateMap[x + '_end'] ? posDateMap[x + '_end'] : posDateMap[x];

	      if (!timestamp) {
	        x = Math.round(x);
	        timestamp = end && posDateMap[x + '_end'] ? posDateMap[x + '_end'] : posDateMap[x];
	      }

	      if (timestamp) {
	        date = new Date(timestamp);
	      }

	      return date;
	    }
	  }, {
	    key: "showMoreUsers",
	    value: function showMoreUsers() {
	      this.MIN_ENTRY_ROWS = this.MAX_ENTRY_ROWS;
	      this.update(this.entries, this.accessibility);
	      this.rebuildDebounce();
	    }
	  }, {
	    key: "adjustHeight",
	    value: function adjustHeight() {
	      var newHeight = this.DOM.entrieListWrap.offsetHeight + this.DOM.entrieListWrap.offsetTop + 30,
	          currentHeight = parseInt(this.DOM.wrap.style.height) || this.height;

	      if (this.compactMode && currentHeight < newHeight || !this.compactMode) {
	        this.DOM.wrap.style.height = currentHeight + 'px';
	        this.resizePlannerHeight(newHeight, Math.abs(newHeight - currentHeight) > 10);
	      }
	    }
	  }, {
	    key: "resizePlannerHeight",
	    value: function resizePlannerHeight(height) {
	      var _this6 = this;

	      var animation = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      this.height = height;

	      if (animation) {
	        // Stop animation before starting another one
	        if (this.resizeAnimation) {
	          this.resizeAnimation.stop();
	          this.resizeAnimation = null;
	        }

	        this.resizeAnimation = new BX.easing({
	          duration: 800,
	          start: {
	            height: parseInt(this.DOM.wrap.style.height)
	          },
	          finish: {
	            height: height
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function step(state) {
	            _this6.resizePlannerHeight(state.height, false);
	          },
	          complete: function complete() {
	            _this6.resizeAnimation = null;
	          }
	        });
	        this.resizeAnimation.animate();
	      } else {
	        this.DOM.wrap.style.height = height + 'px';
	        this.DOM.mainWrap.style.height = height + 'px';
	        this.DOM.timelineFixedWrap.style.height = height + 'px';
	        var timelineDataContHeight = this.DOM.entrieListWrap.offsetHeight + 3;
	        this.DOM.timelineDataWrap.style.height = timelineDataContHeight + 'px'; // Todo: resize selector
	        //this.selector.wrap.style.height = (timelineDataContHeight + 10) + 'px';

	        this.DOM.entriesOuterWrap.style.height = height + 'px';

	        if (this.DOM.proposeTimeButton && this.DOM.proposeTimeButton.style.display !== "none") {
	          this.DOM.proposeTimeButton.style.top = this.DOM.timelineDataWrap.offsetTop + timelineDataContHeight / 2 - 16 + "px";
	        }
	      }
	    }
	  }, {
	    key: "resizePlannerWidth",
	    value: function resizePlannerWidth(width, animation) {
	      if (!animation && this.DOM.wrap && this.DOM.mainWrap) {
	        this.DOM.wrap.style.width = width + 'px';
	        var entriesListWidth = this.compactMode ? 0 : this.entriesListWidth;
	        this.DOM.mainWrap.style.width = width + 'px';
	        this.DOM.entriesOuterWrap.style.width = entriesListWidth + 'px';
	      }
	    }
	  }, {
	    key: "expandTimeline",
	    value: function expandTimeline(scaleDateFrom, scaleDateTo) {
	      var _this7 = this;

	      var loadedTimelineSize;
	      var scrollLeft;
	      var prevScaleDateFrom = this.scaleDateFrom;
	      var prevScaleDateTo = this.scaleDateTo;
	      if (!scaleDateFrom) scaleDateFrom = this.scaleDateFrom;
	      if (!scaleDateTo) scaleDateTo = this.scaleDateTo;

	      if (this.expandTimelineDirection === 'past') {
	        var oldScaleDateFrom = new Date(this.scaleDateFrom.getTime());
	        this.scaleDateFrom = new Date(scaleDateFrom.getTime() - calendar_util.Util.getDayLength() * this.EXPAND_OFFSET);
	        loadedTimelineSize = (this.scaleDateTo.getTime() - this.scaleDateFrom.getTime()) / calendar_util.Util.getDayLength();

	        if (loadedTimelineSize > this.maxTimelineSize) {
	          this.scaleDateTo = new Date(this.scaleDateFrom.getTime() + calendar_util.Util.getDayLength() * this.maxTimelineSize);
	          this.loadedDataFrom = this.scaleDateFrom;
	          this.loadedDataTo = this.scaleDateTo;
	          this.limitScaleSizeMode = true;
	        }

	        scrollLeft = this.getPosByDate(oldScaleDateFrom);
	      } else if (this.expandTimelineDirection === 'future') {
	        var oldDateTo = this.scaleDateTo;
	        scrollLeft = this.DOM.timelineFixedWrap.scrollLeft;
	        this.scaleDateTo = new Date(scaleDateTo.getTime() + calendar_util.Util.getDayLength() * this.EXPAND_OFFSET);
	        loadedTimelineSize = (this.scaleDateTo.getTime() - this.scaleDateFrom.getTime()) / calendar_util.Util.getDayLength();

	        if (loadedTimelineSize > this.maxTimelineSize) {
	          this.scaleDateFrom = new Date(this.scaleDateTo.getTime() - calendar_util.Util.getDayLength() * this.maxTimelineSize);
	          this.loadedDataFrom = this.scaleDateFrom;
	          this.loadedDataTo = this.scaleDateTo;
	          scrollLeft = this.getPosByDate(oldDateTo) - this.DOM.timelineFixedWrap.offsetWidth;
	          setTimeout(function () {
	            _this7.DOM.timelineFixedWrap.scrollLeft = _this7.getPosByDate(oldDateTo) - _this7.DOM.timelineFixedWrap.offsetWidth;
	          }, 10);
	          this.limitScaleSizeMode = true;
	        }
	      } else {
	        this.scaleDateFrom = new Date(scaleDateFrom.getTime() - calendar_util.Util.getDayLength() * this.SCALE_OFFSET_BEFORE);
	        this.scaleDateTo = new Date(scaleDateTo.getTime() + calendar_util.Util.getDayLength() * this.SCALE_OFFSET_AFTER);
	      }

	      var reloadData = this.scaleDateFrom.getTime() < prevScaleDateFrom.getTime() || this.scaleDateTo.getTime() > prevScaleDateTo.getTime();
	      this.hideLoader();
	      this.emit('onExpandTimeline', new main_core_events.BaseEvent({
	        data: {
	          reload: reloadData,
	          dateFrom: this.scaleDateFrom,
	          dateTo: this.scaleDateTo
	        }
	      }));
	      this.rebuild({
	        updateSelector: false
	      });

	      if (scrollLeft !== undefined) {
	        this.DOM.timelineFixedWrap.scrollLeft = scrollLeft;
	      }

	      this.expandTimelineDirection = null;
	    }
	  }, {
	    key: "update",
	    value: function update() {
	      var _this8 = this;

	      var entries = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	      var accessibility = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      main_core.Dom.clean(this.DOM.entrieListWrap);
	      main_core.Dom.clean(this.DOM.accessibilityWrap);
	      this.entriesDataRowMap = new Map();

	      if (!main_core.Type.isArray(entries)) {
	        return;
	      }

	      this.entries = entries;
	      this.accessibility = accessibility;
	      var userId = parseInt(this.userId); // sort entries list by amount of accessibility data
	      // Entries without accessibility data should be in the end of the array
	      // But first in the list will be meeting room
	      // And second (or first) will be owner-host of the event

	      entries.sort(function (a, b) {
	        if (b.status === 'h' || parseInt(b.id) === userId && a.status !== 'h') {
	          return 1;
	        }

	        if (a.status === 'h' || parseInt(a.id) === userId && b.status !== 'h') {
	          return -1;
	        }

	        return 0;
	      });

	      if (this.selectedEntriesWrap) {
	        main_core.Dom.clean(this.selectedEntriesWrap);

	        if (this.selector && this.selector.controlWrap) {
	          main_core.Dom.clean(this.selector.controlWrap);
	        }
	      }

	      var cutData = [];
	      var cutDataTitle = [];
	      var usersCount = 0;
	      var cutAmount = 0;
	      var dispDataCount = 0;
	      entries.forEach(function (entry, ind) {
	        entry.uid = Planner.getEntryUniqueId(entry);
	        var accData = main_core.Type.isArray(accessibility[entry.uid]) ? accessibility[entry.uid] : [];

	        _this8.entriesIndex.set(entry.uid, entry);

	        if (entry.type === 'user') {
	          usersCount++;
	        }

	        if (ind < _this8.MIN_ENTRY_ROWS || entries.length === _this8.MIN_ENTRY_ROWS + 1) {
	          dispDataCount++;

	          _this8.displayEntryRow(entry, accData);
	        } else {
	          cutAmount++;
	          cutDataTitle.push(entry.name);
	          accData.forEach(function (item) {
	            item = Planner.prepareAccessibilityItem(item);

	            if (item) {
	              cutData.push(item);
	            }
	          });
	        }
	      }); // Update entries title count

	      if (this.entriesListTitleCounter) {
	        this.entriesListTitleCounter.innerHTML = usersCount > this.MAX_ENTRY_ROWS ? '(' + usersCount + ')' : '';
	      }

	      this.emit('onDisplayAttendees', new main_core_events.BaseEvent({
	        data: {
	          usersCount: usersCount
	        }
	      }));

	      if (cutAmount > 0) {
	        if (dispDataCount === this.MAX_ENTRY_ROWS) {
	          this.displayEntryRow({
	            name: main_core.Loc.getMessage('EC_PL_ATTENDEES_LAST') + ' (' + cutAmount + ')',
	            type: 'lastUsers',
	            title: cutDataTitle.join(', ')
	          }, cutData);
	        } else {
	          this.displayEntryRow({
	            name: main_core.Loc.getMessage('EC_PL_ATTENDEES_SHOW_MORE') + ' (' + cutAmount + ')',
	            type: 'moreLink'
	          }, cutData);
	        }
	      }

	      calendar_util.Util.extendPlannerWatches({
	        entries: entries,
	        userId: this.userId
	      });
	      this.adjustHeight();
	    }
	  }, {
	    key: "updateAccessibility",
	    value: function updateAccessibility(accessibility) {
	      var _this9 = this;

	      this.accessibility = accessibility;

	      if (main_core.Type.isPlainObject(accessibility)) {
	        var key;

	        for (key in accessibility) {
	          if (accessibility.hasOwnProperty(key) && main_core.Type.isArray(accessibility[key]) && accessibility[key].length) {
	            (function () {
	              var wrap = _this9.entriesDataRowMap.get(key);

	              if (main_core.Type.isDomNode(wrap)) {
	                accessibility[key].forEach(function (event) {
	                  event = Planner.prepareAccessibilityItem(event);

	                  if (event) {
	                    _this9.addAccessibilityItem(event, wrap);
	                  }
	                });
	              }
	            })();
	          }
	        }
	      }
	    }
	  }, {
	    key: "updateSelector",
	    value: function updateSelector(from, to, fullDay) {
	      var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

	      if (this.shown && this.selector) {
	        this.setFullDayMode(fullDay); // Update limits of scale

	        if (!this.isOneDayScale()) {
	          if (calendar_util.Util.formatDate(from) !== calendar_util.Util.formatDate(to)) {
	            this.extendScaleTime(0, 24);
	          } else {
	            var timeFrom = parseInt(from.getHours()) + Math.floor(from.getMinutes() / 60),
	                timeTo = parseInt(to.getHours()) + Math.ceil(to.getMinutes() / 60);

	            if (timeFrom < this.shownScaleTimeFrom) {
	              this.extendScaleTime(timeFrom, false);
	            }

	            if (timeTo > this.shownScaleTimeTo) {
	              this.extendScaleTime(false, timeTo);
	            }
	          }
	        }

	        if (to.getTime() > this.scaleDateTo.getTime() || from.getTime() < this.scaleDateFrom.getTime()) {
	          this.expandTimelineDirection = false;
	          this.expandTimeline(from, to);
	        }

	        this.selector.update({
	          from: from,
	          to: to,
	          fullDay: fullDay,
	          focus: options.focus !== false
	        });

	        if (options.focus !== false) {
	          this.selector.focus(false, 300);
	        }
	      }
	    }
	  }, {
	    key: "handleSelectorChanges",
	    value: function handleSelectorChanges(event) {
	      if (event instanceof main_core_events.BaseEvent) {
	        var data = event.getData();
	        this.emit('onDateChange', new main_core_events.BaseEvent({
	          data: data
	        }));
	      }
	    }
	  }, {
	    key: "doCheckSelectorStatus",
	    value: function doCheckSelectorStatus(event) {
	      if (event instanceof main_core_events.BaseEvent) {
	        var data = event.getData();
	        this.clearCacheTime();
	        var selectorStatus = this.checkTimePeriod(data.dateFrom, data.dateTo) === true;
	        this.selector.setSelectorStatus(selectorStatus);

	        if (selectorStatus) {
	          main_core.Dom.removeClass(this.DOM.mainWrap, 'calendar-planner-selector-warning');
	          this.hideProposeControl();
	        } else {
	          main_core.Dom.addClass(this.DOM.mainWrap, 'calendar-planner-selector-warning');
	          this.showProposeControl();
	        }
	      }
	    }
	  }, {
	    key: "proposeTime",
	    value: function proposeTime() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      var curTimestamp = Math.round(this.selector.getDateFrom().getTime() / (this.accuracy * 1000)) * this.accuracy * 1000,
	          curDate = new Date(curTimestamp),
	          duration = this.selector.getDuration(),
	          data = [],
	          k,
	          i;
	      curDate.setSeconds(0, 0);
	      curTimestamp = curDate.getTime();

	      for (k in this.accessibility) {
	        if (this.accessibility.hasOwnProperty(k) && this.accessibility[k] && this.accessibility[k].length > 0) {
	          for (i = 0; i < this.accessibility[k].length; i++) {
	            if (this.accessibility[k][i].toTimestampReal >= curTimestamp) {
	              var item = Planner.prepareAccessibilityItem(this.accessibility[k][i]);

	              if (item) {
	                data.push(item);
	              }
	            }
	          }
	        }
	      }

	      data.sort(function (a, b) {
	        return a.fromTimestamp - b.fromTimestamp;
	      });
	      var ts = curTimestamp,
	          checkRes,
	          dateFrom,
	          dateTo,
	          timeTo,
	          timeFrom;

	      while (true) {
	        dateFrom = new Date(ts);
	        dateTo = new Date(ts + duration);

	        if (!this.isOneDayScale()) {
	          timeFrom = parseInt(dateFrom.getHours()) + dateFrom.getMinutes() / 60;
	          timeTo = parseInt(dateTo.getHours()) + dateTo.getMinutes() / 60;

	          if (timeFrom <= this.shownScaleTimeFrom) {
	            dateFrom.setHours(this.shownScaleTimeFrom, 0, 0, 0);
	            ts = dateFrom.getTime();
	            dateTo = new Date(ts + duration);
	          }

	          if (timeTo > this.shownScaleTimeTo) {
	            dateFrom = new Date(ts + calendar_util.Util.getDayLength() - 1000); // next day

	            dateFrom.setHours(this.shownScaleTimeFrom, 0, 0, 0);
	            ts = dateFrom.getTime();
	            dateTo = new Date(ts + duration);
	          }
	        }

	        if (this.fullDayMode) {
	          dateFrom.setHours(0, 0, 0, 0);
	          dateTo.setHours(0, 0, 0, 0);
	        }

	        checkRes = this.checkTimePeriod(dateFrom, dateTo, data);

	        if (checkRes === true) {
	          if (dateTo.getTime() > this.scaleDateTo.getTime()) {
	            if (dateTo.getTime() - this.scaleDateTo.getTime() > this.proposeTimeLimit * calendar_util.Util.getDayLength() || params.checkedFuture === true) {
	              Planner.showNoResultNotification();
	            } else if (params.checkedFuture !== true) {
	              var scrollLeft = this.DOM.timelineFixedWrap.scrollLeft;
	              this.scaleDateTo = new Date(this.scaleDateTo.getTime() + calendar_util.Util.getDayLength() * this.proposeTimeLimit);
	              this.rebuild();
	              this.DOM.timelineFixedWrap.scrollLeft = scrollLeft;
	              var entry = void 0,
	                  entrieIds = [];

	              for (i = 0; i < this.entries.length; i++) {
	                entry = this.entries[i];
	                entrieIds.push(entry.id);
	              }
	            }
	          } else {
	            if (this.fullDayMode) dateTo = new Date(dateTo.getTime() - calendar_util.Util.getDayLength());
	            this.selector.update({
	              from: dateFrom,
	              to: dateTo,
	              updateScaleType: false,
	              updateScaleLimits: true,
	              animation: true,
	              focus: true
	            });
	            this.emit('onDateChange', new main_core_events.BaseEvent({
	              data: {
	                dateFrom: dateFrom,
	                dateTo: dateTo,
	                fullDay: this.fullDayMode
	              }
	            }));
	          }

	          break;
	        } else if (checkRes && checkRes.toTimestampReal) {
	          ts = checkRes.toTimestampReal;

	          if (this.fullDayMode) {
	            var dt = new Date(ts + calendar_util.Util.getDayLength() - 1000); // next day

	            dt.setHours(0, 0, 0, 0);
	            ts = dt.getTime();
	          }
	        }
	      }
	    }
	  }, {
	    key: "checkTimePeriod",
	    value: function checkTimePeriod(fromDate, toDate, data) {
	      var _this10 = this;

	      var result = true;
	      var entry;

	      if (!main_core.Type.isDate(fromDate) || !main_core.Type.isDate(toDate)) {
	        return result;
	      }

	      var fromTimestamp = fromDate.getTime();
	      var toTimestamp = toDate.getTime();
	      var cacheKey = fromTimestamp + '_' + toTimestamp;
	      var accuracy = 3 * 60 * 1000; // 3min

	      if (main_core.Type.isArray(data)) {
	        for (var i = 0; i < data.length; i++) {
	          var item = data[i];

	          if (item.type && item.type === 'hr') {
	            continue;
	          }

	          if (item.fromTimestamp + accuracy <= toTimestamp && (item.toTimestampReal || item.toTimestamp) - accuracy >= fromTimestamp) {
	            result = item;
	            break;
	          }
	        }
	      } else if (main_core.Type.isArray(this.entries)) {
	        (function () {
	          var selectorAccuracy = _this10.selectorAccuracy * 1000,
	              entryId;

	          if (_this10.checkTimeCache[cacheKey] !== undefined) {
	            result = _this10.checkTimeCache[cacheKey];
	          } else {
	            for (entryId in _this10.accessibility) {
	              if (_this10.accessibility.hasOwnProperty(entryId)) {
	                entry = _this10.entries.find(function (el) {
	                  return el.id === entryId.toString();
	                });

	                if (!entry || _this10.selectMode && !entry.selected) {
	                  continue;
	                }

	                if (main_core.Type.isArray(_this10.accessibility[entryId])) {
	                  for (var _i = 0; _i < _this10.accessibility[entryId].length; _i++) {
	                    var _item = _this10.accessibility[entryId][_i];

	                    if (_item.type && _item.type === 'hr') {
	                      continue;
	                    }

	                    if (_item.fromTimestamp + selectorAccuracy <= toTimestamp && (_item.toTimestampReal || _item.toTimestamp) - selectorAccuracy >= fromTimestamp) {
	                      result = _item;
	                      break;
	                    }
	                  }
	                }
	              }
	            }

	            _this10.checkTimeCache[cacheKey] = result;
	          }
	        })();
	      }

	      return result;
	    }
	  }, {
	    key: "clearCacheTime",
	    value: function clearCacheTime() {
	      this.checkTimeCache = {};
	    }
	  }, {
	    key: "checkEntryTimePeriod",
	    value: function checkEntryTimePeriod(entry, fromDate, toDate) {
	      var data = [],
	          i;

	      if (entry && entry.id && BX.type.isArray(this.accessibility[entry.id])) {
	        for (i = 0; i < this.accessibility[entry.id].length; i++) {
	          var item = Planner.prepareAccessibilityItem(this.accessibility[entry.id][i]);

	          if (item) {
	            data.push(item);
	          }
	        }
	      }

	      return this.checkTimePeriod(fromDate, toDate, data) === true;
	    }
	  }, {
	    key: "showSettingsPopup",
	    value: function showSettingsPopup() {
	      var _this11 = this;

	      var settingsDialogCont = main_core.Tag.render(_templateObject41 || (_templateObject41 = babelHelpers.taggedTemplateLiteral(["<div class=\"calendar-planner-settings-popup\"></div>"])));
	      var scaleRow = settingsDialogCont.appendChild(main_core.Tag.render(_templateObject42 || (_templateObject42 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"calendar-planner-settings-row\">\n\t\t\t\t<i>", ":</i>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('EC_PL_SETTINGS_SCALE')));
	      var scaleWrap = scaleRow.appendChild(main_core.Tag.render(_templateObject43 || (_templateObject43 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"calendar-planner-option-container\"></span>\n\t\t"]))));

	      if (this.fullDayMode) {
	        scaleRow.title = main_core.Loc.getMessage('EC_PL_SETTINGS_SCALE_READONLY_TITLE');
	        main_core.Dom.addClass(scaleRow, 'calendar-planner-option-container-disabled');
	      }

	      this.scaleTypes.forEach(function (scale) {
	        scaleWrap.appendChild(main_core.Tag.render(_templateObject44 || (_templateObject44 = babelHelpers.taggedTemplateLiteral(["<span class=\"calendar-planner-option-tab ", "\" data-bx-planner-scale=\"", "\">", "</span>"])), scale === _this11.scaleType ? ' calendar-planner-option-tab-active' : '', scale, main_core.Loc.getMessage('EC_PL_SETTINGS_SCALE_' + scale.toUpperCase())));
	      }); // Create and show settings popup

	      var popup = main_popup.PopupWindowManager.create(this.id + "-settings-popup", this.DOM.settingsButton, {
	        autoHide: true,
	        closeByEsc: true,
	        offsetTop: -1,
	        offsetLeft: 7,
	        lightShadow: true,
	        content: settingsDialogCont,
	        zIndex: 4000,
	        angle: {
	          postion: 'top'
	        },
	        cacheable: false
	      });
	      popup.show(true);
	      main_core.Event.bind(scaleWrap, 'click', function (e) {
	        if (!_this11.fullDayMode) {
	          var nodeTarget = e.target || e.srcElement,
	              scale = nodeTarget && nodeTarget.getAttribute && nodeTarget.getAttribute('data-bx-planner-scale');

	          if (scale) {
	            _this11.changeScaleType(scale);

	            popup.close();
	          }
	        }
	      });
	    }
	  }, {
	    key: "changeScaleType",
	    value: function changeScaleType(scaleType) {
	      if (scaleType !== this.scaleType) {
	        this.setScaleType(scaleType);
	        this.rebuild();
	        this.selector.focus(true, 300);
	      }
	    }
	  }, {
	    key: "setFullDayMode",
	    value: function setFullDayMode(fullDayMode) {
	      if (fullDayMode !== this.fullDayMode) {
	        this.fullDayMode = fullDayMode;

	        if (fullDayMode && !this.isOneDayScale()) {
	          this.savedScaleType = this.scaleType;
	          this.changeScaleType('1day');
	        } else if (!fullDayMode && this.isOneDayScale() && this.savedScaleType) {
	          this.changeScaleType(this.savedScaleType);
	          this.savedScaleType = null;
	        }
	      }
	    }
	  }, {
	    key: "showProposeControl",
	    value: function showProposeControl() {
	      if (!this.DOM.proposeTimeButton) {
	        this.DOM.proposeTimeButton = this.DOM.mainWrap.appendChild(main_core.Tag.render(_templateObject45 || (_templateObject45 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-time-arrow-right\">\n\t\t\t\t\t<span class=\"calendar-planner-time-arrow-right-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t\t<span class=\"calendar-planner-time-arrow-right-item\"></span>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('EC_PL_PROPOSE')));
	        main_core.Event.bind(this.DOM.proposeTimeButton, 'click', this.proposeTime.bind(this));
	      }

	      this.DOM.proposeTimeButton.style.display = "block";
	      this.DOM.proposeTimeButton.style.top = this.DOM.timelineDataWrap.offsetTop + this.DOM.timelineDataWrap.offsetHeight / 2 - 16 + "px";
	    }
	  }, {
	    key: "hideProposeControl",
	    value: function hideProposeControl() {
	      if (this.DOM.proposeTimeButton) {
	        this.DOM.proposeTimeButton.style.display = "none";
	      }
	    }
	  }, {
	    key: "mouseMoveHandler",
	    value: function mouseMoveHandler(e) {
	      var i,
	          nodes,
	          entryUid,
	          parentTarget,
	          prevEntry,
	          mainContWrap = this.DOM.mainWrap,
	          target = e.target || e.srcElement;
	      entryUid = target.getAttribute('data-bx-planner-entry');

	      if (!entryUid) {
	        parentTarget = BX.findParent(target, function (node) {
	          if (node == mainContWrap || node.getAttribute && node.getAttribute('data-bx-planner-entry')) {
	            return true;
	          }
	        }, mainContWrap);

	        if (parentTarget) {
	          entryUid = target.getAttribute('data-bx-planner-entry');
	        } else {
	          main_core.Dom.removeClass(this.hoverRow, 'show');
	          nodes = this.selector.controlWrap.querySelectorAll('.calendar-planner-selector-control-row.hover');

	          for (i = 0; i < nodes.length; i++) {
	            main_core.Dom.removeClass(nodes[i], 'hover');
	          }

	          prevEntry = this.getEntryByUniqueId(this.howerEntryId);

	          if (prevEntry && prevEntry.selectWrap) {
	            prevEntry.selectWrap.style.opacity = 1;
	          }
	        }
	      }

	      if (entryUid) {
	        if (this.howerEntryId !== entryUid) {
	          this.howerEntryId = entryUid;
	          var entry = this.getEntryByUniqueId(entryUid);

	          if (entry) {
	            var top = parseInt(entry.dataRowWrap.offsetTop);
	            main_core.Dom.addClass(this.hoverRow, 'show');
	            this.hoverRow.style.top = top + 36 + 'px';
	            this.hoverRow.style.width = parseInt(this.DOM.mainWrap.offsetWidth) + 5 + 'px';

	            if (entry.selectorControlWrap) {
	              nodes = this.selector.controlWrap.querySelectorAll('.calendar-planner-selector-control-row.hover');

	              for (i = 0; i < nodes.length; i++) {
	                main_core.Dom.removeClass(nodes[i], 'hover');
	              }

	              main_core.Dom.addClass(entry.selectorControlWrap, 'hover');
	            }
	          }
	        }
	      }
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      this.hideLoader();
	      this.DOM.loader = this.DOM.mainWrap.appendChild(calendar_util.Util.getLoader(40));
	      main_core.Dom.addClass(this.DOM.loader, 'calendar-planner-main-loader');
	      this.loaderShown = true;
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      if (main_core.Type.isDomNode(this.DOM.loader)) {
	        main_core.Dom.remove(this.DOM.loader);
	      }

	      this.loaderShown = false;
	    }
	  }, {
	    key: "isLoaderShown",
	    value: function isLoaderShown() {
	      return this.loaderShown;
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this.shown;
	    }
	  }, {
	    key: "isBuilt",
	    value: function isBuilt() {
	      return this.built;
	    }
	  }, {
	    key: "isLocked",
	    value: function isLocked() {
	      return this.locked;
	    }
	  }, {
	    key: "lock",
	    value: function lock() {
	      if (!this.DOM.lockScreen) {
	        this.DOM.lockScreen = main_core.Tag.render(_templateObject46 || (_templateObject46 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"calendar-planner-timeline-locker\">\n\t\t\t\t\t<div class=\"calendar-planner-timeline-locker-container\">\n\t\t\t\t\t\t<div class=\"calendar-planner-timeline-locker-top\">\n\t\t\t\t\t\t\t<div class=\"calendar-planner-timeline-locker-icon\"></div>\n\t\t\t\t\t\t\t<div class=\"calendar-planner-timeline-text\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"calendar-planner-timeline-locker-button\">\n\t\t\t\t\t\t\t<a href=\"javascript:void(0)\" onclick=\"top.BX.UI.InfoHelper.show('limit_crm_calender_planner');\" class=\"ui-btn ui-btn-sm ui-btn-light-border ui-btn-round\">", "</a>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('EC_PL_LOCKED_TITLE'), main_core.Loc.getMessage('EC_PL_UNLOCK_FEATURE'));
	      }

	      main_core.Dom.addClass(this.DOM.timelineFixedWrap, '--lock');
	      this.DOM.timelineFixedWrap.appendChild(this.DOM.lockScreen);
	    }
	  }], [{
	    key: "prepareAccessibilityItem",
	    value: function prepareAccessibilityItem(entry) {
	      if (!main_core.Type.isDate(entry.from)) {
	        entry.from = calendar_util.Util.parseDate(entry.dateFrom);
	      }

	      if (!main_core.Type.isDate(entry.to)) {
	        entry.to = calendar_util.Util.parseDate(entry.dateTo);
	      }

	      if (!main_core.Type.isDate(entry.from) || !main_core.Type.isDate(entry.to)) {
	        return false;
	      }

	      entry.from.setSeconds(0, 0);
	      entry.fromTimestamp = entry.from.getTime();
	      entry.to.setSeconds(0, 0);
	      entry.toTimestamp = entry.to.getTime();

	      if (!main_core.Type.isDate(entry.toReal)) {
	        // Full day
	        if ((entry.toTimestamp - entry.fromTimestamp) % calendar_util.Util.getDayLength() === 0 && BX.date.format('H:i', entry.toTimestamp / 1000) === '00:00') {
	          entry.toReal = new Date(entry.to.getTime() + calendar_util.Util.getDayLength());
	          entry.toReal.setSeconds(0, 0);
	          entry.toTimestampReal = entry.toReal.getTime();
	        } else {
	          entry.toReal = entry.to;
	          entry.toTimestampReal = entry.toTimestamp;
	        }
	      }

	      return entry;
	    }
	  }, {
	    key: "getEntryAvatarNode",
	    value: function getEntryAvatarNode(entry) {
	      var imageNode;
	      var img = entry.avatar;

	      if (!img || img === "/bitrix/images/1.gif") {
	        imageNode = main_core.Tag.render(_templateObject47 || (_templateObject47 = babelHelpers.taggedTemplateLiteral(["<div bx-tooltip-user-id=\"", "\" bx-tooltip-classname=\"calendar-planner-user-tooltip\" title=\"", "\" class=\"ui-icon calendar-planner-user-image-icon ", "\"><i></i></div>"])), entry.id, main_core.Text.encode(entry.name), entry.emailUser ? 'ui-icon-common-user-mail' : 'ui-icon-common-user');
	      } else {
	        imageNode = main_core.Tag.render(_templateObject48 || (_templateObject48 = babelHelpers.taggedTemplateLiteral(["<div bx-tooltip-user-id=\"", "\" bx-tooltip-classname=\"calendar-planner-user-tooltip\" title=\"", "\" class=\"ui-icon calendar-planner-user-image-icon\"><i style=\"background-image: url('", "')\"></i></div>"])), entry.id, main_core.Text.encode(entry.name), entry.avatar);
	      }

	      return imageNode;
	    }
	  }, {
	    key: "getEntryUniqueId",
	    value: function getEntryUniqueId(entry) {
	      return ['user', 'room'].includes(entry.type) ? entry.id : entry.type + '-' + entry.id;
	    }
	  }, {
	    key: "getScaleSize",
	    value: function getScaleSize(scaleType) {
	      var hour = 3600,
	          map = {
	        '15min': Math.round(hour / 4),
	        '30min': Math.round(hour / 2),
	        '1hour': hour,
	        '2hour': hour * 2,
	        '1day': hour * 24
	      };
	      return map[scaleType] || hour;
	    }
	  }, {
	    key: "showNoResultNotification",
	    value: function showNoResultNotification() {
	      alert(main_core.Loc.getMessage('EC_PL_PROPOSE_NO_RESULT'));
	    }
	  }]);
	  return Planner;
	}(main_core_events.EventEmitter);

	exports.Planner = Planner;

}((this.BX.Calendar = this.BX.Calendar || {}),BX.Event,BX.Calendar,BX,BX.Main));
//# sourceMappingURL=planner.bundle.js.map
