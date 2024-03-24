import '../css/main.css';
import { Event, Runtime } from 'main.core';
import { sliderButtonsAdapter } from '../ai-form-fill-app';
import { myScrollTo } from '../services/utils';
import { CloseConfirm } from './close-confirm';
import { EntityEditorWrapper } from './entity-editor-wrapper';
import { FeedbackMessage } from './feedback-message';
import { FloatingActionButton } from './floating-action-button';
import { Loader } from './loader';
import { mapActions, mapGetters, mapMutations } from 'ui.vue3.vuex';
import { ToolBar } from './tool-bar';
import { Merger } from './merger';

export const Main = {
	name: 'Main',
	components: {
		Loader,
		EntityEditorWrapper,
		ToolBar,
		Merger,
		FloatingActionButton,
		CloseConfirm,
		FeedbackMessage,
	},
	data() {
		return {};
	},
	computed: {
		...mapGetters([
			'conflictFields',
			'isLoading',
			'eeControlPositions',
			'getFirstUnseenFieldPosition',
			'aiValuesAppliedCount',
			'mergeUuid',
			'isSliderConfirmPopupShown',
			'isFeedbackMessageShown',
			'isFooterHiddenAndSaveDisabled',
		]),
	},
	methods: {
		...mapActions([
			'initialize',
			'saveFormFieldsToMerge',
			'updateControlPositionInfo',
			'updateSliderFooter',
			'closeFormWithoutConfirm',
			'sendAiCallParsingData',
		]),
		...mapMutations([
			'changeMainLayoutScrollPosition',
			'startLoading',
			'stopLoading',
			'setMainLayoutScrollHeight',
		]),
		onFooterSaveBtn() {
			this.saveFormFieldsToMerge()
				.then(() => this.sendAiCallParsingData('conflict_accept_changes'))
				.catch(() => {})
			;
		},
		onFooterCancelBtn() {
			this.closeFormWithoutConfirm();
			this.sendAiCallParsingData('conflict_cancel_changes');
		},
		onCloseConfirm() {
			this.closeFormWithoutConfirm();
		},
		handleScroll: null, // will be assigned in the mounted callback
		positionChanged() {
			this.setMainLayoutScrollHeight(this.$refs.layout.scrollHeight);
			this.changeMainLayoutScrollPosition({
				scrollTop: this.$refs.layout.scrollTop,
				containerHeight: this.$refs.layout.getBoundingClientRect().height,
			});
		},
		resizeHandler() {
			this.handleScroll();
		},
		scrollToNext() {
			const scrollTo = this.getFirstUnseenFieldPosition;
			if (scrollTo)
			{
				myScrollTo(this.$refs.layout, scrollTo, 300);
			}
		},
		subscribeInternalEvents() {
			this.$Bitrix.eventEmitter.subscribe('crm:ai:form-fill:scroll-to-next', this.scrollToNext);
			this.$Bitrix.eventEmitter.subscribe('crm:ai:form-fill:close-confirm:confirmClose', this.onCloseConfirm);
			this.$Bitrix.eventEmitter.subscribe('crm:ai:form-fill:close-confirm:cancelClose', this.scrollToNext);
		},
		unSubscribeInternalEvents() {
			this.$Bitrix.eventEmitter.unsubscribe('crm:ai:form-fill:scroll-to-next', this.scrollToNext);
			this.$Bitrix.eventEmitter.unsubscribe('crm:ai:form-fill:close-confirm:confirmClose', this.onCloseConfirm);
			this.$Bitrix.eventEmitter.unsubscribe('crm:ai:form-fill:close-confirm:cancelClose', this.scrollToNext);
		},
		autoScrollToFirst() {
			const height = this.$refs.layout.getBoundingClientRect().height;
			const firstPosY = this.getFirstUnseenFieldPosition;
			if (firstPosY && firstPosY > height)
			{
				myScrollTo(this.$refs.layout, firstPosY, 800);
			}
		},
	},
	async mounted() {
		this.updateSliderFooter();
		this.startLoading();
		this.handleScroll = Runtime.throttle(() => {
			this.positionChanged();
		}, 300);

		await this.initialize();
		this.positionChanged();

		this.subscribeInternalEvents();

		await this.$nextTick(() => {
			Event.bind(window, 'resize', this.resizeHandler);
		});
		this.stopLoading();

		this.autoScrollToFirst();

		sliderButtonsAdapter.onSaveCallback = this.onFooterSaveBtn;
		sliderButtonsAdapter.onCancelCallback = this.onFooterCancelBtn;
	},
	watch: {
		aiValuesAppliedCount: {
			handler(newVal, oldVal) {
				this.updateSliderFooter();
			},
			immediate: true,
		},
	},
	unmounted() {
		this.unSubscribeInternalEvents();
		Event.unbind(window, 'resize', this.resizeHandler);
	},
	template: `
		<div class="bx-crm-ai-merge-fields" :class="{'hidden-footer': isFooterHiddenAndSaveDisabled}">
			<div 
				class="bx-crm-ai-merge-fields-layout" 
				@scroll="handleScroll"
				ref="layout"
				:style="{'visibility': !isLoading ? 'visible' : 'hidden'}"
				:class="{'hidden-footer': isFooterHiddenAndSaveDisabled}"
			>
				<EntityEditorWrapper class="bx-crm-ai-merge-fields-layout__ee_column"/>
				<Merger class="bx-crm-ai-merge-fields-layout__aifields_column"/>
				<div class="bx-crm-ai-merge-fields-layout__floating-button_column">
					<FloatingActionButton/>
				</div>
			</div>
			<Loader v-if="isLoading" />
			<CloseConfirm v-if="isSliderConfirmPopupShown" />
			<FeedbackMessage v-if="isFeedbackMessageShown" />
		</div>
	`,
};
