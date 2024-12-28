/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3_components_audioplayer,main_core,ui_vue3) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var defaultPlaybackRateValues = [0.5, 0.7, 1, 1.2, 1.5, 1.7, 2];

	// @vue/component
	var AudioPlayerProps = {
	  props: {
	    playbackRateValues: {
	      type: Array,
	      "default": function _default() {
	        return defaultPlaybackRateValues;
	      }
	    },
	    isShowPlaybackRateMenu: {
	      type: Boolean,
	      "default": true
	    },
	    recordName: {
	      type: String,
	      "default": ''
	    },
	    mini: {
	      type: Boolean,
	      "default": false
	    },
	    // eslint-disable-next-line vue/require-prop-types
	    context: {
	      "default": window
	    }
	  },
	  data: function data() {
	    return _objectSpread(_objectSpread({}, this.parentData()), {}, {
	      playbackRate: defaultPlaybackRateValues[2],
	      isSeeking: true
	    });
	  },
	  computed: {
	    containerClassname: function containerClassname() {
	      return ['crm-audio-player-container', 'ui-vue-audioplayer-container', {
	        'ui-vue-audioplayer-container-dark': this.isDark,
	        'ui-vue-audioplayer-container-mobile': this.isMobile,
	        '--mini': this.mini
	      }];
	    },
	    controlClassname: function controlClassname() {
	      return ['ui-vue-audioplayer-control', 'ui-btn-icon-start', {
	        'ui-vue-audioplayer-control-loader': this.loading,
	        'ui-vue-audioplayer-control-play': !this.loading && this.state !== this.State.play,
	        'ui-vue-audioplayer-control-pause': !this.loading && this.state === this.State.play
	      }];
	    },
	    timeCurrentClassname: function timeCurrentClassname() {
	      return ['ui-vue-audioplayer-time', 'ui-vue-audioplayer-time-current', {
	        '--is-playing': this.state === this.State.play
	      }];
	    },
	    totalTimeClassname: function totalTimeClassname() {
	      return ['ui-vue-audioplayer-total-time'];
	    },
	    progressPosition: function progressPosition() {
	      return "width: ".concat(this.progressInPixel, "px;");
	    },
	    seekPosition: function seekPosition() {
	      var _this$$refs$seek$offs, _this$$refs$seek;
	      var minSeekWidth = 20;
	      var seekWidth = (_this$$refs$seek$offs = (_this$$refs$seek = this.$refs.seek) === null || _this$$refs$seek === void 0 ? void 0 : _this$$refs$seek.offsetWidth) !== null && _this$$refs$seek$offs !== void 0 ? _this$$refs$seek$offs : minSeekWidth;
	      return "left: ".concat(this.progressInPixel - seekWidth / 2, "px;");
	    },
	    formatTimeCurrent: function formatTimeCurrent() {
	      return this.formatTime(this.timeCurrent);
	    },
	    formatTimeTotal: function formatTimeTotal() {
	      return this.formatTime(this.timeTotal);
	    },
	    playbackRateMenuItems: function playbackRateMenuItems() {
	      var _this = this;
	      return this.playbackRateValues.map(function (rate) {
	        return _this.createPlaybackRateMenuItem({
	          text: _this.getPlaybackRateOptionText(rate),
	          rate: rate,
	          isActive: rate === _this.playbackRate
	        });
	      });
	    }
	  },
	  methods: {
	    changePlaybackRate: function changePlaybackRate(playbackRate) {
	      var _this$$refs, _this$$refs$source;
	      this.playbackRate = playbackRate;
	      if ((_this$$refs = this.$refs) !== null && _this$$refs !== void 0 && (_this$$refs$source = _this$$refs.source) !== null && _this$$refs$source !== void 0 && _this$$refs$source.playbackRate) {
	        this.$refs.source.playbackRate = playbackRate;
	      }
	    },
	    createPlaybackRateMenuItem: function createPlaybackRateMenuItem() {
	      var _this2 = this;
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var rate = options.rate || 1;
	      var text = options.text || '';
	      var isActive = options.isActive || false;
	      var className = "playback-speed-menu-item ".concat(isActive ? 'menu-popup-item-accept-sm' : '');
	      return {
	        text: text,
	        className: className,
	        onclick: function onclick(event, item) {
	          _this2.changePlaybackRate(rate);
	          item.menuWindow.popupWindow.close();
	          return true;
	        }
	      };
	    },
	    getPlaybackRateOptionText: function getPlaybackRateOptionText(rate) {
	      return this.isFloat(rate) ? "".concat(rate, "x") : "".concat(rate, ".0x");
	    },
	    showPlaybackRateMenu: function showPlaybackRateMenu() {
	      var _this3 = this;
	      if (this.menu && this.menu.getPopupWindow().isShown()) {
	        return;
	      }
	      this.menu = new this.context.BX.Main.Menu({
	        id: "12xx".concat(this.id),
	        className: 'crm-audio-player__playback-speed-menu_scope',
	        width: 100,
	        bindElement: this.$refs.playbackRateButtonContainer,
	        events: {
	          onPopupShow: function onPopupShow() {
	            var _Dom$getPosition = main_core.Dom.getPosition(_this3.$refs.playbackRateButtonContainer),
	              btnContainerWidth = _Dom$getPosition.width;
	            var popupWindow = _this3.menu.getPopupWindow();
	            popupWindow.setOffset({
	              offsetLeft: btnContainerWidth / 2
	            });
	            popupWindow.adjustPosition();
	          },
	          onPopupClose: function onPopupClose() {
	            _this3.menu.destroy();
	            _this3.menu = null;
	          }
	        },
	        angle: {
	          position: 'top'
	        },
	        offsetLeft: 0,
	        items: this.playbackRateMenuItems
	      });
	      this.menu.show();
	    },
	    isFloat: function isFloat(n) {
	      return n % 1 !== 0;
	    },
	    startSeeking: function startSeeking(event) {
	      this.isSeeking = true;
	      var clientX = event.clientX;
	      if (this.source()) {
	        this.source().pause();
	        main_core.bind(this.context.document, 'mouseup', this.finishSeeking);
	        main_core.bind(this.context.document, 'mousemove', this.seeking);
	        this.setSeekByCursor(clientX);
	      }
	    },
	    seeking: function seeking(event) {
	      if (!this.isSeeking) {
	        return;
	      }
	      var timeline = this.$refs.track;
	      var clientX = event.clientX;
	      var _Dom$getPosition2 = main_core.Dom.getPosition(timeline),
	        left = _Dom$getPosition2.left,
	        right = _Dom$getPosition2.right,
	        width = _Dom$getPosition2.width;
	      if (clientX < left) {
	        this.seek = 0;
	      } else if (clientX > right) {
	        this.seek = width - 1;
	      } else {
	        this.seek = clientX - left;
	      }
	      this.setPosition();
	      event.preventDefault();
	    },
	    finishSeeking: function finishSeeking() {
	      this.isSeeking = false;
	      this.setPosition();
	      if (this.source()) {
	        this.source().play();
	        main_core.unbind(this.context.document, 'mouseup', this.finishSeeking);
	        main_core.unbind(this.context.document, 'mousemove', this.seeking);
	      }
	    },
	    setSeekByCursor: function setSeekByCursor(x) {
	      var timeline = this.$refs.track;
	      var _event = event,
	        clientX = _event.clientX;
	      var _Dom$getPosition3 = main_core.Dom.getPosition(timeline),
	        left = _Dom$getPosition3.left,
	        right = _Dom$getPosition3.right,
	        width = _Dom$getPosition3.width;
	      if (clientX < left) {
	        this.seek = 0;
	      } else if (x > right) {
	        this.seek = width;
	      } else {
	        this.seek = clientX - left;
	      }
	    },
	    setPosition: function setPosition(event) {
	      if (!this.loaded) {
	        this.loadFile(true);
	        return;
	      }
	      var pixelPerPercent = this.$refs.track.offsetWidth / 100;
	      this.setProgress(this.seek / pixelPerPercent, this.seek);
	      this.source().currentTime = this.timeTotal / 100 * this.progress;
	    },
	    setProgress: function setProgress(percent) {
	      var pixel = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : -1;
	      if (this.mini) {
	        return;
	      }
	      this.progress = Number.isNaN(percent) ? 0 : percent;
	      this.progressInPixel = pixel > 0 ? pixel : Math.round(this.$refs.track.offsetWidth / 100 * percent);
	    },
	    audioEventRouterWrapper: function audioEventRouterWrapper(eventName, event) {
	      this.audioEventRouter(eventName, event);
	    }
	  },
	  // Language=Vue3
	  template: "\n\t\t<div\n\t\t\t:class=\"containerClassname\"\n\t\t\tref=\"body\"\n\t\t>\n\t\t\t<div class=\"ui-vue-audioplayer-controls-container\">\n\t\t\t\t<button :class=\"controlClassname\" @click=\"clickToButton\">\n\t\t\t\t\t<svg\n\t\t\t\t\t\tv-if=\"state !== State.play\"\n\t\t\t\t\t\tclass=\"ui-vue-audioplayer-control-icon\"\n\t\t\t\t\t\twidth=\"9\"\n\t\t\t\t\t\theight=\"12\"\n\t\t\t\t\t\tviewBox=\"0 0 9 12\"\n\t\t\t\t\t\tfill=\"none\"\n\t\t\t\t\t\txmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M8.52196 5.40967L1.77268 0.637568C1.61355 0.523473 1.40621 0.510554 1.23498 0.604066C1.06375 0.697578 0.957151 0.881946 0.958524 1.0822V10.6259C0.956507 10.8265 1.06301 11.0114 1.23449 11.105C1.40597 11.1987 1.61368 11.1854 1.77268 11.0706L8.52196 6.29847C8.66466 6.19871 8.75016 6.0322 8.75016 5.85407C8.75016 5.67593 8.66466 5.50942 8.52196 5.40967Z\"/>\n\t\t\t\t\t</svg>\n\t\t\t\t\t<svg\n\t\t\t\t\t\tv-else\n\t\t\t\t\t\tclass=\"ui-vue-audioplayer-control-icon\"\n\t\t\t\t\t\twidth=\"8\"\n\t\t\t\t\t\theight=\"10\"\n\t\t\t\t\t\tviewBox=\"0 0 8 10\"\n\t\t\t\t\t\txmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<rect width=\"2\" height=\"9\" x=\"0%\"></rect>\n\t\t\t\t\t\t<rect width=\"2\" height=\"9\" x=\"55%\"></rect>\n\t\t\t\t\t</svg>\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t\t<div class=\"ui-vue-audioplayer-timeline-container\">\n\t\t\t\t<div v-if=\"!mini\" class=\"ui-vue-audioplayer-record-name\">{{ recordName }}</div>\n\t\t\t\t<div v-if=\"!mini\" class=\"ui-vue-audioplayer-track-container\" @mousedown=\"startSeeking\" ref=\"track\">\n\t\t\t\t\t<div class=\"ui-vue-audioplayer-track-mask\"></div>\n\t\t\t\t\t<div class=\"ui-vue-audioplayer-track\" :style=\"progressPosition\"></div>\n\t\t\t\t\t<div @mousedown=\"startSeeking\" class=\"ui-vue-audioplayer-track-seek\" :style=\"seekPosition\">\n\t\t\t\t\t\t<div ref=\"seek\" class=\"ui-vue-audioplayer-track-seek-icon\"></div>\n\t\t\t\t\t</div>\n\t<!--\t\t\t\t\t<div class=\"ui-vue-audioplayer-track-event\" @mousemove=\"seeking\"></div>-->\n\t\t\t\t</div>\n\t\t\t\t<div :class=\"totalTimeClassname\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"(mini && timeCurrent > 0) || !mini\"\n\t\t\t\t\t\tref=\"currentTime\"\n\t\t\t\t\t\t:class=\"timeCurrentClassname\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<span style=\"position: absolute; right: 0; top: 0;\">\n\t\t\t\t\t\t\t{{formatTimeCurrent}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span style=\"opacity: 0;\">{{formatTimeTotal}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<span class=\"ui-vue-audioplayer-time-divider\" v-if=\"mini && timeCurrent > 0\">&nbsp;/&nbsp;</span>\n\t\t\t\t\t<div ref=\"totalTime\" class=\"ui-vue-audioplayer-time\">{{formatTimeTotal}}</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div\n\t\t\t\tv-if=\"!mini\"\n\t\t\t\t@click=\"showPlaybackRateMenu\"\n\t\t\t\tref=\"playbackRateButtonContainer\"\n\t\t\t\tclass=\"ui-vue-audioplayer_playback-speed-menu-container\"\n\t\t\t\tstyle=\"user-select: none;\"\n\t\t\t>\n\t\t\t\t{{ getPlaybackRateOptionText(playbackRate) }}\n\t\t\t</div>\n\t\t\t<audio\n\t\t\t\tv-if=\"src\"\n\t\t\t\t:src=\"src\"\n\t\t\t\tclass=\"ui-vue-audioplayer-source\"\n\t\t\t\tref=\"source\"\n\t\t\t\t:preload=\"preload\"\n\t\t\t\t@abort=\"audioEventRouter('abort', $event)\"\n\t\t\t\t@error=\"audioEventRouter('error', $event)\"\n\t\t\t\t@suspend=\"audioEventRouter('suspend', $event)\"\n\t\t\t\t@canplay=\"audioEventRouter('canplay', $event)\"\n\t\t\t\t@canplaythrough=\"audioEventRouter('canplaythrough', $event)\"\n\t\t\t\t@durationchange=\"audioEventRouter('durationchange', $event)\"\n\t\t\t\t@loadeddata=\"audioEventRouter('loadeddata', $event)\"\n\t\t\t\t@loadedmetadata=\"audioEventRouter('loadedmetadata', $event)\"\n\t\t\t\t@timeupdate=\"audioEventRouter('timeupdate', $event)\"\n\t\t\t\t@play=\"audioEventRouterWrapper('play', $event)\"\n\t\t\t\t@playing=\"audioEventRouter('playing', $event)\"\n\t\t\t\t@pause=\"audioEventRouterWrapper('pause', $event)\"\n\t\t\t></audio>\n\t\t</div>\n\t"
	};

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	var _application = /*#__PURE__*/new WeakMap();
	var AudioPlayer = /*#__PURE__*/function () {
	  function AudioPlayer(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, AudioPlayer);
	    _classPrivateFieldInitSpec(this, _application, {
	      writable: true,
	      value: void 0
	    });
	    this.setRootNode(options.rootNode);
	    this.setAudioProps(options.audioProps);
	    var AudioPlayerComponent = AudioPlayer.getComponent({});
	    babelHelpers.classPrivateFieldSet(this, _application, ui_vue3.BitrixVue.createApp({
	      name: 'crm-audio-player',
	      components: {
	        AudioPlayerComponent: AudioPlayerComponent
	      },
	      data: function data() {
	        return {
	          audioProps: _this.audioProps
	        };
	      },
	      template: '<AudioPlayerComponent v-bind="audioProps" />'
	    }));
	  }
	  babelHelpers.createClass(AudioPlayer, [{
	    key: "attachTemplate",
	    value: function attachTemplate() {
	      babelHelpers.classPrivateFieldGet(this, _application).mount(this.rootNode);
	    }
	  }, {
	    key: "detachTemplate",
	    value: function detachTemplate() {
	      babelHelpers.classPrivateFieldGet(this, _application).unmount(this.rootNode);
	    }
	  }, {
	    key: "setRootNode",
	    value: function setRootNode(rootNode) {
	      if (rootNode === null || rootNode === undefined) {
	        return;
	      }
	      if (main_core.Type.isString(rootNode)) {
	        this.rootNode = document.querySelector("#".concat(rootNode));
	        if (!this.rootNode) {
	          throw new Error('Crm.AudioPlayer: \'rootNode\' not found');
	        }
	        return;
	      }
	      if (main_core.Type.isElementNode(rootNode)) {
	        this.rootNode = rootNode;
	        return;
	      }
	      throw new Error('Crm.AudioPlayer: \'rootNode\' Must be either a string or an ElementNode');
	    }
	  }, {
	    key: "setAudioProps",
	    value: function setAudioProps(audioProps) {
	      this.audioProps = audioProps;
	    }
	  }], [{
	    key: "getComponent",
	    value: function getComponent(mutations) {
	      var defaultMutations = AudioPlayerProps;
	      Object.keys(mutations).forEach(function (mutationKey) {
	        defaultMutations[mutationKey] = defaultMutations[mutationKey] ? BX.util.objectMerge(defaultMutations[mutationKey], mutations[mutationKey]) : mutations[mutationKey];
	      });
	      return ui_vue3.BitrixVue.cloneComponent(ui_vue3_components_audioplayer.AudioPlayer, defaultMutations);
	    }
	  }]);
	  return AudioPlayer;
	}();
	var AudioPlayerComponent = AudioPlayer.getComponent({});

	exports.AudioPlayer = AudioPlayer;
	exports.AudioPlayerComponent = AudioPlayerComponent;

}((this.BX.Crm = this.BX.Crm || {}),BX.Vue3.Components,BX,BX.Vue3));
//# sourceMappingURL=audio-player.bundle.js.map
