var awReportsUnit_Salesbyproductattr = Class.create();
awReportsUnit_Salesbyproductattr.prototype = {
    _containerTpl: "<div class='aw-reports-unit-sbpa-container'>" +
        "<div class='aw-reports-unit-sbpa-container-list'></div>" +
        "<div class='aw-reports-unit-sbpa-add_btn'>" +
            "<button type='button' class='scalable task'><span>{{title}}</span></button>" +
            "<div class='arep-button-set attribute-grid'>" +
                "<button type='button' class='scalable task' onclick='{{reportGridObject}}.doFilter()'><span>{{apply_title}}</span></button>" +
            "</div>" +
        "</div>" +
    "</div>",
    _containerCSSClass: "aw-reports-unit-sbpa-container",
    _containerListCSSClass: "aw-reports-unit-sbpa-container-list",
    _containerAddBtnCSSClass: "aw-reports-unit-sbpa-add_btn",

    _itemTpl: "<div>" +
        "<div class='aw-reports-unit-sbpa-operand'></div>" +
        "<div class='aw-reports-unit-sbpa-attribute'></div>" +
        "<div class='aw-reports-unit-sbpa-condition'></div>" +
        "<div class='aw-reports-unit-sbpa-value'></div>" +
        "<div class='aw-reports-unit-sbpa-remove_btn'>" +
            "<button type='button' class='scalable task'><span>{{title}}</span></button>" +
        "</div>" +
    "</div>",

    _itemOperandContainerCSSClass: "aw-reports-unit-sbpa-operand",
    _itemAttributeContainerCSSClass: "aw-reports-unit-sbpa-attribute",
    _itemConditionContainerCSSClass: "aw-reports-unit-sbpa-condition",
    _itemValueContainerCSSClass: "aw-reports-unit-sbpa-value",
    _itemRemoveBtnContainerCSSClass: "aw-reports-unit-sbpa-remove_btn",


    initialize: function(){
        var me = this;
        Event.observe(document, 'dom:loaded', function(e){
            me.init();
        });
    },

    init: function() {
        this.config = awReportsUnit_Salesbyproductattr_DATA;

        this.container = new Element('div');
        this.container.update(
            this._containerTpl
                .replace("{{title}}", this.config.titles.add_attribute)
                .replace("{{apply_title}}", this.config.titles.applyReport)
                .replace("{{reportGridObject}}", this.config.reportGridObject)
        );

        this.container = this.container.select("." + this._containerCSSClass).first();

        var addBtn = this.container.select("." + this._containerAddBtnCSSClass + " button").first();
        var me = this;
        addBtn.observe('click', function(e){
            me.addNewRow();
        });
        $('aw_chart_container').up().insert(this.container);

        var currentFilterAsArray = this.config.current_filter;
        if (!Object.isArray(currentFilterAsArray)) {
            currentFilterAsArray = Object.values(currentFilterAsArray);
        }
        currentFilterAsArray.each(function(obj){
            var row = me.addNewRow();
            var operand = row.select('.' + me._itemOperandContainerCSSClass + ' select').first();
            operand.setValue(obj['operand']);
            var attribute = row.select('.' + me._itemAttributeContainerCSSClass + ' select').first();
            attribute.setValue(obj['attribute']);
            me.onAttributeChange(row);

            var condition = row.select('.' + me._itemConditionContainerCSSClass + ' select').first();
            condition.setValue(obj['condition']);
            var value = row.select('.' + me._itemValueContainerCSSClass + ' select,input').first();
            value.setValue(obj['value']);
        });
    },

    addNewRow: function() {
        var me = this;
        var row = new Element('div');
        row.update(
            this._itemTpl.replace("{{title}}", this.config.titles.remove)
        );
        row = row.down();
        row.select('.' + this._itemOperandContainerCSSClass).first().update(
            this._getOperandHtml()
        );
        row.select('.' + this._itemAttributeContainerCSSClass).first().update(
            this._getAttributeHtml()
        ).observe('change', function(e){
            me.onAttributeChange(row);
        });
        row.select('.' + this._itemRemoveBtnContainerCSSClass + ' button').first()
            .observe('click', function(e){
                row.remove();
                me._reInitUINames();
            });

        var list = this.container.select("." + this._containerListCSSClass).first();
        list.appendChild(row);

        this._reInitUINames();
        return row;
    },

    onAttributeChange: function(row) {
        var attributeEl = row.select('.' + this._itemAttributeContainerCSSClass + ' select').first();
        var code = attributeEl.getValue();
        var conditionContainer = row.select('.' + this._itemConditionContainerCSSClass).first();
        var valueContainer = row.select('.' + this._itemValueContainerCSSClass).first();
        if (code.length < 1) {
            conditionContainer.update("");
            valueContainer.update("");
            return;
        }
        conditionContainer.update(
            this._getConditionHtmlByAttributeCode(code)
        );
        valueContainer.update(
            this._getValueHtmlByAttributeCode(code)
        );
        if (this.config.attributes[code].type === "date") {
            Calendar.setup({
                inputField: valueContainer.select('input').first(),
                ifFormat: "%Y-%m-%d",
                showsTime: false,
                align: "Bl",
                singleClick : true
            });
        }
        this._reInitUINames();
    },

    _getOperandHtml: function() {
        return "<select name='operand'>" +
            "<option value='and'>" + this.config.titles.operand_and + "</option>" +
            "<option value='or'>" + this.config.titles.operand_or + "</option>" +
        "</select>";
    },

    _getAttributeHtml: function() {
        var optionHtml = "<option></option>";
        Object.values(this.config.attributes).each(function(attribute){
            optionHtml += "<option value='{{value}}'>{{label}}</option>"
                .replace("{{value}}", attribute.code)
                .replace("{{label}}", attribute.label)
            ;
        });
        return "<select name='attribute'>" + optionHtml + "</select>";
    },

    _getConditionHtmlByAttributeCode: function(code) {
        var type = this.config.attributes[code].type;
        var options = [];
        options.push({value: "eq", label: this.config.titles.condition_eq});
        options.push({value: "neq", label: this.config.titles.condition_neq});
        if (["text", "textarea"].indexOf(type) !== -1) {
            options.push({value: "like", label: this.config.titles.condition_like});
            options.push({value: "nlike", label: this.config.titles.condition_nlike});
        }
        if (["date", "price"].indexOf(type) !== -1) {
            options.push({value: "gteq", label: this.config.titles.condition_gteq});
            options.push({value: "lteq", label: this.config.titles.condition_lteq});
            options.push({value: "gt", label: this.config.titles.condition_gt});
            options.push({value: "lt", label: this.config.titles.condition_lt});
        }
        if (["text", "textarea", "price"].indexOf(type) !== -1) {
            options.push({value: "in", label: this.config.titles.condition_in});
            options.push({value: "nin", label: this.config.titles.condition_nin});
        }

        var optionHtml = "";
        options.each(function(option){
            optionHtml += "<option value='{{value}}'>{{label}}</option>"
                .replace("{{value}}", option.value)
                .replace("{{label}}", option.label)
            ;
        });
        return "<select name='condition'>" + optionHtml + "</select>";
    },

    _getValueHtmlByAttributeCode: function(code) {
        var options = this.config.attributes[code].options;
        if (options.length < 1) {
            return "<input type='text' name='value'/>";
        }
        var optionHtml = "";
        options.each(function(option){
            optionHtml += "<option value='{{value}}'>{{label}}</option>"
                .replace("{{value}}", option.value)
                .replace("{{label}}", option.label)
            ;
        });
        return "<select name='value'>" + optionHtml + "</select>";
    },

    _reInitUINames: function() {
        var rows = this.container.select('.' + this._containerListCSSClass + '>div');
        var rowNum = 0;
        rows.each(function(row) {
            var elements = row.select('input,select');
            elements.each(function(el) {
                var name = el.getAttribute('name');
                var match = name.match(/productattr\[[^\]]+\]\[([^\]]+)\]/);
                var elementName = name;
                if (match) {
                    elementName = match[1];
                }
                el.setAttribute('name', 'productattr[' + rowNum + '][' + elementName + ']');
            });
            rowNum++;
        });
    }
};
new awReportsUnit_Salesbyproductattr();