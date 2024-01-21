(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');
	AppTheme.extend('medalSelector', {
		Opacity: [1, 0.1],
	});

	this.NewPostComponent = class NewPostComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.styles = {
				menuCancelTextColor: AppTheme.colors.base4,
				inputTextColor: AppTheme.colors.base1,
				inputColoredTextColor: AppTheme.colors.bgContentPrimary,
				placeholderTextColor: AppTheme.colors.base4,
				placeholderColoredTextColor: AppTheme.colors.baseWhiteFixed,
			};

			const postData = BX.componentParameters.get('POST_DATA', {});
			const forAll = Utils.getForAllValue({
				postData,
				forAllAllowed: !BX.componentParameters.get('DESTINATION_TO_ALL_DENY', false),
				forAllDefault: BX.componentParameters.get('DESTINATION_TO_ALL_DEFAULT', true),
			});

			const recipientsCount = Utils.getRecipientsCountValue({
				recipients: (
					typeof postData.recipients === 'undefined'
						? { users: (forAll ? ['UA'] : []) }
						: postData.recipients
				),
			});
			const recipientsValue = Utils.getRecipientsValue({
				postData,
				forAllAllowed: !BX.componentParameters.get('DESTINATION_TO_ALL_DENY', false),
				forAllDefault: BX.componentParameters.get('DESTINATION_TO_ALL_DEFAULT', true),
			});
			const attachmentsValue = Utils.getAttachmentsValue({ postData });
			const backgroundImageCodeValue = Utils.getBackgroundImageCodeValue({ postData });
			const isImportantValue = Utils.getIsImportantValue({ postData });
			const importantUntilValue = (isImportantValue ? Utils.getImportantUntilValue({ postData }) : null);
			const medalValue = (isImportantValue ? null : Utils.getMedalValue({ postData }));
			const gratitudeEmployeesValue = (medalValue ? Utils.getGratitudeEmployeesValue({ postData }) : []);
			const voteValue = (!isImportantValue && !medalValue ? Utils.getVoteValue({ postData }) : { questions: [] });
			const backgroundImageValue = isImportantValue || medalValue
				? null
				: this.getBackgroundImageFromCode(backgroundImageCodeValue);

			this.postTitleValue = (
				typeof postData.post !== 'undefined'
				&& typeof postData.post.PostTitle !== 'undefined'
					? postData.post.PostTitle
					: ''
			);

			this.postText = (
				typeof postData.post !== 'undefined'
				&& typeof postData.post.PostDetailText !== 'undefined'
					? postData.post.PostDetailText
					: ''
			);

			if (typeof votePanelRef !== 'undefined')
			{
				// deep clone
				votePanelRef.questions = (voteValue.questions ? JSON.parse(JSON.stringify(voteValue.questions)) : []);
				if (voteValue.questions)
				{
					voteValue.questions.forEach((value, index) => {
						voteValue.questions[index].answers.push({});
						votePanelRef.addAnswer(index);
					});
				}
			}

			this.state = {
				actionSheetShown: (this.postText.length <= 0),
				titleShown: (this.postTitleValue.length > 0),
				mentionHintShown: (this.postText.length <= 0),
				isEdit: false,
				attachments: attachmentsValue,
				backgroundImage: (backgroundImageValue === undefined ? null : backgroundImageValue),
				backgroundImageCode: backgroundImageCodeValue,
				isImportant: isImportantValue,
				importantUntil: importantUntilValue,
				voteData: voteValue,
				medal: medalValue,
				gratitudeEmployees: gratitudeEmployeesValue,
				forAll,
				recipientsCount,
				recipientsStringActionsheet: renderDestinationList({
					recipients: recipientsValue,
				}),
				recipientsStringKeyboard: renderDestinationList({
					recipients: recipientsValue,
					useContainer: true,
				}),
				dummy: true,
			};

			this.config = {
				coloredMessageTextLimit: 141,
				coloredMessageTextLinesLimit: 6,
				marginBottom: 63,
				attachmentPanelHeight: 75,
				postMessageMarginTop: 14,
				postMessageMarginBottom: 10,
				postMessageLineHeight: 21,
				postTitleMargin: 10,
				backdropHeight: 630,
				maxKeyboardRecipientsHeight: 45,
				// maxActionSheetRecipientsHeight: 60,
			};

			this.actionSheet = null;
			this.attachmentSlider = null;

			this.actionSheetWidget = null;
			this.attachmentWidget = null;
			this.backgroundWidget = null;
			this.medalWidget = null;

			this.postTextCursorPosition = 0;
			this.recipients = recipientsValue;
			this.hiddenRecipients = Utils.getHiddenRecipientsValue({ postData });
			this.attachments = attachmentsValue;

			this.keyboardPanelRecipientsCountLimit = 3;
			// this.actionSheetRecipientsCountLimit = 3;

			this.pageId = postData.pageId || false;
			this.postId = (parseInt(postData.postId, 10) > 0 ? parseInt(postData.postId, 10) : 0);
			this.groupId = (parseInt(postData.groupId, 10) > 0 ? parseInt(postData.groupId, 10) : 0);

			this.postTitleRef = null;
			this.postMessageRef = null;
			this.actionSheetRef = null;

			this.postMessageFocused = false;
			this.postMessageScrollCheckNeeded = false;

			this.height = {
				root: null,
				rootScroll: null,
				scrollContent: null,
				postTitle: null,
				postMessage: null,
				importantPanel: null,
				gratitudePanel: null,
				votePanel: null,
				attachmentPanel: null,
			};

			this.postMessageScrollY = 0;
			this.postMessageCursorY = 0;

			this.rootRef = {
				element: null,
				heightWithKeyboard: null,
			};
			this.rootScrollRef = {
				element: null,
			};
		}

		isScrollContentReachedScrollHeight()
		{
			return this.height.scrollContent >= this.height.rootScroll - this.config.marginBottom;
		}

		onScrollViewClick()
		{
			if (this.isScrollContentReachedScrollHeight())
			{
				return;
			}

			this.hideActionSheet(() => {
				this.postMessageRef.focus();
			});
		}

		onPostMessageChange({ height })
		{
			this.scrollPostMessageToCursor();

			this.height.postMessage = height;
		}

		onPostMessageInput({ char, selection })
		{
			if (char === '@')
			{
				this.onClickMentionMenuItem({
					keyboard: true,
				});
			}

			if (this.postMessageScrollCheckNeeded)
			{
				this.scrollPostMessageToCursor();
			}

			this.postMessageScrollCheckNeeded = false;
		}

		scrollPostMessageToCursor()
		{
			const {
				attachments,
			} = this.state;

			let postTitleHeight = this.height.postTitle;
			if (postTitleHeight > 0)
			{
				postTitleHeight += this.config.postTitleMargin * 2;
			}

			const cursorPosition = (this.postMessageCursorY + this.config.postMessageLineHeight - this.postMessageScrollY);
			const bottomEdge = (
				this.height.rootScroll
				- this.config.postMessageMarginTop
				- this.config.postMessageMarginBottom
				- postTitleHeight
				- (attachments.length > 0 ? this.config.attachmentPanelHeight : 0)
			);

			if (cursorPosition > bottomEdge)
			{
				const scrollValue = (
					this.postMessageCursorY
					+ this.config.postMessageMarginTop
					+ this.config.postMessageLineHeight
					+ postTitleHeight
					- this.height.rootScroll
				);
				this.rootScrollRef.element.scrollTo({ y: scrollValue }, true);
			}
		}

		scrollPostMessageToEnd({ type })
		{
			const {
				attachments,
			} = this.state;

			let actionBlockHeight = 0;

			switch (type)
			{
				case 'important':
					actionBlockHeight = parseInt(this.height.importantPanel, 10);
					break;

				case 'medal':
					actionBlockHeight = this.height.gratitudePanel;
					break;

				case 'vote':
					actionBlockHeight = this.height.votePanel;
					break;

				default: // No default
			}

			const contentHeight = this.height.postMessage + actionBlockHeight;
			const bottomEdge = (
				this.height.rootScroll
				- this.config.postMessageMarginTop
				- this.config.postMessageMarginBottom
				- this.height.postTitle
				- (attachments.length > 0 ? this.config.attachmentPanelHeight : 0)
			);

			if (
				this.rootScrollRef.element
				&& contentHeight >= bottomEdge
			)
			{
				this.rootScrollRef.element.scrollToEnd(true);
			}
		}

		onCursorPositionChange({ y })
		{
			this.postMessageCursorY = y;
		}

		showActionSheet()
		{
			this.setState({ actionSheetShown: true });
		}

		hideActionSheet(callback)
		{
			this.setState({ actionSheetShown: false }, () => {
				if (typeof callback === 'function')
				{
					callback();
				}
			});
		}

		getStyle(key)
		{
			return this.styles[key];
		}

		onClose()
		{
			const {
				voteData,
				medal,
				isImportant,
				backgroundImage,
			} = this.state;

			if (
				this.postTitleValue.length <= 0
				&& this.postText.length <= 0
				&& this.attachments.length <= 0
				&& (
					!Array.isArray(voteData.questions)
					|| voteData.questions.length <= 0
				)
				&& !medal
				&& !isImportant
				&& !backgroundImage
			)
			{
				postFormLayoutWidget.close();

				return;
			}

			navigator.notification.confirm(
				BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CLOSE_CONFIRM_MESSAGE'),
				(btnNum) => {
					if (btnNum === 1)
					{
						BX.postWebEvent('Livefeed.Database::clear', {
							groupId: this.groupId,
						});

						postFormLayoutWidget.close();
					}
				},
				BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CLOSE_CONFIRM_TITLE'),
				[
					BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CLOSE_CONFIRM_BUTTON_OK'),
					BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CLOSE_CONFIRM_BUTTON_CANCEL'),
				],
			);
		}

		onPublish()
		{
			let postTitle = this.postTitleValue;
			const postText = this.postText;
			const recipients = this.recipients;
			const hiddenRecipients = this.hiddenRecipients;
			const attachments = this.attachments;

			const {
				isImportant,
				importantUntil,
				medal,
				gratitudeEmployees,
				voteData,
				backgroundImageCode,
				titleShown,
			} = this.state;

			const ufCode = BX.componentParameters.get('POST_FILE_UF_CODE', 'UF_BLOG_POST_FILE');
			const postData = {
				contentType: 'post',
				ufCode,
			};

			if (!titleShown)
			{
				postTitle = '';
			}

			this.processData(postData)
				.then((postData) => this.processDestinations(postData, recipients, hiddenRecipients))
				.then((postData) => this.processFiles(postData, attachments, { ufCode }))
				.then((postData) => this.processText(postData, postText, postTitle))
				.then((postData) => this.processImportant(postData, isImportant, importantUntil))
				.then((postData) => this.processGratitude(postData, medal, gratitudeEmployees))
				.then((postData) => this.processBackground(postData, backgroundImageCode))
				.then((postData) => this.processVote(postData, voteData))
				.then((postData) => {
					BX.postComponentEvent(
						'Livefeed.PublicationQueue::setItem',
						[
							{
								key: postData.postVirtualId,
								item: postData,
								pageId: this.pageId,
								groupId: this.groupId,
							},
						],
						'background',
					);

					postFormLayoutWidget.close();
				})
				.catch((error) => {
					console.error(error.message);

					if (error.code === 'DESTINATIONS_EMPTY')
					{
						this.onClickDestinationMenuItem({
							title: BX.message('RECIPIENT_SELECT_TITLE'),
							callback: this.onPublish.bind(this),
						});
					}
					else
					{
						Utils.showError({
							errorText: error.message,
							callback: null,
						});
					}
				});
		}

		processText(postData, postText, postTitle)
		{
			const promise = new Promise((resolve, reject) => {
				if (postText.length <= 0)
				{
					reject({
						message: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_TEXT_EMPTY'),
						code: 'TEXT_EMPTY',
					});
				}

				postData.POST_MESSAGE = postText;

				if (postTitle.length > 0)
				{
					postData.POST_TITLE = postTitle;
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processData(postData)
		{
			const promise = new Promise((resolve, reject) => {
				if (this.postId > 0)
				{
					postData.post_id = this.postId;
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processDestinations(postData, recipients, hiddenRecipients)
		{
			const promise = new Promise((resolve, reject) => {
				let destinationList = [];
				const destinationData = {};
				const entityPrefixes = {
					users: 'U',
					groups: 'SG',
					departments: 'DR',
				};

				Object.keys(recipients)
					.filter((key) => Array.isArray(recipients[key]) && recipients[key].length)
					.forEach((key) => {
						const prefix = entityPrefixes[key];
						recipients[key].forEach((item) => {
							destinationList.push((item.id === 'UA' ? 'UA' : prefix + item.id));
							destinationData[prefix + item.id] = { title: item.title };
						});
					});

				if (Array.isArray(hiddenRecipients))
				{
					destinationList = [...destinationList, ...hiddenRecipients];
				}

				if (destinationList.length <= 0)
				{
					reject({
						message: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_DESTINATIONS_EMPTY'),
						code: 'DESTINATIONS_EMPTY',
					});
				}

				postData.DEST = destinationList;
				postData.DEST_DATA = destinationData;

				resolve(postData);
			});

			promise.catch((error) => console.error(error));

			return promise;
		}

		processImportant(postData, isImportant, importantUntil)
		{
			const promise = new Promise((resolve, reject) => {
				postData.IMPORTANT = 'N';
				postData.IMPORTANT_DATE_END = false;

				if (isImportant)
				{
					postData.IMPORTANT = 'Y';

					if (importantUntil)
					{
						postData.IMPORTANT_DATE_END = new Date(importantUntil).toISOString();
					}
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processGratitude(postData, medal, gratitudeEmployees)
		{
			const promise = new Promise((resolve, reject) => {
				postData.GRATITUDE_MEDAL = '';
				postData.GRATITUDE_EMPLOYEES_DATA = {};

				if (
					medal
					&& (
						!Array.isArray(gratitudeEmployees)
						|| gratitudeEmployees.length <= 0
					)
				)
				{
					reject({
						message: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_GRATITUDE_EMPLOYEE_EMPTY'),
						code: 'GRATITUDE_EMPLOYEE_EMPTY',
					});
				}

				if (
					medal
					&& gratitudeEmployees
					&& Array.isArray(gratitudeEmployees)
				)
				{
					postData.GRATITUDE_MEDAL = medal;
					postData.GRATITUDE_EMPLOYEES = gratitudeEmployees.map((item) => {
						return parseInt(item.id, 10);
					});

					postData.GRATITUDE_EMPLOYEES_DATA = {};
					gratitudeEmployees.forEach((item) => {
						postData.GRATITUDE_EMPLOYEES_DATA[item.id] = item;
					});
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processBackground(postData, backgroundImageCode)
		{
			const promise = new Promise((resolve, reject) => {
				postData.BACKGROUND_CODE = '';

				if (
					backgroundImageCode
					&& this.checkColoredText(postData.POST_MESSAGE)
				)
				{
					postData.BACKGROUND_CODE = backgroundImageCode;
					postData.POST_TITLE = '';
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processVote(postData, voteData)
		{
			if (!voteData)
			{
				voteData = {
					questions: [],
				};
			}

			const promise = new Promise((resolve, reject) => {
				postData.UF_BLOG_POST_VOTE = 0;

				if (
					Array.isArray(voteData.questions)
					&& voteData.questions.length > 0
				)
				{
					const voteId = 'n0';
					const dataKey = `UF_BLOG_POST_VOTE_${voteId}_DATA`;

					postData.UF_BLOG_POST_VOTE = voteId;
					postData[`UF_BLOG_POST_VOTE_${voteId}`] = 0;
					postData[dataKey] = {
						ID: 0,
						URL: '',
						QUESTIONS: [],
						ANONYMITY: true,
						OPTIONS: [
							true,
						],
					};

					voteData.questions.forEach((question, questionIndex) => {
						const questionData = {
							QUESTION: question.value,
							QUESTION_TYPE: 'text',
							FIELD_TYPE: question.allowMultiSelect ? '1' : '0',
							ANSWERS: [],
							C_SORT: (questionIndex + 1) * 10,
						};
						if (
							Array.isArray(question.answers)
							&& question.answers.length > 0
						)
						{
							question.answers.forEach((answer, answerIndex) => {
								questionData.ANSWERS.push({
									MESSAGE: answer.value,
									MESSAGE_TYPE: 'text',
									FIELD_TYPE: 0,
									C_SORT: (answerIndex + 1) * 10,
								});
							});
						}

						postData[dataKey].QUESTIONS.push(questionData);
					});
				}

				resolve(postData);
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		processFiles(postData, attachedFiles, params)
		{
			const promise = new Promise((resolve, reject) => {
				const ufCode = params.ufCode;

				postData.postVirtualId = parseInt(Math.random() * 100_000);
				postData.tasksList = [];

				if (
					typeof attachedFiles !== 'undefined'
					&& attachedFiles.length > 0
				)
				{
					let readedFileCount = 0;
					const fileTotal = attachedFiles.length;
					const fileCountIncrement = (() => {
						readedFileCount++;
						if (readedFileCount >= fileTotal)
						{
							this.postProgressingFiles(postData, attachedFiles, params);
							resolve(postData);
						}
					});

					const uploadTasks = [];

					attachedFiles.forEach((fileData) => {
						const isFileFromBitrix24Disk = (
							typeof fileData.VALUE !== 'undefined' // Android
							|| (
								typeof fileData.id !== 'undefined'
								&& parseInt(fileData.id) > 0
							) // disk object
							|| (
								typeof fileData.dataAttributes !== 'undefined'
								&& typeof fileData.dataAttributes.VALUE !== 'undefined'
							) // iOS and modern Android too
							|| (
								typeof fileData.ufCode !== 'undefined'
								&& fileData.ufCode === ufCode
							)
						);

						const isNewFileOnDevice = (
							typeof fileData.url === 'undefined'
							|| typeof fileData.id !== 'number'
						);

						if (
							fileData.url
							&& isNewFileOnDevice
							&& !isFileFromBitrix24Disk
						)
						{
							const taskId = `postTask_${parseInt(Math.random() * 100_000)}`;

							let filename = fileData.name;
							const extension = Utils.getExtension({
								uri: fileData.name,
							});
							if (extension === 'heic')
							{
								filename = `${filename.slice(0, Math.max(0, filename.length - (extension.length)))}jpg`;
							}

							uploadTasks.push({
								taskId,
								type: fileData.type,
								name: filename,
								mimeType: Utils.getFileMimeType(fileData.type),
								folderId: parseInt(BX.componentParameters.get('USER_FOLDER_FOR_SAVED_FILES', 0)),
								params: {
									postVirtualId: postData.postVirtualId,
								},
								url: fileData.url,
								previewUrl: (fileData.previewUrl ? fileData.previewUrl : null),
								resize: Utils.getResizeOptions(fileData.type),
							});
							postData.tasksList.push(taskId);
						}
						else
						{
							if (isFileFromBitrix24Disk)
							{
								if (typeof postData[ufCode] === 'undefined')
								{
									postData[ufCode] = [];
								}

								if (typeof fileData.VALUE !== 'undefined')
								{
									postData[ufCode].push(fileData.VALUE);
								}
								else if (parseInt(fileData.id) > 0)
								{
									postData[ufCode].push(parseInt(fileData.id));
								}
								else if (
									typeof fileData.dataAttributes !== 'undefined'
									&& typeof fileData.dataAttributes.VALUE !== 'undefined'
									&& typeof fileData.dataAttributes.ID !== 'undefined')
								{
									const matches = /^n(\d+)$/.exec(fileData.dataAttributes.VALUE);
									if (matches)
									{
										if (parseInt(matches[1]) === parseInt(fileData.dataAttributes.ID))
										{
											postData[ufCode].push(fileData.dataAttributes.VALUE);
										}
										else
										{
											postData[ufCode].push(fileData.dataAttributes.ID);
										}
									}
								}
							}

							fileCountIncrement();
						}
					});

					if (uploadTasks.length > 0)
					{
						BX.postComponentEvent('onFileUploadTaskReceived', [
							{
								files: uploadTasks,
							},
						], 'background');
					}
					resolve(postData);
				}
				else
				{
					postData[ufCode] = ['empty'];
					resolve(postData);
				}
			});

			promise.catch((error) => {
				console.error(error);
			});

			return promise;
		}

		postProgressingFiles(postData, attachedFiles, params)
		{
			const ufCode = params.ufCode;

			if (typeof postData[ufCode] === 'undefined')
			{
				postData[ufCode] = [];
			}

			if (typeof attachedFiles === 'undefined')
			{
				attachedFiles = [];
			}

			if (postData[ufCode].length <= 0)
			{
				postData[ufCode].push('empty');
			}
		}

		showKeyboardPanel(options = {}, callback)
		{
			const newState = { actionSheetShown: false, ...options };

			let differenceFound = false;

			for (const key in this.state)
			{
				if (
					!this.state.hasOwnProperty(key)
					|| !newState.hasOwnProperty(key)
					|| this.state[key] === newState[key]
				)
				{
					continue;
				}

				differenceFound = true;
				break;
			}

			if (options.isEdit)
			{
				this.setRootHeightWithKeyboard();
			}

			if (differenceFound)
			{
				this.setState(newState, () => {
					if (typeof callback === 'function')
					{
						callback();
					}
				});
			}
		}

		hideKeyboardPanel()
		{
			this.setState({
				isEdit: false,
				actionSheetShown: true,
			});
			Keyboard.dismiss();
		}

		setRootHeightWithKeyboard()
		{
			if (!this.rootRef.heightWithKeyboard)
			{
				setTimeout(() => {
					this.rootRef.heightWithKeyboard = this.height.root;
				}, 100);
			}
		}

		onClickDestinationMenuItem(params)
		{
			const recipients = this.recipients;
			const showAll = !BX.componentParameters.get('DESTINATION_TO_ALL_DENY', false);
			const entities = ['users', 'groups', 'departments'];
			if (showAll === true)
			{
				entities.push('meta-user');
			}
			(new FormEntitySelector('BLOG_POST', entities))
				.setEntitiesOptions({
					'meta-user': {
						options: {
							'all-users': {
								title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_PANEL_ITEM_SELECTOR_VALUE_ALL'),
								allowView: true,
							},
						},
						searchable: true,
						dynamicLoad: true,
						dynamicSearch: false,
					},
					project: {
						options: {
							features: {
								blog: ['premoderate_post', 'moderate_post', 'write_post', 'full_post'],
							},
						},
						searchable: true,
						dynamicLoad: true,
						dynamicSearch: true,
					},
				})
				.open({
					selected: Utils.formatSelectedRecipients(recipients),
					title: (params && params.title ? params.title : null),
				})
				.then((recipients) => {
					this.onSelectedRecipient(recipients);
					if (params && params.callback)
					{
						params.callback();
					}
				})
				.catch((e) => console.error(e));
		}

		onSelectedRecipient(recipients)
		{
			if (typeof recipients === 'undefined')
			{
				return;
			}

			this.keyboardPanelRecipientsCountLimit = 3;

			recipients = Utils.formatSelectedRecipients(recipients);

			const newState = {
				recipientsStringActionsheet: renderDestinationList({
					recipients,
				}),
				recipientsStringKeyboard: renderDestinationList({
					recipients,
					useContainer: true,
				}),

				forAll: (
					recipients.users
					&& (typeof recipients.users.find((item) => (item.id == 'A')) !== 'undefined')
				),
				recipientsCount: Utils.getRecipientsCountValue({ recipients }),
			};

			this.setState(newState);
			if (this.actionSheet)
			{
				this.actionSheet.setState(newState);
			}

			this.recipients = recipients;
		}

		onClickMentionMenuItem({ keyboard })
		{
			const params = {
				allowMultipleSelection: false,
				singleChoose: true,
				title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_DIALOG_MENTION_TITLE'),
			};

			(new FormEntitySelector('MENTION', ['user', 'project', 'department']))
				.setEntitiesOptions({
					user: {
						options: {
							intranetUsersOnly: true,
							emailUsers: true,
						},
						searchable: true,
						dynamicLoad: true,
						dynamicSearch: true,
					},
				})
				.open(params)
				.then((recipients) => {
					const regexList = [
						{
							full: /\[user=(\d+)]|\[\/user]/gi,
							end: '[/USER]',
						},
						{
							full: /\[project=(\d+)]|\[\/project]/gi,
							end: '[/PROJECT]',
						},
						{
							full: /\[department=(\d+)]|\[\/department]/gi,
							end: '[/DEPARTMENT]',
						},
						{
							full: /\[b]|\[\/b]/gi,
							end: '[/B]',
						},
						{
							full: /\[i]|\[\/i]/gi,
							end: '[/I]',
						},
						{
							full: /\[s]|\[\/s]/gi,
							end: '[/S]',
						},
						{
							full: /\[font=(\d+)pt]|\[\/font]/gi,
							end: '[/FONT]',
						},
					];

					let matches = null;
					let realPosition = this.postTextCursorPosition;

					const indices = [];

					for (const key in recipients)
					{
						if (!recipients.hasOwnProperty(key))
						{
							continue;
						}

						if (
							!Array.isArray(recipients[key])
							|| recipients[key].length === 0
						)
						{
							continue;
						}

						regexList.forEach((regex) => {
							while ((matches = regex.full.exec(this.postText)))
							{
								indices.push({
									type: (matches[0] === regex.end ? 'end' : 'start'),
									index: matches.index,
									length: matches[0].length,
								});
							}
						});

						indices.sort((a, b) => {
							if (a.index === b.index)
							{
								return 0;
							}

							return (a.index < b.index) ? -1 : 1;
						});

						indices.forEach((item) => {
							if (
								(item.type === 'end' && item.index <= realPosition)
								|| (item.type === 'start' && item.index < realPosition)
							)
							{
								realPosition += item.length;
							}
						});

						const before = this.postText.slice(
							0,
							Math.max(0, (keyboard ? realPosition - 1 : realPosition)),
						);
						const after = this.postText.substr(realPosition, this.postText.length);
						const entityData = recipients[key].shift();

						let entityBB = entityData.title;
						switch (key)
						{
							case 'users':
								entityBB = `[USER=${entityData.id}]${entityData.title}[/USER]`;
								break;
							case 'groups':
								entityBB = `[PROJECT=${entityData.id}]${entityData.title}[/PROJECT]`;
								break;
							case 'departments':
								entityBB = `[DEPARTMENT=${entityData.id}]${entityData.title}[/DEPARTMENT]`;
								break;
						}

						this.postText = `${before}${(before.length > 0 ? ' ' : '')}${entityBB} ${after}`;
						this.postTextCursorPosition += (keyboard ? -1 : 0) + `${(before.length > 0 ? ' ' : '')}${entityData.title} `.length;

						this.setState({ dummy: true }, () => {
							setTimeout(() => {
								this.focusPostMessage();
							}, 1);
						});
					}
				})
				.catch((error) => {
					console.error(error);
				});
		}

		onClickAttachmentMenuItem()
		{
			FilePicker.show({
				callback: this.onSelectedFilePicker.bind(this),
				moduleWebdavInstalled: BX.componentParameters.get('MODULE_WEBDAV_INSTALLED', 'N') == 'Y',
				moduleDiskInstalled: BX.componentParameters.get('MODULE_DISK_INSTALLED', 'N') == 'Y',
				fileAttachPath: BX.componentParameters.get('FILE_ATTACH_PATH', ''),
			});
		}

		onOpenAttachmentList()
		{
			const {
				attachments,
			} = this.state;

			PageManager.openWidget(
				'layout',
				{
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ATTACHMENTS_DIALOG_TITLE').replace(
						'#NUM#',
						attachments.length,
					),
					useLargeTitleMode: true,
					modal: false,
					backdrop: {
						mediumPositionPercent: 75,
					},
					onReady: (layoutWidget) => {
						this.attachmentWidget = layoutWidget;
						this.attachmentSlider = new AttachmentComponent({
							attachments,
							onDeleteAttachmentItem: (index) => this.onDeleteAttachmentItem(index),
							postFormData: BX.componentParameters.get('POST_FORM_DATA', {}),
							serverName: BX.componentParameters.get('SERVER_NAME', ''),
						});
						layoutWidget.showComponent(this.attachmentSlider);
					},
					onError: (error) => reject(error),
				},
			);
		}

		onSelectedFilePicker(filesMetaArray)
		{
			const {
				attachments,
			} = this.state;

			const attachmentsValue = [
				...attachments,
				...filesMetaArray,
			];

			const state = {
				attachments: attachmentsValue,
			};

			this.attachments = attachmentsValue;

			this.setState(state);
			if (this.actionSheet)
			{
				this.actionSheet.setState(state);
			}
		}

		onDeleteAttachmentItem(itemIndex)
		{
			let { attachments } = this.state;

			attachments = attachments.filter((item, currentIndex) => {
				return currentIndex !== itemIndex;
			});

			this.attachments = attachments;

			const newState = {
				attachments,
			};

			this.setState(newState);
			if (this.actionSheet)
			{
				this.actionSheet.setState(newState);
			}

			if (this.attachmentSlider)
			{
				this.attachmentSlider.setState(newState);
			}

			if (this.attachmentWidget)
			{
				this.attachmentWidget.setTitle({
					text: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_ATTACHMENTS_DIALOG_TITLE').replace(
						'#NUM#',
						attachments.length,
					),
					largeMode: true,
				});
			}
		}

		processChangeMessageType(newStateData, params)
		{
			const {
				voteData,
				medal,
				isImportant,
				backgroundImage,
			} = this.state;

			const action = (state, type, callback) => {
				state.actionSheetShown = false;
				this.setState(state, () => {
					if (typeof callback === 'function')
					{
						callback();
					}

					setTimeout(() => {
						if (
							type === 'medal'
							|| type === 'important'
							|| type === 'vote'
						)
						{
							this.scrollPostMessageToEnd({
								type,
							});
						}
						else
						{
							this.focusPostMessage();
						}
					}, 100);
				});
			};

			newStateData.backgroundImage = (newStateData.backgroundImageCode ? this.getBackgroundImageFromCode(
				newStateData.backgroundImageCode,
			) : null);
			const newState = newStateData;

			let nonEmptyVote = Array.isArray(voteData.questions);

			if (nonEmptyVote)
			{
				const questions = voteData.questions.filter((question) => {
					if (question.value.length > 0)
					{
						return true;
					}

					const answers = question.answers.filter((answer) => {
						if (answer.value.length > 0)
						{
							return true;
						}
					});

					return (answers.length > 0);
				});
				nonEmptyVote = (questions.length > 0);
			}

			if (
				!nonEmptyVote
				&& typeof votePanelRef !== 'undefined'
			)
			{
				votePanelRef.clearQuestions();
			}

			let type = null;
			if (newStateData.medal)
			{
				type = 'medal';
			}
			else if (
				Array.isArray(newStateData.voteData.questions)
				&& newStateData.voteData.questions.length > 0
			)
			{
				type = 'vote';
			}
			else if (newStateData.isImportant)
			{
				type = 'important';

				const dates = Utils.getImportantDatePeriods();
				newStateData.importantUntil = dates.oneWeek;
			}
			else if (newStateData.backgroundImageCode)
			{
				type = 'colored';
			}

			if (
				nonEmptyVote
				|| medal != null
				|| isImportant
				|| backgroundImage != null
			)
			{
				const title = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_CONFIRM_TITLE');
				let message = '';

				if (
					medal != null
					&& !newStateData.medal
				)
				{
					message = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_MEDAL_CONFIRM_MESSAGE');
				}
				else if (
					Array.isArray(voteData.questions)
					&& voteData.questions.length > 0
					&& (
						!Array.isArray(newStateData.voteData.questions)
						|| newStateData.voteData.questions.length <= 0
					)
				)
				{
					message = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_VOTE_CONFIRM_MESSAGE');
				}
				else if (
					isImportant
					&& !newStateData.isImportant
				)
				{
					message = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_IMPORTANT_CONFIRM_MESSAGE');
				}
				else if (
					backgroundImage != null
					&& !newStateData.backgroundImage
				)
				{
					message = BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_BACKGROUND_CONFIRM_MESSAGE');
				}

				navigator.notification.confirm(
					message,
					(btnNum) => {
						if (btnNum === 1)
						{
							action(newState, type, () => {
								if (
									params
									&& typeof params.onAccepted === 'function'
								)
								{
									params.onAccepted();
								}
							});
						}
						else if (
							params
							&& typeof params.onRejected === 'function'
						)
						{
							params.onRejected();
						}
					},
					title,
					[
						BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_CONFIRM_BUTTON_OK'),
						BX.message('MOBILE_EXT_LAYOUT_POSTFORM_CHANGETYPE_CONFIRM_BUTTON_CANCEL'),
					],
				);
			}
			else
			{
				action(newState, type, () => {
					if (
						params
						&& typeof params.onAccepted === 'function'
					)
					{
						params.onAccepted();
					}
				});
			}
		}

		onClickBackgroundMenuItem()
		{
			const {
				backgroundImage,
				medal,
				isImportant,
				voteData,
			} = this.state;

			if (
				medal
				|| isImportant
				|| (
					Array.isArray(voteData.questions)
					&& voteData.questions.length > 0
				)
				|| !this.checkColoredText(this.postText)
			)
			{
				return;
			}

			let backdropHeight = this.config.backdropHeight;
			const maxHackdropHeight = (this.height.root);
			if (maxHackdropHeight < backdropHeight)
			{
				backdropHeight = maxHackdropHeight;
			}

			PageManager.openWidget(
				'layout',
				{
					title: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_BACKGROUNDS_DIALOG_TITLE'),
					useLargeTitleMode: true,
					modal: false,
					backdrop: {
						mediumPositionHeight: backdropHeight,
					},
					onReady: (layoutWidget) => {
						this.backgroundWidget = layoutWidget;
						const backgroundSelector = new BackgroundSelectorComponent({
							backgroundImage,
							backgroundImagesData: BX.componentParameters.get('BACKGROUND_IMAGES_DATA', []),
							heightRatio: ((backdropHeight < this.config.backdropHeight ? (backdropHeight - 50) : backdropHeight) / this.config.backdropHeight),
							onSelectBackground: (val) => this.onSelectBackground(val),
						});
						layoutWidget.showComponent(backgroundSelector);
					},
					onError: (error) => reject(error),
				},
			);
		}

		onSelectBackground(backgroundImageCode)
		{
			const backgroundImage = this.getBackgroundImageFromCode(backgroundImageCode);

			if (backgroundImage === undefined)
			{
				return;
			}

			this.backgroundWidget.close(() => {
				this.setState({
					backgroundImageCode,
					backgroundImage,
				}, () => {
					setTimeout(() => {
						this.focusPostMessage();
					}, 1);
				});
			});
		}

		getBackgroundImageFromCode(backgroundImageCode)
		{
			const backgroundImagesData = BX.componentParameters.get('BACKGROUND_IMAGES_DATA', {});

			let result = null;

			if (
				typeof backgroundImagesData.images !== 'undefined'
				&& typeof backgroundImagesData.images[backgroundImageCode] !== 'undefined'
			)
			{
				result = currentDomain + backgroundImagesData.images[backgroundImageCode].originalUrl;
			}
			else if (backgroundImageCode !== null)
			{
				result = undefined;
			}

			return result;
		}

		onClickImportantMenuItem()
		{
			const {
				isImportant,
			} = this.state;

			this.processChangeMessageType({
				medal: null,
				isImportant: !isImportant,
				voteData: { questions: [] },
				backgroundImageCode: null,
			});
		}

		onSetImportant(value)
		{
			this.setState({ isImportant: Boolean(value) });
		}

		onSetImportantUntil(value)
		{
			this.setState({ importantUntil: value });
		}

		onClickGratitudeMenuItem()
		{
			const {
				medal,
			} = this.state;

			this.processChangeMessageType({
				medal: (medal ? null : 'cup'),
				isImportant: false,
				voteData: { questions: [] },
				backgroundImageCode: null,
			});
		}

		onClickShowHideTitleItem(params)
		{
			if (typeof params.show !== 'boolean')
			{
				return;
			}

			const coloredMessage = this.getColoredMessageStatus();
			if (coloredMessage)
			{
				return;
			}

			this.setState({
				titleShown: params.show,
			}, () => {
				if (params.show)
				{
					if (this.postTitleRef)
					{
						this.postTitleRef.focus();
					}

					if (this.rootScrollRef.element)
					{
						this.rootScrollRef.element.scrollToBegin(true);
					}
				}
			});
		}

		onSetGratitudeEmployee(value)
		{
			this.setState({
				gratitudeEmployees: value,
			});
		}

		onSetGratitudeMedal(value)
		{
			const newState = {
				medal: value,
			};

			if (value)
			{
				newState.isImportant = false;
				this.medalWidget.close();
			}
			this.setState(newState, () => {
				this.scrollPostMessageToEnd({
					type: 'medal',
				});
			});
		}

		onSetGratitudeMedalWidget(layoutWidget)
		{
			this.medalWidget = layoutWidget;
		}

		onClickVoteMenuItem()
		{
			const {
				voteData,
			} = this.state;

			if (
				(
					Array.isArray(voteData.questions)
					&& voteData.questions.length <= 0
				)
			)
			{
				const voteDataInstance = new VoteDataStateManager(voteData);
				voteDataInstance.addQuestion();
				voteDataInstance.addAnswer(0);

				this.processChangeMessageType(
					{
						medal: null,
						isImportant: false,
						voteData: voteDataInstance.get(),
						backgroundImageCode: null,
					},
					{
						onAccepted: () => {
							if (typeof votePanelRef !== 'undefined')
							{
								votePanelRef.addQuestion();
								votePanelRef.addAnswer(0);

								setTimeout(() => {
									votePanelRef.questions[0].element.focus();
								}, 200);
							}
						},
						onRejected: () => {
							voteDataInstance.deleteQuestion(0);
						},
					},
				);
			}
			else
			{
				this.addVoteQuestion({
					actionSheetShown: false,
				});
			}
		}

		addVoteQuestion(options = {})
		{
			const {
				voteData,
			} = this.state;

			if (!Array.isArray(voteData.questions))
			{
				voteData.questions = [];

				if (typeof votePanelRef !== 'undefined')
				{
					votePanelRef.clearQuestions();
				}
			}

			const newState = {
				medal: null,
				isImportant: false,
				voteData,
				backgroundImage: null,
				backgroundImageCode: null,
				...options,
			};

			const voteDataInstance = new VoteDataStateManager(voteData);
			voteDataInstance.addQuestion();
			voteDataInstance.addAnswer(newState.voteData.questions.length - 1);

			newState.voteData = voteDataInstance.get();

			if (typeof votePanelRef !== 'undefined')
			{
				votePanelRef.addQuestion();
				votePanelRef.addAnswer(newState.voteData.questions.length - 1);
			}
			this.setState(newState, () => {
				setTimeout(() => {
					votePanelRef.questions[newState.voteData.questions.length - 1].element.focus();
				}, 200);
			});
		}

		onSetVoteData(value)
		{
			this.setState({
				voteData: value,
			});
		}

		onSetVoteQuestionMultiple(voteData, questionIndex, value)
		{
			const voteDataInstance = new VoteDataStateManager(voteData);
			voteDataInstance.setQuestionMultiSelect(questionIndex, value);

			this.setState({
				voteData: voteDataInstance.get(),
			});
		}

		getFormBackgroundImage()
		{
			const {
				backgroundImage,
				isImportant,
				medal,
			} = this.state;

			const medalsList = BX.componentParameters.get('MEDALS_LIST', {});
			const backgroundCommon = BX.componentParameters.get('BACKGROUND_COMMON', {});
			if (medalsList[medal] !== undefined)
			{
				return {
					backgroundColor: AppTheme.colors.accentSoftBlue2,
					outer: {
						opacity: AppTheme.colors.medalSelectorOpacity,
						backgroundImageSvgUrl: currentDomain + medalsList[medal].backgroundUrl,
					},
					inner: {
						backgroundResizeMode: 'cover',
						backgroundImage: currentDomain + backgroundCommon.url,
					},
				};
			}

			if (isImportant)
			{
				const importantData = BX.componentParameters.get('IMPORTANT_DATA', {});

				if (
					importantData
					&& importantData.backgroundUrl
				)
				{
					return {
						outer: {
							backgroundImageSvgUrl: currentDomain + importantData.backgroundUrl,
						},
						inner: {
							backgroundResizeMode: 'cover',
							backgroundImage: currentDomain + backgroundCommon.url,
						},
					};
				}
			}

			return {
				outer: {
					backgroundImage: (
						backgroundImage
						&& this.checkColoredText(this.postText)
							? backgroundImage
							: null
					),
					backgroundResizeMode: 'cover',
				},
				inner: {},
			};
		}

		checkColoredText(text)
		{
			if (!text)
			{
				text = '';
			}

			return (
				Utils.sanitizeText(text).length < this.config.coloredMessageTextLimit
				&& text.split(/\r\n|\r|\n/).length < this.config.coloredMessageTextLinesLimit
			);
		}

		getColoredMessageStatus()
		{
			const {
				backgroundImage,
			} = this.state;

			return (
				backgroundImage !== null
				&& this.checkColoredText(this.postText)
			);
		}

		recalcBackgroundByTextLength({
			oldText,
			newText,
		})
		{
			const {
				backgroundImage,
			} = this.state;

			const newState = {};

			if (
				(
					this.checkColoredText(oldText)
					&& !this.checkColoredText(newText)
				)
				|| (
					!this.checkColoredText(oldText)
					&& this.checkColoredText(newText)
				)
			)
			{
				newState.backgroundImage = backgroundImage; // to recalc background rendering forcefully
			}

			if (Object.keys(newState).length > 0)
			{
				this.setState(newState, () => {
					setTimeout(() => {
						this.focusPostMessage();
					}, 1);
				}); // to recalc background rendering forcefully
			}
		}

		recalcMentionHint({
			text,
		})
		{
			const {
				mentionHintShown,
			} = this.state;

			const newState = {};

			if (
				mentionHintShown
				&& text.length > 0
			)
			{
				newState.mentionHintShown = false;
			}
			else if (
				!mentionHintShown
				&& text.length <= 0
			)
			{
				newState.mentionHintShown = true;
			}

			if (Object.keys(newState).length > 0)
			{
				this.setState(newState);
			}
		}

		render()
		{
			const {
				attachments,
				isImportant,
				importantUntil,
				voteData,
				medal,
				gratitudeEmployees,
				forAll,
				recipientsCount,
				backgroundImage,
				recipientsStringActionsheet,
				recipientsStringKeyboard,
				actionSheetShown,
				titleShown,
			} = this.state;

			const postTitle = this.postTitleValue;
			const postText = this.postText;

			const attachmentPanel = AttachmentPanel({
				attachments,
				onDeleteAttachmentItem: (itemId) => this.onDeleteAttachmentItem(itemId),
				serverName: BX.componentParameters.get('SERVER_NAME'),
				postFormData: BX.componentParameters.get('POST_FORM_DATA', {}),
			});

			const backgroundData = this.getFormBackgroundImage();
			const coloredMessage = this.getColoredMessageStatus();
			const postFormData = BX.componentParameters.get('POST_FORM_DATA', {});
			const medalsList = BX.componentParameters.get('MEDALS_LIST', {});
			const moduleVoteInstalled = BX.componentParameters.get('MODULE_VOTE_INSTALLED', 'N') === 'Y';
			const useImportant = BX.componentParameters.get('USE_IMPORTANT', 'Y') === 'Y';

			const backgroundAvailable = !(
				medal
				|| isImportant
				|| (
					Array.isArray(voteData.questions)
					&& voteData.questions.length > 0
				)
				|| !this.checkColoredText(this.postText)
			);

			return View(
				{
					ref: (ref) => {
						this.rootRef.element = ref;
					},
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					resizableByKeyboard: true,
					style: {
						backgroundColor: backgroundData.backgroundColor ?? AppTheme.colors.bgContentPrimary,
					},
					onLayout: this.processRootViewLayoutChange.bind(this),
				},
				View({
					style: {
						backgroundResizeMode: 'cover',
						position: 'absolute',
						height: '100%',
						width: '100%',
						...(coloredMessage ? {} : backgroundData.outer),
					},
				}),
				ScrollView(
					{
						ref: (ref) => {
							this.rootScrollRef.element = ref;
						},
						style: {
							flex: 1,
							marginBottom: this.config.marginBottom + (attachments.length > 0 ? this.config.attachmentPanelHeight : 0),
						},
						onLayout: ({ height }) => {
							this.height.rootScroll = height;
						},
						onScroll: ({ contentOffset: { y }, contentSize: { height } }) => {
							this.height.scrollContent = height;
							this.postMessageScrollY = y;
							this.postMessageScrollCheckNeeded = true;
						},
						onTouchesEnded: this.onScrollViewClick.bind(this),
						scrollEventThrottle: 0,
					},
					View(
						{
							style: {
								marginBottom: (this.postMessageFocused ? 0 : device.screen.safeArea.bottom),
							},
						},
						TextField({
							testId: 'postTitle',
							value: postTitle,
							multiline: false,
							placeholder: BX.message('MOBILE_EXT_LAYOUT_POSTFORM_TITLE_PLACEHOLDER_MSGVER_1'),
							placeholderTextColor: this.getStyle('placeholderTextColor'),
							style: {
								display: (!titleShown || coloredMessage ? 'none' : 'flex'),
								color: this.getStyle('inputTextColor'),
								fontSize: 20,
								margin: this.config.postTitleMargin,
								textAlign: 'left',
								textAlignVertical: 'top',
							},
							onFocus: () => {
								this.showKeyboardPanel({
									isEdit: true,
								});
							},
							onChangeText: (currentText) => {
								this.postTitleValue = currentText;
							},
							onLayout: ({ height }) => {
								this.height.postTitle = height;
							},
							onSubmitEditing: this.onPostTitleSubmitEditing.bind(this),
							autoCapitalize: 'sentences',
							ref: (ref) => {
								this.postTitleRef = ref;
							},
						}),
						PostMessage({
							actionSheetShown,
							postText,
							backgroundImage,
							coloredMessageBackgroundData: (coloredMessage ? backgroundData.outer : {}),
							deviceHeight: parseInt(BX.componentParameters.get('DEVICE_HEIGHT', 0)),
							deviceRatio: BX.componentParameters.get('DEVICE_RATIO', 3),
							moduleVoteInstalled,
							inputTextColor: this.getStyle(coloredMessage ? 'inputColoredTextColor' : 'inputTextColor'),
							placeholderTextColor: this.getStyle(coloredMessage ? 'placeholderColoredTextColor' : 'placeholderTextColor'),
							checkColoredText: this.checkColoredText.bind(this),
							rootHeightWithKeyboard: this.rootRef.heightWithKeyboard,
							marginTop: this.config.postMessageMarginTop,
							marginBottom: this.config.postMessageMarginBottom,
							onFocus: () => {
								this.postMessageFocused = true;

								this.showKeyboardPanel({
									isEdit: true,
								}, () => {
									this.scrollPostMessageToCursor();
								});
							},
							onBlur: () => {
								this.postMessageFocused = false;
							},
							onRef: this.onPostMessageRef.bind(this),
							onChangeText: (currentText) => {
								this.recalcBackgroundByTextLength({
									oldText: this.postText,
									newText: currentText,
								});
								this.recalcMentionHint({
									text: currentText,
								});
								this.postText = currentText;
							},
							onSelectionChange: (data) => {
								const isFocused = this.postMessageRef && this.postMessageRef.isFocused && this.postMessageRef.isFocused();
								if (!isFocused || !data.selection)
								{
									return;
								}

								const {
									start,
									end,
								} = data.selection;

								if (start === end)
								{
									this.postTextCursorPosition = start;
								}
							},
							onInput: this.onPostMessageInput.bind(this),
							onPostMessageChange: this.onPostMessageChange.bind(this),
							onCursorPositionChange: this.onCursorPositionChange.bind(this),
							onScrollViewClick: this.onScrollViewClick.bind(this),
						}),
						isImportant && ImportantPanel({
							importantUntil,
							onSetImportant: (value) => {
								this.onSetImportant(value);
							},
							onSetImportantUntil: (value) => {
								this.onSetImportantUntil(value);
							},
							onLayout: ({ height }) => {
								this.height.importantPanel = height;
							},
							menuCancelTextColor: this.getStyle('menuCancelTextColor'),
						}),
						medal && GratitudePanel({
							onSetGratitudeEmployee: (value) => {
								this.onSetGratitudeEmployee(value);
							},
							onSetGratitudeMedal: (value) => {
								this.onSetGratitudeMedal(value);
							},
							onSetGratitudeMedalWidget: (layoutWidget) => {
								this.onSetGratitudeMedalWidget(layoutWidget);
							},
							onLayout: ({ height }) => {
								this.height.gratitudePanel = height;
							},
							employees: gratitudeEmployees,
							medal,
							menuCancelTextColor: this.getStyle('menuCancelTextColor'),
							postFormData,
							medalsList,
						}),
						VotePanel({
							onSetVoteData: (value) => {
								this.onSetVoteData(value);
							},
							onAddVoteQuestion: () => {
								this.addVoteQuestion();
							},
							onSetVoteQuestionMultiple: (voteData, questionIndex, value) => {
								this.onSetVoteQuestionMultiple(voteData, questionIndex, value);
							},
							onFocus: () => this.showKeyboardPanel(),
							onLayout: ({ height }) => {
								this.height.votePanel = height;
							},
							voteData,
							inputTextColor: this.getStyle('inputTextColor'),
							menuCancelTextColor: this.getStyle('menuCancelTextColor'),
							placeholderTextColor: this.getStyle('placeholderTextColor'),
							postFormData,
							rootScrollRef: this.rootScrollRef.element,
						}),
					),
				),
				View(
					{
						style: {
							flex: 0,
						},
					},
				),
				!actionSheetShown && KeyboardPanel({
					attachmentPanel,
					forAll,
					recipientsCount,
					recipientsString: recipientsStringKeyboard,
					attachments,
					postFormData,
					backgroundAvailable,
					onClickDestinationMenuItem: () => this.onClickDestinationMenuItem(),
					onClickMentionMenuItem: () => {
						this.onClickMentionMenuItem({
							keyboard: false,
						});
					},
					onClickAttachmentMenuItem: () => this.onClickAttachmentMenuItem(),
					onClickBackgroundMenuItem: () => this.onClickBackgroundMenuItem(),
					onKeyboardClick: () => this.hideKeyboardPanel(),
					onRecipientsLayout: this.onRecipientsLayout.bind(this),
				}),
				actionSheetShown && ActionSheet({
					attachmentPanel,
					forAll,
					recipientsString: recipientsStringActionsheet,
					attachments,
					postFormData,
					titleShown,
					coloredMessage,
					onClickDestinationMenuItem: () => this.onClickDestinationMenuItem(),
					onClickMentionMenuItem: () => {
						this.onClickMentionMenuItem({
							keyboard: false,
						});
					},
					onClickAttachmentMenuItem: () => this.onClickAttachmentMenuItem(),
					onOpenAttachmentList: () => this.onOpenAttachmentList(),
					onClickBackgroundMenuItem: () => this.onClickBackgroundMenuItem(),
					onClickImportantMenuItem: () => this.onClickImportantMenuItem(),
					onClickGratitudeMenuItem: () => this.onClickGratitudeMenuItem(),
					onClickShowHideTitleItem: (params) => this.onClickShowHideTitleItem(params),
					onClickVoteMenuItem: () => this.onClickVoteMenuItem(),
					moduleVoteInstalled,
					useImportant,
					backgroundAvailable,
					onHide: () => this.hideActionSheet(),
					animation: (this.height.root === null ? {} : { duration: 0.5, delay: 0 }),
				}),
			);
		}

		processRootViewLayoutChange({ height })
		{
			this.height.root = height;
		}

		onPostMessageRef(ref)
		{
			this.postMessageRef = ref;
			if (
				ref
				&& typeof ref.focus === 'function'
				&& this.postText.length > 0
			)
			{
				ref.focus();
			}
		}

		onPostTitleSubmitEditing()
		{
			if (this.postMessageRef)
			{
				this.postMessageRef.focus();
			}
		}

		focusPostMessage()
		{
			if (this.postMessageRef)
			{
				this.postMessageRef.focus();
				this.postMessageRef.setSelection(this.postTextCursorPosition, this.postTextCursorPosition);
			}
		}

		onRecipientsLayout({
			type,
			height,
		})
		{
			if (type === 'KeyboardPanel' && height > this.config.maxKeyboardRecipientsHeight && this.keyboardPanelRecipientsCountLimit > 0)
			{
				this.keyboardPanelRecipientsCountLimit--;

				this.setState({
					recipientsStringKeyboard: renderDestinationList({
						recipients: this.recipients,
						useContainer: true,
						limit: this.keyboardPanelRecipientsCountLimit,
					}),
				});
			}
			/*
						else if (type === 'ActionSheet')
						{
							if (height > this.config.maxActionSheetRecipientsHeight)
							{
								if (this.actionSheetRecipientsCountLimit > 0)
								{
									this.actionSheetRecipientsCountLimit--;

									this.setState({
										recipientsStringActionSheet: renderDestinationList({
											recipients: this.recipients,
											limit: this.actionSheetRecipientsCountLimit,
										})
									});
								}
							}
							else
							{
								this.actionSheetRecipientsCountLimit = 3;
							}
						}
			*/
		}
	};
})();
