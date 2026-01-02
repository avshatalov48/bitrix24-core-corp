/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_popup,main_core,ui_system_skeleton) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const Skeleton = () => main_core.Tag.render(_t || (_t = _`
	<div class="task-skeleton" style="padding: 24px">
		<div class="--title --row">
			${0}
			<div class="--fire">${0}</div>
			${0}
		</div>
		<div class="--description">${0}</div>
		<div style="margin-bottom: 17px">${0}</div>
		${0}
		<div class="--chips">${0}</div>
		<div class="--buttons --row">
			${0}
			<div class="--cancel">${0}</div>
			${0}
		</div>
	</div>
`), line(240, 18, 99), circle(), circle(), line(80, 12, 99), FieldRow(), FieldRow(), line(null, 32), line(84, 34), line(84, 34), line(97, 12, 99));
	const FieldRow = () => main_core.Tag.render(_t2 || (_t2 = _`
	<div class="--row">
		${0}
		<div style="margin: 0 8px 0 41px">${0}</div>
		${0}
	</div>
`), line(100, 12, 99), circle(22), line(130, 12, 99));
	const FullSkeleton = () => main_core.Tag.render(_t3 || (_t3 = _`
	<div class="task-skeleton --full">
		<div class="--main">
			<div style="padding: 28px 24px">
				<div class="--full-title --row">
					${0}
					${0}
				</div>
				<div class="--full-description">${0}</div>
				${0}
				<div style="margin: 12px 0">${0}</div>
				${0}
				<div class="--full-chips --row">
					${0}
					${0}
					${0}
				</div>
			</div>
			<div class="--row --footer">
				${0}
				<div class="--more">${0}</div>
				${0}
			</div>
		</div>
		<div class="--chat">
			<div class="--chat-title --row">
				${0}
				<div class="--chat-info">
					${0}
					${0}
				</div>
				${0}
				<div style="margin-left: 8px">${0}</div>
			</div>
			<div class="--chat-bg">
				<div class="--textarea --row">
					${0}
					${0}
				</div>
			</div>
		</div>
	</div>
`), line(350, 18), circle(), line(260, 18), line(null, 84), line(null, 84), line(null, 84), line(88, 32), line(88, 32), line(88, 32), line(85, 38), line(38, 38), line(131, 22), circle(40), line(110, 12), line(75, 10), circle(), circle(), line(null, 47), circle(44));
	const line = ui_system_skeleton.Line;
	const circle = ui_system_skeleton.Circle;

	class TaskCard {
	  static showCompactCard(params = {}) {
	    const id = `tasks-compact-card-${params.taskId}`;
	    if (main_popup.PopupManager.getPopupById(id)) {
	      return;
	    }

	    // eslint-disable-next-line init-declarations
	    let card;
	    const popup = new main_popup.Popup({
	      id,
	      className: 'tasks-compact-card-popup',
	      width: 580,
	      borderRadius: '16px',
	      noAllPaddings: true,
	      content: Skeleton(),
	      cacheable: false,
	      events: {
	        onAfterClose: () => card.unmountCard()
	      },
	      overlay: {
	        opacity: 100,
	        backgroundColor: '#0363',
	        blur: 'blur(2px)'
	      }
	    });
	    void BX.Runtime.loadExtension('tasks.v2.application.task-compact-card').then(({
	      TaskCompactCard
	    }) => {
	      card = new TaskCompactCard({
	        analytics: {},
	        ...params
	      });
	      card.mountCard(popup);
	    });
	    popup.show();
	  }
	  static showFullCard(params = {}) {
	    // eslint-disable-next-line init-declarations
	    let card;
	    BX.SidePanel.Instance.open(`tasks-full-card-${params.taskId}`, {
	      contentClassName: 'tasks-full-card-slider-content',
	      width: 1510 - (params.widthOffset || 0),
	      customLeftBoundary: 0,
	      cacheable: false,
	      contentCallback: slider => {
	        void top.BX.Runtime.loadExtension('tasks.v2.application.task-full-card').then(({
	          TaskFullCard
	        }) => {
	          card = new TaskFullCard({
	            analytics: {},
	            ...params
	          });
	          card.mountCard(slider);
	        });
	        return FullSkeleton();
	      },
	      events: {
	        onClose: () => {
	          var _card;
	          return (_card = card) == null ? void 0 : _card.unmountCard();
	        }
	      }
	    });
	  }
	}

	exports.FullSkeleton = FullSkeleton;
	exports.TaskCard = TaskCard;

}((this.BX.Tasks.V2.Application = this.BX.Tasks.V2.Application || {}),BX.Main,BX,BX.UI.System));
//# sourceMappingURL=task-card.bundle.js.map
