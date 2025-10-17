function chatReceiveObject(objects) {
    for (var i in objects) {
        var fnc, fnc_name = 'controller_' + i.replace('|', '_');
        try {
            fnc = eval(fnc_name);
            if (typeof fnc == 'function') {
                var object = {};
                object[i] = objects[i];
                fnc.call(this, object);
            }
        } catch (e) {}
    }
}

function controller_cube_puton(obj) {
    swfObject('cube', obj);
}

function controller_cube_putoff(obj) {
    swfObject('cube', obj);
}

function controller_cube_craft(obj) {
    swfObject('cube', obj);
}

function controller_cube_use_recipe(obj) {
    swfObject('cube', obj);
}