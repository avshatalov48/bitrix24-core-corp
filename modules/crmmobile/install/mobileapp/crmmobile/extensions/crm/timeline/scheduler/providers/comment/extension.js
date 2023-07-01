/**
 * @module crm/timeline/scheduler/providers/comment
 */
jn.define('crm/timeline/scheduler/providers/comment', (require, exports, module) => {
	include('InAppNotifier');

	const { Loc } = require('loc');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const {
		Toolbar,
		ToolbarIcon,
		ToolbarButton,
	} = require('crm/timeline/ui/toolbar');
	const { Textarea } = require('crm/timeline/ui/textarea');
	const { FileField } = require('layout/ui/fields/file');
	const { isArray } = require('utils/object');

	/**
	 * @class TimelineSchedulerCommentProvider
	 */
	class TimelineSchedulerCommentProvider extends TimelineSchedulerBaseProvider
	{
		constructor(props)
		{
			super(props);

			this.state = {
				text: '',
				files: [],
			};

			this.textInputRef = null;

			/** @type {Fields.FileField|null} */
			this.fileFieldRef = null;

			/** @type {ToolbarButton|null} */
			this.createButtonRef = null;
		}

		static getId()
		{
			return 'comment';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.75227 6.85869H23.5113C24.3531 6.85869 25.0356 7.54116 25.0356 8.38302V18.916C25.0356 19.7579 24.3531 20.4404 23.5113 20.4404H15.6318L10.4074 25.6641V20.4404H7.75227C6.9104 20.4404 6.22794 19.7579 6.22794 18.916V8.38302C6.22794 7.54116 6.9104 6.85869 7.75227 6.85869Z" fill="#767C87"/></svg>`;
		}

		static getMenuPosition()
		{
			return 200;
		}

		static isSupported()
		{
			return false;
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.focus();
		}

		hasUploadingFiles()
		{
			if (!this.fileFieldRef)
			{
				return false;
			}

			return this.fileFieldRef.hasUploadingFiles();
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
					resizableByKeyboard: true,
				},
				this.renderTextField(),
				this.renderAttachments(),
				this.renderToolbar(),
			);
		}

		renderTextField()
		{
			return Textarea({
				ref: (ref) => this.textInputRef = ref,
				text: this.state.text,
				placeholder: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_PLACEHOLDER'),
				onChange: (text) => {
					this.state.text = text;
					this.refreshSaveButton();
				},
			});
		}

		renderAttachments()
		{
			return View(
				{
					style: {
						paddingHorizontal: 20,
						display: this.state.files.length === 0 ? 'none' : 'flex',
					},
				},
				FileField({
					ref: (ref) => this.fileFieldRef = ref,
					showTitle: false,
					showAddButton: false,
					multiple: true,
					value: [],
					config: {
						fileInfo: {},
						mediaType: 'file',
						parentWidget: this.layout,
						controller: {
							endpoint: 'crm.FileUploader.ActivityController',
							options: {
								entityTypeId: this.entity.typeId,
								entityId: this.entity.id,
								categoryId: this.entity.categoryId,
							},
						},
					},
					readOnly: false,
					onChange: (files) => {
						files = isArray(files) ? files : [];
						this.setState({ files }, () => this.refreshSaveButton());
					},
				}),
			);
		}

		renderToolbar()
		{
			return Toolbar({
				right: () => new ToolbarButton({
					ref: (ref) => this.createButtonRef = ref,
					text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_CREATE'),
					loadingText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_COMMENT_CREATE_PROGRESS'),
					disabled: !this.isSaveAllowed(),
					onClick: () => this.save(),
				}),
				center: () => View(
					{
						style: { flexDirection: 'row' },
					},
					ToolbarIcon({
						svg: '<svg width="17" height="19" viewBox="0 0 17 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.1313 7.48636C16.2696 7.62472 16.2696 7.84904 16.1313 7.98739L15.2028 8.91584C15.0645 9.05419 14.8401 9.05419 14.7018 8.91584L9.2344 3.44845C7.66198 1.87603 5.08893 1.87603 3.5165 3.44845C1.94408 5.02087 1.94408 7.59393 3.5165 9.16635L10.2236 15.8735C11.2097 16.8596 12.8112 16.8596 13.7973 15.8735C14.7834 14.8874 14.7834 13.2859 13.7973 12.2998L7.80493 6.3074C7.40456 5.90703 6.77582 5.90703 6.37545 6.3074C5.97509 6.70777 5.97509 7.33651 6.37545 7.73688L11.1281 12.4895C11.2665 12.6279 11.2665 12.8522 11.1281 12.9906L10.1997 13.919C10.0613 14.0574 9.83698 14.0574 9.69863 13.919L4.94598 9.16635C3.7594 7.97977 3.7594 6.0645 4.94598 4.87793C6.13256 3.69135 8.04783 3.69135 9.2344 4.87793L15.2268 10.8703C16.9991 12.6426 16.9991 15.5307 15.2268 17.303C13.4545 19.0752 10.5664 19.0752 8.79416 17.303L2.08703 10.5958C-0.271604 8.23719 -0.271604 4.37761 2.08703 2.01898C4.44566 -0.339659 8.30525 -0.339659 10.6639 2.01898L16.1313 7.48636Z" fill="#D5D7DB"/></svg>',
						width: 17,
						height: 19,
						onClick: () => this.fileFieldRef && this.fileFieldRef.openFilePicker(),
					}),
					ToolbarIcon({
						svg: '<svg width="14" height="20" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.8209 8.6302C13.4064 8.61776 13.8912 9.08233 13.9036 9.66785C13.9607 12.3582 11.9789 15.5172 8.13976 16.039L8.13894 17.4335L8.59835 17.4339C9.12608 17.4339 9.55389 17.8617 9.55389 18.3894C9.55389 18.9171 9.12608 19.3449 8.59835 19.3449H5.40111C4.87338 19.3449 4.44557 18.9171 4.44557 18.3894C4.44557 17.8617 4.87338 17.4339 5.40111 17.4339L5.85906 17.4335L5.85898 16.0381C2.02549 15.5205 0.0658277 12.4368 0.0956103 9.67891C0.101934 9.0933 0.581793 8.62369 1.1674 8.62989C1.71398 8.63592 2.15949 9.05432 2.21132 9.58621L2.2163 9.70181C2.21036 10.2519 2.51762 11.3083 3.08472 12.1298C3.8989 13.3093 5.15055 13.9929 7.01102 13.9929C8.86116 13.9929 10.1084 13.2962 10.924 12.0939C11.4448 11.3263 11.7482 10.351 11.7806 9.82579L11.7833 9.71288C11.7708 9.12737 12.2354 8.64263 12.8209 8.6302ZM6.99973 0.29834C8.59695 0.29834 9.89175 1.59314 9.89175 3.19036V9.28705C9.89175 10.8843 8.59695 12.1791 6.99973 12.1791C5.40252 12.1791 4.10772 10.8843 4.10772 9.28705V3.19036C4.10772 1.59314 5.40252 0.29834 6.99973 0.29834Z" fill="#D5D7DB"/></svg>',
						width: 14,
						height: 20,
						onClick: () => {
							InAppNotifier.showNotification({
								title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_VOICE_NOTES_TITLE'),
								message: `${Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_VOICE_NOTES_BODY')} 😉`,
								time: 3,
								backgroundColor: '#004f69',
								blur: true,
								code: 'voice_hint',
							});
						},
					}),
					ToolbarIcon({
						svg: '<svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.16738 19C6.45494 19 4.24893 18.1952 2.54936 16.5856C0.849785 14.9586 0 12.7104 0 9.84116C0 6.98941 0.909871 4.63628 2.72961 2.78177C4.54936 0.927256 6.78112 0 9.42489 0C11.9828 0 14.0515 0.76105 15.6309 2.28315C17.2103 3.80525 18 5.67726 18 7.89917C18 9.75368 17.5193 11.3108 16.5579 12.5704C15.5966 13.8126 14.4549 14.4337 13.133 14.4337C12.6695 14.4337 12.2575 14.2937 11.897 14.0138C11.5365 13.7339 11.3219 13.3577 11.2532 12.8854C10.5665 13.9876 9.56223 14.5387 8.24034 14.5387C7.14163 14.5387 6.24893 14.1275 5.56223 13.3052C4.87554 12.483 4.53219 11.3982 4.53219 10.0511C4.53219 8.66897 4.96996 7.41805 5.84549 6.29834C6.7382 5.17864 7.85408 4.61878 9.19313 4.61878C10.3777 4.61878 11.2275 5.12615 11.7425 6.14088L11.9485 4.95994H14.2403L13.4163 9.39503C13.176 10.7072 13.0558 11.512 13.0558 11.8094C13.0558 12.2468 13.2017 12.4655 13.4936 12.4655C14.0773 12.4655 14.6094 12.0106 15.0901 11.1008C15.588 10.1911 15.8369 9.1326 15.8369 7.92541C15.8369 6.26335 15.2704 4.88996 14.1373 3.80525C13.0043 2.70304 11.4678 2.15193 9.5279 2.15193C7.43348 2.15193 5.71674 2.89549 4.37768 4.3826C3.03863 5.85221 2.3691 7.68048 2.3691 9.8674C2.3691 12.0718 2.97854 13.7864 4.19742 15.011C5.41631 16.2357 7.1073 16.8481 9.27039 16.8481C10.3863 16.8481 11.4421 16.7256 12.4378 16.4807L11.897 18.8163C11.1245 18.9388 10.2146 19 9.16738 19ZM8.62661 12.2818C9.36481 12.2818 9.96566 11.9494 10.4292 11.2845C10.9099 10.6022 11.1502 9.85865 11.1502 9.05387C11.1502 8.38904 10.9957 7.86418 10.6867 7.47928C10.3777 7.07689 9.9485 6.87569 9.39914 6.87569C8.66094 6.87569 8.0515 7.19061 7.57081 7.82044C7.09013 8.45027 6.84978 9.20258 6.84978 10.0773C6.84978 10.7422 7.00429 11.2758 7.3133 11.6782C7.63948 12.0806 8.07725 12.2818 8.62661 12.2818Z" fill="#BDC1C6"/></svg>',
						width: 18,
						height: 19,
						onClick: () => this.openUserSelector(),
					}),
				),
			});
		}

		openUserSelector()
		{
			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {},
				createOptions: {
					enableCreation: false,
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: (selectedUsers) => this.insertMention(selectedUsers),
				},
				widgetParams: {
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_MENTION_USER_TITLE'),
					backgroundColor: '#eef2f4',
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				},
			});

			return selector.show({}, this.layout);
		}

		insertMention(users)
		{
			if (isArray(users) && users.length > 0)
			{
				const { id, title } = users[0];
				const mention = `[URL=data://user=${id}][COLOR=#0B66C3][B]${title}[/B][/COLOR][/URL]`;

				const text = this.endsWithWhitespace(this.state.text)
					? `${this.state.text}${mention} `.trimStart()
					: `${this.state.text} ${mention} `.trimStart();

				this.setState({ text }, () => {
					this.setCursorPosition(text.length);
					this.focus();
				});
			}
		}

		endsWithWhitespace(str)
		{
			const trimmed = str.trimEnd();
			return trimmed !== str;
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		setCursorPosition(pos)
		{
			if (this.textInputRef)
			{
				this.textInputRef.setSelection(pos, pos);
			}
		}

		isSaveAllowed()
		{
			return this.state.text.length && !this.hasUploadingFiles();
		}

		refreshSaveButton()
		{
			if (!this.createButtonRef)
			{
				return;
			}

			if (this.isSaveAllowed())
			{
				this.createButtonRef.enable();
			}
			else
			{
				this.createButtonRef.disable();
			}
		}

		save()
		{
			return new Promise((resolve, reject) => {
				// @todo Replace with ajax call when backend will be ready
				setTimeout(() => {
					const users = [...this.state.text.matchAll(/\[URL=data:\/\/user=(\d+)]/g)]
						.map((match) => Number(match[1]));

					const data = {
						text: this.state.text.trim(),
						files: this.state.files.map((file) => file.token),
						users: [...new Set(users)],
					};
					console.log('sending', data);
					resolve();

					this.layout.close();
				}, 2000);
			});
		}
	}

	module.exports = { TimelineSchedulerCommentProvider };
});
