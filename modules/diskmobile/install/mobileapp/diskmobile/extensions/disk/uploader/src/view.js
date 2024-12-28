/**
 * @module disk/uploader/src/view
 */
jn.define('disk/uploader/src/view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { clone, merge } = require('utils/object');
	const { throttle } = require('utils/function');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { UploaderClient } = require('uploader/client');
	const { DiskUploaderFileRow } = require('disk/uploader/src/file-row');
	const { UploadStatus } = require('disk/uploader/src/config');
	const { createVideoPreviewMiddleware } = require('disk/uploader/src/video-preview');
	const { Uuid } = require('utils/uuid');
	const { confirmDestructiveAction, Alert } = require('alert');

	const UPLOADER_CONTEXT_NAME = 'DiskmobileAirUploader';

	class DiskUploaderView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;
			this.layoutWidget.preventBottomSheetDismiss(true);
			this.layoutWidget.on('preventDismiss', this.#confirmedCancel);

			this.uploaderClient = new UploaderClient(UPLOADER_CONTEXT_NAME);
			this.uploaderClient.on('done', this.#onUploadDone);
			this.uploaderClient.on('error', this.#onUploadError);
			this.uploaderClient.on('progress', this.#onUploadProgress);

			this.taskProgressHandlers = new Map();

			/** @type {Map<string, DiskUploaderFileRow>} */
			this.fileRowRefs = new Map();

			this.state = {
				tasks: props.tasks || [],
				originalTasks: clone(props.tasks || []),
				finalization: false,
			};
		}

		/**
		 * @private
		 * @return {[]}
		 */
		get tasks()
		{
			return this.state.tasks;
		}

		/**
		 * @private
		 * @return {[]}
		 */
		get successTasks()
		{
			return this.tasks.filter((item) => item.status === UploadStatus.DONE);
		}

		/**
		 * @private
		 * @return {[]}
		 */
		get pendingTasks()
		{
			return this.tasks.filter((item) => !item.status || item.status === UploadStatus.PROGRESS);
		}

		#getTestId(suffix)
		{
			return `disk-uploader-view-${suffix}`;
		}

		componentDidMount()
		{
			this.tasks.forEach((task) => this.uploaderClient.addTask(clone(task)));
		}

		#onUploadDone = (id, data) => {
			this.#setTask(id, {
				status: UploadStatus.DONE,
				commitConfig: data?.result?.commitConfig,
				rollbackConfig: data?.result?.rollbackConfig,
			});
		};

		#onUploadProgress = (id, data) => {
			if (!this.taskProgressHandlers.has(id))
			{
				this.taskProgressHandlers.set(id, throttle(
					(_data) => this.#setTaskProgress(id, _data),
					500,
					this,
				));
			}

			const { byteSent, byteTotal } = data || {};
			const finalStep = (byteSent === byteTotal);

			if (finalStep)
			{
				// avoid throttling when final chunk uploaded
				this.#setTaskProgress(id, { byteSent, byteTotal });
			}
			else
			{
				const handler = this.taskProgressHandlers.get(id);
				handler({ byteSent, byteTotal });
			}
		};

		#onUploadError = (id) => {
			this.#setTask(id, {
				status: UploadStatus.ERROR,
			});
		};

		#setTaskProgress(id, { byteSent, byteTotal })
		{
			const index = this.tasks.findIndex((item) => item.taskId === id);

			if (index > -1)
			{
				if (this.fileRowRefs.has(id))
				{
					merge(this.tasks[index], { byteSent, byteTotal, status: UploadStatus.PROGRESS });

					const fileRow = this.fileRowRefs.get(id);
					fileRow.setProgress({ byteSent, byteTotal });
				}
				else
				{
					// fallback in case of ref not inited yet
					this.#setTask(id, { byteSent, byteTotal, status: UploadStatus.PROGRESS });
				}
			}
		}

		#setTask(id, data = {})
		{
			const index = this.tasks.findIndex((item) => item.taskId === id);

			if (index > -1)
			{
				merge(this.tasks[index], data);

				return new Promise((resolve) => {
					this.setState({ tasks: this.tasks }, resolve);
				});
			}

			return Promise.resolve();
		}

		#removeTask(id)
		{
			if (this.state.finalization)
			{
				return;
			}

			const index = this.tasks.findIndex((item) => item.taskId === id);

			if (index > -1)
			{
				const tasks = this.tasks;
				const { rollbackConfig } = tasks[index];
				if (rollbackConfig)
				{
					BX.ajax(rollbackConfig);
				}

				if (tasks.length === 1)
				{
					this.layoutWidget?.close();
				}
				else
				{
					tasks.splice(index, 1);

					this.setState({ tasks });
				}
			}
		}

		#cancelTask(id)
		{
			if (this.state.finalization)
			{
				return;
			}

			this.uploaderClient.cancelTask(id);

			this.#setTask(id, {
				status: UploadStatus.CANCELED,
			});
		}

		#retryTask(id)
		{
			if (this.state.finalization)
			{
				return;
			}

			const originalTask = this.state.originalTasks.find((item) => item.taskId === id);
			if (!originalTask)
			{
				return;
			}

			const index = this.tasks.findIndex((item) => item.taskId === id);
			if (index < 0)
			{
				return;
			}

			originalTask.taskId = Uuid.getV4();

			const tasks = this.tasks;
			tasks.splice(index, 1, originalTask);
			this.setState({ tasks });
			this.uploaderClient.addTask(clone(originalTask));
		}

		#confirmedCancel = () => {
			if (this.state.finalization)
			{
				return;
			}

			const doCancel = () => {
				this.tasks.forEach((item) => {
					this.uploaderClient.cancelTask(item.taskId);
					if (item.rollbackConfig)
					{
						BX.ajax(item.rollbackConfig);
					}
				});
				this.layoutWidget?.close();
			};

			if (this.successTasks.length > 0 || this.pendingTasks.length > 0)
			{
				confirmDestructiveAction({
					title: Loc.getMessage('M_DISK_UPLOADER_CANCEL_CONFIRM_TITLE'),
					description: Loc.getMessage('M_DISK_UPLOADER_CANCEL_CONFIRM_BODY'),
					destructionText: Loc.getMessage('M_DISK_UPLOADER_CANCEL'),
					cancelText: Loc.getMessage('M_DISK_UPLOADER_CANCEL_CONFIRM_CANCEL_BUTTON_TEXT'),
					onDestruct: doCancel,
				});
			}
			else
			{
				doCancel();
			}
		};

		#commitAndClose = () => {
			if (this.state.finalization)
			{
				return;
			}

			// user can click button twice, before setState() finished.
			this.state.finalization = true;

			this.setState({ finalization: true }, () => {
				const commits = [];
				this.tasks.forEach((item) => {
					if (item.status === UploadStatus.DONE && item.commitConfig)
					{
						commits.push(this.#commitTask(item));
					}
					else if (item.status === UploadStatus.ERROR || item.status === UploadStatus.CANCELED)
					{
						this.uploaderClient.cancelTask(item.taskId);
						if (item.rollbackConfig)
						{
							BX.ajax(item.rollbackConfig);
						}
					}
				});

				Promise.allSettled(commits)
					.then((results) => {
						const successResults = results.filter((item) => item?.value?.status === 'success');
						const errorResults = results.filter((item) => item?.value?.status !== 'success');
						const committedFiles = successResults.map((item) => item.value.data?.file);
						const doClose = () => {
							this.layoutWidget?.close(() => {
								this.props.onCommit?.(committedFiles);
							});
						};

						if (errorResults.length > 0)
						{
							console.error('DiskUploader commit error', errorResults);

							Alert.alert(
								Loc.getMessage('M_DISK_UPLOADER_COMMON_ERROR_TITLE'),
								Loc.getMessage('M_DISK_UPLOADER_COMMIT_ERROR'),
								doClose,
								Loc.getMessage('M_DISK_UPLOADER_COMMON_ERROR_BUTTON_OK'),
							);
						}
						else
						{
							doClose();
						}
					})
					.catch((err) => {
						console.error(err);
						this.layoutWidget?.close();
					});
			});
		};

		async #commitTask(task)
		{
			const videoPreview = await createVideoPreviewMiddleware(task);

			const commitConfig = merge(
				{},
				task.commitConfig,
				videoPreview,
			);

			return BX.ajax(commitConfig);
		}

		render()
		{
			return Box(
				{
					withScroll: true,
					footer: this.#renderButtons(),
					resizableByKeyboard: true,
				},
				View(
					{},
					...this.tasks.map((task) => new DiskUploaderFileRow({
						...task,
						ref: (ref) => {
							this.fileRowRefs.set(task.taskId, ref);
						},
						onRemoveTaskClick: (taskId) => this.#removeTask(taskId),
						onCancelTaskClick: (taskId) => this.#cancelTask(taskId),
						onRetryTaskClick: (taskId) => this.#retryTask(taskId),
					})),
					this.#renderScrollSafeArea(),
				),
			);
		}

		#renderButtons()
		{
			return BoxFooter(
				{},
				Button({
					testId: this.#getTestId('done-button'),
					text: Loc.getMessage('M_DISK_UPLOADER_DO_UPLOAD'),
					badge: this.#renderBadge(),
					disabled: this.pendingTasks.length > 0,
					loading: this.state.finalization,
					stretched: true,
					size: ButtonSize.L,
					style: {
						marginBottom: 10,
					},
					onClick: this.#commitAndClose,
				}),
				Button({
					testId: this.#getTestId('cancel-button'),
					text: Loc.getMessage('M_DISK_UPLOADER_DO_CANCEL'),
					stretched: true,
					size: ButtonSize.L,
					design: ButtonDesign.PLAN_ACCENT,
					onClick: this.#confirmedCancel,
				}),
			);
		}

		#renderBadge()
		{
			const successCount = this.successTasks.length;

			return successCount === 0 ? null : BadgeCounter({
				value: successCount,
				testId: this.#getTestId('done-button-badge'),
				design: BadgeCounterDesign.WHITE,
			});
		}

		#renderScrollSafeArea()
		{
			return View({
				style: {
					height: 70, // just empiric crutch to better scrolling experience
				},
			});
		}
	}

	module.exports = { DiskUploaderView };
});
