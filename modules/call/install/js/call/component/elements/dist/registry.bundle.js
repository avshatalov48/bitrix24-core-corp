/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
this.BX.Call.Component = this.BX.Call.Component || {};
(function (exports,main_popup,ui_loader,ui_vue3,ui_vue3_components_audioplayer,main_core) {
	'use strict';

	const POPUP_CONTAINER_PREFIX = '#popup-window-content-';
	const POPUP_BORDER_RADIUS = '10px';

	// @vue/component
	const CallPopupContainer = {
	  name: 'CallPopupContainer',
	  props: {
	    id: {
	      type: String,
	      required: true
	    },
	    config: {
	      type: Object,
	      required: false,
	      default() {
	        return {};
	      }
	    }
	  },
	  emits: ['close'],
	  computed: {
	    popupContainer() {
	      return `${POPUP_CONTAINER_PREFIX}${this.id}`;
	    }
	  },
	  created() {
	    this.instance = this.getPopupInstance();
	    this.instance.show();
	  },
	  mounted() {
	    this.instance.adjustPosition({
	      forceBindPosition: true,
	      position: this.getPopupConfig().bindOptions.position
	    });
	  },
	  beforeUnmount() {
	    if (!this.instance) {
	      return;
	    }
	    this.closePopup();
	  },
	  methods: {
	    getPopupInstance() {
	      if (!this.instance) {
	        var _PopupManager$getPopu;
	        (_PopupManager$getPopu = main_popup.PopupManager.getPopupById(this.id)) == null ? void 0 : _PopupManager$getPopu.destroy();
	        this.instance = new main_popup.Popup(this.getPopupConfig());
	      }
	      return this.instance;
	    },
	    getDefaultConfig() {
	      return {
	        id: this.id,
	        bindOptions: {
	          position: 'bottom'
	        },
	        offsetTop: 0,
	        offsetLeft: 0,
	        className: 'bx-call__scope',
	        cacheable: false,
	        closeIcon: false,
	        autoHide: true,
	        closeByEsc: true,
	        animation: 'fading',
	        events: {
	          onPopupClose: this.closePopup.bind(this),
	          onPopupDestroy: this.closePopup.bind(this)
	        },
	        contentBorderRadius: POPUP_BORDER_RADIUS
	      };
	    },
	    getPopupConfig() {
	      const defaultConfig = this.getDefaultConfig();
	      const {
	        className = '',
	        offsetTop = defaultConfig.offsetTop,
	        bindOptions = {}
	      } = this.config;
	      const combinedClassName = `${defaultConfig.className} ${className}`.trim();
	      const adjustedOffsetTop = bindOptions.position === 'top' && main_core.Type.isNumber(offsetTop) ? offsetTop - 10 : offsetTop;
	      return {
	        ...defaultConfig,
	        ...this.config,
	        className: combinedClassName,
	        offsetTop: adjustedOffsetTop
	      };
	    },
	    closePopup() {
	      this.$emit('close');
	      this.instance.destroy();
	      this.instance = null;
	    },
	    enableAutoHide() {
	      this.getPopupInstance().setAutoHide(true);
	    },
	    disableAutoHide() {
	      this.getPopupInstance().setAutoHide(false);
	    },
	    adjustPosition() {
	      this.getPopupInstance().adjustPosition({
	        forceBindPosition: true,
	        position: this.getPopupConfig().bindOptions.position
	      });
	    }
	  },
	  template: `
		<Teleport :to="popupContainer">
			<slot
				:adjustPosition="adjustPosition"
				:enableAutoHide="enableAutoHide"
				:disableAutoHide="disableAutoHide"
			></slot>
		</Teleport>
	`
	};

	const LOADER_SIZE = 'xs';
	const LOADER_TYPE = 'BULLET';

	// @vue/component
	const CallLoader = {
	  name: 'CallLoader',
	  props: {
	    isLight: {
	      type: Boolean,
	      required: false,
	      default: false
	    }
	  },
	  computed: {
	    loaderStyle() {
	      return this.isLight ? 'rgba(255, 255, 255, 0.24)' : '';
	    }
	  },
	  mounted() {
	    this.loader = new ui_loader.Loader({
	      target: this.$refs['call-loader'],
	      type: LOADER_TYPE,
	      size: LOADER_SIZE,
	      color: this.loaderStyle
	    });
	    this.loader.render();
	    this.loader.show();
	  },
	  beforeUnmount() {
	    this.loader.hide();
	    this.loader = null;
	  },
	  template: `
		<div class="bx-call-elements-loader__container" ref="call-loader"></div>
	`
	};

	const defaultPlaybackRateValues = [0.5, 0.7, 1.0, 1.2, 1.5, 1.7, 2.0];
	// @vue/component
	const AudioPlayer = ui_vue3.BitrixVue.cloneComponent(ui_vue3_components_audioplayer.AudioPlayer, {
	  name: 'AudioPlayer',
	  components: {},
	  props: {
	    context: {
	      required: false,
	      default: window
	    },
	    playbackRateValues: {
	      type: Array,
	      required: false,
	      default: () => defaultPlaybackRateValues
	    },
	    isShowPlaybackRateMenu: {
	      type: Boolean,
	      required: false,
	      default: true
	    },
	    analyticsCallback: {
	      type: Function,
	      required: false,
	      default: () => {}
	    }
	  },
	  data() {
	    return {
	      ...this.parentData(),
	      playbackRate: defaultPlaybackRateValues[2],
	      isSeeking: true
	    };
	  },
	  computed: {
	    progressPosition() {
	      return {
	        width: `${this.progressInPixel}px`
	      };
	    },
	    seekPosition() {
	      var _this$$refs$seek$offs, _this$$refs$seek;
	      const minSeekWidth = 20;
	      const seekWidth = (_this$$refs$seek$offs = (_this$$refs$seek = this.$refs.seek) == null ? void 0 : _this$$refs$seek.offsetWidth) != null ? _this$$refs$seek$offs : minSeekWidth;
	      return this.progressInPixel ? `left: ${this.progressInPixel - seekWidth / 2}px;` : `left: ${this.progressInPixel - 2}px`;
	    },
	    playbackRateMenuItems() {
	      return this.playbackRateValues.map(rate => {
	        return this.createPlaybackRateMenuItem({
	          text: this.getPlaybackRateOptionText(rate),
	          rate,
	          isActive: rate === this.playbackRate
	        });
	      });
	    },
	    formatTimeCurrent() {
	      return this.formatTime(this.timeCurrent);
	    },
	    formatTimeTotal() {
	      return this.formatTime(this.timeTotal);
	    }
	  },
	  methods: {
	    choosePlaybackTime(seconds) {
	      if (!this.source()) {
	        return;
	      }
	      this.source().currentTime = seconds;
	      this.audioEventRouter('timeupdate');
	      if (this.state !== ui_vue3_components_audioplayer.AudioPlayerState.play) {
	        this.clickToButton();
	      }
	    },
	    startSeeking(event) {
	      this.isSeeking = true;
	      const {
	        clientX
	      } = event;
	      if (this.source()) {
	        this.source().pause();
	        main_core.bind(this.context.document, 'mouseup', this.finishSeeking);
	        main_core.bind(this.context.document, 'mousemove', this.seeking);
	        this.setSeekByCursor(clientX);
	      }
	    },
	    seeking(event) {
	      if (!this.isSeeking) {
	        return;
	      }
	      const timeline = this.$refs.track;
	      const {
	        clientX
	      } = event;
	      const {
	        left,
	        right,
	        width
	      } = main_core.Dom.getPosition(timeline);
	      this.seek = Math.max(0, Math.min(clientX - left, width - 1));
	      this.setPosition();
	      event.preventDefault();
	    },
	    finishSeeking() {
	      this.isSeeking = false;
	      this.setPosition();
	      if (this.source()) {
	        this.source().play();
	        main_core.unbind(this.context.document, 'mouseup', this.finishSeeking);
	        main_core.unbind(this.context.document, 'mousemove', this.seeking);
	      }
	    },
	    setPosition() {
	      if (!this.loaded) {
	        this.loadFile(true);
	        return;
	      }
	      const pixelPerPercent = this.$refs.track.offsetWidth / 100;
	      this.setProgress(this.seek / pixelPerPercent, this.seek);
	      this.source().currentTime = this.timeTotal / 100 * this.progress;
	    },
	    setProgress(percent, pixel = -1) {
	      this.progress = Number.isNaN(percent) ? 0 : percent;
	      this.progressInPixel = pixel > 0 ? pixel : Math.round(this.$refs.track.offsetWidth / 100 * percent);
	    },
	    setSeekByCursor(x) {
	      const timeline = this.$refs.track;
	      const {
	        left,
	        width
	      } = main_core.Dom.getPosition(timeline);
	      this.seek = Math.max(0, Math.min(x - left, width));
	    },
	    showPlaybackRateMenu() {
	      if (this.menu && this.menu.getPopupWindow().isShown()) {
	        return;
	      }
	      const {
	        BX
	      } = this.context;
	      const bindElement = this.$refs.playbackRateButtonContainer;
	      this.menu = new BX.Main.Menu({
	        id: `bx-call-audio-player-${this.id}`,
	        className: 'bx-call-audio-player__playback-speed-menu_scope',
	        width: 100,
	        bindElement,
	        events: {
	          onPopupShow: () => {
	            const {
	              width: btnContainerWidth
	            } = main_core.Dom.getPosition(bindElement);
	            const popupWindow = this.menu.getPopupWindow();
	            popupWindow.setOffset({
	              offsetLeft: btnContainerWidth / 2
	            });
	            popupWindow.adjustPosition();
	          },
	          onPopupClose: () => {
	            this.menu.destroy();
	            this.menu = null;
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
	    getPlaybackRateOptionText(rate) {
	      return main_core.Type.isFloat(rate) ? `${rate}x` : `${rate}.0x`;
	    },
	    createPlaybackRateMenuItem({
	      rate = 1,
	      text = '',
	      isActive = false
	    } = {}) {
	      return {
	        text,
	        className: `bx-call-audio-player__playback-speed-menu-item ${isActive ? 'menu-popup-item-accept-sm' : ''}`,
	        onclick: (event, item) => {
	          this.changePlaybackRate(rate);
	          item.menuWindow.popupWindow.close();
	          return true;
	        }
	      };
	    },
	    changePlaybackRate(playbackRate) {
	      var _this$$refs, _this$$refs$source;
	      this.playbackRate = playbackRate;
	      if ((_this$$refs = this.$refs) != null && (_this$$refs$source = _this$$refs.source) != null && _this$$refs$source.playbackRate) {
	        this.$refs.source.playbackRate = playbackRate;
	      }
	    },
	    onClickControlButton() {
	      if (this.state !== ui_vue3_components_audioplayer.AudioPlayerState.play) {
	        this.analyticsCallback();
	      }
	      this.clickToButton();
	    }
	  },
	  template: `
		<div 
			class="bx-call-audio-player__container bx-call-audio-player__scope" 
			ref="body"
		>
			<div class="bx-call-audio-player__control-container">
				<button :class="['bx-call-audio-player__control-button', {
					'bx-call-audio-player__control-loader': loading,
					'bx-call-audio-player__control-play': !loading && state !== State.play,
					'bx-call-audio-player__control-pause': !loading && state === State.play,
				}]" @click="onClickControlButton"></button>
			</div>
			<div class="bx-call-audio-player__timeline-container">
				<div class="bx-call-audio-player__track-container" @mousedown="startSeeking" ref="track">
					<div class="bx-call-audio-player__track-mask">
						<div v-for="n in 7" :key="n" class="bx-call-audio-player__track-mask-separator"></div>
					</div>
					<div class="bx-call-audio-player__track-mask --active" :style="progressPosition"></div>
					<div class="bx-call-audio-player__track-seek" @mousedown="startSeeking" :style="seekPosition">
						<div ref="seek" class="bx-call-audio-player__track-seek-icon"></div>
					</div>
				</div>
				<div class="bx-call-audio-player__timer-container">
					<span>{{formatTimeCurrent}}</span>
					<span>{{formatTimeTotal}}</span>
				</div>
			</div>
			<div
				@click="showPlaybackRateMenu"
				ref="playbackRateButtonContainer"
				class="bx-call-audio-player__playback-speed-menu-container"
				style="user-select: none;"
			>
				{{ getPlaybackRateOptionText(playbackRate) }}
			</div>
			<audio 
				v-if="src" 
				:src="src" 
				class="bx-call-audio-player__audio-source" 
				ref="source" 
				:preload="preload"
				@abort="audioEventRouter('abort', $event)"
				@error="audioEventRouter('error', $event)"
				@suspend="audioEventRouter('suspend', $event)"
				@canplay="audioEventRouter('canplay', $event)"
				@canplaythrough="audioEventRouter('canplaythrough', $event)"
				@durationchange="audioEventRouter('durationchange', $event)"
				@loadeddata="audioEventRouter('loadeddata', $event)"
				@loadedmetadata="audioEventRouter('loadedmetadata', $event)"
				@timeupdate="audioEventRouter('timeupdate', $event)"
				@play="audioEventRouter('play', $event)"
				@playing="audioEventRouter('playing', $event)"
				@pause="audioEventRouter('pause', $event)"
			></audio>
		</div>
	`
	});

	exports.CallPopupContainer = CallPopupContainer;
	exports.CallLoader = CallLoader;
	exports.AudioPlayer = AudioPlayer;

}((this.BX.Call.Component.Elements = this.BX.Call.Component.Elements || {}),BX.Main,BX.UI,BX.Vue3,BX.Vue3.Components,BX));
//# sourceMappingURL=registry.bundle.js.map
