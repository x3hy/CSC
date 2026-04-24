// loads all the users from the
// database as a table.

async function toggle_admin(id, e){
	e.disabled = true;
	await toggle_admin_status(id);
	load_user_data();
}

async function load_user_data(){
	const table = document.getElementById("users-table");
	const _tbody = table.querySelector("tbody");
	const _thead = table.querySelector("thead");
	
	if (!_is_admin){
		_tbody.innerText = "Failed to load data (you don't have ring 2 permission)";
		return false; // youre not a admin bro..
	}
	
	const resp = await POST({
		"call": "list_users"
	});
	
	// if the server responded without success
	if (resp.status != 0){
		_tbody.innerText = "Failed to load data (internal error, this may occur if the pages _is_admin variable has been tampered with OR if you have had your admin access revoked during this session)";
		return false;
	}
	
	let new_content = "";
	
	// append the users to the dashboard
	resp.message.forEach(async (user, index) => {
		new_content += `
			<tr>
				<td>${user.id}</td>
				<td><strong>${(user.display  == null) ? "No Display" : user.display}</strong></td>
				<td>${user.username}</td>
				<td>${user.admin ? 
			"<svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\"><path d=\"M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z\"/></svg>" : "<i><svg xmlns=\"http://www.w3.org/2000/svg\" height=\"24px\" viewBox=\"0 -960 960 960\" width=\"24px\"><path d=\"m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z\"/></svg></i>"}</td>
				<td>
					<i>
						<button onclick="delete_user(${user.id});load_user_data()">Delete user</button>
						<button onclick="toggle_admin(${user.id}, this)">${user.admin ? "Revoke Admin" : "Make Admin"}</button>
						<button onclick="change_users_password(${user.id})">Change Password</button>
					</i>
				</td>
			</tr>
		`;
	});
	_tbody.innerHTML = new_content;
};

// load the page
(async ()=>{
	await validate_session_permanence();
	load_user_data();
	make_sortable_table("users-table");
})();

// edit data form:
const edit_form = document.getElementById("edit-form");
const edit_form_username = document.getElementById("edit-form-username");
const edit_form_display = document.getElementById("edit-form-display");
const edit_form_password = document.getElementById("edit-form-password");
const edit_form_password_confirm = document.getElementById("edit-form-password-confirm");
const edit_form_submit = document.getElementById("edit-form-submit");

edit_form.addEventListener("submit", async (e) => {
	e.preventDefault();
	let data = Object.fromEntries((new FormData(edit_form)).entries());
	
	for (const [key, value] of Object.entries(data))
  		if (value == "") delete data[key];
	
	// per field logic (*I'm tired boss*)
	if (data.password) {
		if (data.password != data.confirm){
			// passwords are not the same
			window.alert("Passwords must be the same");
			return;
		}
	}
	
	if (data.username){
		const username_resp = await validate_username(data.username);
		if (await username_resp.status != 0){
			set_error(await username_resp.message);
			return false;
		}
	}
});
