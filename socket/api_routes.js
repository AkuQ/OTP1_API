const client_http = require('http');

/**
 * @callback requestCallback
 * @param {object} result_body
 */


//ROUTES:

/**
 * @param {object} params
 * @param {int} params.chat_id
 * @param {int} params.user_id
 * @param {string} params.message
 * @param {requestCallback} callback
 */
function post_message(params, callback) {
    api_call('/messages/post', params, callback);
}

/**
 * @param {object} params
 * @param {int} params.user_id
 * @param {requestCallback} callback
 */
function leave_room(params, callback) {
    api_call('/rooms/join', params, callback);
}

function workspace_insert(params, callback) {
    api_call('/workspaces/insert', params, callback);
}

function workspace_remove(params, callback) {
    api_call('/workspaces/remove', params, callback);
}

exports.post_message = post_message;
exports.leave_room = leave_room;
exports.workspace_insert = workspace_insert;
exports.workspace_remove = workspace_remove;

//AUTH:

/**
 * @param {object} params
 * @param {int} params.user_id
 * @param {string} params.token
 * @param {requestCallback} callback
 */
function user_access_auth(params, callback) {
    api_call('/users/auth', params, function(data) {
        is_auth(data, callback)
    });
}

/**
 * @param {object} params
 * @oaram {int} params.chat_id
 * @param {int} params.user_id
 * @param {string} params.token
 * @param {requestCallback} callback
 */
function room_access_auth(params, callback) {
    api_call('/rooms/auth', params, function (data) {
        is_auth(data, callback)
    });
}

function is_auth(data, next) {
    if(data.result === 1) {
        next();
    }
    else {
        let err = new Error('Authentication error');
        err.data = {type: 'authentication_failed'};
        next(err);
    }
}

var auth = {
  user_access: user_access_auth,
  room_access: room_access_auth
};

exports.auth = auth;

//PRIVATE:

function api_call(path, param, callback)
{
    var options = {
        host: "localhost",
        port: 80,
        path: '/api' + path,
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    };

    let auth_req = client_http.request(options, function(auth_res) {
        let data = '';
        auth_res.on('data', function(chunk) {data += chunk;} );
        auth_res.on('end', function()  {callback(JSON.parse(data));} );
    });
    auth_req.write(JSON.stringify(param));
    auth_req.end();
}
