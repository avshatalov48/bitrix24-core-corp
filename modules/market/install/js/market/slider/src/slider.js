import "./slider.css";

export const Slider = {
	props: [
		'info', 'options',
	],
	data() {
		return {
			wrapper: null,
			container: null,
			slideBox: null,
			arItems: null,
			count: null,
			currentItem: null,
			arrows: null,
			controls: null,
			borderRadius: null,
			column: null,

			linkOpen: {
				tab: 'TAB',
				slider: 'SLIDER',
				market: 'MARKET',
			},
		}
	},
	computed: {
		isShow: function () {
			return this.countItems > 0;
		},
		countItems: function () {
			return BX.Type.isArray(this.info) ? this.info.length : 0;
		},
		isImageViewer: function () {
			return this.options.hasOwnProperty('viewerGroupBy') && this.options.viewerGroupBy.length > 0;
		},
	},
	mounted: function () {
		if (!this.isShow) {
			return;
		}

		this.wrapper = document.querySelector('#item_' + this.options.sliderId);
		this.init();
	},
	methods: {
		init: function () {
			this.container = this.wrapper.querySelector(".market-slider-container");
			this.slideBox = this.wrapper.querySelector(".market-slider-slidebox");
			this.arItems = this.wrapper.querySelectorAll(".market-slider-item");
			this.count = this.arItems.length;
			this.currentItem = 0;
			this.arrows = this.options.arrows || false;
			this.controls = this.options.controls || true;
			this.borderRadius = this.options.borderRadius;
			this.column = this.options.column || 1;

			if (this.countItems === 1) {
				this.arrows = false;
			}

			this.create();
		},
		create: function () {
			if (this.column > 1) {
				this.count = (this.arItems.length - (this.column - 1));
				this.container.classList.add("--columns");
			}

			if (!BX.type.isDomNode(this.container) || !BX.type.isDomNode(this.slideBox)) {
				return;
			}

			this.slideBox.style.width = (100 * this.arItems.length / this.column) + "%";
			this.slideBox.style.left = "0%";

			if (this.column > 1) {
				this.arItems[0].classList.add("--active");

				for (let i = 0; i < this.arItems.length; i++) {
					this.arItems[i].classList.add("--columns");
					this.arItems[i].setAttribute('data-item', i);
				}
			}

			if (this.arrows) {
				this.prevBtn = BX.create('div', {
					props: {
						className: "market-slider-prev-btn"
					},
					html: '<svg width="42" height="83" viewBox="0 0 42 83" fill="none" xmlns="http://www.w3.org/2000/svg">' +
						'<path opacity="0.35" fill-rule="evenodd" clip-rule="evenodd" d="M-7.25609e-06 0C23.196 -2.02786e-06 42 18.5802 42 41.5C42 64.4198 23.196 83 0 83" fill="black"/>' +
						'<path fill-rule="evenodd" clip-rule="evenodd" d="M22.4698 32.9856L16.0565 39.3989L14.45 40.9999L16.0565 42.5997L22.4699 49.013L20.2068 51.2761L9.93004 40.9994L20.2068 30.7227L22.4698 32.9856Z" fill="#fff"/></svg>',
					events: {
						click: BX.proxy(this.prev, this)
					},
				})

				this.nextBtn = BX.create('div', {
					props: {
						className: "market-slider-next-btn"
					},
					html: '<svg width="42" height="83" viewBox="0 0 42 83" fill="none" xmlns="http://www.w3.org/2000/svg">' +
						'<path opacity="0.35" fill-rule="evenodd" clip-rule="evenodd" d="M42 0C18.804 -2.02786e-06 5.63176e-06 18.5802 3.62805e-06 41.5C1.62433e-06 64.4198 18.804 83 42 83" fill="black"/>' +
						'<path fill-rule="evenodd" clip-rule="evenodd" d="M19.5302 32.9856L25.9435 39.3989L27.55 40.9999L25.9435 42.5997L19.5301 49.013L21.7932 51.2761L32.07 40.9994L21.7932 30.7227L19.5302 32.9856Z" fill="white"/></svg>',
					events: {
						click: BX.proxy(this.next, this)
					},
				})

				this.container.appendChild(this.prevBtn)
				this.container.appendChild(this.nextBtn)
			}

			if (this.controls) {
				this.controls = BX.create('div', {
					props: {
						className: "market-slider-controls-container"
					},
				});

				let i = 0;
				while (i < this.count)
				{
					let controlBtn = BX.create('div', {
						props: {
							className: (i ==  this.currentItem) ? "market-slider-control-btn current" : "market-slider-control-btn"
						},
						attrs: {
							"data-item": String(i)
						},
						events: {
							click: (event) => {
								this.goTo(event.target.getAttribute("data-item"))
							}
						}
					});

					this.controls.appendChild(controlBtn)
					i++;
				}

				this.wrapper.appendChild(this.controls)
			}
		},
		goTo: function(itemTarget) {
			this.slideBox.style.left = (itemTarget * (-100 / this.column)) +  '%'

			if(this.controls.querySelector(".market-slider-control-btn.current")) {
				this.controls.querySelector(".market-slider-control-btn.current").classList.remove("current");
			}

			for (let i = 0; i < this.arItems.length; i++) {
				if(this.arItems[i].classList.contains("--active")) {
					this.arItems[i].classList.remove("--active");
				}
			}

			this.controls.querySelector(".market-slider-control-btn[data-item='" + itemTarget + "']").classList.add("current");
			this.arItems[itemTarget].classList.add("--active");

			this.currentItem = itemTarget;
		},
		prev: function () {
			let prevItem = this.currentItem - 1;
			if (prevItem < 0) {
				prevItem = this.count - 1
			}

			this.goTo(prevItem)
		},
		next: function () {
			let nextItem = this.currentItem + 1;
			if (nextItem >= this.count) {
				nextItem = 0
			}

			this.goTo(nextItem)
		},
		sliderClick: function (event, sliderItem) {
			if (sliderItem.LINK_OPEN === this.linkOpen.market) {
				event.preventDefault();
				this.$root.emitLoadContent(event)
			} else if (sliderItem.LINK_OPEN === this.linkOpen.slider) {
				event.preventDefault();
				BX.SidePanel.Instance.open(
					sliderItem.LINK,
					{
						width: 800,
					}
				);
			}
		},
	},
	template: `
		<div class="market-container-slider"
			 v-if="isShow"
		>
			<div class="market-slider"
				 :id="'item_' + options.sliderId"
			>
				<div class="market-slider-container">
					<div class="market-slider-slidebox">
						<template v-if="isImageViewer">
							<div class="market-slider-item"
								 v-for="sliderItem in info"
							>
								<div class="market-slider-item-inner">
									<img class="market-slider-item-img-detail"
										 :src="sliderItem.PREVIEW"
										 :data-src="sliderItem.IMG"
										 :data-viewer-group-by="options.viewerGroupBy"
										 data-viewer
										 data-actions="[]"
									>
								</div>
							</div>
						</template>
						<template v-else>
							<div class="market-slider-item"
								 v-for="sliderItem in info"
							>
								<a :href="sliderItem.LINK" 
								   target="_blank"
								   data-slider-ignore-autobinding="true"
								   data-load-content="list"
								   @click="sliderClick($event, sliderItem)"
								>
									<img class="market-slider-item-img"
										 :src="sliderItem.IMG"
									>
								</a>
							</div>
						</template>
					</div>
				</div>
			</div>
		</div>
	`,
}