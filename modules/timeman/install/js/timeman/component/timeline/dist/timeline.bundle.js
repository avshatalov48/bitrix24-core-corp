this.BX = this.BX || {};
this.BX.Timeman = this.BX.Timeman || {};
(function (exports,main_core,ui_designTokens,timeman_timeformatter,ui_vue_components_hint,ui_vue) {
    'use strict';

    var Interval = ui_vue.BitrixVue.localComponent('bx-timeman-component-timeline-chart-interval', {
      props: {
        type: String,
        start: Date,
        finish: Date,
        finishAlias: String,
        size: Number,
        fixedSize: Boolean,
        showStartMarker: Boolean,
        showFinishMarker: Boolean,
        clickable: Boolean,
        hint: String,
        isFirst: Boolean,
        isLast: Boolean,
        display: String
      },
      computed: {
        intervalItemClass: function intervalItemClass() {
          return ['bx-timeman-component-timeline-chart-interval-item', this.type ? 'bx-timeman-component-timeline-chart-interval-item-' + this.type : '', this.clickable ? 'bx-timeman-component-timeline-chart-interval-item-clickable' : '', this.isFirst && !(this.isFirst && this.isLast) ? 'bx-timeman-component-timeline-chart-interval-first' : '', this.isLast && !(this.isFirst && this.isLast) ? 'bx-timeman-component-timeline-chart-interval-last' : '', this.isFirst && this.isLast ? 'bx-timeman-component-timeline-chart-interval-round' : '', this.display ? 'bx-timeman-component-timeline-chart-interval-item-' + this.display : ''];
        },
        intervalInlineStyle: function intervalInlineStyle() {
          var style = {};

          if (this.fixedSize) {
            style.width = '50px';
          } else {
            style.width = this.size + '%';
          }

          return style;
        }
      },
      methods: {
        toShortTime: function toShortTime(time) {
          if (timeman_timeformatter.TimeFormatter.isInit()) {
            return timeman_timeformatter.TimeFormatter.toShort(time);
          }

          return BX.date.format('H:i', time);
        },
        intervalClick: function intervalClick() {
          this.$emit('intervalClick', {
            type: this.type,
            start: this.start,
            finish: this.finish
          });
        }
      },
      // language=Vue
      template: "\n\t\t<div \n\t\t\tclass=\"bx-timeman-component-timeline-chart-interval\"\n\t\t\t:style=\"intervalInlineStyle\"\n\t\t>\n\t\t\t<div\n\t\t\t\tv-if=\"clickable && hint\"\n\t\t\t\tv-bx-hint=\"{\n\t\t\t\t\ttext: hint, \n\t\t\t\t\tposition: 'top'\n\t\t\t\t}\"\n\t\t\t\t:class=\"intervalItemClass\"\n\t\t\t\t@click=\"intervalClick\"\n\t\t\t/>\n\t\t\t<div\n\t\t\t\tv-else\n\t\t\t\t:class=\"intervalItemClass\"\n\t\t\t/>\n\t\t\t\n\t\t\t<div\n\t\t\t\tclass=\"bx-timeman-component-timeline-chart-interval-marker-container\"\n\t\t\t>\n\t\t\t\t<div \n\t\t\t\t\tv-if=\"showStartMarker\"\n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-timeman-component-timeline-chart-interval-marker \n\t\t\t\t\t\tbx-timeman-component-timeline-chart-interval-marker-start\n\t\t\t\t\t\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"bx-timeman-component-timeline-chart-interval-marker-line\"/>\n\t\t\t\t\t<div class=\"bx-timeman-component-timeline-chart-interval-marker-title\">\n\t\t\t\t\t\t{{ toShortTime(start) }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"showFinishMarker\"\n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-timeman-component-timeline-chart-interval-marker \n\t\t\t\t\t\tbx-timeman-component-timeline-chart-interval-marker-finish\n\t\t\t\t\t\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"bx-timeman-component-timeline-chart-interval-marker-line\"/>\n\t\t\t\t\t<div class=\"bx-timeman-component-timeline-chart-interval-marker-title\">\n\t\t\t\t\t\t{{ finishAlias ? finishAlias : toShortTime(finish) }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Chart = ui_vue.BitrixVue.localComponent('bx-timeman-component-timeline-chart', {
      components: {
        Interval: Interval
      },
      props: {
        intervals: Array,
        fixedSizeType: String,
        readOnly: Boolean,
        showMarkers: {
          type: Boolean,
          "default": true
        },
        isOverChart: {
          type: Boolean,
          "default": false
        }
      },
      computed: {
        processedIntervals: function processedIntervals() {
          var _this = this;

          var oneHour = 3600000;
          var intervals = this.intervals.map(function (interval) {
            interval.time = interval.finish - interval.start;
            return interval;
          });
          var totalTime = intervals.reduce(function (sum, interval) {
            return sum + interval.time;
          }, 0);
          var totalDynamicTime = totalTime - intervals.filter(function (interval) {
            return interval.type === _this.fixedSizeType && interval.time > oneHour && !interval.stretchable;
          }).reduce(function (sum, interval) {
            return sum + interval.time;
          }, 0);
          var lastStartMarkerTime = null;
          intervals = intervals.map(function (interval, index, pureIntervals) {
            if (index === 0) {
              interval.showStartMarker = true;
              lastStartMarkerTime = interval.start;
            } else if (interval.start - lastStartMarkerTime >= oneHour) {
              interval.showStartMarker = true;
              lastStartMarkerTime = interval.start;
            }

            interval.showFinishMarker = index === pureIntervals.length - 1;
            interval.fixedSize = interval.type === _this.fixedSizeType && interval.time > oneHour && !interval.stretchable;

            if (!interval.fixedSize) {
              interval.size = 100 / (totalDynamicTime / interval.time);
            } else {
              interval.size = null;
            }

            return interval;
          });
          intervals[0].isFirst = true;
          intervals[intervals.length - 1].isLast = true;

          if (intervals[intervals.length - 1].finish.getHours() === 23 && intervals[intervals.length - 1].finish.getMinutes() === 59) {
            intervals[intervals.length - 1].finishAlias = '24:00';
          } //to avoid collisions with the start marker of the last interval, which is always displayed


          if (intervals.length > 3) {
            intervals[intervals.length - 1].showStartMarker = true;

            for (var i = intervals.length - 2; i > 0; i--) {
              if (intervals[i].showStartMarker && intervals[intervals.length - 1].start - intervals[i].start < oneHour) {
                intervals[i].showStartMarker = false;
                break;
              }
            }
          } else if (intervals.length === 3) {
            intervals[intervals.length - 1].showStartMarker = true;
            intervals[intervals.length - 2].showStartMarker = true;
          } //to avoid collisions between markers of the last interval


          if (intervals[intervals.length - 1].finish - intervals[intervals.length - 1].start <= oneHour) {
            intervals[intervals.length - 1].showStartMarker = false;
          }

          return intervals;
        }
      },
      methods: {
        onIntervalClick: function onIntervalClick(event) {
          this.$emit('intervalClick', event);
        }
      },
      // language=Vue
      template: "\n\t\t<div \n\t\t\t:class=\"{\n\t\t\t\t'bx-timeman-component-timeline-chart': !this.isOverChart,\n\t\t\t\t'bx-timeman-component-timeline-over-chart': this.isOverChart,\n\t\t  \t}\"\n\t\t>\n\t\t\t<div class=\"bx-timeman-component-timeline-chart-outline\">\n\t\t\t\t<div class=\"bx-timeman-component-timeline-chart-outline-background\"/>\n\t\t\t</div>\n\t\t\t\n\t\t\t<transition-group \n\t\t\t\tname=\"bx-timeman-component-timeline-chart\"\n\t\t\t\tclass=\"bx-timeman-component-timeline-chart-container\"\n\t\t\t\ttag=\"div\"\n\t\t\t>\n\n\t\t\t<Interval\n\t\t\t\tv-for=\"interval of processedIntervals\"\n\t\t\t\t:key=\"interval.start.getTime()\"\n\t\t\t\t:type=\"interval.type\"\n\t\t\t\t:start=\"interval.start\"\n\t\t\t\t:finish=\"interval.finish\"\n\t\t\t\t:finishAlias=\"interval.finishAlias ? interval.finishAlias : null\"\n\t\t\t\t:showStartMarker=\"showMarkers ? interval.showStartMarker: false\"\n\t\t\t\t:showFinishMarker=\"showMarkers ? interval.showFinishMarker: false\"\n\t\t\t\t:clickable=\"!readOnly ? interval.clickable : false\"\n\t\t\t\t:hint=\"!readOnly ? interval.clickableHint : null\"\n\t\t\t\t:fixedSize=\"interval.fixedSize\"\n\t\t\t\t:size=\"interval.size\"\n\t\t\t\t:isFirst=\"interval.isFirst\"\n\t\t\t\t:isLast=\"interval.isLast\"\n\t\t\t\t:display=\"interval.display\"\n\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t/>\n\n\t\t\t</transition-group>\n\t\t</div>\n\t"
    });

    var Item = ui_vue.BitrixVue.localComponent('bx-timeman-component-timeline-legend-item', {
      props: ['type', 'title'],
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-component-timeline-legend-item\">\n\t\t\t<div \n\t\t\t\t:class=\"[ \n\t\t\t\t\t'bx-timeman-component-timeline-legend-item-marker',\n\t\t\t\t\ttype ? 'bx-timeman-component-timeline-legend-item-marker-' + type : '',\n\t\t\t\t]\"\n\t\t\t/>\n\t\t\t<div class=\"bx-timeman-component-timeline-legend-item-title\">\n\t\t\t\t{{ title }}\n\t\t\t</div>\n\t\t</div>\n\t"
    });

    var Legend = ui_vue.BitrixVue.localComponent('bx-timeman-component-timeline-legend', {
      components: {
        Item: Item
      },
      props: ['items'],
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-component-timeline-legend\">\n\t\t\t<transition-group \n\t\t\t\tname=\"bx-timeman-component-timeline-legend\"\n\t\t\t\tclass=\"bx-timeman-component-timeline-legend-container\"\n\t\t\t>\n\n\t\t\t\t<Item\n\t\t\t\t\tv-for=\"item of items\"\n\t\t\t\t\t:key=\"item.id\"\n\t\t\t\t\t:type=\"item.type\"\n\t\t\t\t\t:title=\"item.title\"\n\t\t\t\t/>\n\n\t\t\t</transition-group>\n\t\t</div>\n\t"
    });

    ui_vue.Vue.component('bx-timeman-component-timeline', {
      components: {
        Chart: Chart,
        Legend: Legend
      },
      props: {
        chart: Array,
        legend: Array,
        fixedSizeType: String,
        readOnly: Boolean,
        overChart: Array
      },
      computed: {
        Type: function Type() {
          return main_core.Type;
        }
      },
      methods: {
        onIntervalClick: function onIntervalClick(event) {
          this.$emit('intervalClick', event);
        }
      },
      // language=Vue
      template: "\n\t\t<div class=\"bx-timeman-component-timeline\">\n\t\t\t<Chart\n\t\t\t\t:intervals=\"chart\"\n\t\t\t\t:fixedSizeType=\"fixedSizeType\"\n\t\t\t\t:readOnly=\"readOnly\"\n\t\t\t\t@intervalClick=\"onIntervalClick\"\n\t\t\t/>\n\t\t\t\n\t\t\t<Legend\n\t\t\t\t:items=\"legend\"\n\t\t\t/>\n\n\t\t\t<transition appear name=\"bx-timeman-component-timeline-fade\">\n\t\t\t\t<Chart\n\t\t\t\t\tv-if=\"Type.isArrayFilled(overChart)\"\n\t\t\t\t\t:intervals=\"overChart\"\n\t\t\t\t\t:fixedSizeType=\"fixedSizeType\"\n\t\t\t\t\t:readOnly=\"true\"\n\t\t\t\t\t:showMarkers=\"false\"\n\t\t\t\t\t:isOverChart=\"true\"\n\t\t\t\t/>\n\t\t\t</transition>\n\t\t</div>\n\t"
    });

}((this.BX.Timeman.Component = this.BX.Timeman.Component || {}),BX,BX,BX.Timeman,window,BX));
//# sourceMappingURL=timeline.bundle.js.map
