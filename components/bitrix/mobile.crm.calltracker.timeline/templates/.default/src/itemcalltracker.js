import Item from './item';
import {Loc, Runtime, Tag, Text, Type} from "main.core";
import Utils from "./utils";
import 'ui.vue.components.audioplayer';
import {Vue} from 'ui.vue';

function formatDuration(timestamp) {
	return timestamp;
}

export default class ItemCalltracker extends Item
{
	getOwnerId()
	{
		return [this.data['ASSOCIATED_ENTITY']['TYPE_ID'], this.data['ASSOCIATED_ENTITY']['ID']].join('_');
	}
	getNode()
	{
		return this.cache.remember('mainNode', () => {
			const direction = parseInt(this.data['ASSOCIATED_ENTITY']['DIRECTION']);
			const rawDuration = parseInt(this.data['ASSOCIATED_ENTITY']['SETTINGS']['DURATION']);
			const hasDuration = (rawDuration > 0);
			const duration = Utils.formatInterval(rawDuration);

			const created = Text.encode(this.data['CREATED']);
			const comment = Loc.getMessage('MPL_CALL_IS_PROCESSED');

			const hasStatus = this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? this.data['ASSOCIATED_ENTITY']['CALL_INFO']['HAS_STATUS'] : false;
			const status = this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? this.data['ASSOCIATED_ENTITY']['CALL_INFO']['SUCCESSFUL'] : null;
			const iconClasses = [
				(direction === BX.CrmActivityDirection.incoming
					? 'ui-icon-service-call-in' : (
						direction === BX.CrmActivityDirection.outgoing
							? 'ui-icon-service-call-out' : 'ui-icon-service-callback')),
			];

			const render = Tag.render`
			<div class="feed-com-block-cover crm-phonetracker-notification">
				<div class="post-comment-block post-comment-block-old post-comment-block-approved  mobile-longtap-menu ">
					<div class="ui-icon ${iconClasses.join(' ')} crm-phonetracker-icon">
						<i></i>
						${(hasStatus && status !== true) ?
							`<div class="ui-icon-cross">
								<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M7.19252 5.532L10.7451 9.08457L9.08463 10.745L5.53206 7.19246L1.91046 10.8141L0.25 9.15361L3.87161 5.532L0.319037 1.97943L1.97949 0.318976L5.53206 3.87155L9.15367 0.249939L10.8141 1.91039L7.19252 5.532Z" fill="#767C87"/>
								</svg>
							</div>` : ''
						}
					</div>
					<div class="post-comment-detail">
						<div class="post-comment-balloon">
							<div class="post-comment-cont">
								<span class="post-comment-author crm-phonetracker-event-name">
									${comment}
								</span>
								<div class="post-comment-time">${created}</div>
							</div>
							<div class="post-comment-wrap-outer">
								${
									hasDuration
										? Tag.render`
											<div class="post-comment-wrap">
												<div class="post-comment-text">${duration}</div>
											</div>`
										: ''
								}
								<div class="post-label-wrap">
									<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M12.5615 10.4593L14.0291 11.559C14.4726 11.8959 14.507 12.5616 14.0978 12.9414C12.5634 14.4077 10.0067 14.977 6.64094 11.4185C3.27513 7.86011 3.94451 5.31279 5.47892 3.8464C5.88763 3.46681 6.53873 3.52962 6.8498 3.99195L7.87625 5.49314C8.23575 6.04419 8.00868 6.79498 7.45713 7.16309L6.84098 7.57434C6.6994 7.66453 6.65996 7.85351 6.73593 7.99833C7.53276 9.39692 8.68397 10.6226 10.0304 11.5009C10.1646 11.5784 10.3575 11.5383 10.4511 11.4106L10.8862 10.7938C11.2666 10.2563 12.0403 10.0677 12.5615 10.4593Z" fill="white"/>
										<path d="M13.8358 4.26291C12.8706 3.2977 11.577 2.7646 10.1993 2.77788C9.9525 2.79346 9.7557 2.99026 9.75345 3.22376C9.76427 3.47033 9.95731 3.66337 10.1908 3.66111C11.3472 3.63671 12.4084 4.0686 13.2193 4.8795C14.0303 5.69041 14.4621 6.75159 14.4372 7.90855C14.436 8.0385 14.4867 8.14155 14.5641 8.21887C14.6415 8.29628 14.7579 8.33373 14.875 8.34529C15.1218 8.32972 15.3186 8.13292 15.3209 7.89942C15.3348 6.52238 14.801 5.22812 13.8358 4.26291Z" fill="white"/>
										<path d="M12.5749 5.52404C12.0088 4.95793 11.258 4.65303 10.4524 4.66131C10.2056 4.67688 10.0088 4.87368 10.0065 5.10718C10.0174 5.35375 10.2104 5.54679 10.4439 5.54453C11.0155 5.53902 11.5458 5.7547 11.945 6.15386C12.3436 6.5525 12.5598 7.08336 12.5543 7.65492C12.553 7.78487 12.6038 7.88792 12.6812 7.96534L12.7231 8.00097C12.7976 8.05494 12.8946 8.08326 12.9923 8.0929C13.2391 8.07732 13.4359 7.88052 13.4381 7.64702C13.4459 6.84088 13.141 6.09014 12.5749 5.52404Z" fill="white"/>
									</svg>
									<div class="post-label-text">${Text.encode(this.data['ASSOCIATED_ENTITY']['CREATED'])}</div>
								</div>
							</div>
						</div>
						${this.getFilesNode()}
						${this.getActionNode()}
					</div>
				</div>
			</div>
			`;
			return render;
		});
	}
	getFilesNode() {
		return this.cache.remember('filesBlock', () => {
			if (!this.data['ASSOCIATED_ENTITY']['MEDIA_FILE_INFO'])
			{
				return '';
			}

			const renderTag = Tag.render`<div class="post-item-attached-audio"></div>`;

			Vue.create({
				el: renderTag.appendChild(document.createElement('DIV')),
				template: `<bx-audioplayer src="${this.data['ASSOCIATED_ENTITY']['MEDIA_FILE_INFO']['URL']}" background="dark"/>`
			});

			return Tag.render`<div class="post-item-attached-file-wrap">
				<div class="post-item-attached-file-wrap">
					<div class="post-item-attached-file-list">
						${renderTag}
					</div>
				</div>
			</div>`;
		});
	}

	static checkForPaternity(data)
	{
		return data['TYPE_CODE'] === 'CALL_TRACKER' && !!data['ASSOCIATED_ENTITY'];
	}
}