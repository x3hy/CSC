const post_message = document.getElementById("post-message");
const post_user = document.getElementById("post-user");
const post_time = document.getElementById("post-time");
const quote = document.getElementById("post-quote");
const post_e = document.getElementById("post-info");
const form_submit = document.getElementById("form-submit");
const form_content = document.getElementById("form-content");
const form_cancel = document.getElementById("form-cancel");
let post_id = false;

// Load data onto the page:
async function load_post_data(id){
	if (id == undefined){
		window.alert("Id given is undefined.. this will break something");
		return;
	}
	
	const post = await POST({"call": "get_post", "content": id});
	if (post.status != 0)
		open_error(post.message, 404);
	
	// Set quoted information:
	post_message.innerText = post.message.description;
	post_time.innerText = time_since(post.message.time_issued);
	if (is_deleted_user(post)){
		post_user.innerText = "Deleted User";
	} else post_user.innerText = `<a>${resolve_name(post)}</a>`;
	

	quote.addEventListener("click", ()=>{
		open_post(`?post_id=${post.message.id}`)
	});
	post_e.style.display = "block";
}

// Load the post, more comment in feed.js
post_e.style.display = "none";
const params = new URLSearchParams(String(location.href).split("?")[1]);
for (let pair of params.entries())
	if (pair[0] == "post_id"){
		post_id = pair[1];
		if (post_id != false) 
			load_post_data(post_id);
		else window.alert("Failed to load post #" + post_id);
	}

form_submit.addEventListener("click", async ()=>{
	console.log(form_content.value);
	let packet = {};
	packet["content"] = form_content.value;
	if (post_id != false)
		packet["parent_id"] = post_id;
	
	console.log(packet);
	const resp = await POST({"call": "create_post", "content": packet});
	if (resp.status == 0)
		form_cancel.click();
	else 
		window.alert(resp.message);
});

form_cancel.addEventListener("click", async ()=>{
	location.href = (__DIR__ +  "../../../feed.html" + String(await post_id ? `?post_id=${await post_id}` : ""));
});
