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
    let room_id = socket.handshake.query.room_id;
    let user_id = socket.handshake.query.user_id;

    socket.join(room_id);
    console.log(socket.id + " joined");

    socket.on('update', function (msg) {
        text = msg;
        io.to(room_id).emit('updated', text);
        console.log(text);
    });

    socket.on('send message', function (msg) {
        api.post_message(msg, function (result) {
            if (result.result === 1)
                io.to(room_id).emit('receive message', msg);
        });
    });

    socket.on('user enter', function (user) {
        io.to(room_id).emit('update entered');
    });

    socket.on('disconnect', function () {
        // api_call.logout_user(user_id);
        io.to(room_id).emit('update users');

    });
});


http.listen(5000, function () {
    console.log("listening port 5000");
});

