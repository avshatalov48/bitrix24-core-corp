this.BX=this.BX||{},function(e,t,i,s,n,a,o,r){"use strict";class l{static send(e){t.sendData({tool:l.TOOLS,category:l.CATEGORY_INVITATION,event:e,p1:l.isAdmin?"isAdmin_Y":"isAdmin_N"})}static sendCreateCollab(){t.sendData({tool:"im",category:"collab",event:"click_create_new",c_section:l.SECTION_POPUP,p2:"user_intranet"})}}l.TOOLS="headerPopup",l.TOOLS_LEGACY="Invitation",l.CATEGORY_INVITATION="invitation",l.CATEGORY_INVITATION_LEGACY="invitation",l.EVENT_NAME_LEGACY="drawer_open",l.SECTION_POPUP="headerPopup",l.EVENT_SHOW="show",l.EVENT_OPEN_SLIDER_INVITATION="drawer_open",l.EVENT_OPEN_STRUCTURE="vis_structure_open",l.EVENT_OPEN_USER_LIST="company_open",l.EVENT_OPEN_SLIDER_EXTRANET_INVITATION="extranetinvitation_open",l.isAdmin=!1;class c extends a.EventEmitter{constructor(e){super(),this.cache=new o.Cache.MemoryCache,this.setOptions(e),this.analytics=new l}setOptions(e){this.cache.set("options",{...e})}getOptions(){return this.cache.get("options",{})}getLayout(){throw new Error("Must be implemented in a child class")}showInfoHelper(e){BX.UI.InfoHelper.show(e),this.sendAnalytics(e)}sendAnalytics(e){o.ajax.runAction("intranet.invitationwidget.analyticsLabel",{data:{},analyticsLabel:{helperCode:e,headerPopup:"Y"}})}getHintPopup(e,t,i){return this.cache.remember(i,()=>new n.Popup("bx-hint-"+o.Text.getRandom(),t,{content:e,className:"bx-invitation-warning",zIndex:15e3,angle:!0,offsetTop:0,offsetLeft:40,closeIcon:!1,autoHide:!0,darkMode:!0,overlay:!1,maxWidth:300,events:{onShow:e=>{a.EventEmitter.emit(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.InvitationWidget.HintPopup:show",new a.BaseEvent({data:{popup:e.target}}));const t=setTimeout(()=>{e.target.close()},4e3);a.EventEmitter.subscribeOnce(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.InvitationWidget.HintPopup:close",()=>{clearTimeout(t)})},onClose:()=>{a.EventEmitter.emit(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.InvitationWidget.HintPopup:close")}}}))}showHintPopup(e,t,i){this.getHintPopup(e,t,i).toggle()}showInvitationPlace(e,t,i){this.getOptions().isInvitationAvailable?this.showInvitationSlider(i):this.showHintPopup(e,t,"hint-"+i)}showInvitationSlider(e){let t=this.getOptions().invitationLink;"extranet"===e?(t+="&firstInvitationBlock=extranet",l.send(l.EVENT_OPEN_SLIDER_EXTRANET_INVITATION)):l.send(l.EVENT_OPEN_SLIDER_INVITATION),BX.SidePanel.Instance.open(t,{cacheable:!1,allowChangeHistory:!1,width:1100})}getConfig(){return{html:this.getLayout()}}}let d,p=e=>e;class v extends c{constructor(e){super(e),this.setEventNamespace("BX.Intranet.InvitationWidget.InvitationContent"),this.setOptions(e)}getConfig(){return{html:this.getLayout(),backgroundColor:"#14bfd5"}}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(d||(d=p`
				<div data-id="bx-invitation-widget-content-invitation" class="intranet-invitation-widget-invite">
					<div class="intranet-invitation-widget-invite-main">
						<div class="intranet-invitation-widget-inner">
							<div class="intranet-invitation-widget-content">
								<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--invite"></div>
								<div class="intranet-invitation-widget-item-content">
									<div class="intranet-invitation-widget-item-name">
										<span>
											${0}
										</span>
									</div>
									<div class="intranet-invitation-widget-item-link">
										<span onclick="${0}" class="intranet-invitation-widget-item-link-text">
											${0}
										</span>
									</div>
								</div>
							</div>
							<a onclick="${0}" class="intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--invite"> 
								${0}
							</a>
						</div>
					</div>
				</div>
			`),o.Loc.getMessage("INTRANET_INVITATION_WIDGET_INVITE_EMPLOYEE"),()=>{this.showInfoHelper("limit_why_team_invites")},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_DESC"),e=>{e.stopPropagation(),this.showInvitationPlace(o.Loc.getMessage("INTRANET_INVITATION_WIDGET_DISABLED_TEXT_MSGVER_1"),e.target,"default-invitation")},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_INVITE")))}}let h,g=e=>e;var u=babelHelpers.classPrivateFieldLooseKey("getCounterWrapper"),b=babelHelpers.classPrivateFieldLooseKey("showCounter"),m=babelHelpers.classPrivateFieldLooseKey("getCounter"),E=babelHelpers.classPrivateFieldLooseKey("getCounterValue"),T=babelHelpers.classPrivateFieldLooseKey("onFirstWatchNewStructure");class I extends c{constructor(e){super(e),Object.defineProperty(this,T,{value:O}),Object.defineProperty(this,E,{value:N}),Object.defineProperty(this,m,{value:P}),Object.defineProperty(this,b,{value:w}),Object.defineProperty(this,u,{value:L}),this.setEventNamespace("BX.Intranet.InvitationWidget.StructureContent")}getConfig(){return babelHelpers.classPrivateFieldLooseBase(this,b)[b](),this.getOptions().shouldShowStructureCounter&&a.EventEmitter.subscribeOnce("HR.company-structure:first-popup-showed",babelHelpers.classPrivateFieldLooseBase(this,T)[T].bind(this)),{html:this.getLayout(),flex:3}}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(h||(h=g`
				<div data-id="bx-invitation-widget-content-structure" class="intranet-invitation-widget-item intranet-invitation-widget-item--company intranet-invitation-widget-item--active">
					<div class="intranet-invitation-widget-item-logo"></div>
					<div class="intranet-invitation-widget-item-content">
						<div class="intranet-invitation-widget-item-name">
							<span>
								${0}
							</span>
						</div>
						<a onclick="${0}" href="${0}" class="intranet-invitation-widget-item-btn"> 
							${0}
						</a>
					</div>
				</div>
			`),o.Loc.getMessage("INTRANET_INVITATION_WIDGET_STRUCTURE"),()=>{l.send(l.EVENT_OPEN_STRUCTURE)},this.getOptions().structureLink,o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EDIT")))}}function L(){return this.cache.remember("counter-wrapper",()=>this.getLayout().querySelector(".intranet-invitation-widget-item-name"))}function w(){babelHelpers.classPrivateFieldLooseBase(this,E)[E]()>0&&(o.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this,m)[m]().getContainer(),"invitation-structure-counter"),babelHelpers.classPrivateFieldLooseBase(this,m)[m]().renderTo(babelHelpers.classPrivateFieldLooseBase(this,u)[u]()))}function P(){return this.cache.remember("counter",()=>new s.Counter({value:babelHelpers.classPrivateFieldLooseBase(this,E)[E](),color:s.Counter.Color.DANGER}))}function N(){return this.getOptions().shouldShowStructureCounter?1:0}function O(){const e=babelHelpers.classPrivateFieldLooseBase(this,m)[m]().value;o.Type.isNumber(e)&&this.getOptions().shouldShowStructureCounter&&(this.getOptions().shouldShowStructureCounter=!1,babelHelpers.classPrivateFieldLooseBase(this,m)[m]().destroy(),this.cache.delete("counter"))}let _,y,C,B=e=>e;var A=babelHelpers.classPrivateFieldLooseKey("rightType"),H=babelHelpers.classPrivateFieldLooseKey("showCounter"),f=babelHelpers.classPrivateFieldLooseKey("onReceiveCounterValue"),F=babelHelpers.classPrivateFieldLooseKey("getCounter"),R=babelHelpers.classPrivateFieldLooseKey("getCounterWrapper");class S extends c{constructor(e){super(e),Object.defineProperty(this,R,{value:V}),Object.defineProperty(this,F,{value:M}),Object.defineProperty(this,f,{value:G}),Object.defineProperty(this,H,{value:x}),Object.defineProperty(this,A,{writable:!0,value:void 0}),this.setEventNamespace("BX.Intranet.InvitationWidget.EmployeesContent"),babelHelpers.classPrivateFieldLooseBase(this,H)[H]()}getConfig(){return{html:this.getLayout(),flex:5,sizeLoader:55}}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(_||(_=B`
				<div data-id="bx-invitation-widget-content-employees" class="intranet-invitation-widget-item intranet-invitation-widget-item--emp ${0}">
					<div class="intranet-invitation-widget-inner">
						<div class="intranet-invitation-widget-content">
							<div class="intranet-invitation-widget-item-content">
								<div onclick="${0}" class="intranet-invitation-widget-item-progress ${0}"/>
								<div class="intranet-invitation-widget-employees">
									<div onclick="${0}" class="intranet-invitation-widget-item-name">
										<span style="margin-right: 2px;">
											${0}
										</span>
									</div>
									<div onclick="${0}" class="intranet-invitation-widget-item-num">
										${0}
									</div>
								</div>
							</div>
							${0}
							${0}
						</div>
					</div>
				</div>
			`),this.getOptions().isLimit?"intranet-invitation-widget-item--emp-alert":null,this.showUserList(),this.getOptions().isLimit?"intranet-invitation-widget-item-progress--crit":"intranet-invitation-widget-item-progress--full",this.showUserList(),o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EMPLOYEES"),this.showUserList(),this.getOptions().users.currentUserCountMessage,this.getDetail(),this.getOptions().isAdmin?this.getSelectorRights():null))}getDetail(){return this.cache.remember("detail",()=>{let e="";return e=0===Number(this.getOptions().users.maxUserCount)?o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EMPLOYEES_NO_LIMIT"):this.getOptions().isLimit?o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EMPLOYEES_LIMIT"):this.getOptions().users.leftCountMessage,o.Tag.render(y||(y=B`
				<div onclick="${0}" class="intranet-invitation-widget-item-detail">
					<span class="intranet-invitation-widget-item-link-text">
						${0}
					</span>
				</div>
			`),this.showUserList(),e)})}showUserList(){return this.cache.remember("showUserList",()=>()=>{l.send(l.EVENT_OPEN_USER_LIST),document.location.href="/company/"})}getSelectorRights(){return this.cache.remember("selector-rights",()=>{const e=o.Tag.render(C||(C=B`
				<div onclick="${0}" class="intranet-invitation-widget-item-menu"></div>
			`),e=>{e.stopPropagation(),this.getRightsMenu(e.target).toggle()});return this.subscribe("right-selected",t=>{const i=this.getRightsMenu(e);i.close(),i.destroy(),t.data.type&&(this.cache.delete("menu-rights"),babelHelpers.classPrivateFieldLooseBase(this,A)[A]=t.data.type)}),e})}getRightsMenu(e){return this.cache.remember("menu-rights",()=>new n.Menu("menu-rights-"+o.Text.getRandom(),e,this.getMenuRightsItems(),{autoHide:!0,offsetLeft:10,offsetTop:0,angle:!0,className:"license-right-popup-men",events:{onPopupShow:e=>{a.EventEmitter.emit(a.EventEmitter.GLOBAL_TARGET,this.getEventNamespace()+":showRightMenu",new a.BaseEvent({data:{popup:e}}))},onPopupClose:e=>{a.EventEmitter.emit(a.EventEmitter.GLOBAL_TARGET,this.getEventNamespace()+":closeRightMenu",new a.BaseEvent({data:{popup:e}}))},onPopupFirstShow:e=>{a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"SidePanel.Slider:onOpenStart",()=>{e.close()})}}}))}getMenuRightsItems(){return babelHelpers.classPrivateFieldLooseBase(this,A)[A]||(babelHelpers.classPrivateFieldLooseBase(this,A)[A]=this.getOptions().users.rightType),[{text:o.Loc.getMessage("INTRANET_INVITATION_WIDGET_SETTING_ALL_INVITE"),className:"all"===babelHelpers.classPrivateFieldLooseBase(this,A)[A]?"menu-popup-item-accept":"",onclick:()=>{this.saveInvitationRightSetting("all").then(()=>{this.emit("right-selected",new a.BaseEvent({data:{type:"all"}}))})}},{text:o.Loc.getMessage("INTRANET_INVITATION_WIDGET_SETTING_ADMIN_INVITE"),className:"admin"===babelHelpers.classPrivateFieldLooseBase(this,A)[A]?"menu-popup-item-accept":"",onclick:()=>{this.saveInvitationRightSetting("admin").then(()=>{this.emit("right-selected",new a.BaseEvent({data:{type:"admin"}}))})}}]}saveInvitationRightSetting(e){return o.ajax.runAction("intranet.invitationwidget.saveInvitationRight",{data:{type:e}})}}function x(){this.getOptions().invitationCounter>0&&babelHelpers.classPrivateFieldLooseBase(this,F)[F]().renderTo(babelHelpers.classPrivateFieldLooseBase(this,R)[R]()),BX.addCustomEvent("onPullEvent-main",babelHelpers.classPrivateFieldLooseBase(this,f)[f].bind(this))}function G(e,t){if("user_counter"===e&&t[BX.message("SITE_ID")]){const e=BX.clone(t[BX.message("SITE_ID")])[this.getOptions().counterId];if(!o.Type.isNumber(e))return;babelHelpers.classPrivateFieldLooseBase(this,F)[F]().update(e),this.getOptions().invitationCounter=e,e>0?babelHelpers.classPrivateFieldLooseBase(this,F)[F]().renderTo(babelHelpers.classPrivateFieldLooseBase(this,R)[R]()):(babelHelpers.classPrivateFieldLooseBase(this,F)[F]().destroy(),this.cache.delete("counter"))}}function M(){return this.cache.remember("counter",()=>new s.Counter({value:Number(this.getOptions().invitationCounter),color:s.Counter.Color.DANGER}))}function V(){return this.cache.remember("counter-wrapper",()=>this.getLayout().querySelector(".intranet-invitation-widget-item-name"))}let D,W,X=e=>e;class $ extends c{constructor(e){super(e),this.articleCode="6770709",this.setEventNamespace("BX.Intranet.InvitationWidget.ExtranetContent")}getConfig(){return{html:this.getLayout(),minHeight:"55px",sizeLoader:37,marginBottom:24,secondary:!0}}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(D||(D=X`
				<div data-id="bx-invitation-widget-content-extranet" class="${0}">
					<div class="intranet-invitation-widget-content">
						<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--ext"></div>
						<div class="intranet-invitation-widget-item-content">
							<div class="intranet-invitation-widget-item-name">
								<span>
									${0}
								</span>
							</div>
							<div class="intranet-invitation-widget-item-link">
								<span onclick="${0}" class="intranet-invitation-widget-item-link-text">
									${0}
								</span>
							</div>
							${0}
						</div>
					</div>
					<button onclick="${0}" class="intranet-invitation-widget-item-btn">
						${0}
					</button>
				</div>
			`),this.getWrapperClass(),o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EXTRANET"),()=>{BX.Helper.show("redirect=detail&code="+this.articleCode),this.sendAnalytics(this.articleCode)},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_EXTRANET_DESC"),this.getCountUserMessage(),e=>{e.stopPropagation(),this.showInvitationPlace(o.Loc.getMessage("INTRANET_INVITATION_WIDGET_DISABLED_TEXT_MSGVER_1"),e.target,"extranet")},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_INVITE")))}getWrapperClass(){return this.cache.remember("wrapper-class",()=>{const e="intranet-invitation-widget-item intranet-invitation-widget-item--wide";return this.getOptions().currentExtranetUserCount>0?e+" intranet-invitation-widget-item--active":e})}getCountUserMessage(){return this.cache.remember("count-user-message",()=>this.getOptions().currentExtranetUserCount>0?o.Tag.render(W||(W=X`
					<div class="intranet-invitation-widget-item-ext-users">
						${0}
					</div>
				`),this.getOptions().currentExtranetUserCountMessage):null)}}let j,U=e=>e;var k=babelHelpers.classPrivateFieldLooseKey("openChat");class K extends c{constructor(e){super(e),this.articleCode="22706764",Object.defineProperty(this,k,{writable:!0,value:void 0}),this.setEventNamespace("BX.Intranet.InvitationWidget.CollabContent")}getConfig(){return{html:this.getOptions().awaitData.then(e=>{const{Messenger:t,CreatableChat:i}=e;return babelHelpers.classPrivateFieldLooseBase(this,k)[k]=()=>{t.openChatCreation(i.collab),l.sendCreateCollab()},this.getLayout()}),minHeight:"55px",sizeLoader:37,marginBottom:24,secondary:!0}}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(j||(j=U`
				<div data-id="bx-invitation-widget-content-collab" class="${0}">
					<div class="intranet-invitation-widget-content">
						<div class="intranet-invitation-widget-item-icon intranet-invitation-widget-item-icon--collab">
							<div class="ui-icon-set --collab"></div>
						</div>
						<div class="intranet-invitation-widget-item-content">
							<div class="intranet-invitation-widget-item-name">
								<span>
									${0}
								</span>
							</div>
							<div class="intranet-invitation-widget-item-link">
								<span onclick="${0}" class="intranet-invitation-widget-item-link-text">
									${0}
								</span>
							</div>
						</div>
					</div>
					<button onclick="${0}" class="intranet-invitation-widget-item-btn intranet-invitation-widget-item-btn--collab">
						${0}
					</button>
				</div>
			`),this.getWrapperClass(),o.Loc.getMessage("INTRANET_INVITATION_WIDGET_COLLAB"),()=>{BX.Helper.show("redirect=detail&code="+this.articleCode),this.sendAnalytics(this.articleCode)},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_COLLAB_DESC"),e=>{babelHelpers.classPrivateFieldLooseBase(this,k)[k](),e.stopPropagation()},o.Loc.getMessage("INTRANET_INVITATION_WIDGET_COLLAB_CREATE")))}getWrapperClass(){return this.cache.remember("wrapper-class",()=>"intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--collab")}}let Y,z,q=e=>e;class J extends c{getLoader(){return this.cache.remember("loader",()=>new r.Loader({size:45}))}getComponentContent(){return this.cache.remember("component-content",()=>{const e=o.Tag.render(Y||(Y=q`
				<div data-role="invitation-widget-ustat-online" class="invitation-widget-ustat-online"/>
			`));return this.getLoader().show(e),o.ajax.runAction("intranet.invitationwidget.getUserOnlineComponent").then(t=>{this.getLoader().hide();const i=t.data.assets;BX.load([...i.css,...i.js],()=>{o.Runtime.html(null,[...i.string].join("\n"),{useAdjacentHTML:!0}).then(()=>{o.Runtime.html(e,t.data.html).then(()=>{this.getLoader().destroy()})})})}),e})}getLayout(){return this.cache.remember("layout",()=>o.Tag.render(z||(z=q`
				<div class="intranet-invitation-widget-item intranet-invitation-widget-item--wide intranet-invitation-widget-item--no-padding">
					${0}
				</div>
			`),this.getComponentContent()))}}var Q=babelHelpers.classPrivateFieldLooseKey("cache"),Z=babelHelpers.classPrivateFieldLooseKey("getAwaitData"),ee=babelHelpers.classPrivateFieldLooseKey("getContent"),te=babelHelpers.classPrivateFieldLooseKey("getInvitationContent"),ie=babelHelpers.classPrivateFieldLooseKey("getStructureContent"),se=babelHelpers.classPrivateFieldLooseKey("getEmployeesContent"),ne=babelHelpers.classPrivateFieldLooseKey("getExtranetContent"),ae=babelHelpers.classPrivateFieldLooseKey("getCollabContent"),oe=babelHelpers.classPrivateFieldLooseKey("getUserOnlineContent"),re=babelHelpers.classPrivateFieldLooseKey("getPopupContainer"),le=babelHelpers.classPrivateFieldLooseKey("setEventHandler");class ce extends a.EventEmitter{constructor(e){super(),Object.defineProperty(this,le,{value:Te}),Object.defineProperty(this,re,{value:Ee}),Object.defineProperty(this,oe,{value:me}),Object.defineProperty(this,ae,{value:be}),Object.defineProperty(this,ne,{value:ue}),Object.defineProperty(this,se,{value:ge}),Object.defineProperty(this,ie,{value:he}),Object.defineProperty(this,te,{value:ve}),Object.defineProperty(this,ee,{value:pe}),Object.defineProperty(this,Z,{value:de}),Object.defineProperty(this,Q,{writable:!0,value:new o.Cache.MemoryCache}),this.setEventNamespace("BX.Intranet.InvitationWidget.Popup"),this.setOptions(e),babelHelpers.classPrivateFieldLooseBase(this,le)[le]()}setOptions(e){babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].set("options",e)}getOptions(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].get("options",{})}show(){this.getPopup().show()}close(){this.getPopup().close()}getPopup(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("popup",()=>new i.PopupComponentsMaker({id:"invitation-popup",target:this.getOptions().target,width:350,content:babelHelpers.classPrivateFieldLooseBase(this,ee)[ee](),popupLoader:this.getOptions().loader}))}}function de(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("await-data",()=>new Promise((e,t)=>{o.ajax.runAction("intranet.invitationwidget.getData",{data:{},analyticsLabel:{headerPopup:"Y"}}).then(e).catch(t)}))}function pe(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("content",()=>[babelHelpers.classPrivateFieldLooseBase(this,te)[te]().getConfig(),{html:[babelHelpers.classPrivateFieldLooseBase(this,ie)[ie]().getConfig(),babelHelpers.classPrivateFieldLooseBase(this,se)[se]().getConfig()],marginBottom:24},this.getOptions().isExtranetAvailable?babelHelpers.classPrivateFieldLooseBase(this,ne)[ne]().getConfig():null,this.getOptions().isCollabAvailable?babelHelpers.classPrivateFieldLooseBase(this,ae)[ae]().getConfig():null,babelHelpers.classPrivateFieldLooseBase(this,oe)[oe]().getConfig()])}function ve(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("invitation-content",()=>new v({...this.getOptions()}))}function he(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("structure-content",()=>new I({...this.getOptions()}))}function ge(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("employees-content",()=>new S({...this.getOptions()}))}function ue(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("extranet-content",()=>new $({...this.getOptions()}))}function be(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("collab-content",()=>new K({...this.getOptions(),awaitData:o.Runtime.loadExtension("im.public","im.v2.component.content.chat-forms.forms")}))}function me(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("user-online-content",()=>new J)}function Ee(){return babelHelpers.classPrivateFieldLooseBase(this,Q)[Q].remember("popup-container",()=>this.getPopup().getPopup().getPopupContainer())}function Te(){const e=e=>{e.data.popup&&setTimeout(()=>{o.Event.bind(babelHelpers.classPrivateFieldLooseBase(this,re)[re](),"click",()=>{e.data.popup.close()})},100)};a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.InvitationWidget.EmployeesContent:showRightMenu",e),a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.InvitationWidget.HintPopup:show",e),a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"BX.Intranet.UstatOnline:showPopup",e),a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"SidePanel.Slider:onOpenStart",()=>{this.close()})}var Ie=babelHelpers.classPrivateFieldLooseKey("cache"),Le=babelHelpers.classPrivateFieldLooseKey("instance"),we=babelHelpers.classPrivateFieldLooseKey("getPopup");class Pe extends a.EventEmitter{constructor(){super(),Object.defineProperty(this,we,{value:Ne}),Object.defineProperty(this,Ie,{writable:!0,value:new o.Cache.MemoryCache}),this.setEventNamespace("BX.Intranet.InvitationWidget")}static getInstance(){return babelHelpers.classPrivateFieldLooseBase(this,Le)[Le]||(babelHelpers.classPrivateFieldLooseBase(this,Le)[Le]=new this),babelHelpers.classPrivateFieldLooseBase(this,Le)[Le]}show(){babelHelpers.classPrivateFieldLooseBase(this,we)[we]().getPopup().isShown()||babelHelpers.classPrivateFieldLooseBase(this,we)[we]().show()}setOptions(e){return babelHelpers.classPrivateFieldLooseBase(this,Ie)[Ie].set("options",e),l.isAdmin=this.getOptions().isCurrentUserAdmin,o.Event.bind(this.getOptions().button,"click",()=>{l.send(l.EVENT_SHOW),babelHelpers.classPrivateFieldLooseBase(this,we)[we]().show()}),a.EventEmitter.subscribe(a.EventEmitter.GLOBAL_TARGET,"BX.Bitrix24.NotifyPanel:showInvitationWidget",()=>{babelHelpers.classPrivateFieldLooseBase(this,we)[we]().show()}),this}getOptions(){return babelHelpers.classPrivateFieldLooseBase(this,Ie)[Ie].get("options",{})}}function Ne(){return babelHelpers.classPrivateFieldLooseBase(this,Ie)[Ie].remember("popup",()=>new ce({...this.getOptions()}))}Object.defineProperty(Pe,Le,{writable:!0,value:void 0}),e.InvitationWidget=Pe}(this.BX.Intranet=this.BX.Intranet||{},BX.UI.Analytics,BX.UI,BX.UI,BX.Main,BX.Event,BX,BX);
//# sourceMappingURL=invitation-widget.bundle.js.map
