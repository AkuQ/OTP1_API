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

    socket.join(room_id);
    console.log(socket.id + " joined");

    socket.on('update', function (msg) {
        text = msg;
        io.to(chat_id).emit('updated', text);
        console.log(text);
    });

    socket.on('send message', function (msg) {
        api.post_message({user_id: user_id, chat_id: chat_id, msg: msg.content}, function (result) {
            if (result.result === 1)
                io.to(chat_id).emit('update messages', msg);
        });
    });

    socket.on('user enter', function (user) {
        //Should have already joined the room if able to access socket,
        //(API call not necessary)
        io.to(chat_id).emit('update users');
    });

    socket.on('disconnect', function () {
        api.leave_room({user_id: user_id}, function (result) {
            io.to(chat_id).emit('update users');
        });
    });
});


http.listen(5000, function () {
    console.log("listening port 5000");
});

