
// loads all the users from the
// database as a table.
async function load_user_data(){
	const table = document.getElementById("users");
	if (await is_user_admin() == false){
		table.innerText = "Failed to load data (you don't have permission)";
		return false; // youre not a admin bro..
	}
	
	const resp = await POST({
		"call": "list_users"
	});
	
	// if the server responded without success
	if (resp.status != 0){
		table.innerText = "Failed to load data (internal error)";
		return false;
	}
	
	// append the users to the dashboard 
	table.innerHTML = "";
	resp.message.forEach(user => {table.innerHTML += `
		<tr>
			<td>${user.id}</td>
			<td><strong>${user.display}</strong></td>
			<td>${user.username}</td>
			<td><code>${user.password}</code></td>
			<td>${is_user_admin(user.id) ? "TRUE" : "FALSE"}</td>
			<td>
				<i>
					<button onclick="delete_user(${user.id});load_user_data()">Delete user</button>
				<i>
			</td>
		</tr>`
	});
}; load_user_data();

const edit_form = document.getElementById("edit-form");
edit_form.addEventListener("submit", (e)=>{e.preventDefault()});