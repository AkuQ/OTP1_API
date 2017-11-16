const app = require('express')();
const http = require('http').Server(app);
const io = require('socket.io')(http);
const cors = require('cors');
const mysql = require('mysql');
const bodyParser = require('body-parser');

app.use(cors());
app.get('/', (req, res) => res.send('Hello World!'));

var text = "";

io.on('connection', function(socket) {
    console.log("connected");
    socket.on('update', function(msg) {
        text = msg
        io.emit('updated', text);
        console.log(text);
    });


});


http.listen(5000, function() {
    console.log("listening port 3001");
});
