const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http);
const cors = require('cors');
const mysql = require('mysql');
const bodyParser = require('body-parser');

app.use(cors());
app.get('/', (req, res) => res.send('Hello World!'));

var text = "";
var nsp = io.of('/my-namespace');
nsp.on('connection', function(socket) {
    console.log("nsp");
});

const con = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "otp1",
    database: "TEST_OTP_API"
});

io.on('connection', function(socket) {
    console.log("connected");
    socket.on('update', function(msg) {
        text = msg
        io.emit('updated', text);
        console.log(text);
    });

    socket.on('send message', function(msg) {
        postMessage(msg);
        io.emit('receive message', msg);
    });

    socket.on('user enter', function(user) {
        logUser(user.id, socket.id);
        io.emit('user entered');
    })


    socket.on('disconnect', function() {
        logoutUser(socket.id);
        io.emit('user disconnect');
    })




});

function postMessage(message) {
    let sql = "INSERT INTO message (chat_id, user_id, content, created) VALUES (";
    let escaped = message.chat_id + ", " + message.user_id + ", " + con.escape(message.content) + ", " + con.escape(message.created) + ")";
    con.query(sql + escaped, function (err, result, fields) {
        if (err)
            throw err;
    });
}

function logUser(user_id, socket) {
    con.query("UPDATE user SET logged='true', socket='" + socket + "' WHERE user_id='" + user_id + "'");
}
function logoutUser(socket) {
    con.query("UPDATE user SET logged='false' WHERE socket='" + socket + "'");

}

http.listen(5000, function() {
    console.log("listening port 3001");
});
