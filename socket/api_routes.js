const client_http = require('http');

//ROUTES:
function post_message(params, callback) {
    api_call('/messages/post', params, next);
}


exports.post_message = post_message

//AUTH:

function user_access(params, next) {
    api_call('/users/auth', params, function(data) {
        is_auth(data, next)
    });
}

function room_access(params, next) {
    api_call('/rooms/auth', params, function (data) {
        is_auth(data, next)
    });
}

function is_auth(data, next) {
    if(data.result === 1)
        next();
    else
        throw new Error("Not authorized");
}

exports.auth.user_access = user_access;
exports.auth.room_access = room_access;

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
