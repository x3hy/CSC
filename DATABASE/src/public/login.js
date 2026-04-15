const _form_submit = document.getElementById("login-form-submit");
const _form_switch = document.getElementById("login-form-switch");
const _form_header = document.getElementById("login-form-header");
const _form_errors = document.getElementById("login-form-errors");
const _form = document.getElementById("login-form");
const _title = document.getElementById("title");
let _let_submit_form = false;

// Submit form
_form_submit.addEventListener("click", () => {_let_submit_form = true});

// Sets the title of the page based on another elements contents.
const get_mode = (()=>{
	return window
		.getComputedStyle(_form_header, '::before')
		.getPropertyValue('content')
		.replaceAll('"', '');
});

// Sets a new error message
function set_error(msg){
	console.error(msg);
	_form_errors.innerText = msg;
	_form_errors.style.display = "block";
}

// Removes the error message
function clear_error(){
	_form_errors.innerText = "";
	_form_errors.style.display = "none";
}

// Creates a new user
async function sign_up(username, password){
	// hash the password
	password = await generate_password(password);
		
	// set it into the local storage:
	localStorage.setItem(_username, username);
	localStorage.setItem(_password, password);
	
	return await POST({"call":"create_user"});
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

// Toggle between the sign-in and sign-up pages
_title.innerText = get_mode();
_form_switch.addEventListener("click", (e) => {
	_form.classList.toggle("form-sign-in");
	_title.innerText = get_mode();
});



(async ()=>{
	if (await validate_session() == true)
		open_dashboard();
})();


// Disable submission of the form unless permitted
_form.addEventListener("submit", (e)=>{
	e.preventDefault();
	clear_error();
	setTimeout(()=>{
		if (!_let_submit_form) return;
		_let_submit_form = false;

		// Executed on submission:
		let data = Object.fromEntries((new FormData(_form)).entries()); 

		// if mode == 0 then its a sign-in submission
		// if mode == 1 then its a sign-up submission
		const mode = (get_mode() == "Sign In") ? 0 : 1;

		// VALIDATION THINGS:
		
		// Check if the passwords match when signing up
		if (mode && data.password != data.passwordconfirm){
			set_error("Passwords do not match");
			return;
		}

		// Ensure that a username has been provided
		if (data.username == ""){
			set_error("Username is required");
			return;
		}
		
		// Ensure that a password has been provided
		if (data.password == ""){
			set_error("Password is required");
			return
		};

		delete data.passwordconfirm;
		
		// VERIFICATION THINGS:

		// Now to verify with the server to check if the props are valid
		if (!(async () => {
			// for SIGN UP mode:
			if (mode){
				// check username:
				const username_resp = await validate_username(data.username);
				if (await username_resp.status != 0){
					set_error(await username_resp.message);
					return false;
				}
				
				// check display name:
				if (data.display != undefined){
					const display_resp = await validate_display(data.display);
					if(await display_resp.status != 0){
						set_error(await display_resp.message);
						return false;
					}
				}
				
				// check password:
				const password_resp = await validate_password(data.password);
				if(await password_resp.status != 0){
					set_error(await password_resp.message);
					return false;
				}
				// Sign up user:
				const sign_up_resp = await sign_up(data.username, data.password);
				if (await  sign_up_resp.status != 0){
					set_error(await sign_up_resp.message);
					return false;
				}
			}
			
			// Now both the username and displayname have been validated. The
			// password and username have been provided, we can now move over
			// to the sign in process
			const sign_in_resp = await sign_in(data.username, data.password);
			if (await  sign_in_resp.status != 0){
				set_error(await sign_in_resp.message);
				return false;
			}
			
			// Move to the dashboard page after successfully authenticating the user
			open_dashboard();
		})()) return;
	}, 200);
});
