import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Actions */
export default class Zoom extends HistoryActivity
{
	constructor()
	{
		super();

		this._videoDummy = null;
		this._audioDummy = null;
		this._videoPlayer = null;
		this._audioPlayer = null;
		this._audioLengthElement = null;
		this._recordings = [];
		this._currentRecordingIndex = 0;
		this.zoomActivitySubject = null;

		this._downloadWrapper = null;
		this._downloadSubject = null;
		this._downloadSubjectDetail = null;
		this._downloadVideoLink = null;
		this._downloadSeparator = null;
		this._downloadAudioLink = null;
		this._playVideoLink = null;
		this.detailZoomCopyVideoLink = null;
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		if (!this._data.hasOwnProperty('PROVIDER_DATA') || this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] !== 'ZOOM_CONF_JOINED')
		{
			header.appendChild(this.prepareSuccessfulLayout());
		}
		header.appendChild(this.prepareTimeLayout());

		return header;
	}

	prepareSuccessfulLayout()
	{
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-successful"},
			text: BX.message('CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY')
		});
	}

	prepareTitleLayout()
	{
		if (this._data.hasOwnProperty('PROVIDER_DATA') && this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] === 'ZOOM_CONF_JOINED')
		{
			return BX.create("SPAN", {
				attrs:{ className: "crm-entity-stream-content-event-title"},
				text: BX.message('CRM_TIMELINE_ZOOM_JOINED_CONFERENCE')
			});
		}
		else
		{
			return BX.create("SPAN", {
				attrs:{ className: "crm-entity-stream-content-event-title"},
				text: BX.message('CRM_TIMELINE_ZOOM_CONFERENCE_END')
			});
		}
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history"}});
		let entityDetailWrapper;
		const zoomData = BX.prop.getObject(this.getAssociatedEntityData(), "ZOOM_INFO", null);
		const subject = BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", null);

		this._recordings = BX.prop.getArray(zoomData, "RECORDINGS", []);

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-zoom" } })
		);

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		const header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		const detailWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail"}});
		contentWrapper.appendChild(detailWrapper);

		if (this._data.hasOwnProperty('PROVIDER_DATA') && this._data["PROVIDER_DATA"]["ZOOM_EVENT_TYPE"] === 'ZOOM_CONF_JOINED')
		{
			entityDetailWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					text: zoomData['CONF_URL']
				}
			);
		}
		else
		{
			entityDetailWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
				}
			);

			if (this._recordings.length > 0)
			{
				if (this._recordings.length > 1)
				{
					//render video parts header

					const tabs = this._recordings.map(function (recording, index) {
						return {
							id: index,
							title: BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD_PART").replace("#NUMBER#", index + 1),
							time: recording["AUDIO"] ? recording["AUDIO"]["LENGTH_FORMATTED"] : "",
							active: index === 0
						}
					});
					const tabsComponent = new Zoom.TabsComponent({
						tabs: tabs,
					});
					tabsComponent.eventEmitter.subscribe("onTabChange", this._onTabChange.bind(this));
					detailWrapper.appendChild(tabsComponent.render());
				}

				this._videoDummy = BX.create("DIV",
					{
						props: { className: "crm-entity-stream-content-detail-zoom-video-wrap"},
						children: [
							BX.create("DIV",
								{
									props: { className: "crm-entity-stream-content-detail-zoom-video" },
									events:
										{
											click: this._onVideoDummyClick.bind(this)
										},
									children: [
										BX.create("DIV", {
											props: {className: "crm-entity-stream-content-detail-zoom-video-inner"},
											children: [
												BX.create("DIV", {
													props: {className: "crm-entity-stream-content-detail-zoom-video-btn"},
													dataset: {
														hint: BX.message("CRM_TIMELINE_ZOOM_LOGIN_REQUIRED"),
														'hintNoIcon': 'Y'
													}
												}),
												BX.create("SPAN", {
													props: {className: "crm-entity-stream-content-detail-zoom-video-text"},
													text: BX.message("CRM_TIMELINE_ZOOM_CLICK_TO_WATCH")
												})
											]
										})
									]
								}
							)
						]
					});

				BX.UI.Hint.init(this._videoDummy);

				this._audioDummy = this._history.getManager().renderAudioDummy("00:15", this._onAudioDummyClick.bind(this));
				this._audioLengthElement = this._audioDummy.querySelector('.crm-audio-cap-time');

				if (zoomData['RECORDINGS'][0]['VIDEO'])
				{
					//video download link with token valid for 24h
					const videoLinkExpireTS = (zoomData['RECORDINGS'][0]['VIDEO']['END_DATE_TS'] * 1000) + (60 * 60 * 23 * 1000);
					if (videoLinkExpireTS < Date.now())
					{
						const videoLinkContainer = BX.create("DIV", {
							props: {
								className: "crm-entity-stream-content-detail-zoom-desc",
							}
						});

						this._playVideoLink = BX.create("DIV", {
							html: BX.message("CRM_TIMELINE_ZOOM_PLAY_LINK_VIDEO"),
						});

						this._detailZoomCopyVideoLink = BX.create("A",
							{
								attrs: {
									className: 'ui-link ui-link-dashed',
								},
								text: BX.message("CRM_TIMELINE_ZOOM_COPY_PASSWORD")
							}
						);

						videoLinkContainer.appendChild(this._playVideoLink);
						videoLinkContainer.appendChild(this._detailZoomCopyVideoLink);
						entityDetailWrapper.appendChild(videoLinkContainer);
					}
					else
					{
						entityDetailWrapper.appendChild(this._videoDummy);
					}
				}
				if (zoomData['RECORDINGS'][0]['AUDIO'])
				{
					const zoomAudioDetailWrapper = BX.create("DIV",
						{
							attrs: {className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"},
						}
					);

					zoomAudioDetailWrapper.appendChild(this._audioDummy);
					zoomAudioDetailWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
					entityDetailWrapper.appendChild(zoomAudioDetailWrapper);
				}

				this._downloadWrapper = BX.create("DIV", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc"},
				});

				entityDetailWrapper.appendChild(this._downloadWrapper);

				this._downloadSubject = BX.create("SPAN", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc-subject"},
				});
				this._downloadSubjectDetail = BX.create("SPAN", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc-detail"},
				});
				this._downloadVideoLink = BX.create("A", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc-link"},
					text: BX.message("CRM_TIMELINE_ZOOM_DOWNLOAD_VIDEO")
				});
				this._downloadSeparator = BX.create("SPAN", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc-separate"},
					html: "&mdash;"
				});
				this._downloadAudioLink = BX.create("A", {
					props: {className: "crm-entity-stream-content-detail-zoom-desc-link"},
					text: BX.message("CRM_TIMELINE_ZOOM_DOWNLOAD_AUDIO")
				});
				this.setCurrentRecording(0);
			}
			else
			{
				this.zoomActivitySubject = BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-title" },
						children:
							[
								BX.create("A",
									{
										attrs: { href: "#" },
										events: { "click": this._headerClickHandler },
										text: subject
									}
								)
							]
					}
				);

				entityDetailWrapper.appendChild(this.zoomActivitySubject);

				if (zoomData['HAS_RECORDING'] === 'Y')
				{
					entityDetailWrapper.appendChild(BX.create("DIV", {
						props: {className: "crm-entity-stream-content-detail-zoom-video"},
						children: [
							BX.create("DIV", {
								props: {className: "crm-entity-stream-content-detail-zoom-video-inner"},
								children: [
									BX.create("DIV", {
										props: {className: "crm-entity-stream-content-detail-zoom-video-img"},
									}),
									BX.create("SPAN", {
										props: {className: "crm-entity-stream-content-detail-zoom-video-text"},
										text: BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD_IN_PROCESS")
									})
								]
							})
						]
					}));
				}
			}
		}
		/*else
		{
			detailWrapper.appendChild(BX.create("span", {text: "456"}));

			var entityDetailWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					text: BX.prop.getString(zoomData, "CONF_URL", "")
				}
			);
		}*/
		//Content //todo


		if(entityDetailWrapper)
		{
			detailWrapper.appendChild(entityDetailWrapper);
		}

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	}

	_onVideoDummyClick()
	{
		BX.UI.Hint.hide();
		const recording = this._recordings[this._currentRecordingIndex]["VIDEO"];
		if(!recording)
		{
			return;
		}
		this._videoPlayer = this._history.getManager().loadMediaPlayer(
			"zoom_video_" + this.getId(),
			recording["DOWNLOAD_URL"],
			"video/mp4",
			this._videoDummy,
			recording["LENGTH"],
			{
				video: true,
				skin: "",
				width: 480,
				height: 270
			}
		);
	}

	_onAudioDummyClick()
	{
		const recording = this._recordings[this._currentRecordingIndex]["AUDIO"];
		if(!recording)
		{
			return;
		}
		this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._audioPlayer = this._history.getManager().loadMediaPlayer(
			"zoom_audio_" + this.getId(),
			recording["DOWNLOAD_URL"],
			"audio/mp4",
			this._audioDummy,
			recording["LENGTH"],
			{
				playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
			}
		));
	}

	_onTabChange(event)
	{
		this.setCurrentRecording(event.data.tabId);
	}

	setCurrentRecording(recordingIndex)
	{
		this._currentRecordingIndex = recordingIndex;
		const videoRecording = this._recordings[this._currentRecordingIndex]["VIDEO"];
		const audioRecording = this._recordings[this._currentRecordingIndex]["AUDIO"];

		if(videoRecording)
		{
			this._videoDummy.hidden = false;
			if(this._videoPlayer)
			{
				this._videoPlayer.pause();
				this._videoPlayer.setSource(videoRecording["DOWNLOAD_URL"]);
				this._downloadVideoLink.href = videoRecording["DOWNLOAD_URL"];
			}
		}
		else
		{
			this._videoDummy.hidden = true;
		}

		if(audioRecording)
		{
			this._audioDummy.hidden = false;
			if(this._audioPlayer)
			{
				this._audioPlayer.pause();
				this._audioPlayer.setSource(audioRecording["DOWNLOAD_URL"]);
			}
			this._downloadAudioLink.href = audioRecording["DOWNLOAD_URL"];
			this._audioLengthElement.innerText = audioRecording["LENGTH_FORMATTED"];
		}
		else
		{
			this._audioDummy.hidden = true;
		}

		BX.clean(this._downloadWrapper);
		if(audioRecording || videoRecording)
		{
			const lengthHuman = audioRecording ? audioRecording["LENGTH_HUMAN"] : videoRecording["LENGTH_HUMAN"];
			this._downloadWrapper.appendChild(this._downloadSubject);
			this._downloadSubject.innerHTML = BX.util.htmlspecialchars(BX.message("CRM_TIMELINE_ZOOM_MEETING_RECORD").replace("#DURATION#", lengthHuman)) + " &mdash; "
			this._downloadWrapper.appendChild(this._downloadSubjectDetail);
		}
		if (videoRecording)
		{
			this._downloadSubjectDetail.appendChild(this._downloadVideoLink);
			this._downloadVideoLink.href = videoRecording['DOWNLOAD_URL'];
			if (audioRecording)
			{
				this._downloadSubjectDetail.appendChild(this._downloadSeparator);
			}

			if (this._playVideoLink)
			{
				this._playVideoLink.lastElementChild.href = videoRecording["PLAY_URL"];
				this._downloadVideoLink.href = videoRecording["PLAY_URL"];
			}
			if (this._detailZoomCopyVideoLink)
			{
				BX.clipboard.bindCopyClick(this._detailZoomCopyVideoLink, {
					text: videoRecording['PASSWORD'],
				});
			}
		}
		if (audioRecording)
		{
			this._downloadSubjectDetail.appendChild(this._downloadAudioLink);
			this._downloadAudioLink.href = audioRecording['DOWNLOAD_URL'];
		}
	}

	prepareActions()
	{
	}

	static create(id, settings)
	{
		const self = new Zoom();
		self.initialize(id, settings);

		//todo: remove debug
		if(!window['zoom'])
		{
			window['zoom'] = [];
		}
		window['zoom'].push(self);
		return self;
	}
}

Zoom.TabsComponent = class
{
	constructor(config)
	{
		this.tabs = BX.prop.getArray(config, "tabs", []);
		this.elements = {
			container: null,
			tabs: {}
		};
		this.eventEmitter = new BX.Event.EventEmitter(this, 'Zoom.TabsComponent');
	}

	render()
	{
		if(this.elements.container)
		{
			return this.elements.container;
		}

		this.elements.container = BX.create("DIV", {
			props: {className: "crm-entity-stream-content-detail-zoom-section-wrapper"},
			children: [
				BX.create("DIV", {
					props: {className: "crm-entity-stream-content-detail-zoom-section-list"},
					children: this.tabs.map(this._renderTab, this)
				})
			]
		});
		return this.elements.container;
	}

	_renderTab(tabDescription)
	{
		const tabId = tabDescription.id;
		this.elements.tabs[tabId] = BX.create("DIV", {
			props: {className: "crm-entity-stream-content-detail-zoom-section" + (tabDescription.active ? " crm-entity-stream-content-detail-zoom-section-active": "")},
			children: [
				BX.create("DIV", {
					props: {className: "crm-entity-stream-content-detail-zoom-section-inner"},
					children: [
						BX.create("DIV", {
							props: {className: "crm-entity-stream-content-detail-zoom-section-title"},
							text: tabDescription.title
						}),
						BX.create("DIV", {
							props: {className: "crm-entity-stream-content-detail-zoom-section-time"},
							text: tabDescription.time
						})
					]
				})
			],
			events: {
				click: function()
				{
					this.setActiveTab(tabDescription.id)
				}.bind(this)
			}
		});
		return this.elements.tabs[tabId];
	}

	setActiveTab(tabId)
	{
		if(!this.elements.tabs[tabId])
		{
			throw new Error ("Tab " + tabId + " is not found");
		}
		for (let id in this.elements.tabs)
		{
			if(!this.elements.tabs.hasOwnProperty(id))
			{
				continue;
			}
			id = Number.parseInt(id, 10);
			if (id === tabId)
			{
				this.elements.tabs[id].classList.add("crm-entity-stream-content-detail-zoom-section-active");
			}
			else
			{
				this.elements.tabs[id].classList.remove("crm-entity-stream-content-detail-zoom-section-active");
			}
		}
		this.eventEmitter.emit("onTabChange", {
			tabId: tabId
		});
	}
};
