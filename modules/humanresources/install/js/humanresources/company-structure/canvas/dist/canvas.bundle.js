/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports) {
	'use strict';

	const TransformCanvas = {
	  name: 'transform-canvas',
	  props: {
	    modelValue: {
	      type: Object,
	      required: false
	    },
	    zoomSensitivity: {
	      type: Number,
	      default: 0.01
	    },
	    zoomSensitivityMouse: {
	      type: Number,
	      default: 0.001
	    }
	  },
	  emits: ['update:modelValue'],
	  data() {
	    return {
	      transform: {
	        x: 0,
	        y: 0,
	        zoom: 1
	      },
	      dragging: false,
	      zooming: false,
	      lastWheelEventTime: null,
	      dragDirection: null
	    };
	  },
	  computed: {
	    canvasClass() {
	      return {
	        '--dragging': this.dragging,
	        '--zooming': this.zooming
	      };
	    },
	    transformStyles() {
	      this.transform = this.modelValue ? {
	        ...this.modelValue
	      } : this.transform;
	      const {
	        x,
	        y,
	        zoom
	      } = this.transform;
	      return {
	        transform: `translate(${x}px, ${y}px) scale(${zoom})`
	      };
	    }
	  },
	  methods: {
	    onmousedown(event) {
	      this.dragOn = true;
	      this.dragDirection = null;
	    },
	    onmousemove(event) {
	      if (!this.dragOn) {
	        return;
	      }
	      if (event.buttons !== 1) {
	        this.dragOn = false;
	        this.dragging = false;
	        this.dragDirection = null;
	        return;
	      }
	      this.dragging = true;
	      if (!this.dragDirection) {
	        if (Math.abs(event.movementX) > Math.abs(event.movementY)) {
	          this.dragDirection = 'horizontal';
	        } else {
	          this.dragDirection = 'vertical';
	        }
	      }
	      window.requestAnimationFrame(() => {
	        this.transform.x += event.movementX;
	        this.transform.y += event.movementY;
	        this.$emit('update:modelValue', this.transform);
	      });
	    },
	    onmouseup() {
	      this.dragOn = false;
	      this.dragging = false;
	      this.dragDirection = null;
	    },
	    oncontextmenu(event) {
	      event.preventDefault();
	    },
	    isTrackpad(event) {
	      return event.wheelDeltaY ? event.wheelDeltaY === -3 * event.deltaY : event.deltaMode === 0;
	    },
	    onwheel(event) {
	      const isTrackpad = this.isTrackpad(event);
	      if (event.ctrlKey) {
	        const zoomSensitivity = !isTrackpad && !event.ctrlKey ? this.zoomSensitivityMouse : this.zoomSensitivity;
	        const mouseX = event.clientX;
	        const mouseY = event.clientY;
	        const zoomChange = -event.deltaY * zoomSensitivity;
	        const newZoom = Math.min(Math.max(0.2, this.transform.zoom + zoomChange), 3);
	        const oldCenterX = (mouseX - this.transform.x) / this.transform.zoom;
	        const oldCenterY = (mouseY - this.transform.y) / this.transform.zoom;
	        this.transform.zoom = newZoom;
	        this.transform.x = mouseX - oldCenterX * newZoom;
	        this.transform.y = mouseY - oldCenterY * newZoom;
	        this.zooming = true;
	        this.$emit('update:modelValue', this.transform);
	        event.preventDefault();
	        setTimeout(() => {
	          this.zooming = false;
	        }, 200);
	      } else {
	        const dx = event.deltaX;
	        const dy = event.deltaY;
	        this.transform.x -= dx;
	        this.transform.y -= dy;
	        this.$emit('update:modelValue', this.transform);
	        event.preventDefault();
	      }
	    }
	  },
	  template: `
		<div
			class="humanresources-canvas"
			:class="canvasClass"
			@mousedown="onmousedown"
			@mousemove="onmousemove"
			@mouseup="onmouseup"
			@wheel="onwheel"
			@contextmenu="oncontextmenu"
		>
		<div
			class="humanresources-canvas__transform"
			:style="transformStyles"
		>
			<slot :transform="transform"/>
		</div>
		</div>
	`
	};

	exports.TransformCanvas = TransformCanvas;

}((this.BX.Humanresources.CompanyStructure = this.BX.Humanresources.CompanyStructure || {})));
//# sourceMappingURL=canvas.bundle.js.map
