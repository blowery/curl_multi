var express = require( 'express' );

const app = express(),
	morgan = require( 'morgan' );

app.use( morgan('combined' ) );

app.get( '/200', function( req, res ) {
	var wait = req.query.wait ? +req.query.wait : 0;
	wait = Math.max( Math.min( wait, 300 ), 0 ) || 0;
	setTimeout( function() {
		res.send( 'success!' );
	}, wait * 1000 );
} );

app.get( '/301', function( req, res ) {
	res.redirect( 301, '/200' );
} );

app.get( '/302', function( req, res ) {
	res.redirect(( 302, '/200' ) );
} );

app.get( '/304', function( req, res ) {
	res.sendStatus( 304 );
} );

app.get( '/500', function( req, res) {
	res.sendStatus( 500 );
} );

app.get( '/slowloris', function( req, res ) {
	var bytesSent = 0;
	function sendChunkAndQueue() {
		if ( bytesSent < 300 ) {
			res.write( 'xxx' );
			bytesSent = bytesSent + 3;
			setTimeout( sendChunkAndQueue, 50 );
		} else {
			res.end()
		}
	}

	res.setHeader('Connection', 'Transfer-Encoding');
	res.setHeader('Content-Type', 'text/text; charset=utf-8');
	res.setHeader('Transfer-Encoding', 'chunked');
	res.status( 200 );

	sendChunkAndQueue();

} );


app.listen( 3001 );
