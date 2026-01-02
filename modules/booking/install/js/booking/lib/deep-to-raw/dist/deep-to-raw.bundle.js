this.BX=this.BX||{},this.BX.Booking=this.BX.Booking||{},function(i,t,s){"use strict";i.deepToRaw=function(i){const e=i=>Array.isArray(i)?i.map(i=>e(i)):s.isRef(i)||s.isReactive(i)||s.isProxy(i)?e(s.toRaw(i)):t.Type.isObject(i)?Object.keys(i).reduce((t,s)=>(t[s]=e(i[s]),t),{}):i;return e(i)}}(this.BX.Booking.Lib=this.BX.Booking.Lib||{},BX,BX.Vue3);
//# sourceMappingURL=deep-to-raw.bundle.js.map
