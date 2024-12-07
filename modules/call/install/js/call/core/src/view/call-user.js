import {Dom, Type} from 'main.core';
import {Menu} from 'main.popup';
import {UserState} from '../engine/engine';
import {BackgroundDialog} from '../dialogs/background_dialog';
import {logPlaybackError} from './tools';
import type {UserModel} from './user-registry'
import {MediaStreamsKinds} from '../call_api';
import Util from '../util';
import { Utils } from 'im.v2.lib.utils';

type CallUserElements = {
	root?: HTMLElement,
	container?: HTMLElement,
	videoContainer?: HTMLElement,
	video?: HTMLVideoElement,
	audio?: HTMLAudioElement,
	screenAudio?: HTMLAudioElement,
	preview?: HTMLVideoElement,
	videoBorder?: HTMLElement,
	avatarContainer?: HTMLElement,
	avatar?: HTMLElement,
	nameContainer?: HTMLElement,
	name?: HTMLElement,
	changeNameIcon?: HTMLElement,
	changeNameContainer?: HTMLElement,
	changeNameCancel?: HTMLElement,
	changeNameInput?: HTMLInputElement,
	changeNameConfirm?: HTMLElement,
	changeNameLoader?: HTMLElement,
	introduceYourselfContainer?: HTMLElement,
	floorRequest?: HTMLElement,
	badNetworkIndicator?: HTMLElement,
	state?: HTMLElement,
	removeButton?: HTMLElement,
	micState?: HTMLElement,
	cameraState?: HTMLElement,
	panel?: HTMLElement,
	buttonMenu?: HTMLElement,
	buttonBackground?: HTMLElement,
	buttonPin?: HTMLElement,
	buttonUnPin?: HTMLElement,
	connectionProblem?: HTMLElement,
	statsOverlay?: HTMLElement,
	connectionQualityIcon?: HTMLElement,
}

type CallUserParams = {
	parentContainer: HTMLElement,
	userModel: UserModel,
	screenAudioElement: ?HTMLAudioElement,
	audioElement: ?HTMLAudioElement,
	allowBackgroundItem: ?boolean,
	allowMaskItem: ?boolean,
	allowPinButton: ?boolean,
	screenSharingUser: ?boolean,
	audioTrack: ?MediaStreamTrack,
	screenAudioTrack: ?MediaStreamTrack,
	videoTrack: ?MediaStreamTrack,

	onClick: () => void,
	onPin: () => void,
	onUnPin: () => void,
	onUserRename: () => void,
	onUserRenameInputFocus: () => void,
	onUserRenameInputBlur: () => void,
}

export class CallUser
{
	userModel: UserModel
	elements: CallUserElements = {}
	menu: ?Menu

	constructor(config: CallUserParams = {})
	{
		this.userModel = config.userModel;
		this.userModel.subscribe("changed", this._onUserFieldChanged.bind(this));
		this.parentContainer = config.parentContainer;
		this.screenSharingUser = Type.isBoolean(config.screenSharingUser) ? config.screenSharingUser : false;
		this.allowBackgroundItem = Type.isBoolean(config.allowBackgroundItem) ? config.allowBackgroundItem : true;
		this.allowMaskItem = Type.isBoolean(config.allowMaskItem) ? config.allowMaskItem : true;
		this._allowPinButton = Type.isBoolean(config.allowPinButton) ? config.allowPinButton : true;
		this._visible = true;
		this._audioTrack = config.audioTrack;
		this._screenAudioTrack = config.screenAudioTrack;
		this._audioStream = this._audioTrack ? new MediaStream([this._audioTrack]) : null;
		this._screenAudioStream = this._screenAudioTrack ? new MediaStream([this._screenAudioTrack]) : null;
		this._videoTrack = config.videoTrack;
		this._stream = this._videoTrack ? new MediaStream([this._videoTrack]) : null;
		this._videoRenderer = null;
		this._previewRenderer = null;
		this._flipVideo = false;

		this.hidden = false;
		this.videoBlurState = false;
		this.isChangingName = false;

		this._badNetworkIndicator = false;

		this.incomingVideoConstraints = {
			width: 0, height: 0
		}
		if (config.audioElement)
		{
			this.elements.audio = config.audioElement;
		}

		if (config.screenAudioElement)
		{
			this.elements.screenAudio = config.screenAudioElement;
		}

		this.callBacks = {
			onClick: Type.isFunction(config.onClick) ? config.onClick : BX.DoNothing,
			onUserRename: Type.isFunction(config.onUserRename) ? config.onUserRename : BX.DoNothing,
			onUserRenameInputFocus: Type.isFunction(config.onUserRenameInputFocus) ? config.onUserRenameInputFocus : BX.DoNothing,
			onUserRenameInputBlur: Type.isFunction(config.onUserRenameInputBlur) ? config.onUserRenameInputBlur : BX.DoNothing,
			onPin: Type.isFunction(config.onPin) ? config.onPin : BX.DoNothing,
			onUnPin: Type.isFunction(config.onUnPin) ? config.onUnPin : BX.DoNothing,
		};
		this.checkAspectInterval = setInterval(this.checkVideoAspect.bind(this), 500);

		this.hintManager = BX.UI.Hint.createInstance({
			popupParameters: {
				targetContainer: document.body,
				className: `bx-messenger-videocall-panel-item-hotkey-hint ${this.userModel.id}`,
				bindOptions: {forceBindPosition: true}
			}
		});

		this.connectionStats = {};
		this.connectionStatsVisible = false;

		this.avatarBackground = Util.getAvatarBackground();

		this.removeAvatarPulseTimer = null;
		this.hideUserNameTimer = null;

		this.init();
	};

	init()
	{
		this.onMouseMoveHandler = this.showUserName.bind(this);
		document.addEventListener('mousemove', this.onMouseMoveHandler);
	}

	get id()
	{
		return this.userModel.id
	}

	get allowPinButton()
	{
		return this._allowPinButton;
	}

	set allowPinButton(allowPinButton)
	{
		if (this._allowPinButton == allowPinButton)
		{
			return;
		}
		this._allowPinButton = allowPinButton;
		this.update()
	}

	get audioTrack()
	{
		return this._audioTrack;
	}

	set audioTrack(audioTrack: ?MediaStreamTrack)
	{
		if (this._audioTrack === audioTrack)
		{
			return;
		}
		this._audioTrack = audioTrack;
		this._audioStream = this._audioTrack ? new MediaStream([this._audioTrack]) : null;
		this.playAudio()
	}

	get audioStream()
	{
		return this._audioStream;
	}

	get screenAudioTrack()
	{
		return this._screenAudioTrack;
	}

	set screenAudioTrack(screenAudioTrack: ?MediaStreamTrack)
	{
		if (this._screenAudioTrack === screenAudioTrack)
		{
			return;
		}
		this._screenAudioTrack = screenAudioTrack;
		this._screenAudioStream = this._screenAudioTrack ? new MediaStream([this._screenAudioTrack]) : null;
		this.playScreenAudio()
	}

	get screenAudioStream()
	{
		return this._screenAudioStream;
	}

	get flipVideo()
	{
		return this._flipVideo;
	}

	set flipVideo(flipVideo)
	{
		this._flipVideo = flipVideo;
		this.update()
	}

	get stream(): ?MediaStream
	{
		return this._stream;
	}

	get visible()
	{
		return this._visible;
	}

	set visible(visible)
	{
		if (this._visible !== visible)
		{
			this._visible = visible;
			this.update();
			this.updateRendererState();
		}
	}

	get videoRenderer()
	{
		return this._videoRenderer;
	}

	set videoRenderer(videoRenderer)
	{
		// we should to reset old video track after switching from a plain call
		// in order to properly check the camera video in hasCameraVideo
		if (this._videoTrack)
		{
			this._videoTrack = null;
		}

		if (this._badNetworkIndicator)
		{
			// Voximplant calls logic with support of streams disabling
			if (videoRenderer.stream)
			{
				this._tempVideoRenderer = videoRenderer;
				this._videoRenderer = null;
			}
			else
			{
				this._tempVideoRenderer = this._videoRenderer = null;
			}
		}
		else
		{
			// Bitrix calls logic with support of preview
			this._tempVideoRenderer = null;
			const currentVideoRendererKind = this._videoRenderer?.kind;
			const newVideoRendererKind = videoRenderer?.kind;

			if (videoRenderer.stream)
			{
				if (newVideoRendererKind === 'sharing' && currentVideoRendererKind === 'video')
				{
					this._previewRenderer = this._videoRenderer;
					this._videoRenderer = videoRenderer;
				}
				else if (newVideoRendererKind === 'video' && currentVideoRendererKind === 'sharing')
				{
					this._previewRenderer = videoRenderer;
				}
				else
				{
					this._videoRenderer = videoRenderer;
				}
			}
			else
			{
				if (newVideoRendererKind === 'sharing')
				{
					if (currentVideoRendererKind === 'sharing')
					{
						this._videoRenderer = this._previewRenderer;
					}
					this._previewRenderer = null;
					delete this.connectionStats[MediaStreamsKinds.Screen];
				}
				else if (newVideoRendererKind === 'video')
				{
					if (currentVideoRendererKind === 'sharing')
					{
						this._previewRenderer = null;
					}
					else
					{
						this._videoRenderer = null;
					}
					delete this.connectionStats[MediaStreamsKinds.Camera];
				}
				this.showConnectionStats();
			}
		}

		this.update();
		this.updateRendererState();
	}

	get previewRenderer()
	{
		return this._previewRenderer;
	}

	get videoTrack()
	{
		return this._videoTrack;
	}

	set videoTrack(videoTrack: MediaStreamTrack)
	{
		if (this._videoTrack === videoTrack)
		{
			return;
		}
		this._videoTrack = videoTrack;
		if (this._videoTrack && this._stream)
		{
			this._stream.removeTrack(this._stream.getVideoTracks()[0]);
			this._stream.addTrack(this._videoTrack);
		}
		else
		{
			this._stream = this._videoTrack ? new MediaStream([this._videoTrack]) : null;
		}

		this.update();
	}

	set badNetworkIndicator(badNetworkIndicator)
	{
		if (this._badNetworkIndicator === badNetworkIndicator)
		{
			return;
		}

		this._badNetworkIndicator = badNetworkIndicator;
		if (this._badNetworkIndicator)
		{
			if (this._videoRenderer)
			{
				this._tempVideoRenderer = this._videoRenderer;
				this._videoRenderer = null;
			}
		}
		else
		{
			if (this._tempVideoRenderer)
			{
				this._videoRenderer = this._tempVideoRenderer;
				this._tempVideoRenderer = null;
			}
		}

		this.update();
	}

	set hasConnectionProblem(hasConnectionProblem)
	{
		this._hasConnectionProblem = hasConnectionProblem;
		if (this._hasConnectionProblem)
		{
			this.elements.connectionProblem.classList.add("connection-problem-visible");
		}
		else {
			this.elements.connectionProblem.classList.remove("connection-problem-visible");
		}
	}

	set connectionQuality(connectionQuality)
	{
		this._connectionQuality = connectionQuality;

		const connectionQualityIcons = {
			excellent: '--excellent-quality-icon',
			good: '--good-quality-icon',
			poor: '--poor-quality-icon',
			bad: '--bad-quality-icon',
		}

		if (this._connectionQuality !== undefined && this.elements.connectionQualityIcon)
		{
			let resultIcon = connectionQualityIcons.bad;

			if (this._connectionQuality >= 4)
			{
				resultIcon = connectionQualityIcons.excellent;
			}

			if (this._connectionQuality >= 3 && this._connectionQuality < 4)
			{
				resultIcon = connectionQualityIcons.good;
			}

			if (this._connectionQuality >= 2 && this._connectionQuality < 3)
			{
				resultIcon = connectionQualityIcons.poor;
			}

			if (this._connectionQuality < 2)
			{
				resultIcon = connectionQualityIcons.bad;
			}

			this.elements.connectionQualityIcon.style.setProperty('--connection-quality-icon', `var(${resultIcon})`);
		}
	}

	isVisibleMediaStateIcon(isActive)
	{
		return !isActive && this.userModel.state === UserState.Connected;
	};

	isVisibleCameraStateIcon()
	{
		return this.isVisibleMediaStateIcon(this.userModel.cameraState);
	};

	isVisibleMicStateIcon()
	{
		return this.isVisibleMediaStateIcon(this.userModel.microphoneState);
	};

	showStats(stats)
	{
		this.connectionStats = stats;
		if (this.elements.statsOverlay && this.connectionStatsVisible)
		{
			this.showConnectionStats();
		}
	}

	render()
	{
		if (this.elements.root)
		{
			return this.elements.root;
		}
		this.elements.root = Dom.create("div", {
			props: {className: "bx-messenger-videocall-user"},
			dataset: {userId: this.userModel.id, order: this.userModel.order},
			children: [
				this.elements.videoBorder = Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-border",
					},
				}),
				this.elements.container = Dom.create("div", {
					props: {className: "bx-messenger-videocall-user-inner"},
					children: [
						this.elements.avatarContainer = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-avatar-border"},
							children: [
								this.elements.avatar = Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar"},
									text: ''
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar-overlay-border"}
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar-pulse-element", style: "animation-delay: -2s;"}
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar-pulse-element", style: "animation-delay: -1.5s;"}
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar-pulse-element", style: "animation-delay: -1s;"}
								}),
								Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-avatar-pulse-element", style: "animation-delay: -0.5s;"}
								}),
							]
						}),
						this.elements.panel = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-panel"}
						}),
						this.elements.state = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-status-text"},
							text: this.getStateMessage(this.userModel.state)
						}),
						this.elements.userBottomContainer = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-bottom"},
							children: [
								this.elements.nameContainer = Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-name-container" + ((this.userModel.allowRename && !this.userModel.wasRenamed) ? " hidden" : "")},
									children: [
										this.elements.name = Dom.create("span", {
											props: {className: "bx-messenger-videocall-user-name", title: (this.screenSharingUser ? BX.message('IM_CALL_USERS_SCREEN').replace("#NAME#", this.userModel.name) : this.userModel.name)},
											text: (this.screenSharingUser ? BX.message('IM_CALL_USERS_SCREEN').replace("#NAME#", this.userModel.name) : this.userModel.name),
										}),
										this.elements.changeNameIcon = Dom.create("div", {
											props: {className: "bx-messenger-videocall-user-name-icon bx-messenger-videocall-user-change-name-icon hidden"},
										})],
									events: {
										click: this.toggleNameInput.bind(this)
									}
								}),
								this.elements.changeNameContainer = Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-change-name-container hidden"},
									children: [
										this.elements.changeNameCancel = Dom.create("div", {
											props: {className: "bx-messenger-videocall-user-change-name-cancel"},
											events: {
												click: this.toggleNameInput.bind(this)
											}
										}),
										this.elements.changeNameInput = Dom.create("input", {
											props: {
												className: "bx-messenger-videocall-user-change-name-input"
											}, attrs: {
												type: 'text', value: this.userModel.name
											}, events: {
												keydown: this.onNameInputKeyDown.bind(this),
												focus: this.callBacks.onUserRenameInputFocus,
												blur: this.callBacks.onUserRenameInputBlur,
												click: function(event) {
													event.stopPropagation();
												},
											}
										}),
										this.elements.changeNameConfirm = Dom.create("div", {
											props: {className: "bx-messenger-videocall-user-change-name-confirm"},
											events: {
												click: this.changeName.bind(this)
											}
										}),
										this.elements.changeNameLoader = Dom.create("div", {
											props: {className: "bx-messenger-videocall-user-change-name-loader hidden"},
											children: [
												Dom.create("div", {
													props: {className: "bx-messenger-videocall-user-change-name-loader-icon"}
												})
											]
										})
									]
								}),
								this.elements.introduceYourselfContainer = Dom.create("div", {
									props: {className: "bx-messenger-videocall-user-introduce-yourself-container" + (!this.userModel.allowRename || this.userModel.wasRenamed ? " hidden" : "")},
									children: [
										Dom.create("div", {
											props: {className: "bx-messenger-videocall-user-introduce-yourself-text"},
											text: BX.message('IM_CALL_GUEST_INTRODUCE_YOURSELF'),
										})
									],
									events: {
										click: this.toggleNameInput.bind(this)
									}
								}),
							]
						}),
						this.elements.floorRequest = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-floor-request bx-messenger-videocall-floor-request-icon"}
						}),
						this.elements.statsOverlay = Dom.create("div", {
							props: {className: "bx-messenger-videocall-user-stats-overlay"},
						})
					]
				}),
			],
			style: {
				order: this.userModel.order
			},
			events: {
				click: function (e)
				{
					e.stopPropagation();
					this.callBacks.onClick({
						userId: this.id
					});
				}.bind(this)
			}
		});

		if (this.userModel.talking)
		{
			this.updateAvatarPulseState()
		}

		this.elements.debugPanel = Dom.create("div", {
			props: {className: "bx-messenger-videocall-user-debug-panel"},
			children: [
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-debug-panel-button connection-stats"
					},
					children: [
						this.elements.connectionQualityIcon = Dom.create("div", {
							props: {
								className: "bx-messenger-videocall-user-debug-panel-button-icon connection-quality-icon",
								title: BX.message("IM_M_CALL_CONNECTION_QUALITY_HINT"),
							},
						}),
					],
					events: {
						click: e => {
							e.stopPropagation();
							this.connectionStatsVisible = !this.connectionStatsVisible;
							if (this.connectionStatsVisible)
							{
								this.showConnectionStats();
								this.elements.statsOverlay.classList.add('stats-overlay-visble');
							}
							else
							{
								this.elements.statsOverlay.classList.remove('stats-overlay-visble');
							}
						}
					}
				}),
				this.elements.connectionProblem = Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-debug-panel-button connection-problem"
					},
					children: [
						Dom.create("div", {
							props: {
								className: "bx-messenger-videocall-user-debug-panel-button-icon connection-problem-icon"
							},
							events: {
								mouseover: (e) =>
								{
									this.hintManager.show(e.currentTarget, BX.message("IM_M_CALL_POOR_CONNECTION_WITH_USER"));
								},
								mouseout: (e) =>
								{
									this.hintManager.hide();
								}
							}
						}),
					]
				})
			]
		});

		if (this.userModel.localUser)
		{
			this.elements.root.classList.add("bx-messenger-videocall-user-self");
		}

		if (this.userModel.avatar !== '')
		{
			this.elements.root.style.setProperty("--avatar", "url('" + this.userModel.avatar + "')");
			this.elements.avatar.innerText = '';
			this.elements.root.style.removeProperty("--avatar-background");
		}
		else
		{
			this.elements.root.style.removeProperty("--avatar");
			this.elements.root.style.setProperty("--avatar-background", this.avatarBackground);
			this.elements.avatar.innerText = this.getAvatarInnerText();
		}

		this.elements.videoContainer = Dom.create("div", {
			props: {
				className: "bx-messenger-videocall-video-container",
			},
			children: [
				this.elements.video = Dom.create("video", {
					props: {
						className: "bx-messenger-videocall-video", volume: 0, autoplay: true
					},
					attrs: {
						playsinline: true, muted: true
					}
				}),
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-preview",
					},
					children: [
						Dom.create("div", {
							props: {
								className: "bx-messenger-videocall-preview-container",
							},
							children: [
								this.elements.preview = Dom.create("video", {
									props: {
										className: "bx-messenger-videocall-preview-video", volume: 0, autoplay: true,
									},
									attrs: {
										playsinline: true, muted: true
									}
								})
							]
						})
					]
				}),
			]
		});
		this.elements.container.appendChild(this.elements.videoContainer);

		if (this.stream && this.stream.active)
		{
			this.elements.video.srcObject = this.stream;
		}

		if (this.flipVideo)
		{
			this.elements.video.classList.add("bx-messenger-videocall-video-flipped");
		}
		if (this.userModel.screenState)
		{
			this.elements.video.classList.add("bx-messenger-videocall-video-contain");
		}

		if (this.isVisibleCameraStateIcon() && this.isVisibleMicStateIcon())
		{
			this.elements.nameContainer.classList.add("extra-padding");
		}

		//this.elements.nameContainer.appendChild(this.elements.micState);

		// todo: show button only if user have the permission to remove user
		/*this.elements.removeButton = Dom.create("div", {
			props: {className: "bx-messenger-videocall-user-close"}
		});

		this.elements.container.appendChild(this.elements.removeButton);*/

		this.elements.buttonBackground = Dom.create("div", {
			props: {
				className: "bx-messenger-videocall-user-panel-button"
			},
			children: [
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-icon background"
					}
				}),
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-text"
					},
					text: BX.message("IM_CALL_CHANGE_BACKGROUND")
				})
			],
			events: {
				click: e => {
					e.stopPropagation();
					BackgroundDialog.open();
				}
			}
		});
		this.elements.buttonMenu = Dom.create("div", {
			props: {
				className: "bx-messenger-videocall-user-panel-button"
			},
			children: [
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-icon menu"
					}
				}),
			],
			events: {
				click: e => {
					e.stopPropagation();
					this.showMenu();
				}
			}
		});
		this.elements.buttonPin = Dom.create("div", {
			props: {
				className: "bx-messenger-videocall-user-panel-button"
			},
			children: [
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-icon pin"
					}
				}),
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-text"
					}, text: BX.message("IM_CALL_PIN")
				})
			],
			events: {
				click: (e) =>
				{
					e.stopPropagation();
					this.callBacks.onPin({userId: this.userModel.id});
				}
			}
		});
		this.elements.buttonUnPin = Dom.create("div", {
			props: {
				className: "bx-messenger-videocall-user-panel-button"
			},
			children: [
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-icon unpin"
					}
				}),
				Dom.create("div", {
					props: {
						className: "bx-messenger-videocall-user-panel-button-text"
					},
					text: BX.message("IM_CALL_UNPIN")
				})
			],
			events: {
				click:  (e) =>
				{
					e.stopPropagation();
					this.callBacks.onUnPin();
				}
			}
		});

		this.elements.userBottomContainer.appendChild(
			Dom.create("div", {
				props: {className: "bx-messenger-videocall-user-device-state-container"},
				children: [
					this.elements.cameraState = Dom.create("div", {
						props: {className: "bx-messenger-videocall-user-name-icon bx-messenger-videocall-user-device-state camera" + (!this.isVisibleCameraStateIcon() ? " hidden" : "")},
					}),
					this.elements.micState = Dom.create("div", {
						props: {className: "bx-messenger-videocall-user-name-icon bx-messenger-videocall-user-device-state mic" + (!this.isVisibleMicStateIcon() ? " hidden" : "")},
					}),
				],
			}),
		);

		this.updatePanelDeferred();
		return this.elements.root;
	};

	showConnectionStats()
	{
		if (!this.elements.statsOverlay)
		{
			return;
		}

		let statsString = '';

		const cameraStats = this.connectionStats?.[MediaStreamsKinds.Camera];
		const screenStats = this.connectionStats?.[MediaStreamsKinds.Screen];
		const audioStats = this.connectionStats?.[MediaStreamsKinds.Microphone];

		if (cameraStats || !screenStats)
		{
			statsString += `Video stats:\n`;
			statsString += this._formatVideoStats(cameraStats);
		}

		if (screenStats)
		{
			if (screenStats)
			{
				statsString += `\n\n`;
			}

			statsString += `Screen share stats:\n`;
			statsString += this._formatVideoStats(screenStats);
		}

		if (audioStats)
		{
			if (cameraStats || screenStats)
			{
				statsString += `\n\n`;
			}

			statsString += `Audio stats:\n`;
			statsString += `Bitrate: ${audioStats?.bitrate || 0}\n`;
			statsString += `PacketsLost: ${audioStats?.packetsLostExtended || 0}\n`;
			statsString += `Codec: ${audioStats?.codecName || '-'}`;
		}

		this.elements.statsOverlay.innerText = statsString;
	}

	setIncomingVideoConstraints(width, height)
	{
		this.incomingVideoConstraints.width = typeof (width) === "undefined" ? this.incomingVideoConstraints.width : width;
		this.incomingVideoConstraints.height = typeof (height) === "undefined" ? this.incomingVideoConstraints.height : height;

		if (!this.videoRenderer)
		{
			return;
		}

		// vox low quality temporary workaround
		// (disabled to test quality)
		// if (this.incomingVideoConstraints.width >= 320 && this.incomingVideoConstraints.width <= 640)
		// {
		// 	this.incomingVideoConstraints.width = 640;
		// }
		// if (this.incomingVideoConstraints.height >= 180 && this.incomingVideoConstraints.height <= 360)
		// {
		// 	this.incomingVideoConstraints.height = 360;
		// }

		this.videoRenderer.requestVideoSize(this.incomingVideoConstraints.width, this.incomingVideoConstraints.height);
	};

	updateRendererState()
	{
		/*if (this.videoRenderer)
		{
			if (this.visible)
			{
				this.videoRenderer.enable();
			}
			else
			{
				this.videoRenderer.disable();
			}
		}*/

		/*if (this.elements.video && this.elements.video.srcObject)
		{
			if (this.visible)
			{
				this.elements.video.play();
			}
			else
			{
				this.elements.video.pause();
			}
		}*/
	};

	_onUserFieldChanged(event)
	{
		const eventData = event.data;

		switch (eventData.fieldName)
		{
			case "id":
				return this.updateId();
			case "name":
				return this.updateName();
			case "avatar":
				return this.updateAvatar();
			case "state":
				return this.updateState();
			case "talking":
				return this.updateTalking();
			case "microphoneState":
				return this.updateMicrophoneState();
			case "cameraState":
				return this.updateCameraState();
			case "videoPaused":
				return this.updateVideoPaused();
			case "floorRequestState":
				return this.updateFloorRequestState();
			case "screenState":
				return this.updateScreenState();
			case "pinned":
				return this.updatePanel();
			case "allowRename":
				return this.updateRenameAllowed();
			case "wasRenamed":
				return this.updateWasRenamed();
			case "renameRequested":
				return this.updateRenameRequested();
			case "order":
				return this.updateOrder();

		}
	};

	toggleRenameIcon()
	{
		if (!this.userModel.allowRename)
		{
			return;
		}

		this.elements.changeNameIcon.classList.toggle('hidden');
	};

	toggleNameInput(event)
	{
		if (!this.userModel.allowRename || !this.elements.root)
		{
			return;
		}

		event.stopPropagation();

		if (this.isChangingName)
		{
			this.isChangingName = false;
			if (!this.userModel.wasRenamed)
			{
				this.elements.introduceYourselfContainer.classList.remove('hidden');
				this.elements.changeNameContainer.classList.add('hidden');
			}
			else
			{
				this.elements.changeNameContainer.classList.add('hidden');
				this.elements.nameContainer.classList.remove('hidden');
			}
		}
		else
		{
			if (!this.userModel.wasRenamed)
			{
				this.elements.introduceYourselfContainer.classList.add('hidden');
			}
			this.isChangingName = true;
			this.elements.nameContainer.classList.add('hidden');
			this.elements.changeNameContainer.classList.remove('hidden');
			this.elements.changeNameInput.value = this.userModel.name;
			this.elements.changeNameInput.focus();
			this.elements.changeNameInput.select();
		}
	};

	onNameInputKeyDown(event)
	{
		if (!this.userModel.allowRename)
		{
			return;
		}

		//enter
		if (event.keyCode === 13)
		{
			this.changeName(event);
		}
		//escape
		else if (event.keyCode === 27)
		{
			this.toggleNameInput(event);
		}
	};

	onNameInputFocus(event)
	{

	};

	onNameInputBlur(event)
	{

	};

	changeName(event)
	{
		event.stopPropagation();

		const inputValue = this.elements.changeNameInput.value;
		const newName = inputValue.trim();
		let needToUpdate = true;
		if (newName === this.userModel.name || newName === '')
		{
			needToUpdate = false;
		}

		if (needToUpdate)
		{
			this.elements.changeNameConfirm.classList.toggle('hidden');
			this.elements.changeNameLoader.classList.toggle('hidden');
			this.callBacks.onUserRename(newName);
		}
		else
		{
			this.toggleNameInput(event);
		}
	};

	showMenu()
	{
		const menuItems = [];

		if (this.userModel.localUser && this.allowBackgroundItem)
		{
			menuItems.push({
				text: (this.allowMaskItem ? BX.message("IM_CALL_CHANGE_BG_MASK") : BX.message("IM_CALL_CHANGE_BACKGROUND")),
				onclick: () =>
				{
					this.menu.close();
					BackgroundDialog.open();
				}
			});
		}
		if (menuItems.length === 0)
		{
			return;
		}

		let rect = Dom.getRelativePosition(this.elements.buttonMenu, this.parentContainer)
		this.menu = new Menu({
			id: 'call-view-user-menu-' + this.userModel.id,
			bindElement: {
				left: rect.left,
				top: rect.top,
				bottom: rect.bottom
			},
			items: menuItems,
			targetContainer: this.parentContainer,
			autoHide: true,
			closeByEsc: true,
			offsetTop: 0,
			offsetLeft: 0,
			bindOptions: {
				position: 'bottom'
			},
			angle: true,
			overlay: {
				backgroundColor: 'white', opacity: 0
			},
			cacheable: false,
			events: {
				onPopupDestroy: () => this.menu = null
			}
		});
		this.menu.show();
	};

	updateAvatar()
	{
		if (this.elements.root)
		{
			if (this.userModel.avatar !== '')
			{
				this.elements.root.style.setProperty("--avatar", "url('" + this.userModel.avatar + "')");
				this.elements.avatar.innerText = '';
				this.elements.root.style.removeProperty("--avatar-background");
			}
			else
			{
				this.elements.root.style.removeProperty("--avatar");
				this.elements.root.style.setProperty("--avatar-background", this.avatarBackground);
				this.elements.avatar.innerText = this.getAvatarInnerText();
			}
		}
	};

	updateId()
	{
		if (this.elements.root)
		{
			this.elements.root.dataset.userId = this.userModel.id;
		}
	};

	updateName()
	{
		if (this.isChangingName)
		{
			this.isChangingName = false;
			this.elements.changeNameConfirm.classList.toggle('hidden');
			this.elements.changeNameLoader.classList.toggle('hidden');
			this.elements.changeNameContainer.classList.add('hidden');
			this.elements.nameContainer.classList.remove('hidden');
		}

		if (this.elements.name)
		{
			this.elements.name.innerText = this.screenSharingUser ? BX.message('IM_CALL_USERS_SCREEN').replace("#NAME#", this.userModel.name) : this.userModel.name;
		}
		if (this.userModel.avatar === '' && this.elements.avatar)
		{
			this.elements.avatar.innerText = this.getAvatarInnerText();
		}

	};

	getAvatarInnerText()
	{
		return Utils.text.getFirstLetters(this.userModel.name).toUpperCase();
	}

	updateRenameAllowed()
	{
		if (this.userModel.allowRename && this.elements.nameContainer && this.elements.introduceYourselfContainer)
		{
			this.elements.nameContainer.classList.add('hidden');
			this.elements.introduceYourselfContainer.classList.remove('hidden');
		}
	};

	updateWasRenamed()
	{
		if (!this.elements.root)
		{
			return;
		}

		if (this.userModel.allowRename)
		{
			this.elements.introduceYourselfContainer.classList.add('hidden');
			this.elements.changeNameIcon.classList.remove('hidden');
			if (this.elements.changeNameContainer.classList.contains('hidden'))
			{
				this.elements.nameContainer.classList.remove('hidden');
			}
		}
	};

	updateRenameRequested()
	{
		if (this.userModel.allowRename)
		{
			this.elements.introduceYourselfContainer.classList.add('hidden');
		}
	};

	updateOrder()
	{
		if (this.elements.root)
		{
			this.elements.root.dataset.order = this.userModel.order;
			this.elements.root.style.order = this.userModel.order;
		}
	};

	updatePanelDeferred()
	{
		setTimeout(this.updatePanel.bind(this), 0);
	};

	updatePanel()
	{
		if (!this.isMounted())
		{
			return;
		}
		const width = this.elements.root.offsetWidth;

		Dom.clean(this.elements.panel);
		if (this.userModel.localUser && this.allowBackgroundItem)
		{
			if (width > 300)
			{
				this.elements.panel.appendChild(this.elements.buttonBackground);
			}
			else
			{
				this.elements.panel.appendChild(this.elements.buttonMenu);
			}
		}

		if (this.allowPinButton)
		{
			if (this.userModel.pinned)
			{
				this.elements.panel.appendChild(this.elements.buttonUnPin);
			}
			else
			{
				this.elements.panel.appendChild(this.elements.buttonPin);
			}

			if (width > 250)
			{
				this.elements.buttonPin.classList.remove("no-text");
				this.elements.buttonUnPin.classList.remove("no-text");
			}
			else
			{
				this.elements.buttonPin.classList.add("no-text");
				this.elements.buttonUnPin.classList.add("no-text");
			}
		}
	};

	playVideoElements(videoElement)
	{
		const hasVideoEl = !!videoElement;
		const isCanPlaying = hasVideoEl && !!videoElement.srcObject;
		const isPaused = hasVideoEl && videoElement.paused;
		const isReadyPlaying = hasVideoEl && videoElement.readyState >= videoElement.HAVE_CURRENT_DATA;
		const isEnded = hasVideoEl && videoElement.ended;

		if (hasVideoEl && !isPaused)
		{
			videoElement.pause();
		}

		if (isCanPlaying && isReadyPlaying && !isEnded)
		{
			videoElement.play().catch(logPlaybackError);
		}

		if (isCanPlaying && isEnded)
		{
			videoElement.load();
		}

		if (isCanPlaying && !isReadyPlaying && !isEnded && !videoElement.onloadeddata)
		{
			videoElement.onloadeddata = () =>
			{
				this.playVideoElements(videoElement);
			};
		}
	}

	update()
	{
		if (!this.elements.root)
		{
			return;
		}

		if (this.hasVideo()/* && this.visible*/)
		{
			if (this.visible)
			{
				if (this.videoRenderer)
				{
					this.videoRenderer.render(this.elements.video);
					this.playVideoElements(this.elements.video);
					if (this._previewRenderer)
					{
						this._previewRenderer.render(this.elements.preview);
					}
					else
					{
						this.elements.preview.srcObject = null;
					}
				}
				else if (this.stream && this.elements.video.srcObject?.id !== this.stream?.id)
				{
					this.elements.video.srcObject = this.stream;
					this.playVideoElements(this.elements.video);
				}

				if (this.elements.avatarContainer)
				{
					this.elements.avatarContainer.classList.add('bx-messenger-videocall-hidden-avatar');
				}
			}

			if (this.videoRenderer?.kind === 'video' && this.flipVideo)
			{
				this.elements.video.classList.toggle("bx-messenger-videocall-video-flipped", this.flipVideo);
				this.elements.preview.classList.toggle("bx-messenger-videocall-video-flipped", !this.flipVideo);
			}
			else if (this.videoRenderer?.kind === 'sharing' && this.flipVideo)
			{
				this.elements.video.classList.toggle("bx-messenger-videocall-video-flipped", !this.flipVideo);
				this.elements.preview.classList.toggle("bx-messenger-videocall-video-flipped", this.flipVideo);
			}
			else
			{
				this.elements.video.classList.toggle("bx-messenger-videocall-video-flipped", this.flipVideo);
			}
			this.elements.video.classList.toggle("bx-messenger-videocall-video-contain", this.userModel.screenState);
		}
		else
		{
			this.elements.video.srcObject = null;
			this.elements.preview.srcObject = null;
			if (this.elements.avatarContainer)
			{
				this.elements.avatarContainer.classList.remove('bx-messenger-videocall-hidden-avatar');
			}
		}
		if (
			Util.isCallServerAllowed()
			&& this.userModel.state === UserState.Connected
			&& !this.elements.debugPanel.parentElement
			&& !this.screenSharingUser
		)
		{
			this.elements.container.appendChild(this.elements.debugPanel);
		}
		this.updatePanelDeferred();
	};

	playAudio()
	{
		if (!this.audioStream)
		{
			this.elements.audio.srcObject = null;
			return;
		}

		if (this.speakerId && Type.isFunction(this.elements.audio.setSinkId))
		{
			this.elements.audio.setSinkId(this.speakerId).then(function ()
			{
				this.elements.audio.srcObject = this.audioStream;
				this.elements.audio.play().catch(logPlaybackError);
			}.bind(this)).catch(console.error);
		}
		else
		{
			this.elements.audio.srcObject = this.audioStream;
			this.elements.audio.play().catch(logPlaybackError);
		}
	};

	playScreenAudio()
	{
		if (!this.screenAudioStream)
		{
			this.elements.screenAudio.srcObject = null;
			return;
		}

		this.elements.screenAudio.srcObject = this.screenAudioStream;
		this.elements.screenAudio.play().catch(logPlaybackError);
	}

	playVideo()
	{
		this.playVideoElements(this.elements.video);
		this.playVideoElements(this.elements.preview);
	};

	blurVideo(blurState)
	{
		blurState = !!blurState;

		if (this.videoBlurState == blurState)
		{
			return;
		}
		this.videoBlurState = blurState;
		if (this.elements.video)
		{
			this.elements.video.classList.toggle('bx-messenger-videocall-video-blurred');
		}
	};

	getStateMessage(userState, videoPaused)
	{
		switch (userState)
		{
			case UserState.Idle:
				return "";
			case UserState.Calling:
				return BX.message("IM_M_CALL_STATUS_WAIT_ANSWER");
			case UserState.Declined:
				return BX.message("IM_M_CALL_STATUS_DECLINED");
			case UserState.Ready:
			case UserState.Connecting:
				return BX.message("IM_M_CALL_STATUS_WAIT_CONNECT");
			case UserState.Connected:
				return videoPaused ? BX.message("IM_M_CALL_STATUS_VIDEO_PAUSED") : "";
			case UserState.Failed:
				return BX.message("IM_M_CALL_STATUS_CONNECTION_ERROR");
			case UserState.Unavailable:
				return BX.message("IM_M_CALL_STATUS_UNAVAILABLE");
			default:
				return "";
		}
	};

	mount(parent, force)
	{
		force = force === true;
		if (!this.elements.root)
		{
			this.render();
		}

		if (this.isMounted() && this.elements.root.parentElement == parent && !force)
		{
			this.updatePanelDeferred();
			return false;
		}

		parent.appendChild(this.elements.root);
		this.update();
	};

	dismount()
	{
		// this.visible = false;
		if (!this.isMounted())
		{
			return false;
		}

		this.elements.video.srcObject = null;
		this.elements.preview.srcObject = null;
		Dom.remove(this.elements.root);
	};

	isMounted()
	{
		return !!(this.elements.root && this.elements.root.parentElement);
	};

	updateState()
	{
		if (!this.elements.root)
		{
			return;
		}

		if (this.userModel.state == UserState.Calling || this.userModel.state == UserState.Connecting)
		{
			this.updateAvatarPulseState();
		}
		else
		{
			this.addAvatarPulseTimer();
		}

		if (this.userModel.state == UserState.Idle)
		{
			this._videoRenderer = null;
			this._previewRenderer = null;
			this._audioTrack = null;
			this._audioStream = null;
		}

		this.elements.state.innerText = this.getStateMessage(this.userModel.state, this.userModel.videoPaused);

		this.updateMicrophoneState()
		this.updateCameraState()

		this.update();
	};

	updateTalking()
	{
		if (!this.elements.root)
		{
			return;
		}
		if (this.userModel.talking)
		{
			this.updateAvatarPulseState();
		}
		else
		{
			this.addAvatarPulseTimer();
		}
	};

	updateMicrophoneState()
	{
		if (!this.elements.root)
		{
			return;
		}
		if (!this.isVisibleMicStateIcon())
		{
			this.elements.micState.classList.add("hidden");
		}
		else
		{
			this.elements.micState.classList.remove("hidden");
			this.clearAvatarPulseTimer();
		}

		if (this.isVisibleCameraStateIcon() && this.isVisibleMicStateIcon())
		{
			this.elements.nameContainer.classList.add("extra-padding");
		}
		else
		{
			this.elements.nameContainer.classList.remove("extra-padding");
		}
	};

	updateCameraState()
	{
		if (!this.elements.root)
		{
			return;
		}
		if (!this.isVisibleCameraStateIcon())
		{
			this.elements.cameraState.classList.add("hidden");
		}
		else
		{
			this.elements.cameraState.classList.remove("hidden");
		}

		if (this.isVisibleCameraStateIcon() && this.isVisibleMicStateIcon())
		{
			this.elements.nameContainer.classList.add("extra-padding");
		}
		else
		{
			this.elements.nameContainer.classList.remove("extra-padding");
		}
	};

	updateVideoPaused()
	{
		if (!this.elements.root)
		{
			return;

		}
		if (this.stream && this.hasVideo())
		{
			this.blurVideo(this.userModel.videoPaused);
		}
		this.updateState();
	};

	updateFloorRequestState()
	{
		if (!this.elements.floorRequest)
		{
			return;
		}
		if (this.userModel.floorRequestState)
		{
			this.elements.floorRequest.classList.add("active");
		}
		else
		{
			this.elements.floorRequest.classList.remove("active");
		}
	};

	updateScreenState()
	{
		if (!this.elements.video)
		{
			return;
		}
		if (this.userModel.screenState)
		{
			this.elements.video.classList.add("bx-messenger-videocall-video-contain");
		}
		else
		{
			this.elements.video.classList.remove("bx-messenger-videocall-video-contain");
		}
	};

	hide()
	{
		if (!this.elements.root)
		{
			return;
		}

		this.elements.root.dataset.hidden = 1;
	};

	show()
	{
		if (!this.elements.root)
		{
			return;
		}

		delete this.elements.root.dataset.hidden;
	};

	hasVideo()
	{
		return this.userModel.state == UserState.Connected && (!!this._videoTrack || !!this._videoRenderer);
	};

	hasCameraVideo()
	{
		return this.userModel.state == UserState.Connected && (!!this._videoTrack || this._videoRenderer?.kind === 'video' || this._previewRenderer?.kind === 'video');
	}

	checkVideoAspect()
	{
		if (!this.elements.video)
		{
			return;
		}

		if (this.elements.video.videoHeight > this.elements.video.videoWidth)
		{
			this.elements.video.classList.add("bx-messenger-videocall-video-vertical");
		}
		else
		{
			this.elements.video.classList.remove("bx-messenger-videocall-video-vertical");
		}
	};

	releaseStream()
	{
		if (this._videoRenderer && !this._previewRenderer)
		{
			if (this.elements.video)
			{
				this.elements.video.srcObject = null;
			}
			this._videoRenderer = null;
		}
		else
		{
			if (this.elements.video)
			{
				this.elements.video.srcObject = null;
			}
			this.videoTrack = null;
		}
	};

	_getVideoStats(videoStats) {
		let resultString = '';
		let limitationsString = '';

		resultString += `Bitrate: ${videoStats?.bitrate || 0}\n`;
		resultString += `PacketsLost: ${videoStats?.packetsLostExtended || 0}\n`;
		resultString += `Codec: ${videoStats?.codecName || '-'}\n`;
		resultString += `Resolution: ${videoStats?.frameWidth || 0}x${videoStats?.frameHeight || 0} `;

		if (videoStats?.qualityLimitationReason)
		{
			resultString += `(changes:${videoStats.qualityLimitationResolutionChanges}, FPS: ${videoStats?.framesPerSecond || 0})`;
			limitationsString = `Limitation: ${videoStats.qualityLimitationReason}`;
			limitationsString += ` (duration: ${Object.entries(videoStats.qualityLimitationDurations || {}).reduce(
				(accumulator, value, index) => accumulator + `${index ? ', ' : ''}` + `${value[0]}: ${value[1]}`,
				'',
			)})`;
		}
		else
		{
			resultString += `(${videoStats?.framesPerSecond || 0} FPS)`;
		}

		return ({
			resultString,
			limitationsString,
		})
	}

	_formatVideoStats(videoStats)
	{
		let result = '';
		let limitations = '';
		if (Type.isArray(videoStats))
		{
			videoStats.forEach((stats, trackIndex) =>
			{
				if (trackIndex)
				{
					result += `\n\n`;
				}

				if (videoStats.length > 1)
				{
					result += `Track ${trackIndex+1}\n`;
				}

				const {resultString, limitationsString} = this._getVideoStats(stats);

				if (resultString)
				{
					result += resultString;
				}

				if (limitationsString)
				{
					limitations = limitationsString;
				}
			});
		}
		else
		{
			const {resultString, limitationsString} = this._getVideoStats(videoStats);

			if (resultString)
			{
				result += resultString;
			}

			if (limitationsString)
			{
				limitations = limitationsString;
			}
		}

		return limitations ? `${limitations}\n\n${result}` : result;
	};

	showUserName()
	{
		if (this.hideUserNameTimer)
		{
			clearTimeout(this.hideUserNameTimer);
			this.hideUserNameTimer = null;
		}

		if (this.elements.nameContainer)
		{
			this.elements.nameContainer.classList.add('active');
			this.hideUserNameTimer = setTimeout(() => {
				this.elements.nameContainer.classList.remove('active')
			}, 5000)
		}
	};

	destroy()
	{
		if (this.hintManager)
		{
			this.hintManager.hide();
			this.hintManager = null;
		}
		this.releaseStream();
		clearInterval(this.checkAspectInterval);

		document.removeEventListener('mousemove', this.onMouseMoveHandler);
	};

	addAvatarPulseTimer()
	{
		if (this.removeAvatarPulseTimer)
		{
			clearTimeout(this.removeAvatarPulseTimer);
			this.removeAvatarPulseTimer = null;
		}

		this.removeAvatarPulseTimer = setTimeout(() => {
			this.elements.avatarContainer.classList.remove("bx-messenger-videocall-user-avatar-pulse");
			this.elements.root.classList.remove("bx-messenger-videocall-user-talking");
		}, 1000);
	}

	clearAvatarPulseTimer()
	{
		if (this.removeAvatarPulseTimer)
		{
			clearTimeout(this.removeAvatarPulseTimer);
			this.removeAvatarPulseTimer = null;
		}

		this.elements.avatarContainer.classList.remove("bx-messenger-videocall-user-avatar-pulse");
		this.elements.root.classList.remove("bx-messenger-videocall-user-talking");
	}

	updateAvatarPulseState()
	{
		if (this.removeAvatarPulseTimer)
		{
			clearTimeout(this.removeAvatarPulseTimer);
			this.removeAvatarPulseTimer = null;
		}

		this.elements.avatarContainer.classList.add("bx-messenger-videocall-user-avatar-pulse");

		if (this.userModel.talking)
		{
			this.elements.root.classList.add("bx-messenger-videocall-user-talking");
		}
	}
}

