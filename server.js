var app = require('express')();
var fs = require('fs');

var debug = require('debug')('STREAMNOW:sockets');
var request = require('request');
var dotenv = require('dotenv').config();

var port = process.env.PORT || '3002';
var chat_save_url = process.env.APP_URL;

var https = require('https');

var SSL_KEY = process.env.SSL_KEY;
var SSL_CERTIFICATE = process.env.SSL_CERTIFICATE;

var server = https.createServer({ 
                key: fs.readFileSync(SSL_KEY),
                cert: fs.readFileSync(SSL_CERTIFICATE) 
             },app);

var io = require('socket.io')(server);

// Sender will user_id and receiver will provider_id

server.listen(port);

// room will be the live video id

io.on('connection', function (socket) {

    socket.join(socket.handshake.query.room);

    socket.emit('connected', 'Connection to server established!');

    socket.on('update sender', function(data) {

        console.log("Update Sender START");

        console.log('update sender', data);

        socket.handshake.query.room = data.room;

        // socket.handshake.query.reqid = data.reqid;

        // socket.reqid = socket.handshake.query.reqid;

        socket.join(socket.handshake.query.room);

        socket.emit('sender updated', 'Sender Updated ID:'+data.room);

        console.log("Update Sender END");

    });

    console.log("ROOM ID"+socket.handshake.query.room);

    socket.on('message', function(data) {

        console.log("Send message",data);

        data.room = socket.handshake.query.room;

        socket.broadcast.to(data.room).emit('message', data);

        url = chat_save_url+'message/save?user_id='+data.user_id
        +'&live_video_viewer_id='+data.live_video_viewer_id
        +'&message='+data.message
        +'&type='+data.type
        +'&live_video_id='+data.live_video_id;

        console.log(url);

        request.get(url, function (error, response, body) {

        });

    });

    socket.on('disconnect', function(data) {
        debug('disconnect', data);
    });

    socket.on('check-video-streaming', function(data) {

       console.log("final_count "+data);

       var room = socket.handshake.query.room;
       
       socket.broadcast.to(room).emit('video-streaming-status', data);

      // socket.emit('video-streaming-status', no_of_views, video_id);

  });

});
