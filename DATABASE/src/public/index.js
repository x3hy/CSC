// Gets the current dir (same as php lolz)
const __DIR__ = (function() {
    const scripts = document.getElementsByTagName('script');
    const src = scripts[scripts.length - 1].src;
    return src ? src.substring(0, src.lastIndexOf('/')) : '';
})();

// Posts data to the back-end API
async function POST(content, callback = console.error) {
	const validate_php_file= __DIR__ + "/../../db/client.php";
	let out;
	
	content["username"] = false;
	content["password"] = false;
	console.log(`sending ${ JSON.stringify(content)} to server.`);
		
	try {
		// Post the data to `validate_php_file`
		out = await fetch(validate_php_file, {
    		method: 'POST',
    		headers: {'Content-Type': 'application/json'},
    		body: JSON.stringify(content)
		});
		// Convert it to JSON
		out = await out.json();
	} catch (err) {
		callback(err);
		return undefined;
	}
	
	// Return the response and set the session id token to the newly
	// generated token.
	console.log(`received ${await out} from server.`);
	return await out;
}

/*
(async () => {
	console.log(await POST({"call": "ping", "content" : "", "session": "test123",}));
})();
*/