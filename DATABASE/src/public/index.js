// Gets the current dir (same as php lolz)
const __DIR__ = (function() {
    const scripts = document.getElementsByTagName('script');
    const src = scripts[scripts.length - 1].src;
    return src ? src.substring(0, src.lastIndexOf('/')) : '';
})();

// Generate a password
async function generate_password(raw) {
    // Convert string to UTF-8 bytes
    const encoder = new TextEncoder();
    const data = encoder.encode(raw);

    // Compute SHA-256 hash using Web Crypto API (available in all modern browsers)
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);

    // Convert buffer to hex string (same format as PHP hash())
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');

    return hashHex;
}

// Check if the server is active
async function server_active(){
	const ret = await POST({"call": "ping", "content": ""});
	if (await ret == "")
		return true;
	return false;
}

// creds key names
const _username = "username";
const _password = "password";

// signs in a user
async function sign_in(username, password){
	// hash the password
	password = await generate_password(password);
	
	// set it into the local storage:
	localStorage.setItem(_username, username);
	localStorage.setItem(_password, password);
	
	// Call the auth API to check if the credentials are valid
	
	const ret = await POST({"call":"auth_ping", "content": ""});
	if (await ret == 0)
		return true;
	return await ret.message;
}

// Posts data to the back-end API
async function POST(content, callback = console.error) {
	const validate_php_file= __DIR__ + "/../../db/client.php";
	let out;
	
	content["username"] = localStorage.getItem(_username);
	content["password"] = localStorage.getItem(_password);
	
	if (content["username"] == null || content["password"] == null)
		content["username"] = content["password"] = false;
	
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
		//console.log(await out.text());
	} catch (err) {
		callback(err);
		return undefined;
	}
	
	// Return the response and set the session id token to the newly
	// generated token.
	console.log(`received ${JSON.stringify(await out)} from server.`);
	return await out;
}

async function validate_username(username){
	const username_resp = await POST(
		{"call":"username", "content" : data.username}
	);
	
	if (await username_resp.message != true)
		return await username_resp.message;
	return true;
}

async function validate_display(display){
	const display_resp = await POST(
		{"call":"display", "content" : data.username}
	);
	
	if (await display_resp.message != true)
		return await display_resp.message;
	return true;
}