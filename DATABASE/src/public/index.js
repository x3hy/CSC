// Gets the current dir (same as php lolz)
const __DIR__ = (function() {
    const scripts = document.getElementsByTagName('script');
    const src = scripts[scripts.length - 1].src;
    return src ? src.substring(0, src.lastIndexOf('/')) : '';
})();

// Generate a password (sha-256)
async function generate_password(raw) {
    const data = new TextEncoder().encode(raw);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    return Array.from(new Uint8Array(hashBuffer))
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');
}

// Cred key names
const _username = "username";
const _password = "password";

// Gets the auth tokens
function get_local_auth(){
	let auth = {};

	// Get values from localStorage
	auth["username"] = localStorage.getItem(_username);
	auth["password"] = localStorage.getItem(_password);
	
	// Ensure they have a value
	if (auth["username"] == null || auth["password"] == null)
		auth["username"] = auth["password"] = false;
	
	return auth;
}

// Posts data to the back-end API
async function POST(content, callback = console.error) {
	const validate_php_file= __DIR__ + "/../../db/client.php";
	let out;
	
	content["auth"] = get_local_auth();
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

// Check if the server is active
async function ping_server(){
	const ret = await POST({"call": "ping", "content": ""});
	if (await ret == "")
		return true;
	return false;
}

function open_sign_in(){
	location.href = __DIR__ + "/../../index.html";
}

function open_dashboard(){
	location.href = __DIR__ + "/../../dashboard.html";
}

// Signs in a user
async function sign_in(username, password){
	// hash the password
	password = await generate_password(password);
	
	// set it into the local storage:
	localStorage.setItem(_username, username);
	localStorage.setItem(_password, password);
	return await POST({"call":"auth_ping"});
 }

// Checks if a user has signed in or not already
async function validate_session(){
	const sign_in_resp = await POST({"call":"auth_ping"});
	if (sign_in_resp.status == 0)
		return true;
	return false;
}

// if a user is not signed in then they will be
// sent to the sign-in page.
async function validate_session_permanence(){
	if (await validate_session() == false){
		sign_out();
		open_sign_in();
	}
}

// Signs the user out!
function sign_out (){
	// Clear the users session
	localStorage.clear();
}

// Removes all occurances of a pattern
// if the current user is not authenticated
async function auth_class_remove(pattern){
	if (await validate_session() == false){
		document.querySelectorAll(pattern)
		.forEach((element) => {
			element.remove();
		});
	}
}

// Removes all occurances of a pattern
// if the current user IS authenticated
async function auth_class_remove(pattern){
	if (await validate_session() == true){
		document.querySelectorAll(pattern)
		.forEach((element) => {
			element.remove();
		});
	}
}
