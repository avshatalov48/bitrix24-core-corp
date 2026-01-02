import { Dom } from 'main.core';
import { UserMiniProfileLoader } from '../loader/loader';

import './style.css';

// @vue/component
export const LoaderTransition = {
	name: 'LoaderTransition',
	components: {
		UserMiniProfileLoader,
	},
	props: {
		isLoading: {
			type: Boolean,
			required: true,
		},
		isShowContent: {
			type: Boolean,
			required: true,
		},
		isLoaderShort: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['end'],
	methods: {
		resetSize(): void
		{
			Dom.style(this.$el, {
				width: '',
				height: '',
			});
		},
		onEnd(): void
		{
			this.resetSize();
			this.$emit('end');
		},
		onEnter(el: HTMLElement): void
		{
			requestAnimationFrame(() => {
				Dom.style(this.$el, {
					width: `${el.offsetWidth}px`,
					height: `${el.offsetHeight}px`,
				});
			});
		},
		onBeforeLeave(el: HTMLElement): void
		{
			Dom.style(this.$el, {
				width: `${el.offsetWidth}px`,
				height: `${el.offsetHeight}px`,
			});
		},
		onAfterLeave(): void
		{
			this.onEnd();
		},

		onAfterEnter(): void
		{
			this.onEnd();
		},
	},

	template: `
		<TransitionGroup 
			name="intranet-user-mini-profile-fade"
			tag="div"
			class="intranet-user-mini-profile__loader-transition-wrapper"
			@enter="onEnter"
			@beforeLeave="onBeforeLeave"
			@afterLeave="onAfterLeave"
			@afterEnter="onAfterEnter"
		>
			<div v-if="isLoading"
                 class="intranet-user-mini-profile__loader-transition-wrapper__loader"
				 key="loader"
			>
				<UserMiniProfileLoader
				   :isShort="isLoaderShort"
				/>
			</div>
			<slot v-else-if="isShowContent"></slot>
		</TransitionGroup>
	`,
};
