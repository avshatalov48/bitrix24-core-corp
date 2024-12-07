import { Text, Type } from 'main.core';
import { TileWidgetComponent } from 'ui.uploader.tile-widget';

const MAX_UPLOAD_FILE_SIZE = 1024 * 1024 * 50; // 50M;

export const TodoEditorBlocksFile = {
	components: {
		TileWidgetComponent,
	},

	props: {
		id: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		settings: {
			type: Object,
			required: true,
		},
		filledValues: {
			type: Object,
		},
		context: {
			type: Object,
			required: true,
		},
		isFocused: {
			type: Boolean,
		},
	},

	emits: [
		'close',
		'updateFilledValues',
	],

	data(): Object
	{
		const data = {
			fileTokens: [],
		};

		return this.getPreparedData(data);
	},

	mounted(): void
	{
		const needShowLoaderPopup = !(BX.localStorage && BX.localStorage.get('skip_autoshow_file_selector'));
		if (this.isFocused && needShowLoaderPopup)
		{
			void this.$nextTick(this.onShowUploaderPopup);
		}

		this.deleteFilesFromServerWhenDestroy = false;
	},

	methods: {
		getId(): string
		{
			return 'file';
		},
		getPreparedData(data: Object): Object
		{
			const { filledValues } = this;

			if (Type.isArray(filledValues?.fileTokens))
			{
				// eslint-disable-next-line no-param-reassign
				data.fileTokens = filledValues.fileTokens;
			}

			return data;
		},
		getExecutedData(): Object
		{
			return {
				fileTokens: this.getFileTokenList(),
			};
		},
		emitUpdateFilledValues(): void
		{
			let { filledValues } = this;
			const { fileTokens } = this;

			const newFilledValues = {
				fileTokens,
			};
			filledValues = { ...filledValues, ...newFilledValues };
			this.$emit('updateFilledValues', this.getId(), filledValues);
		},
		onShowUploaderPopup(): void
		{
			this.$refs.fileBody.querySelector('.ui-tile-uploader-drop-label').click();
		},
		reset(): void
		{
			this.deleteFilesFromServerWhenDestroy = true;
		},
		getFileTokenList(): string[]
		{
			const tokens = [];

			this.$refs.uploader.uploader.getFiles().forEach((file) => {
				if (file.isComplete())
				{
					tokens.push(file.getServerFileId());
				}
			});

			return tokens;
		},
	},

	computed: {
		encodedTitle(): string
		{
			return Text.encode(this.title);
		},
		iconStyles(): Object
		{
			if (!this.icon)
			{
				return {};
			}

			const path = `/bitrix/js/crm/activity/todo-editor-v2/images/${this.icon}`;

			return {
				background: `url('${encodeURI(Text.encode(path))}') center center`,
			};
		},
		actionTitle(): string
		{
			return this.hasFileTokens ? this.changeTitle : this.addTitle;
		},
		changeTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_LINK_BLOCK_CHANGE_ACTION');
		},
		addTitle(): string
		{
			return this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_FILE_BLOCK_UPLOAD_ACTION');
		},
		hasFileTokens(): boolean
		{
			return Type.isArrayFilled(this.fileTokens);
		},
		uploaderOptions(): Object
		{
			return {
				controller: 'crm.fileUploader.todoActivityUploaderController',
				controllerOptions: {
					entityId: this.settings.entityId,
					entityTypeId: this.settings.entityTypeId,
					activityId: this.context.activityId,
				},
				files: this.fileTokens,
				events: {
					/* 'File:onRemove': (event) => {

					},
					onUploadStart: (event) => {

					},
					'File:onComplete': (event) => {

					}, */
					onUploadComplete: () => {
						void this.$nextTick(() => {
							this.fileTokens = this.getFileTokenList();
						});
					},
				},
				multiple: true,
				autoUpload: true,
				maxFileSize: MAX_UPLOAD_FILE_SIZE,
			};
		},
		widgetOptions(): Object
		{
			return {};
		},
	},

	created()
	{
		this.$watch(
			'fileTokens',
			this.emitUpdateFilledValues,
			{
				deep: true,
			},
		);
	},

	beforeUnmount()
	{
		this.$refs.uploader.adapter.setRemoveFilesFromServerWhenDestroy(this.deleteFilesFromServerWhenDestroy);
	},

	template: `
		<div class="crm-activity__todo-editor-v2_block-header --file">
			<span
				class="crm-activity__todo-editor-v2_block-header-icon"
				:style="iconStyles"
			></span>
			<span>{{ encodedTitle }}</span>
			<span
				@click="onShowUploaderPopup"
				ref="file"
				class="crm-activity__todo-editor-v2_block-header-action"
			>
				{{ actionTitle }}
			</span>
			<div
				@click="$emit('close', id)"
				class="crm-activity__todo-editor-v2_block-header-close"
			></div>
		</div>
		<div ref="fileBody" class="crm-activity__todo-editor-v2_block-body --file">
			<TileWidgetComponent 
				ref="uploader"
				:uploaderOptions="uploaderOptions"
				:widgetOptions="widgetOptions"
			/>
		</div>
	`,
};
