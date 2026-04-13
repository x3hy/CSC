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

// Toggle between the sign-in and sign-up pages
_title.innerText = get_mode();
_form_switch.addEventListener("click", (e) => {
	_form.classList.toggle("form-sign-in");
	_title.innerText = get_mode();
});

// Disable submission of the form unless permitted
_form.addEventListener("submit", (e)=>{
	e.preventDefault();
	_form_errors.innerText = "";
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
			_form_errors.innerText = "Passwords do not match";
			return;
		}

		// Ensure that a username has been provided
		if (data.username == ""){
			_form_errors.innerText = "Username is required";
			return;
		}
		
		// Ensure that a password has been provided
		if (data.password == ""){
			_form_errors.innerText = "Password is required";
			return
		};

		delete data.passwordconfirm;
		
		// VERIFICATION THINGS:

		// Now to verify with the server to check if the props are valid
		if (!(async () => {
			if (mode){
				// check username:
				const username_resp = validate_username(data.username);
				if (await username_resp != true){
					_form_errors.innerText = await username_resp;
					return false;
				}
				
				const display_resp = validate_display(data.display);
				if(await display_resp.message != true){
					_form_errors.innerText = await display_resp.message;
					return false;
				}
				
				// Now both the username and displayname have been validated. The
				// password and username have been provided, we can now move over
				// to the sign in process.	
			}
			
			const ret = sign_in(await data.username, data.password);
			if (ret != true){
				_form_errors.innerText = await ret;
				return false;
			}
			
			return true;
		})()) return;

	}, 200);
});