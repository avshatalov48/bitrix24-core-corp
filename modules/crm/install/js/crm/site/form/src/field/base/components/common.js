import "./css/slider.css";
import "./css/dropdown.css";
import {Scroll, MoveObserver} from "../../../util/registry";

const Dropdown = {
	props: ['marginTop', 'maxHeight', 'width', 'visible', 'title'],
	template: `
		<div class="b24-form-dropdown">
			<transition name="b24-form-dropdown-slide" appear>
			<div class="b24-form-dropdown-container" 
				:style="{marginTop: marginTop, maxHeight: maxHeight, width: width, minWidth: width}"
				v-if="visible"
			>
				<div class="b24-form-dropdown-header" ref="header">
					<button @click="close()" type="button" class="b24-window-close"></button>
					<div class="b24-form-dropdown-title">{{ title }}</div>
				</div>			
				<slot></slot>
			</div>
			</transition>
		</div>
	`,
	data: function ()
	{
		return {
			listenerBind: null,
			observers: {},
		};
	},
	created()
	{
		this.listenerBind = this.listener.bind(this);
	},
	mounted()
	{
		this.observers.move = new MoveObserver(this.observeMove.bind(this));
	},
	beforeDestroy()
	{
		document.removeEventListener('mouseup', this.listenerBind);
	},
	watch: {
		visible (val)
		{
			if (val)
			{
				this.$emit('visible:on');
				document.addEventListener('mouseup', this.listenerBind);
			}
			else
			{
				setTimeout(() => this.$emit('visible:off'), 0);
				document.removeEventListener('mouseup', this.listenerBind);
			}
			if (window.innerWidth <= 530)
			{
				setTimeout(() => {
					Scroll.toggle(this.$el.querySelector('.b24-form-dropdown-container'), !val);
					this.observers.move.toggle(val, this.$refs.header);
				}, 0);
			}
			if (this.$root.flags)
			{
				this.$root.flags.hideEars = val;
			}
		}
	},
	methods: {
		close ()
		{
			this.$emit('close');
		},
		listener (e)
		{
			let el = e.target;
			if (this.$el !== el && !this.$el.contains(el))
			{
				this.close();
			}
		},
		observeMove (observer: MoveObserver, isEnd)
		{
			let target = observer.element.parentElement;
			if (!isEnd)
			{
				if (!target.dataset.height)
				{
					target.dataset.height = target.clientHeight;
				}

				target.style.height = target.style.minHeight = (
					parseInt(target.dataset.height) + parseInt(observer.deltaY)
				) + 'px';
			}
			if (isEnd)
			{
				if (observer.deltaY < 0 && Math.abs(observer.deltaY) > target.dataset.height / 2)
				{
					if (document.activeElement)
					{
						document.activeElement.blur();
					}
					this.close();
					setTimeout(() => {
						if (!target)
						{
							return;
						}
						target.dataset.height = null;
						target.style.height = null;
						target.style.minHeight = null;
					}, 300);
				}
				else
				{
					target.style.transition = "all 0.4s ease 0s";
					target.style.height = target.style.minHeight = target.dataset.height + 'px';
					setTimeout(() => target.style.transition = null, 400);
				}
			}
		}
	}
};

const Alert = {
	props: ['field', 'item'],
	template: `
		<div class="b24-form-control-alert-message"
			v-show="hasErrors"
		>
			{{ message }}
		</div>
	`,
	computed: {
		hasErrors()
		{
			return this.field.validated
				&& !this.field.focused
				&& !this.field.valid();
		},
		message()
		{
			if (this.field.isEmptyRequired())
			{
				return this.field.messages.get('fieldErrorRequired');
			}
			else if (this.field.validated && !this.field.valid())
			{
				let type = this.field.type;
				type = type.charAt(0).toUpperCase() + type.slice(1);
				return (
					this.field.messages.get(
						'fieldErrorInvalid' + type
					)
					||
					this.field.messages.get('fieldErrorInvalid')
				);
			}
		},
	},
};

const Slider = {
	props: ['field', 'item'],
	data: function ()
	{
		return {
			index: 0,
			lastItem: null,
			minHeight: 100,
			indexHeight: 100,
			heights: {},
			touch: {
				started: false,
				detecting: false,
				x: 0,
				y: 0,
			},
		};
	},
	template: `
		<div v-if="hasPics" class="b24-from-slider">
			<div class="b24-form-slider-wrapper">
				<div class="b24-form-slider-container" 
					:style="{ height: height + 'px', width: width + '%', left: left + '%'}"
					v-swipe="move"
				>
					<div class="b24-form-slider-item"
						v-for="(pic, picIndex) in getItem().pics"
					>
						<img class="b24-form-slider-item-image" 
							:src="pic"
							@load="saveHeight($event, picIndex)"
						>
					</div>
				</div>
					<div class="b24-form-slider-control-prev"
						@click="prev"
						:style="{ visibility: prevable() ? 'visible' : 'hidden'}"
					><div class="b24-form-slider-control-prev-icon"></div></div>
					<div class="b24-form-slider-control-next"
						@click="next"
						:style="{ visibility: nextable() ? 'visible' : 'hidden'}"
					><div class="b24-form-slider-control-next-icon"></div></div>
			</div>
		</div>
	`,
	directives: {
		swipe: {
			inserted: function (el, binding) {
				let data = {
					started: false,
					detecting: false,
					x: 0,
					y: 0,
					touch: null,
				};

				let hasTouch = (list, item) => {
					for (let i = 0; i < list.length; i++)
					{
						if (list.item(i).identifier === item.identifier)
						{
							return true;
						}
					}

					return false;
				};

				el.addEventListener('touchstart', (e) => {
					if (e.touches.length !== 1 || data.started)
					{
						return;
					}
					let touch = e.changedTouches[0];
					data.detecting = true;
					data.x = touch.pageX;
					data.y = touch.pageY;
					data.touch = touch;
				});
				el.addEventListener('touchmove', (e) => {
					if (!data.started && !data.detecting)
					{
						return;
					}

					let touch = e.changedTouches[0];
					let newX = touch.pageX;
					let newY = touch.pageY;

					if (!hasTouch(e.changedTouches, touch))
					{
						return;
					}

					if (data.detecting)
					{
						if (Math.abs(data.x - newX) >= Math.abs(data.y - newY))
						{
							e.preventDefault();
							data.started = true;
						}

						data.detecting = false;
					}

					if (data.started)
					{
						e.preventDefault();
						data.delta = data.x - newX;
					}
				});

				let onEnd = (e) =>
				{
					if (!hasTouch(e.changedTouches, data.touch) || !data.started)
					{
						return;
					}
					e.preventDefault();
					if (data.delta > 0)
					{
						binding.value(true);
					}
					else if (data.delta < 0)
					{
						binding.value(false);
					}

					data.started = false;
					data.detecting = false;
				};

				el.addEventListener('touchend', onEnd);
				el.addEventListener('touchcancel', onEnd);
			}
		},
	},
	computed: {
		height()
		{
			if (this.indexHeight && this.indexHeight > this.minHeight)
			{
				return this.indexHeight;
			}
			return this.minHeight;
		},
		width()
		{
			return this.getItem().pics.length * 100;
		},
		left()
		{
			return this.index * -100;
		},
		hasPics()
		{
			return (
				this.getItem()
				&& this.getItem().pics
				&& Array.isArray(this.getItem().pics)
				&& this.getItem().pics.length > 0
			);
		},
	},
	methods: {
		saveHeight(e, picIndex)
		{
			this.heights[picIndex] = e.target.clientHeight;
			this.applyIndexHeight();
		},
		applyIndexHeight()
		{
			this.indexHeight = this.heights[this.index];
		},
		getItem()
		{
			let item = this.item || this.field.selectedItem();
			if (this.lastItem !== item)
			{
				this.lastItem = item;
				this.index = 0;
				this.heights = {};
			}

			return this.lastItem;
		},
		nextable()
		{
			return this.index < this.getItem().pics.length - 1;
		},
		prevable()
		{
			return this.index > 0;
		},
		next()
		{
			if (this.nextable())
			{
				this.index++;
				this.applyIndexHeight();
			}
		},
		prev()
		{
			if (this.prevable())
			{
				this.index--;
				this.applyIndexHeight();
			}
		},
		move(next)
		{
			next ? this.next() : this.prev();
		},
	}
};

const Definition = {
	'field-item-alert': Alert,
	'field-item-image-slider': Slider,
	'field-item-dropdown': Dropdown,
};
export {
	Definition,
	Alert,
	Slider,
	Dropdown,
}