(function($) {
    var defaults = {
        storageKey: 'itemsFilterStore_' + _top().myId,
        itemSelector: 'li',
        sortField: 'ord',
        sortOrder: 'asc',
        sortFieldSecond: 'ord',
        sortFieldTertiary: 'data-id',
        filterLengthMin: 3,
        filterElement: null,
        fields: {}
    };
    var methods = {
        init: function(options) {
            var opts = $.extend(defaults, options);

            var sortItemsFilter = $.jStorage.get(defaults.storageKey, {});

            this.each(function() {
                var $this = $(this);
                var o = $this.data('opts');
                if (!o) {
                    $this.data('opts', opts);
                }
                var $filter = opts.filterElement ? $(opts.filterElement) : null;
                if ($filter) {
                    if (sortItemsFilter.filterValue) {
                        $filter.find('[name=filterField]').val(sortItemsFilter.filterValue);
                        methods.filter.apply($this, [sortItemsFilter]);
                    }
                    if (!opts.disableSort && (sortItemsFilter.sortField || (sortItemsFilter.sortOrder != opts.sortOrder))) {
                        $filter.find('[name=sortField]').val(sortItemsFilter.sortField);
                        $filter.find('[name=sortOrder]').val(sortItemsFilter.sortOrder);
                        methods.sort.apply($this, [sortItemsFilter]);
                    }
                }
            });
        },
        filter: function(options) {
            options = options || {};
            var sortItemsFilter = $.jStorage.get(defaults.storageKey, {});
            options = $.jStorage.set(defaults.storageKey, $.extend(sortItemsFilter, options));
            return this.each(function() {
                var $this = $(this);
                var opts = $this.data('opts');
                if (!opts) {
                    return true;
                }
                $this.find(opts.itemSelector).css('opacity', 1);
                if (opts.fields[options.filterField] && opts.fields[options.filterField].type == 's') {
                    options.filterValue = $.trim(options.filterValue);
                    if (opts.filterLengthMin && options.filterValue.length < opts.filterLengthMin) {
                        return;
                    }
                }
                if (!options.filterField || options.filterValue == '') return;
                $this.find(opts.itemSelector).filter(function() {
                    if (opts.fields[options.filterField] && opts.fields[options.filterField].type == 's') {
                        return $(this).attr(options.filterField).toLowerCase().indexOf(options.filterValue.toLowerCase()) == -1;
                    } else {
                        return $(this).attr(options.filterField) != options.filterValue;
                    }
                }).css('opacity', .25);
            });
        },
        sort: function(options) {
            options = options || {};
            var sortItemsFilter = $.jStorage.get(defaults.storageKey, {});
            if (Object.keys(sortItemsFilter).length == 0) {
                sortItemsFilter.sortOrder = defaults.sortOrder;
                sortItemsFilter.sortField = defaults.sortField;
            }

            if (!options.sortOrder) sortItemsFilter.sortOrder = sortItemsFilter.sortField == options.sortField ? (sortItemsFilter.sortOrder == 'asc' ? 'desc' : 'asc') : sortItemsFilter.sortOrder;
            options = $.jStorage.set(defaults.storageKey, $.extend(sortItemsFilter, options));
            return this.each(function() {
                var $this = $(this);
                var opts = $this.data('opts');
                if (!options.sortField) options.sortField = opts.sortField;
                if (!options.sortOrder) options.sortOrder = opts.sortOrder;
                var f = opts.fields[options.sortField];
                $this.find(opts.itemSelector).sort(function(a, b) {
                    if (f.type == 's') {
                        var v1 = $(a).attr(options.sortField).toString().toLowerCase();
                        var v2 = $(b).attr(options.sortField).toString().toLowerCase();
                        var r = options.sortOrder == 'desc' ? v2.localeCompare(v1) : v1.localeCompare(v2);
                        if (r == 0) {
                            v1 = parseInt($(a).attr(opts.sortFieldSecond));
                            v2 = parseInt($(b).attr(opts.sortFieldSecond));
                            if (options.sortOrder == 'desc') {
                                if (v1 > v2) return -1;
                                else if (v1 < v2) return 1;
                                else {
                                    v1 = parseInt($(a).attr(opts.sortFieldTertiary));
                                    v2 = parseInt($(b).attr(opts.sortFieldTertiary));
                                    if (options.sortOrder == 'desc') return (v1 > v2) ? -1 : ((v1 < v2) ? 1 : 0);
                                    else return (v1 < v2) ? -1 : ((v1 > v2) ? 1 : 0);
                                }
                            } else {
                                if (v1 < v2) return -1;
                                else if (v1 > v2) return 1;
                                else {
                                    v1 = parseInt($(a).attr(opts.sortFieldTertiary));
                                    v2 = parseInt($(b).attr(opts.sortFieldTertiary));
                                    if (options.sortOrder == 'desc') return (v1 > v2) ? -1 : ((v1 < v2) ? 1 : 0);
                                    else return (v1 < v2) ? -1 : ((v1 > v2) ? 1 : 0);
                                }
                            }
                        } else return r;
                    } else {
                        var v1 = parseInt($(a).attr(options.sortField));
                        var v2 = parseInt($(b).attr(options.sortField));
                        if (options.sortOrder == 'desc') {
                            if ((!f.zero && (v1 < v2)) || (f.zero && v1 != v2 && (v1 == 0 || v1 < v2))) return 1;
                            else if ((!f.zero && (v1 > v2)) || (f.zero && v1 != v2 && (v2 == 0 || v1 > v2))) return -1;
                            else {
                                v1 = parseInt($(a).attr(opts.sortFieldSecond));
                                v2 = parseInt($(b).attr(opts.sortFieldSecond));
                                if (v1 < v2) return -1;
                                else if (v1 > v2) return 1;
                                else {
                                    v1 = parseInt($(a).attr(opts.sortFieldTertiary));
                                    v2 = parseInt($(b).attr(opts.sortFieldTertiary));
                                    return (v1 < v2) ? -1 : ((v1 > v2) ? 1 : 0);
                                }
                            }
                        } else {
                            if ((!f.zero && (v1 > v2)) || (f.zero && v1 != v2 && (v1 == 0 || v2 != 0 && v1 > v2))) return 1;
                            else if ((!f.zero && (v1 < v2)) || (f.zero && v1 != v2 && (v2 == 0 || v1 != 0 && v1 < v2))) return -1;
                            else {
                                v1 = parseInt($(a).attr(opts.sortFieldSecond));
                                v2 = parseInt($(b).attr(opts.sortFieldSecond));
                                if (v1 < v2) return -1;
                                else if (v1 > v2) return 1;
                                else {
                                    v1 = parseInt($(a).attr(opts.sortFieldTertiary));
                                    v2 = parseInt($(b).attr(opts.sortFieldTertiary));
                                    return (v1 < v2) ? -1 : ((v1 > v2) ? 1 : 0);
                                }
                            }
                        }
                    }
                }).prependTo($this);
            });
        },
        reset: function() {
            var sortItemsFilter = $.jStorage.get(defaults.storageKey, {});
            options = $.jStorage.set(defaults.storageKey, $.extend(sortItemsFilter, {sortField: defaults.sortField, sortOrder: defaults.sortOrder}));
            return this.each(function() {
                var opts = $(this).data('opts');
                var $filter = opts.filterElement ? $(opts.filterElement) : null;
                if ($filter) {
                    $filter.find('[name=sortField]').val(options.sortField);
                }
            });
        },
        toggle_sort: function() {
            var sortItemsFilter = $.jStorage.get(defaults.storageKey, {});
            sortItemsFilter.sortOrder = sortItemsFilter.sortOrder == 'asc' ? 'desc' : 'asc';
            options = $.jStorage.set(defaults.storageKey, sortItemsFilter);
            methods.sort.apply(this, options);
        },
        filter_get: function() {
            var filter = $.jStorage.get(defaults.storageKey, {});
            filter.sortOrder = filter.sortOrder || defaults.sortOrder;
            return filter;
        }
    };
    $.fn.itemsFilter = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' +  method + ' does not exist on jQuery.itemsFilter');
        }
    };
})(jQuery);
