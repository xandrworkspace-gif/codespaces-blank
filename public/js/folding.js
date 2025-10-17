// Help to work with folding lists (as instances or quests)
// opt - object with params ( see inside function )
// synops: 
//		var instance_folding = new list_folding({id_btn: 'inst_btn_', id_expanded: 'inst_full_desc_', id_collapsed: 'inst_short_desc_'});
//		onclick = 'instance_folding.expand(55); return false;'
function list_folding(opt) {
	var defaults = {
		id_btn : false, // prefix of plus/minus button (ie instance_btn_) will
						// be concat with `id`
		id_expanded : false, // prefix of container that will be expand(ie
								// instance_full_description_) will be concat
								// with `id`
		id_collapsed : false, // prefix of container that will be shown when
								// collapse(ie instance_shot_description_) will
								// be concat with `id`
		btn_expanded : 'images/qst_minus.gif',
		btn_collapsed : 'images/qst_plus.gif',
		collapse_unrelated : false,
		cookie_save_state: '', // if not empty will save state ,
		id_list:		Array(), // need for expand_all, collapse_all
		init_state:		'collapsed' // What to do on first call `toggle_all`  
	};
	// merge options
	this.opt = defaults;
	if (opt) {
		for ( var i in opt) {
			this.opt[i] = opt[i];
		}
	}
	this.history_expand =  new Array();
	this.history_collapse= new Array();
}
list_folding.prototype = {
	is_expand_all: -1,
	// refresh state from cookie's
	refresh: function() {
		if (this.opt.cookie_save_state == '') 
			return;
		// check expand all
		var expand_all = this._getcookie('expand_all');
		if (expand_all != null && expand_all != 'undefined' && expand_all != '') {
			expand_all > 0 ? this.expand_all() : this.collapse_all();  
			return;
		}
		var init_collapse = (this.opt.init_state == 'collapsed') ? 1 : 0;
		// load list of items
		var list_str = this._getcookie('list');
		var list = list_str ? list_str.split(',') :  [];
		for(var id in list) {
			if (init_collapse)
				this.expand(list[id]);
			else
				this.collapse(list[id]);
		}
	},
	expand : function(id) {
		// Collapse opened
		if (this.opt.collapse_unrelated) {
			var last = this.history_expand.lenght ? this.history_expand[this.history_expand.lenght-1] : false;
			if (last)
				this.collapse(last);
		}
		this._history_expand(id);
		this._setcookie('expand_all', '');

		this._button_change(id, this.opt.btn_expanded);
		var el_exp = this._gebi('id_expanded', id);
		var el_coll = this._gebi('id_collapsed', id);
		if (el_coll)
			el_coll.style.display = 'none';
		if (el_exp)
			el_exp.style.display = '';
		this.last_expanded = id;
	},
	// will note work when `collapse_unrelated` on
	expand_all: function() {
		if (!this.opt.id_list.length)
			return;
		this.is_expand_all = 1;
		for(var id in this.opt.id_list) {
			this.expand(this.opt.id_list[id]);
		}
		this._setcookie('expand_all', this.is_expand_all);
	},
	collapse: function(id) {
		// Save state to cookie
		this._history_collapse(id);
		this._setcookie('expand_all', '');

		this._button_change(id, this.opt.btn_collapsed);
		var el_exp = this._gebi('id_expanded', id);
		var el_coll = this._gebi('id_collapsed', id);
		if (el_exp)
			el_exp.style.display = 'none';
		if (el_coll)
			el_coll.style.display = '';
	},
	collapse_all: function() {
		if (!this.opt.id_list.length)
			return;
		this.is_expand_all = 0;
		for(var id in this.opt.id_list) {
			this.collapse(this.opt.id_list[id]);
		}	
		this._setcookie('expand_all', this.is_expand_all);
	},
	toggle : function(id) {
		this.is_expanded(id) ? this.collapse(id) : this.expand(id);
	},
	toggle_all: function() {
		if(this.is_expand_all < 0) {
			this.is_expand_all = (this.opt.init_state == 'collapsed') ? 0 : 1;
		}
		if(this.is_expand_all)
			this.collapse_all();
		else
			this.expand_all();
	},
	is_expanded : function(id) {
		var el = this._gebi('id_btn', id);
		if (el) {
			var el_src = el.getAttribute('src');
			if (el_src && el_src.match(this.opt.btn_expanded))
				return true;
			if (el_src && el_src.match(this.opt.btn_collapsed))
				return false;
		}
		el = this._gebi('id_expanded', id);
		if (el && el.style.display == 'none')
			return false;
		el = this._gebi('id_collapsed', id);
		if (el && el.style.display == 'none')
			return true;
		return false;
	},
	_button_change : function(id, src_path) {
		var el = this._gebi('id_btn', id);
		if (el)
			el.setAttribute('src', src_path);
	},
	_gebi : function(prefix, id) {
		prefix = prefix ? this.opt[prefix] : false;
		if (!prefix)
			return false;
		var el = gebi(prefix + id);
		return el;
	},
	_history_save: function() {
		var arr = (this.opt.init_state == 'collapsed') ? this.history_expand : this.history_collapse;
		var str = '';
		for(var id in arr) {
			if (str) str += ",";
			str += arr[id];
		}
		this._setcookie('list', str,{days:31});
	},
	_history_expand: function(id) {
		this.history_expand = this._history_add(this.history_expand, id);
		this.history_collapse = this._history_remove(this.history_collapse, id);
		this._history_save();
	},
	_history_collapse: function(id) {
		this.history_collapse = this._history_add(this.history_collapse, id);
		this.history_expand = this._history_remove(this.history_expand, id);
		this._history_save();
	},
	_history_add: function(arr, id) {
		if (this._hitory_find(arr, id) >= 0)
			return arr;
		arr[arr.length] = id;
		return arr;
	},
	_history_remove: function(arr, id) {
		var pos = this._hitory_find(arr, id);
		if (pos < 0)
			return arr;
		arr.splice(pos, 1);
		return arr;
	},
	_hitory_find: function(arr, search_id) {
		var i = 0;
		for(var id in arr) {
			if (arr[id] == search_id) return i;
			++i;
		}
		return -1; 
	},
	_setcookie: function(key, val,time_exp) {
		if (this.opt.cookie_save_state == '') return;
		setCookie(this.opt.cookie_save_state + key, val,time_exp);
	},
	_getcookie: function(key) {
		if (this.opt.cookie_save_state == '')return false;
		return getCookie(this.opt.cookie_save_state + key);
	}
}	