import {AudioPlayer} from 'ui.vue3.components.audioplayer';
import {BitrixVue} from 'ui.vue3';
import {Button} from 'ui.buttons';

const defaultPlaybackRateValues = [0.5, 1, 1.25, 1.5, 1.75, 2];

export const TimelineAudio = BitrixVue.cloneComponent(AudioPlayer, {
	props: {
		playbackRateValues: {
			type: Array,
			required: false,
			default: () => defaultPlaybackRateValues,
		},
		isShowPlaybackRateMenu: {
			type: Boolean,
			required: false,
			default: true,
		},
	},
	data() {
		return {
			...this.parentData(),
			playbackRate: 1,
		}
	},
	computed: {
		containerClassname() {
			return [
				'crm-timeline__audioplayer-contianer',
				'ui-vue-audioplayer-container', {
				'ui-vue-audioplayer-container-dark': this.isDark,
				'ui-vue-audioplayer-container-mobile': this.isMobile,
			}];
		},
		controlClassname() {
			return [
				'ui-vue-audioplayer-control',
				'ui-btn-icon-start', {
				'ui-vue-audioplayer-control-loader': this.loading,
				'ui-vue-audioplayer-control-play': !this.loading && this.state !== this.State.play,
				'ui-vue-audioplayer-control-pause': !this.loading && this.state === this.State.play,
			}];
		},
		timeCurrentClassname() {
			return [
				'ui-vue-audioplayer-time',
				'ui-vue-audioplayer-time-current',
				{
					'--is-playing': this.state === this.State.play,
				},
			];
		},
		totalTimeClassname() {
			return [
				'ui-vue-audioplayer-total-time',
				{
					'--hidden': this.isTotalTimeHidden,
				}
			]
		},

		progressPosition() {
			return `width: ${this.progressInPixel}px;`;
		},
		seekPosition() {
			const minSeekWidth = 20;
			const seekWidth = this.$refs.seek?.offsetWidth ? this.$refs.seek.offsetWidth : minSeekWidth;
			return `left: ${this.progressInPixel - (seekWidth / 2)}px;`;
		},

		formatTimeCurrent() {
			return this.formatTime(this.timeCurrent);
		},
		formatTimeTotal() {
			return this.formatTime(this.timeTotal);
		},

		isTotalTimeHidden() {
			const totalTimeRef = this.$refs.totalTime;
			const seekRef = this.$refs.seek;
			if (!this.loaded || !totalTimeRef || !seekRef) return true;
			const seekWidth = seekRef.offsetWidth;
			return (this.progressInPixel + seekWidth / 2) >= totalTimeRef.offsetLeft;
		},

		playbackRateMenuItems() {
			return this.playbackRateValues.map((rate) => {
				return this.createPlaybackRateMenuItem({
					text: this.getPlaybackRateOptionText(rate),
					rate,
					isActive: rate === this.playbackRate,
				})
			});
		},
	},
	methods: {
		changePlaybackRate(playbackRate: number) {
			const audio = this.$refs.source;

			this.playbackRate = playbackRate;
			audio.playbackRate = playbackRate;
		},
		createPlaybackRateMenuItem(options = {}) {
			const rate = options.rate || 1;
			const text = options.text || '';
			const isActive = options.isActive || false;
			const className = `playback-speed-menu-item ${isActive ? 'menu-popup-item-accept-sm' : ''}`;
			return {
				text: text,
				className,
				onclick: (event, item) => {
					this.changePlaybackRate(rate);
					item.menuWindow.popupWindow.close();
					return true;
				}
			}
		},
		getPlaybackRateOptionText(rate: number): string {
			return `${rate}x`;
		},
		renderPlaybackRateButton() {
			const playbackRateButtonContainer = this.$refs.playbackRateButtonContainer;
			playbackRateButtonContainer.innerHTML = '';
			const btn = new Button({
				text: this.getPlaybackRateOptionText(this.playbackRate),
				round: true,
				dropdown: true,
				color: Button.Color.LIGHT_BORDER,
				size: Button.Size.EXTRA_SMALL,
				noCaps: true,
				className: 'playback-speed-button crm-timeline__playback-speed-menu_scope',
				menu: {
					className: 'crm-timeline__playback-speed-menu_scope',
					width: 100,
					events: {
						onPopupShow: ()=> {
							const btnContainerWidth = btn.getContainer().offsetWidth;
							const popupWindow = btn.getMenuWindow().getPopupWindow();

							popupWindow.setWidth(btnContainerWidth * 1.8);
							popupWindow.setOffset({
								offsetLeft: btnContainerWidth - 8,
							});

							popupWindow.adjustPosition();
						}
					},
					angle: {
						position: 'top',
					},
					offsetLeft: 0,
					items: this.playbackRateMenuItems,
				},
			});

			btn.renderTo(playbackRateButtonContainer);
		},
	},
	watch: {
		playbackRate() {
			this.renderPlaybackRateButton();
		},
	},
	mounted() {
		this.renderPlaybackRateButton();
	},
	template: `
		<div
			:class="containerClassname"
			ref="body"
		>
			<div class="ui-vue-audioplayer-controls-container">
				<button :class="controlClassname" @click="clickToButton">
					<svg v-if="state !== State.play" class="ui-vue-audioplayer-control-icon" width="9" height="12" viewBox="0 0 9 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M8.52196 5.40967L1.77268 0.637568C1.61355 0.523473 1.40621 0.510554 1.23498 0.604066C1.06375 0.697578 0.957151 0.881946 0.958524 1.0822V10.6259C0.956507 10.8265 1.06301 11.0114 1.23449 11.105C1.40597 11.1987 1.61368 11.1854 1.77268 11.0706L8.52196 6.29847C8.66466 6.19871 8.75016 6.0322 8.75016 5.85407C8.75016 5.67593 8.66466 5.50942 8.52196 5.40967Z"/>
					</svg>
					<svg v-else width="8" height="10" viewBox="0 0 8 10" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.5625 0.333008H0.375V9.66634H2.5625V0.333008Z" fill="inherit" />
						<path d="M7.25 0.333008H5.0625V9.66634H7.25V0.333008Z" fill="inherit" />
					</svg>
				</button>
			</div>
			<div class="ui-vue-audioplayer-timeline-container">
				<div class="ui-vue-audioplayer-track-container" @mousemove="seeking" @click="setPosition" ref="track">
					<div class="ui-vue-audioplayer-track-mask"></div>
					<div class="ui-vue-audioplayer-track" :style="progressPosition"></div>
					<div @click.stop class="ui-vue-audioplayer-track-seek" :style="seekPosition">
						<div ref="seek" class="ui-vue-audioplayer-track-seek-icon"></div>
						<div :class="timeCurrentClassname">{{formatTimeCurrent}}</div>
					</div>
<!--					<div class="ui-vue-audioplayer-track-event" @mousemove="seeking"></div>-->
				</div>
				<div :class="totalTimeClassname">
					<div ref="totalTime" class="ui-vue-audioplayer-time">{{formatTimeTotal}}</div>
				</div>
			</div>
			<div
				v-if="isShowPlaybackRateMenu"
				ref="playbackRateButtonContainer"
				class="ui-vue-audioplayer_playback-speed-menu-container">
			</div>
			<audio
				v-if="src" 
				:src="src"
				class="ui-vue-audioplayer-source"
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