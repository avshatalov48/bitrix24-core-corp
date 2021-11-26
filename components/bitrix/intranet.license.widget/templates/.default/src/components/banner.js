import { Type } from 'main.core';
import { Loader } from "main.loader";

export const BannerComponent = {
	data()
	{
		return {
			sliderAllCount: 0,
			sliderActive: 1,
			sliderOffsetLeft: 0,
			sliderOffsetHeight: 0,
			sliderInterval: null,
			sliderTimer: 10000,
			offsetCache: 0,
			isBannersExist: true,
			sliderList: [],
			loader: new Loader({ size: 50 })
		};
	},
	mounted()
	{
		BX.ajax.runAction(
			'intranet.license.getBannerData',
			{data: {}}
		).then((response) => {

			if (!Type.isObject(response.data))
			{
				this.isBannersExist = false;
				return;
			}
			const now = Date.now();
			this.sliderList = Object.values(
				response.data
			).filter((item) => {
				let startString = item['CONDITIONS']['DATE_FROM'],
					endString = item['CONDITIONS']['DATE_TO'],
					start = Type.isString(startString)? new Date(startString) : null,
					end = Type.isString(endString)? new Date(endString) : null;
				//if start/end condition exists check it

				let width = item['OPTIONS']['width'];
				let height = item['OPTIONS']['height'];
				let ratio = width / height;

				let wrapperWidth = this.$refs.wrapper.offsetWidth === 0 ? 324 : this.$refs.wrapper.offsetWidth;

				if(this.sliderOffsetHeight < wrapperWidth / ratio)
				{
					this.sliderOffsetHeight = (wrapperWidth / ratio) + 'px';
				}
				this.loader.hide();

				return (start? start.getTime() <= now : true) && (end? end.getTime() > now : true)
			}).sort((first,second) => {
				return parseInt(first['SORT'] ?? '0') - parseInt(second['SORT'] ?? '0');
			});
			this.sliderAllCount = this.sliderList.length;
			this.isBannersExist = this.sliderAllCount > 0;
			this.initSlider();

		}).catch(() => this.isBannersExist = false);
		this.loader.show(this.$refs.container);
	},
	methods: {
		sliderOffsetStep()
		{
			if (this.offsetCache === 0)
			{
				this.offsetCache = this.$refs.wrapper ? this.$refs.wrapper.offsetWidth : 0
			}

			return this.offsetCache;
		},
		runSlide()
		{
			if(this.sliderAllCount > 1 || this.sliderTimer > 0)
			{
				this.sliderOffsetStep();
				this.sliderInterval = setInterval(() => {
					let sliderNum = this.sliderActive + 1 > this.sliderAllCount
						? 1
						: this.sliderActive + 1;
					this.openSlide(sliderNum);
				}, this.sliderTimer);
			}
		},

		stopSlide()
		{
			clearInterval(this.sliderInterval);
		},

		initSlider()
		{
			if(this.sliderAllCount > 1 || this.sliderTimer > 0)
			{
				this.runSlide();
			}
		},

		openSlide(id, event)
		{
			if (id > 0 && id <= this.sliderAllCount)
			{
				this.sliderActive = id;
				this.sliderOffsetLeft = 'translateX(' + -(this.sliderOffsetStep() * (this.sliderActive - 1)) + 'px)';
			}

			if(event === 'toggler')
			{
				clearInterval(this.sliderInterval);
				this.runSlide();
			}
		},

		nextSlide()
		{
			if (this.sliderActive < this.sliderAllCount)
			{
				this.sliderActive += 1
				this.openSlide(this.sliderActive)
			}
		},

		prevSlide()
		{
			if (this.sliderActive > 1)
			{
				this.sliderActive -= 1;
				this.openSlide(this.sliderActive);
			}
		}
	},
	template: `
		<div v-if="isBannersExist" style="margin-top: 20px">
			<div class="license-widget-banner-container --relative" ref="container">
				<div data-role="license-widget-banner" class="license-widget-item license-widget-item--widew">
					<div class="license-widget-banner-slider" ref="wrapper">
						<div
							class="license-widget-banner-slider-wrapper"
							v-bind:style="{ transform: sliderOffsetLeft }"
							v-on:mouseenter="stopSlide"
							v-on:mouseleave="runSlide"
							>
							<div
								v-for="banner in sliderList"
								class="license-widget-banner-slider-item"
								:style="{ height: sliderOffsetHeight }">
								<img :src="banner.OPTIONS.src" alt="" :width="sliderOffsetStep()">
								<a :href="banner.OPTIONS.actionParameters.target" target="_self"></a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="license-widget-banner-slider-nav" v-if="sliderAllCount > 1">
				<div
					v-for="(_, index) in sliderList"
					v-on:click="openSlide(index+1, 'toggler')"
					class="license-widget-banner-slider-nav-item"
					v-bind:class="{ '--active': sliderActive === index+1 }"
				>
				</div>
			</div>
		</div>
	`,
};
