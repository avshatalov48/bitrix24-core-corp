export const TransformPanel = {
	name: 'transform-panel',

	props: {
		modelValue: {
			type: Object,
			required: true,
		},
	},

	emits: ['locate', 'update:modelValue'],

	data(): { selectedId: string; }
	{
		return {
			selectedId: '',
		};
	},

	created(): void
	{
		this.actions = Object.freeze({
			zoomIn: 'zoomIn',
			zoomOut: 'zoomOut',
			locate: 'locate',
			navigate: 'navigate',
		});
	},

	computed:
	{
		zoomInPercent(): string
		{
			const percent = '<span class="humanresources-transform-panel__zoom_percent">%</span>';

			return `${(this.modelValue.zoom * 100).toFixed(0)}${percent}`;
		},
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		onZoom(zoomIn: boolean): void
		{
			const leftBound = 0.2;
			const rightBound = 3;
			let direction = -1;
			if (zoomIn)
			{
				direction = 1;
				this.selectedId = this.actions.zoomIn;
			}
			else
			{
				this.selectedId = this.actions.zoomOut;
			}

			const zoom = Number((this.modelValue.zoom + leftBound * direction).toFixed(1));
			if (zoom < leftBound || zoom > rightBound)
			{
				return;
			}

			this.$emit('update:modelValue', { ...this.modelValue, zoom });
		},
		onLocate(): void
		{
			const { locate } = this.actions;
			this.$emit(locate);
			this.selectedId = locate;
		},
		onfocusout(): void
		{
			this.selectedId = '';
		},
	},

	template: `
		<div class="humanresources-transform-panel" @focusout="onfocusout" tabindex="-1">
			<div
				class="humanresources-transform-panel__locate"
				:class="{ '--selected': selectedId === actions.locate }"
				@click="onLocate"
			>
				{{loc('HUMANRESOURCES_COMPANY_STRUCTURE_TREE_LOCATE')}}
			</div>
			<div class="humanresources-transform-panel__separator"></div>
			<div class="humanresources-transform-panel__zoom">
				<svg
					viewBox="0 0 16 16"
					fill="none"
					class="humanresources-transform-panel__icon --zoom-out"
					:class="{ '--selected': selectedId === actions.zoomOut }"
					@click="onZoom(false)"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8.66671V7.33337H7.33333H8.66667H12V8.66671H8.66667H7.33333H4Z" fill="#6A737F"/>
				</svg>
				<span v-html="zoomInPercent"></span>
				<svg
					viewBox="0 0 16 16"
					fill="none"
					class="humanresources-transform-panel__icon --zoom-in"
					:class="{ '--selected': selectedId === actions.zoomIn }"
					@click="onZoom(true)"
				>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M7.83333 4H9.16667V7.33333H12.5V8.66667H9.16667V12H7.83333V8.66667H4.5V7.33333H7.83333V4Z" fill="#6A737F"/>
				</svg>
			</div>
		</div>
	`,
};
