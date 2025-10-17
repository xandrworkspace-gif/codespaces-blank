//FOR AREA_COMPASS (I. P.) 2018
var Stack = function() {
    this.stack = [];
    this.constructor = function(){
        this.stack = [];
    };
    this.length = function(){
        return this.stack.length;
    };
    this.size = function(){
        return this.length();
    };
    this.push = function(item){
        this.stack.push(item);
    };
    this.pop = function(){
        return this.stack.pop();
    };
    this.peek = function(){
        return this.stack[this.length() - 1];
    };
    this.hasItems = function(){
        return !!this.length();
    };
    this.isEmpty = function(){
        return !this.hasItems();
    };
};

function Queue(){
    var queue  = [];
    var offset = 0;

    this.getLength = function(){
        return (queue.length - offset);
    };
    this.isEmpty = function(){
        return (queue.length == 0);
    };
    this.enqueue = function(item){
        queue.push(item);
    };
    this.dequeue = function(){
        if (queue.length == 0) return undefined;
        var item = queue[offset];
        if (++ offset * 2 >= queue.length){
            queue  = queue.slice(offset);
            offset = 0;
        }
        return item;

    };
    this.peek = function(){
        return (queue.length > 0 ? queue[offset] : undefined);
    };
}

function Graph() {
    this.vertices = [];
    this.adjacentList = new Map();
    this.numberOfEdges = 0;

    this.breathFirstSearch = function(startingVertex) {
        var color = [],
            distances = [],
            preceding = [],
            queue = new Queue(),
            isEmptyQueue = true;

        this.setVertexColorsAndEdges = function() {
            for(var i = 0; i < this.vertices.length; i++) {
                color[this.vertices[i]] = 'white';
                distances[this.vertices[i]] = -1;
                if(this.vertices[i] === startingVertex) {
                    distances[this.vertices[i]] = 0;
                    preceding[this.vertices[i]] = startingVertex;
                } else {
                    preceding[this.vertices[i]] = null;
                }
            }
        };

        this.setVertexColorsAndEdges();
        queue.enqueue(startingVertex);
        while(!queue.isEmpty()) {
            var queueFrontVertex = queue.dequeue();
            color[queueFrontVertex] = 'grey';
            var frontVertexAdjLst = this.getAdjacencyListVertex(queueFrontVertex);
            frontVertexAdjLst.forEach(function(adjVertex){
                if(color[adjVertex] === 'white') {
                    color[adjVertex] = 'grey';
                    if(distances[queueFrontVertex] === -1) {
                        distances[queueFrontVertex] = 1;
                    } else {
                        distances[adjVertex] = distances[queueFrontVertex] + 1;
                    }
                    preceding[adjVertex] = queueFrontVertex;
                    queue.enqueue(adjVertex);
                }
            });

            color[queueFrontVertex] = 'black';
            //console.log(queueFrontVertex + ' посетили');
        }
        for (var i in distances) {
            //console.log('Минимальное расстояние от ' + startingVertex + ' до ' + i + ' в ' + distances[i] + ' шаг(ах)');
        }

        return {
            distances : distances,
            preceding : preceding
        };
    };

    this.getAdjacencyListVertex = function(vertex){
        return this.adjacentList.get(vertex);
    };
    this.addEdge = function(u, v, t){
        //t - this is demon!
        var uVertex = this.getAdjacencyListVertex(u),
            vVertex = this.getAdjacencyListVertex(v);
        if(t) {
            vVertex.push(u);
            uVertex.push(v);
        }else{
            vVertex.push(u);
            uVertex.push(v);
        }
    };

    this.addVertex = function(v){
        this.vertices.push(v);
        this.adjacentList.set(v, []);
        //100 -> 120, 130, 140
    };

    this.vertexExist = function(v) {
        return this.vertices.indexOf(v) >= 0;
    };

    this.shortestPathToAll = function(source, bfs) {
        if(!this.vertexExist(source)) {
            return console.log('Стартовая локация не найдена');
        }

        for(var i = 0; i < this.vertices.length; i++) {
            var currentVertex = this.vertices[i];
            this.shortest_path(bfs, source, currentVertex);
        }
    };

    this.shortestPathFromTo = function(bfs, source, destination) {
        if(!this.vertexExist(source)) {
            return console.log('Стартовая локация не найдена');
        }
        return this.shortest_path(bfs, source, destination);
    };

    this.shortest_path = function(bfs, source, destination) {
        var distances = bfs.distances,
            precedingNode = bfs.preceding,
            finalString,
            path = new Stack();

        if(!(source === destination)) {
            path.push(destination);
        }

        var previousVertex = precedingNode[destination];
        //console.log(previousVertext);

        while (previousVertex !== null && previousVertex !== source) {
            path.push(previousVertex);
            previousVertex = precedingNode[previousVertex];
        }

        if(previousVertex === null) {
            console.log('Нет пути от ' + source + ' до ' + destination);
        } else { // the previous is equal to the source.
            //add the source
            path.push(source);
        }
        //print final results
        finalString = path.pop();
        var way = [];
        while(!path.isEmpty()) {
            temp_path = path.pop();
            way.push(temp_path);
            finalString += ' - ' + temp_path;
        }
        console.log(finalString);
        return way;
    };
}

//COMMON.JS
function format_by_count(count, form1, form2, form3){
    count = Math.abs(count) % 100;
    lcount = count % 10;
    if (count >= 11 && count <= 19) return(form3);
    if (lcount >= 2 && lcount <= 4) return(form2);
    if (lcount == 1) return(form1);
    return form3;
}

//Class FINDWAY BEST YO!
function FindWay(){
    this.graph = new Graph();
    this._vertices = []; //Вершины граф
    this.area_list = {};
    this.bfs = undefined;
    this.kind = 0;
    var fw = this;

    this.init = function(area_list, kind){
        this.kind = kind;

        Object.keys(area_list).forEach(function (area_id) {
            area_id = parseInt(area_id);
            if (!(fw._vertices.indexOf(area_id) != -1)) fw._vertices.push(area_id);

            area_list[area_id]['area_ids'].forEach(function (area_link) {
                if(parseInt(area_link['kind']) > 0 && parseInt(area_link['kind']) !== fw.kind){
                    return;
                }
                if (!(fw._vertices.indexOf(parseInt(area_link['id'])) != -1)) fw._vertices.push(parseInt(area_link['id']));
            });
        });

        Object.keys(area_list).forEach(function (area_id) {
            area_id = parseInt(area_id);
            area_list[area_id]['area_ids'].forEach(function (area_link) {
                if(parseInt(area_link['kind']) > 0 && parseInt(area_link['kind']) !== fw.kind){
                    //delete fw._vertices[fw._vertices.indexOf(parseInt(area_link['id']))];
                    return;
                }
            });
        });

        //Вершины и добавление каждоый веришины в графу
        fw._vertices.map(function (vertex) {
            fw.graph.addVertex(vertex);
        });

        //Создаем грани
        Object.keys(area_list).forEach(function (area_id) {
            area_id = parseInt(area_id);

            area_list[area_id]['area_ids'].forEach(function (area_link) {
                if(parseInt(area_link['kind']) > 0 && parseInt(area_link['kind']) !== fw.kind){
                    return;
                }
                area_id_x = parseInt(area_link['id']);

                //back have link
                fw.graph.addEdge(area_id, area_id_x, fw.back_have_check(area_id_x, area_id));
            });
        });
    };


    this.back_have_check = function(area_id, area_id_check){
        var have = false;
        if(!this.area_list[area_id]) return have;
        try{
            this.area_list[area_id]['area_ids'].forEach(function (area_link) {
                if(have) return;
                if(parseInt(area_link['kind']) > 0 && parseInt(area_link['kind']) !== fw.kind){
                    return;
                }
                if(area_id_check === parseInt(area_link['id'])){
                    have = true;
                    return;
                }
            });
        }catch (e) {}
        return have;
    };

    this.find = function (_t_area_id, _t_area_id_dest) {
        //Исключаем возможные зацикливания исключения граф
        if(!(this._vertices.indexOf(_t_area_id) !== -1) || !(this._vertices.indexOf(_t_area_id_dest) !== -1)) return false;
        this.bfs = fw.graph.breathFirstSearch(_t_area_id);
        //graph.shortestPathToAll(_t_area_id, bfs);
        var shortestPathFromTo = this.graph.shortestPathFromTo(this.bfs, _t_area_id, _t_area_id_dest);

        //Отладка
        console.log("Путь локаций: ", shortestPathFromTo);
        console.log("Путь локаций подробный: ");
        shortestPathFromTo.forEach(function (area_id) {
            console.log(area_list[area_id]['title'] + ' > ');
        });

        return shortestPathFromTo;


    }
}

function FindWayV2(){
    this._locverge = {};
    this._graph = [];
    this.cache_cont = [];
    this.area_list = {};
    this.kind = 0;
    var fw = this;

    this.init = function(area_list, kind){
        this.kind = kind;
        this.area_list = area_list;
    };

    this._search = function(v, k){
        var l = v.length;
        while(l--){ if(v[l] === k) {return true;} }
        return false;
    };

    this.nodeIndex = function(v){
        var l = this._graph.length;
        var o = 0;
        while (o < l){
            if(this._graph[o]['id'] === v) return o;
            o++;
        }
        return -1;
    };

    this.getNearEdge = function(v){
        var c = [];
        this._locverge.forEach(function (_loc_v, i) {
            var len = _loc_v.length;
            lp: do {
                while(len--){
                    var len2 = _loc_v[len].length;
                    while(len2--) {
                        if(_loc_v[len][len2] === v) {
                            c.push(i);
                            break lp;
                        }
                    }
                }
            } while (0)
        });
        return c;
    };

    this.clearArr = function(v, k){
        var l = k.length;
        while(l--) {
            var l2 = v.length;
            while(l2--) {
                if(v[l2] === k[l]) {
                    v.splice(l2,1);
                    l2++;
                }
            }
        }
    };

    this.getNextCacheEdge = function(v){
        this.cache_cont.forEach(function(cc, i){
            var l = cc.length;
            while(l--) {
                if(parseInt(cc[l]) === v) {
                    return i;
                }
            }
        });
        return 1;
    };

    this.buildGraph = function(){
        this._graph = [];
        this._locverge = {};
        Object.keys(this.area_list).forEach(function (area_id) {
            area_id = parseInt(area_id);

            _neighbors = [];
            area_list[area_id]['area_ids'].forEach(function (area_link) {
                if (parseInt(area_link['kind']) !== 0 && parseInt(area_link['kind']) !== fw.kind) return;
                _neighbors.push(parseInt(area_link['id']));
            });

            fw._graph.push({
                'id': area_id,
                'neighbors': _neighbors,
                'previous': -1
            });

            fw._locverge[area_id] = _neighbors;
        });
    };

    this.find = function(area_id, area_id_dest) {
        var pushed = false;
        this.buildGraph();
        openNodes = [];
        closedNodes = [];
        var node_index = this.nodeIndex(area_id);
        var node = this._graph[node_index];
        this._graph.splice(node_index,1);
        openNodes.push(node);
        if(area_id === area_id_dest) {
            return [];
        }
        if(node_index === -1) {
            return [];
        }
        while(openNodes.length > 0) {
            _queue = openNodes.shift();
            if(_queue['id'] === area_id_dest) {
                closedNodes.push(_queue);
                pushed = true;
                break;
            }
            _neighbrs = _queue['neighbors'];
            ix = 0;
            while(ix < _neighbrs.length) {
                iy = this.nodeIndex(_neighbrs[ix]);
                if(iy != -1) {
                    _tNode = this._graph[iy];
                    this._graph.splice(iy,1);
                    _tNode['previous'] = _queue['id'];
                    openNodes.push(_tNode);
                }
                ix++;
            }
            closedNodes.push(_queue);
        }
        if(!pushed) {
            return [];
        }
        f_way = [];
        _queue = closedNodes.pop();
        while(_queue['id'] != area_id) {
            f_way.push(_queue["id"]);
            ix = 0;
            while(ix < closedNodes.length) {
                if(closedNodes[ix]['id'] == _queue['previous'])
                {
                    _queue = closedNodes[ix];
                    break;
                }
                ix++;
            }
        }
        f_way.push(_queue["id"]);
        f_way = this.mirrow_arr(f_way);
        if(f_way[0] === area_id) f_way.splice(0, 1);
        return f_way;
    };

    this.mirrow_arr = function (arr) {
        var temp;
        for(var i=0,j=arr.length-1; i<j; i++,j--) {
            temp = arr[j];
            arr[j] = arr[i];
            arr[i] = temp;
        }
        return arr;
    }
}

function _getNavigatorData(){
    var _data = '';
    $.ajax('/_navigator_data.php', {
        dataType : 'json',
        data : {},
        complete : function(data, statusCode){
            _data = data.responseText;
        },
        async: false,
    });
    return _data;
}
