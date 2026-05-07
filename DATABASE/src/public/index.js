// Gets the current dir (same as php lolz)
const __DIR__ = (function() {
    const scripts = document.getElementsByTagName('script');
    const src = scripts[scripts.length - 1].src;
    return src ? src.substring(0, src.lastIndexOf('/')) : '';
})();

function resolve_name(row){
	return row.display ? row.display : row.username ? row.username : "Deleted User";
}

function is_deleted_user(row){
	return (row.display == undefined && row.username == undefined);
}


async function generate_password(raw) {
    const data = new TextEncoder().encode(raw);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    return Array.from(new Uint8Array(hashBuffer))
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');
}

// Posts data to the back-end API
let _resp_count = 0; async function POST(content, callback = console.error) {
	const validate_php_file= __DIR__ + "/../../db/client.php";
	let out;
	const _resp_id = (_resp_count++);
	
	content["auth"] = get_local_auth();
	console.log(`#${_resp_id} [POST] ${ JSON.stringify(content)} to server.`);
		
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
	console.log(`#${_resp_id} [GET] ${JSON.stringify(await out)} from server.`);
	return await out;
}

// if a user is not signed in then they will be
// sent to the sign-in page. callback is run if user
// is not signed in.
async function validate_session_permanence(callback){
	function _callback(){
		sign_out();
		open_error("You are not signed in.", 202);
	}
	
	// check ring 2 access first, if this passes then
	// we know the user must have valid id to pass ring 1.
	// 
	// if we simply checked the validity using the regular
	// auth call then we'd also have to figure out if the
	// user was an admin, this function kills two auths with
	// one request.
	await update_admin_status();
	if (!_is_admin){ // does user have ring-2?
		await update_session();
		if (!_is_valid) // does user have ring-1?
			if (callback == undefined)
				_callback(); // logout
			else callback();
	} else _is_valid = true; // sudoku
	
	// Hooks section:
	init_auth_elements();
	return _is_loaded = true; // for other functions
}

// Cred key names
const _username = "username";
const _password = "password";
let _is_admin = false;
let _is_valid = false;
let _is_loaded = false;

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

// self explanitory..
async function delete_user(id){
	let resp;
	if (id != undefined){
		if (is_user_admin() == false)
			return false; // you dont have permission
		
		if (!window.confirm(`Are you sure you want to delete user #${id}`))
			return false;
		
		resp = await POST({
			"call":"delete_user", "content": id
		});
	} else {
		if (!window.confirm(`Are you sure you want to delete your account (${await get_name()})`))
			return false;
			
		resp = await POST({
			"call":"delete_self"
		});
	}
	
	if (await resp.status != 0)
		return false; // server responded badly
	
	if (id == undefined)
		open_error("Account Deleted.", 200);
	return true;
}

// Check if the server is active
async function ping_server(){
	const ret = await POST({"call": "ping", "content": ""});
	if (await ret.status == 0)
		return true;
	return false;
}

function open_sign_in(){
	location.href = __DIR__ + "/../../sign_in.html";
}

function open_dashboard(){
	location.href = __DIR__ + "/../../dashboard.html";
}

function open_home(){
	location.href = __DIR__ + "/../../";
}

function open_feed(extra){
	let href = __DIR__ + "/../../feed.html";
	if (extra != undefined)
		href +=  extra;
	location.href = href; 
}

function open_create(extra){
	let href = __DIR__ + "/../../create.html";
	if (extra != undefined)
		href += extra;
	location.href = href;
}

function open_profile(extra){
	let href = __DIR__ + "/../../user.html";
	if (extra != undefined)
		href += extra;
	location.href = href;
}

function open_error(reason, code = 200){
	let href = __DIR__ + "/../../error.html";
	if (reason != undefined)
		href += `?q=${reason}&c=${code}`;
	location.href = href;
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
function validate_session(){
	return _is_valid;
}

async function update_session(){
	const sign_in_resp = await POST({"call":"auth_ping"});
	_is_valid = (sign_in_resp.status == 0);
	return _is_valid;
}

async function get_name() {
    const auth = get_local_auth();
    if (_is_valid) {
        const resp = await POST({"call": "get_display","content": auth["username"]});
        if (resp.status !== 0)
            return auth["username"];
        return resp.message;
    }
    return auth["username"];
}

// returns a boolian for if the user is or is not an admin
async function is_user_admin(id){
	if (id !== undefined)
		return await get_admin_status(id);
	return _is_admin;
}

async function get_admin_status(id){
	let resp;
	if (id !== undefined){
		resp = await POST({"call":"is_admin_id", "content": id});
	} else resp = await POST({"call":"is_admin"});
	return (resp.status == 0);
}

//
async function update_admin_status(){
	_is_admin = await get_admin_status();
	return await _is_admin;
}

async function toggle_admin_status(id) {
	if (id == undefined) return false;
	const resp = await POST({"call": "toggle_admin", "content": id});
	return (resp.status == 0);
}

async function validate_session(){
	return await validate_session_permanence(()=>{});
}

// Signs the user out by clearing thier username and
// password from localStorage.
function sign_out (){
	localStorage.clear();
}

// Removes certain elements depending on authentication
// level.
async function init_auth_elements(){
	const auth_class = "auth";
	const non_auth_class = "non-auth";
	const admin_class = "admin";
	
	// If the user is authed:
	if (_is_valid == true){
		document.querySelectorAll("." + auth_class)
			.forEach(element => element.classList.remove(auth_class));
		document.querySelectorAll("." + non_auth_class)
			.forEach(element => element.remove());
	}
	
	// If the user is also an admin:
	if (_is_admin){
		document.querySelectorAll("." + admin_class)
		.forEach(element => element.classList.remove(admin_class));
	}

	const name = await get_name();
	document.querySelectorAll(".display")
	.forEach(async (element) => {
		element.innerHTML = await name;
	});
}

// From stackoverflow
function make_sortable_table(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return console.error("Table not found:", tableId);

  const tbody = table.querySelector("tbody");
  const headers = table.querySelectorAll("th");
  let currentSort = { columnIndex: null, direction: 1 };

  function getCellValue(tr, index) {
    return tr.children[index].textContent.trim();
  }

  function sortTable(columnIndex) {
    if (currentSort.columnIndex === columnIndex) {
      currentSort.direction *= -1;
    } else {
      currentSort.columnIndex = columnIndex;
      currentSort.direction = 1;
    }

    // Update header styles
    headers.forEach((th, i) => {
      th.classList.remove("sorted-asc", "sorted-desc");
      if (i === columnIndex) {
        th.classList.add(
          currentSort.direction === 1 ? "sorted-asc" : "sorted-desc"
        );
      }
    });

    const rows = Array.from(tbody.querySelectorAll("tr"));

    rows.sort((a, b) => {
      let valA = getCellValue(a, columnIndex);
      let valB = getCellValue(b, columnIndex);

      let numA = parseFloat(valA);
      let numB = parseFloat(valB);

      if (!isNaN(numA) && !isNaN(numB)) {
        return (numA - numB) * currentSort.direction;
      }

      return valA.localeCompare(valB) * currentSort.direction;
    });

    // Re-append rows in sorted order (preserves innerHTML + events)
    rows.forEach(tr => tbody.appendChild(tr));
  }

  headers.forEach((th, index) => {
    th.style.cursor = "pointer";
    th.addEventListener("click", () => sortTable(index));
  });
}


// Validates a username using server api
async function validate_username(username){
	 return await POST(
		{"call":"username", "content" : username}
	);
}

// Validates a display name using the server api
async function validate_display(display){
	return await POST(
		{"call":"display", "content" : display}
	);
}

// Validates a password using the server api
async function validate_password(password){
	return await POST(
		{"call":"password", "content" : password}
	);
}

function time_since (date)
		{
			const fmt = (time, ext) =>
				{return `${time} ${ext}${(time >= 1) ? "s" : ""}`}
		
			const old_date = new Date(date);
			let seconds = Math.floor((new Date() - old_date) / 1000)
			
			var interval = seconds / 31536000;
			if (interval > 1)
				return fmt(Math.floor(interval), "year");
			
			interval = seconds / 2592000;
			if (interval > 1)
				return fmt(Math.floor(interval),"month");
			
			interval = seconds / 86400;
			if (interval > 1)
				return fmt(Math.floor(interval),"day");
			
			interval = seconds / 3600;
			if (interval > 1)
				return fmt(Math.floor(interval), "hour");
			
			interval = seconds / 60;
			if (interval > 1)
				return fmt(Math.floor(interval), "minute");
			
			return fmt(Math.floor(seconds), "second");
		}
