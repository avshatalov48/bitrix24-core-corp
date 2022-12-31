import {HistoryCall} from "../actions/call";
import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Call extends HistoryActivity
{
	constructor()
	{
		super();
		this._playerDummyClickHandler = BX.delegate(this.onPlayerDummyClick, this);
		this._playerWrapper = null;
		this._transcriptWrapper = null;
		this._mediaFileInfo = null;
	}

	getTypeDescription()
	{
		const entityData = this.getAssociatedEntityData();
		const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
		const callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
		if(callTypeText !== "")
		{
			return callTypeText;
		}

		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());

		//Position is important
		const entityData = this.getAssociatedEntityData();
		const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
		const hasCallInfo = callInfo !== null;
		const isSuccessfull = hasCallInfo ? BX.prop.getBoolean(callInfo, "SUCCESSFUL", false) : false;
		const statusText = hasCallInfo ? BX.prop.getString(callInfo, "STATUS_TEXT", "") : "";

		if(hasCallInfo && statusText.length)
		{
			header.appendChild(
				BX.create("DIV",
					{
						attrs:
							{
								className: isSuccessfull
									? "crm-entity-stream-content-event-successful"
									: "crm-entity-stream-content-event-missing"
							},
						text: statusText
					}
				)
			);
		}

		header.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		);

		return header;
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();

		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const communicationTitle = BX.prop.getString(communication, "TITLE", "");
		const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		const communicationValue = BX.prop.getString(communication, "VALUE", "");
		const communicationValueFormatted = BX.prop.getString(communication, "FORMATTED_VALUE", communicationValue);

		const callInfo = BX.prop.getObject(entityData, "CALL_INFO", null);
		const hasCallInfo = callInfo !== null;
		const durationText = hasCallInfo ? BX.prop.getString(callInfo, "DURATION_TEXT", "") : "";
		const hasTranscript = hasCallInfo ? BX.prop.getBoolean(callInfo, "HAS_TRANSCRIPT", "") : "";
		const isTranscriptPending = hasCallInfo ? BX.prop.getBoolean(callInfo, "TRANSCRIPT_PENDING", "") : "";
		const callId = hasCallInfo ? BX.prop.getString(callInfo, "CALL_ID", "") : "";
		const callComment = hasCallInfo ? BX.prop.getString(callInfo, "COMMENT", "") : "";

		const outerWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-call"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-call" }
				}
			)
		);

		if (this.isFixed())
			BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');

		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		const header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"}
			}
		);
		wrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareMultilineCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		if(hasCallInfo)
		{
			const callInfoWrapper = BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"}
				}
			);
			detailWrapper.appendChild(callInfoWrapper);

			this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
			if(this._mediaFileInfo !== null)
			{
				this._playerWrapper = this._history.getManager().renderAudioDummy(durationText, this._playerDummyClickHandler);
				callInfoWrapper.appendChild(
					this._playerWrapper
				);
				callInfoWrapper.appendChild(this._history.getManager().getAudioPlaybackRateSelector().render());
			}

			if(hasTranscript)
			{
				this._transcriptWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-audio-transcript-wrap-container"},
						events: {
							click: function(e)
							{
								if(BX.Voximplant && BX.Voximplant.Transcript)
								{
									BX.Voximplant.Transcript.create({
										callId: callId
									}).show();
								}
							}
						},
						children: [
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-icon"}	}),
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-conversation"}, text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT") } )
						]
					}
				);
				callInfoWrapper.appendChild(this._transcriptWrapper);
			}
			else if(isTranscriptPending)
			{
				this._transcriptWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-audio-transcript-wrap-container-pending"},
						children: [
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-icon-pending"}, html: '<svg class="crm-transcript-loader-circular" viewBox="25 25 50 50"><circle class="crm-transcript-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"></circle></svg>'}),
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-conversation"}, text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING") } )
						]
					}
				);
				callInfoWrapper.appendChild(this._transcriptWrapper);
			}

			if(callComment)
			{
				detailWrapper.appendChild(BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-description"},
						text: callComment
					}
				));
			}
		}
		const communicationWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail-contact-info"}
			}
		);
		detailWrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(communicationValueFormatted !== "")
		{
			if(communicationTitle !== "")
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: " " }));
			}
			communicationWrapper.appendChild(
				BX.create(
					"SPAN",
					{
						attrs: { className: "crm-entity-stream-content-detail-email-address" },
						text: communicationValueFormatted
					}
				)
			);
		}

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		wrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly()) {

			wrapper.appendChild(this.prepareFixedSwitcherLayout());
		}


		return outerWrapper;
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			HistoryCall.create(
				"call",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._history.getOwnerInfo()
				}
			)
		);
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		const messageName = (direction === BX.CrmActivityDirection.incoming) ? 'incomingCallRemove' : 'outgoingCallRemove';
		const title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage(messageName).replace("#TITLE#", title);
	}

	onPlayerDummyClick(e)
	{
		const stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
		if(stubNode)
		{
			BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
		}

		this._history.getManager().getAudioPlaybackRateSelector().addPlayer(this._history.getManager().loadMediaPlayer(
			"history_" + this.getId(),
			this._mediaFileInfo["URL"],
			this._mediaFileInfo["TYPE"],
			this._playerWrapper,
			this._mediaFileInfo["DURATION"],
			{
				playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
			}
		));
	}

	static create(id, settings)
	{
		const self = new Call();
		self.initialize(id, settings);
		return self;
	}
}
