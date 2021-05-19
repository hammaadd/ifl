/*
 * Vue Inspector control host row implementation
 */
Vue.component('backend-component-inspector-controlhost-row', {
    props: {
        obj: {
            type: Object,
            required: true
        },
        control: {
            type: Object,
            required: true
        },
        splitterData: {
            type: Object,
            required: true
        },
        depth: {
            type: Number,
            required: true
        },
        panelUpdateData: {
            type: Object,
            required: true
        },
        controlHostUniqueId: {
            type: String,
            required: true
        },
        layoutUpdateData: {
            type: Object
        },
        inspectorPreferences: {
            type: Object
        },
        isFullWidth: {
            type: Boolean
        }
    },
    data: function () {
        return {
            hasErrors: false
        };
    },
    computed: {
        titlePanelStyle: function computeTitlePanelStyle() {
            var result = {},
                sizePx = this.splitterData.position + 'px';

            result['width'] = sizePx;

            return result;
        },

        controlColspan: function computeControlColspan() {
            return this.isFullWidth ? 2 : 1;
        },

        labelStyle: function computeLabelStyle() {
            if (!this.depth) {
                return {};
            }

            return {
                'margin-left': (this.depth * 10) + 'px'
            };
        },

        controlEditorId: function computeControlEditorId() {
            return this.controlHostUniqueId + this.control.property;
        }
    },
    methods: {
        onEditorFocus: function onEditorFocus() {
            $(this.$el).closest('.component-backend-inspector-panel').find('tr.inspector-control-row').removeClass('focused');
            $(this.$el).addClass('focused');
        },

        onEditorBlur: function onEditorBlur() {
            $(this.$el).removeClass('focused');
        },

        onLabelClick: function onLabelClick() {
            if (this.$refs.editor.onInspectorLabelClick !== undefined) {
                this.$refs.editor.onInspectorLabelClick();
            }
        },

        onEditorInvalid: function onEditorInvalid() {
            this.hasErrors = true;
        },

        onEditorValid: function onEditorValid() {
            this.hasErrors = false;
        }
    },
    created: function created() {
    },
    template: '#backend_vuecomponents_inspector_controlhost_row'
});