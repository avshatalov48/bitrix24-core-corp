import {Scroll} from "../../util/registry";

const Scrollable = {
	props: ['show', 'enabled', 'zIndex', 'text', 'topIntersected', 'bottomIntersected'],
	template: `
		<div>
			<transition name="b24-a-fade">
				<div class="b24-window-scroll-arrow-up-box"
					v-if="enabled && !text && !anchorTopIntersected" 
					:style="{ zIndex: zIndexComputed + 10}"
					@click="scrollTo(false)"
				>
					<button type="button" class="b24-window-scroll-arrow-up"></button>
				</div>
			</transition>						
			<div class="b24-window-scrollable" :style="{ zIndex: zIndexComputed }">
				<div v-show="enabled" class="b24-window-scroll-anchor"></div>
				<slot></slot>
				<div v-show="enabled" class="b24-window-scroll-anchor"></div>
			</div>
			<transition name="b24-a-fade">
				<div class="b24-window-scroll-arrow-down-box"
					v-if="enabled && !text && !anchorBottomIntersected && !hideEars"
					:style="{ zIndex: zIndexComputed + 10}"
					@click="scrollTo(true)"
				>
					<button type="button" class="b24-window-scroll-arrow-down"></button>
				</div>
				<div class="b24-form-scroll-textable"
					v-if="enabled && text && !anchorBottomIntersected && !hideEars" 
					:style="{ zIndex: zIndexComputed + 10}"
					@click="scrollTo(true)"
				>
					<p class="b24-form-scroll-textable-text">{{ text }}</p>
					<div class="b24-form-scroll-textable-arrow">
						<div class="b24-form-scroll-textable-arrow-item"></div>
						<div class="b24-form-scroll-textable-arrow-item"></div>
						<div class="b24-form-scroll-textable-arrow-item"></div>
					</div>
				</div>
			</transition>
		</div>	
	`,
	data: function ()
	{
		return {
			showed: false,
			anchorObserver: null,
			anchorTopIntersected: true,
			anchorBottomIntersected: true,
		};
	},
	computed: {
		zIndexComputed()
		{
			return this.zIndex || 200;
		},
		hideEars()
		{
			return this.$root.flags ? this.$root.flags.hideEars : false;
		},
	},
	methods: {
		getScrollNode()
		{
			return this.$el.querySelector('.b24-window-scrollable');
		},
		scrollTo(toDown)
		{
			toDown = toDown || false;

			let el = this.getScrollNode();
			let interval = 10;
			let duration = 100;

			let diff = toDown
				? (el.scrollHeight - el.offsetHeight - el.scrollTop)
				: el.scrollTop;

			let step = diff / (duration / interval);
			let scroller = () => {
				diff -= step;
				el.scrollTop += toDown ? +step : -step;
				if (diff > 0)
				{
					setTimeout(scroller, interval);
				}
			};
			scroller();
		},
		toggleScroll ()
		{
			Scroll.toggle(
				this.getScrollNode(),
				!this.show
			);
		},
		toggleObservingScrollHint ()
		{
			if (!window.IntersectionObserver)
			{
				return;
			}

			let scrollable = this.getScrollNode();
			if (!scrollable)
			{
				return;
			}
			let topAnchor = scrollable.firstElementChild;
			let bottomAnchor = scrollable.lastElementChild;
			if (!topAnchor && !bottomAnchor)
			{
				return;
			}

			if (this.anchorObserver)
			{
				topAnchor ? this.anchorObserver.unobserve(topAnchor) : null;
				bottomAnchor ? this.anchorObserver.unobserve(bottomAnchor) : null;
				this.anchorObserver = null;
				return;
			}

			this.anchorObserver = new IntersectionObserver(
				entries => {
					entries.forEach(entry => {
						if (entry.target === topAnchor)
						{
							this.anchorTopIntersected = !!entry.isIntersecting;
						}
						else if (entry.target === bottomAnchor)
						{
							this.anchorBottomIntersected = !!entry.isIntersecting;
						}
					});
				},
				{
					root: scrollable,
					rootMargin: this.scrollDownText ? '80px' : '60px',
					threshold: 0.1
				}
			);
			topAnchor ? this.anchorObserver.observe(topAnchor) : null;
			bottomAnchor ? this.anchorObserver.observe(bottomAnchor) : null;
		},
	},
	mounted ()
	{
		if (this.show)
		{
			this.toggleScroll();
			this.toggleObservingScrollHint();
		}
	},
	watch: {
		show (val)
		{
			if (val && !this.showed)
			{
				this.showed = true;
			}

			this.toggleScroll();
			this.toggleObservingScrollHint();
		},
	},
};

export {
	Scrollable,
}