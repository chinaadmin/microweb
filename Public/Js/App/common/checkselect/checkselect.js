define(function(require, exports, module) {
	var $ = require('jquery');
	var select = {
		selected : function(opt) {
			opt = opt || {};
			var options = {
				leftClass : opt.leftClass || "link-select",
				rightClass : opt.rightClass || "checked-select",
				selectClass : opt.selectClass || "selected",
				input : opt.input || false,
				name : opt.name || "goods"
			}
			this.leftselect(options);
			this.rightselect(options);
		},

		leftselect : function(options) {
			var the_this = this;
			$("#" + options.leftClass).on(
					'click',
					'.' + options.selectClass,
					function(e) {
						e.stopPropagation();
						e.preventDefault();
						_this = $(this);
						the_this.add(_this, options.rightClass,
								options.selectClass, true, options.input,
								options.name);
					})
			this.defaultInput(options.leftClass, options.selectClass);
		},

		rightselect : function(options) {
			var the_this = this;
			$("#" + options.rightClass).on(
					'click',
					'.' + options.selectClass,
					function(e) {
						e.stopPropagation();
						e.preventDefault();
						_this = $(this);
						the_this.add(_this, options.leftClass,
								options.selectClass, false, options.input,
								options.name);
					})
			this.defaultInput(options.rightClass, options.selectClass);
		},

		add : function(obj, lrClass, selectClass, right, input, namekey) {
			var _this = obj;
			_this.remove();
			var _span = _this.children('span');
			var name = _span.text();
			var ids = _span.attr('ids');
			var price = _span.attr('price');
			var str = "<li class='" + selectClass + "'><span ids=" + ids
					+ " price=" + price + ">" + name + "</span>";
			if (right) {
				// 左到右
				if (!input) {
					str += "<input type=hidden name=" + namekey + "[" + ids
							+ "] value='" + price + "'/>";
				} else {
					// 可以改价格
					str += "价格：<input type=text name=" + namekey + "[" + ids
							+ "] value='" + price + "'/>";
				}
			}
			var link_id = this.linked(ids, lrClass);
			if (link_id) {
				str += link_id;
			}
			// 右到左
			str += "</li>"
			$('#' + lrClass + " ul").append(str);
		},
		linked : function(ids, lrClass) {
			var link_id = "";
			var link_type = $('#' + lrClass).siblings('#link_type').find(
					"input[name=link_type]");
			if (link_type.attr('name')) {
				link_type
						.each(function(i) {
							var checked = link_type.eq(i).get(0).checked;
							if (checked) {
								var value = link_type.eq(i).val();
								if (value == 2) {
									link_id += "<input type='hidden' name='goods_linked[]' value='"
											+ ids + "'/>";
								}
							}
						})
			}
			return link_id;
		},
		defaultInput : function(lrclass, selectClass) {
			$("#" + lrclass).on('click', '.' + selectClass + " input",
					function(e) {
						e.stopPropagation();
						e.preventDefault();
					})
		}
	}
	module.exports = select;
});