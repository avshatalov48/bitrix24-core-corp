this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,main_core,main_core_events,ui_tour) {
	'use strict';

	var FirstProject = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FirstProject, _EventEmitter);

	  function FirstProject(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, FirstProject);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FirstProject).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Tour.FirstProject');

	    _this.popupData = params.popupData;
	    _this.targetNode = document.getElementById(params.targetNodeId);
	    _this.guide = new ui_tour.Guide({
	      steps: [{
	        target: _this.targetNode,
	        title: _this.popupData[0].title,
	        text: _this.popupData[0].text,
	        article: _this.popupData[0].article
	      }],
	      onEvents: true
	    });

	    _this.bindEvents();

	    return _this;
	  }

	  babelHelpers.createClass(FirstProject, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('UI.Tour.Guide:onFinish', this.onGuideFinish.bind(this));
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onProjectSliderMessage.bind(this));
	    }
	  }, {
	    key: "onGuideFinish",
	    value: function onGuideFinish(event) {
	      var _event$getData = event.getData(),
	          guide = _event$getData.guide;

	      if (guide === this.guide) {
	        this.targetNode.href = main_core.Uri.removeParam(this.targetNode.href, ['PROJECT_OPTIONS']);
	      }
	    }
	  }, {
	    key: "onProjectSliderMessage",
	    value: function onProjectSliderMessage(event) {
	      var _event$getData2 = event.getData(),
	          _event$getData3 = babelHelpers.slicedToArray(_event$getData2, 1),
	          sliderEvent = _event$getData3[0];

	      if (sliderEvent.getEventId() !== 'sonetGroupEvent') {
	        return;
	      }

	      var sliderEventData = sliderEvent.getData();

	      if (sliderEventData.code !== 'afterCreate' || sliderEventData.data.projectOptions.tourId !== this.guide.getId()) {
	        return;
	      }

	      var projectId = sliderEventData.data.group.ID;
	      this.emit('afterProjectCreated', projectId);
	    }
	  }, {
	    key: "showFinalStep",
	    value: function showFinalStep(target) {
	      var _this2 = this;

	      this.guide.steps.push(new ui_tour.Step({
	        target: target,
	        cursorMode: true,
	        targetEvent: function targetEvent() {
	          BX.SidePanel.Instance.open(target.href);
	          setTimeout(function () {
	            return _this2.guide.close();
	          }, 1000);
	        }
	      }));
	      this.finish();
	      this.showNextStep();
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      this.targetNode.href = main_core.Uri.addParam(this.targetNode.href, {
	        PROJECT_OPTIONS: {
	          tourId: this.guide.getId()
	        }
	      });
	      this.showNextStep();
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      main_core.ajax.runAction('tasks.tourguide.firstprojectcreation.finish');
	    }
	  }, {
	    key: "showNextStep",
	    value: function showNextStep() {
	      var _this3 = this;

	      setTimeout(function () {
	        return _this3.guide.showNextStep();
	      }, 1000);
	    }
	  }]);
	  return FirstProject;
	}(main_core_events.EventEmitter);

	var FirstScrum = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FirstScrum, _EventEmitter);

	  function FirstScrum(params) {
	    var _this;

	    babelHelpers.classCallCheck(this, FirstScrum);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FirstScrum).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Tour.FirstScrum');

	    _this.popupData = params.popupData;
	    _this.targetNode = document.getElementById(params.targetNodeId);
	    _this.guide = new ui_tour.Guide({
	      steps: [{
	        target: _this.targetNode,
	        title: _this.popupData[0].title,
	        text: _this.popupData[0].text,
	        article: null
	      }],
	      onEvents: true
	    });

	    _this.bindEvents();

	    return _this;
	  }

	  babelHelpers.createClass(FirstScrum, [{
	    key: "bindEvents",
	    value: function bindEvents() {
	      main_core_events.EventEmitter.subscribe('UI.Tour.Guide:onFinish', this.onGuideFinish.bind(this));
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onProjectSliderMessage.bind(this));
	    }
	  }, {
	    key: "onGuideFinish",
	    value: function onGuideFinish(event) {
	      var _event$getData = event.getData(),
	          guide = _event$getData.guide;

	      if (guide === this.guide) {
	        this.targetNode.href = main_core.Uri.removeParam(this.targetNode.href, ['PROJECT_OPTIONS']);
	      }
	    }
	  }, {
	    key: "onProjectSliderMessage",
	    value: function onProjectSliderMessage(event) {
	      var _event$getData2 = event.getData(),
	          _event$getData3 = babelHelpers.slicedToArray(_event$getData2, 1),
	          sliderEvent = _event$getData3[0];

	      if (sliderEvent.getEventId() !== 'sonetGroupEvent') {
	        return;
	      }

	      var sliderEventData = sliderEvent.getData();

	      if (sliderEventData.code !== 'afterCreate' || sliderEventData.data.projectOptions.tourId !== this.guide.getId()) {
	        return;
	      }

	      var projectId = sliderEventData.data.group.ID;
	      this.emit('afterProjectCreated', projectId);
	    }
	  }, {
	    key: "showFinalStep",
	    value: function showFinalStep(target) {
	      var _this2 = this;

	      this.guide.steps.push(new ui_tour.Step({
	        target: target,
	        cursorMode: true,
	        targetEvent: function targetEvent() {
	          BX.SidePanel.Instance.open(target.href);
	          setTimeout(function () {
	            return _this2.guide.close();
	          }, 1000);
	        }
	      }));
	      this.finish();
	      this.showNextStep();
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      this.targetNode.href = main_core.Uri.addParam(this.targetNode.href, {
	        PROJECT_OPTIONS: {
	          tourId: this.guide.getId()
	        }
	      });
	      this.showNextStep();
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      main_core.ajax.runAction('tasks.tourguide.firstscrumcreation.finish');
	    }
	  }, {
	    key: "showNextStep",
	    value: function showNextStep() {
	      var _this3 = this;

	      setTimeout(function () {
	        return _this3.guide.showNextStep();
	      }, 1000);
	    }
	  }]);
	  return FirstScrum;
	}(main_core_events.EventEmitter);

	var Tour = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Tour, _EventEmitter);

	  function Tour(params) {
	    var _tours$firstProjectCr, _tours$firstScrumCrea;

	    var _this;

	    babelHelpers.classCallCheck(this, Tour);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Tour).call(this, params));

	    _this.setEventNamespace('BX.Tasks.Tour');

	    var tours = params.tours;
	    var firstProjectData = (_tours$firstProjectCr = tours.firstProjectCreation) !== null && _tours$firstProjectCr !== void 0 ? _tours$firstProjectCr : {};
	    var firstScrumData = (_tours$firstScrumCrea = tours.firstScrumCreation) !== null && _tours$firstScrumCrea !== void 0 ? _tours$firstScrumCrea : {};

	    if (firstProjectData.show) {
	      _this.firstProject = new FirstProject({
	        targetNodeId: firstProjectData.targetNodeId,
	        popupData: firstProjectData.popupData
	      });

	      _this.firstProject.subscribe('afterProjectCreated', function (baseEvent) {
	        _this.emit('FirstProject:afterProjectCreated', baseEvent.getData());
	      });

	      _this.firstProject.start();
	    }

	    if (firstScrumData.show) {
	      _this.firstScrum = new FirstScrum({
	        targetNodeId: firstScrumData.targetNodeId,
	        popupData: firstScrumData.popupData
	      });

	      _this.firstScrum.subscribe('afterProjectCreated', function (baseEvent) {
	        _this.emit('FirstScrum:afterScrumCreated', baseEvent.getData());
	      });

	      _this.firstScrum.start();
	    }

	    return _this;
	  }

	  babelHelpers.createClass(Tour, [{
	    key: "showFinalStep",
	    value: function showFinalStep(target) {
	      if (this.firstProject) {
	        this.firstProject.showFinalStep(target);
	      }

	      if (this.firstScrum) {
	        this.firstScrum.showFinalStep(target);
	      }
	    }
	  }]);
	  return Tour;
	}(main_core_events.EventEmitter);

	exports.Tour = Tour;

}((this.BX.Tasks.Tour = this.BX.Tasks.Tour || {}),BX,BX.Event,BX.UI.Tour));
//# sourceMappingURL=tour.bundle.js.map
