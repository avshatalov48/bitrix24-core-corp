import { bind, Dom, unbind } from 'main.core';
import { BitrixVueComponentProps } from 'ui.vue3';

const defaultPlaybackRateValues = [0.5, 0.7, 1, 1.2, 1.5, 1.7, 2];

// @vue/component
export const AudioPlayerProps: BitrixVueComponentProps = {
	props: {
		playbackRateValues: {
			type: Array,
			default: () => defaultPlaybackRateValues,
		},
		isShowPlaybackRateMenu: {
			type: Boolean,
			default: true,
		},
		recordName: {
			type: String,
			default: '',
		},
		mini: {
			type: Boolean,
			default: false,
		},
		// eslint-disable-next-line vue/require-prop-types
		context: {
			default: window,
		},
	},
	data(): Object
	{
		return {
			...this.parentData(),
			playbackRate: defaultPlaybackRateValues[2],
			isSeeking: true,
		};
	},
	computed: {
		containerClassname(): Array
		{
			return [
				'crm-audio-player-container',
				'ui-vue-audioplayer-container', {
					'ui-vue-audioplayer-container-dark': this.isDark,
					'ui-vue-audioplayer-container-mobile': this.isMobile,
					'--mini': this.mini,
				}];
		},
		controlClassname(): Array
		{
			return [
				'ui-vue-audioplayer-control',
				'ui-btn-icon-start', {
					'ui-vue-audioplayer-control-loader': this.loading,
					'ui-vue-audioplayer-control-play': !this.loading && this.state !== this.State.play,
					'ui-vue-audioplayer-control-pause': !this.loading && this.state === this.State.play,
				}];
		},
		timeCurrentClassname(): Array
		{
			return [
				'ui-vue-audioplayer-time',
				'ui-vue-audioplayer-time-current',
				{
					'--is-playing': this.state === this.State.play,
				},
			];
		},

		totalTimeClassname(): Array
		{
			return ['ui-vue-audioplayer-total-time'];
		},

		progressPosition(): string
		{
			return `width: ${this.progressInPixel}px;`;
		},

		seekPosition(): string
		{
			const minSeekWidth = 20;
			const seekWidth = this.$refs.seek?.offsetWidth ?? minSeekWidth;

			return `left: ${this.progressInPixel - (seekWidth / 2)}px;`;
		},

		formatTimeCurrent(): string
		{
			return this.formatTime(this.timeCurrent);
		},

		formatTimeTotal(): string
		{
			return this.formatTime(this.timeTotal);
		},

		playbackRateMenuItems(): Array
		{
			return this.playbackRateValues.map((rate) => {
				return this.createPlaybackRateMenuItem({
					text: this.getPlaybackRateOptionText(rate),
					rate,
					isActive: rate === this.playbackRate,
				});
			});
		},
	},
	methods: {
		changePlaybackRate(playbackRate: number): void
		{
			this.playbackRate = playbackRate;
			if (this.$refs?.source?.playbackRate)
			{
				this.$refs.source.playbackRate = playbackRate;
			}
		},

		createPlaybackRateMenuItem(options = {}): Object
		{
			const rate = options.rate || 1;
			const text = options.text || '';
			const isActive = options.isActive || false;
			const className = `playback-speed-menu-item ${isActive ? 'menu-popup-item-accept-sm' : ''}`;

			return {
				text,
				className,
				onclick: (event, item): true => {
					this.changePlaybackRate(rate);
					item.menuWindow.popupWindow.close();

					return true;
				},
			};
		},

		getPlaybackRateOptionText(rate: number): string
		{
			return this.isFloat(rate) ? `${rate}x` : `${rate}.0x`;
		},

		showPlaybackRateMenu(): void {
			if (this.menu && this.menu.getPopupWindow().isShown())
			{
				return;
			}

			this.menu = new this.context.BX.Main.Menu({
				id: `12xx${this.id}`,
				className: 'crm-audio-player__playback-speed-menu_scope',
				width: 100,
				bindElement: this.$refs.playbackRateButtonContainer,
				events: {
					onPopupShow: () => {
						const { width: btnContainerWidth } = Dom.getPosition(this.$refs.playbackRateButtonContainer);
						const popupWindow = this.menu.getPopupWindow();

						popupWindow.setOffset({
							offsetLeft: btnContainerWidth / 2,
						});

						popupWindow.adjustPosition();
					},

					onPopupClose: () => {
						this.menu.destroy();
						this.menu = null;
					},
				},
				angle: {
					position: 'top',
				},
				offsetLeft: 0,
				items: this.playbackRateMenuItems,
			});

			this.menu.show();
		},

		isFloat(n: number): boolean
		{
			return n % 1 !== 0;
		},

		startSeeking(event): void
		{
			this.isSeeking = true;
			const { clientX } = event;

			if (this.source())
			{
				this.source().pause();

				bind(this.context.document, 'mouseup', this.finishSeeking);
				bind(this.context.document, 'mousemove', this.seeking);

				this.setSeekByCursor(clientX);
			}
		},

		seeking(event): void
		{
			if (!this.isSeeking)
			{
				return;
			}

			const timeline = this.$refs.track;
			const { clientX } = event;

			const { left, right, width } = Dom.getPosition(timeline);
			if (clientX < left)
			{
				this.seek = 0;
			}
			else if (clientX > right)
			{
				this.seek = width - 1;
			}
			else
			{
				this.seek = clientX - left;
			}

			this.setPosition();
			event.preventDefault();
		},

		finishSeeking(): void
		{
			this.isSeeking = false;
			this.setPosition();
			if (this.source())
			{
				this.source().play();

				unbind(this.context.document, 'mouseup', this.finishSeeking);
				unbind(this.context.document, 'mousemove', this.seeking);
			}
		},

		setSeekByCursor(x: number): void
		{
			const timeline = this.$refs.track;
			const { clientX } = event;

			const { left, right, width } = Dom.getPosition(timeline);
			if (clientX < left)
			{
				this.seek = 0;
			}
			else if (x > right)
			{
				this.seek = width;
			}
			else
			{
				this.seek = clientX - left;
			}
		},

		setPosition(event): void
		{
			if (!this.loaded)
			{
				this.loadFile(true);

				return;
			}

			const pixelPerPercent = this.$refs.track.offsetWidth / 100;

			this.setProgress(this.seek / pixelPerPercent, this.seek);

			this.source().currentTime = this.timeTotal / 100 * this.progress;
		},

		setProgress(percent, pixel = -1): void
		{
			if (this.mini)
			{
				return;
			}

			this.progress = Number.isNaN(percent) ? 0 : percent;
			this.progressInPixel = pixel > 0 ? pixel : Math.round(this.$refs.track.offsetWidth / 100 * percent);
		},

		audioEventRouterWrapper(eventName: String, event): void
		{
			this.audioEventRouter(eventName, event);
		},
	},

	// Language=Vue3
	template: `
		<div
			:class="containerClassname"
			ref="body"
		>
			<div class="ui-vue-audioplayer-controls-container">
				<button :class="controlClassname" @click="clickToButton">
					<svg
						v-if="state !== State.play"
						class="ui-vue-audioplayer-control-icon"
						width="9"
						height="12"
						viewBox="0 0 9 12"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path fill-rule="evenodd" clip-rule="evenodd" d="M8.52196 5.40967L1.77268 0.637568C1.61355 0.523473 1.40621 0.510554 1.23498 0.604066C1.06375 0.697578 0.957151 0.881946 0.958524 1.0822V10.6259C0.956507 10.8265 1.06301 11.0114 1.23449 11.105C1.40597 11.1987 1.61368 11.1854 1.77268 11.0706L8.52196 6.29847C8.66466 6.19871 8.75016 6.0322 8.75016 5.85407C8.75016 5.67593 8.66466 5.50942 8.52196 5.40967Z"/>
					</svg>
					<svg
						v-else
						class="ui-vue-audioplayer-control-icon"
						width="8"
						height="10"
						viewBox="0 0 8 10"
						xmlns="http://www.w3.org/2000/svg"
					>
						<rect width="2" height="9" x="0%"></rect>
						<rect width="2" height="9" x="55%"></rect>
					</svg>
				</button>
			</div>
			<div class="ui-vue-audioplayer-timeline-container">
				<div v-if="!mini" class="ui-vue-audioplayer-record-name">{{ recordName }}</div>
				<div v-if="!mini" class="ui-vue-audioplayer-track-container" @mousedown="startSeeking" ref="track">
					<div class="ui-vue-audioplayer-track-mask"></div>
					<div class="ui-vue-audioplayer-track" :style="progressPosition"></div>
					<div @mousedown="startSeeking" class="ui-vue-audioplayer-track-seek" :style="seekPosition">
						<div ref="seek" class="ui-vue-audioplayer-track-seek-icon"></div>
					</div>
	<!--					<div class="ui-vue-audioplayer-track-event" @mousemove="seeking"></div>-->
				</div>
				<div :class="totalTimeClassname">
					<div
						v-if="(mini && timeCurrent > 0) || !mini"
						ref="currentTime"
						:class="timeCurrentClassname"
					>
						<span style="position: absolute; right: 0; top: 0;">
							{{formatTimeCurrent}}
						</span>
						<span style="opacity: 0;">{{formatTimeTotal}}</span>
					</div>
					<span class="ui-vue-audioplayer-time-divider" v-if="mini && timeCurrent > 0">&nbsp;/&nbsp;</span>
					<div ref="totalTime" class="ui-vue-audioplayer-time">{{formatTimeTotal}}</div>
				</div>
			</div>
			<div
				v-if="!mini"
				@click="showPlaybackRateMenu"
				ref="playbackRateButtonContainer"
				class="ui-vue-audioplayer_playback-speed-menu-container"
				style="user-select: none;"
			>
				{{ getPlaybackRateOptionText(playbackRate) }}
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
				@play="audioEventRouterWrapper('play', $event)"
				@playing="audioEventRouter('playing', $event)"
				@pause="audioEventRouterWrapper('pause', $event)"
			></audio>
		</div>
	`,
};
