function array_combine (keys, values) {
    var new_array = {},
    keycount = keys && keys.length,
    i = 0;
    if (keycount !== values.length) {
        return false;
    }

    // input sanitation
    if (typeof keys !== 'object' || typeof values !== 'object'
        || typeof keycount !== 'number' 
        || typeof values.length !== 'number' 
        || !keycount) {
            return false;
    }
    if (keycount != values.length) {
        return false;
    }

    for (i = 0; i < keycount; i++) {
        new_array[keys[i]] = values[i];
    }

    return new_array;
}
function renameKeysInMultiArray (arrayObj, from, to) {
    for (key in arrayObj) {
        arrayObj[key].to = arrayObj[key].from;
        delete arrayObj[key].from;
    }
}