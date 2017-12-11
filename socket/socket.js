const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http);
const cors = require('cors');
const bodyParser = require('body-parser');
const api = require('./api_routes.js');
const auth = api.auth;

app.use(cors());
app.use(bodyParser.json());

app.get('/', (req, res) => res.send('StormChat Socket API'));

app.get('/bad_auth', (req, res) => res.send('Authorization failed'));

io.use(function (socket, next) {
    auth.room_access(socket.handshake.query, next);
});

io.sockets.on('connection', function (socket) {
    let chat_id = socket.handshake.query.chat_id;
    let user_id = socket.handshake.query.user_id;

    socket.join(chat_id);

    console.log(socket.id + ': ' + user_id + ' connected');
    io.to(chat_id).emit('update users');

    socket.on('edit workspace', function (update) {
        if (update.insert) {
            api.workspace_insert({
                user_id: user_id,
                chat_id: chat_id,
                pos: update.pos,
                since: update.since,
                content: update.insert
            },function () { io.to(chat_id).emit('update workspace');} );
        }
        else if(update.remove){
            api.workspace_remove({
                user_id: user_id,
                chat_id: chat_id,
                pos: update.pos,
                since: update.since,
                len: update.remove
            },function () { io.to(chat_id).emit('update workspace');} );
        }
    });

    socket.on('post message', function (msg) {
        api.post_message({user_id: user_id, chat_id: chat_id, message: msg.content}, function (result) {
            if (result.result === 1)
                console.log(socket.id + ': ' + user_id + ' sent  ' + msg.content);
                io.to(chat_id).emit('update messages');
        });
    });

    socket.on('disconnect', function () {
        api.leave_room({user_id: user_id}, function (result) {
            console.log(socket.id + ': ' + user_id + ' disconnected');
            io.to(chat_id).emit('update users');
        });
    });
});


http.listen(5000, function () {
    console.log("listening port 5000");
});

