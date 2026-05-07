const _feed_table = document.getElementById("feed");
let _feed_tbody = _feed_table.querySelector("tbody");
const _body = document.getElementById("body");
const _path = document.getElementById("post-path");

function upvote_post_id(id, e){
	e.classList.toggle("active");
}

function downvote_post_id(id, e){
	e.classList.toggle("active");
}

// macro to create an element
function ce(t, content){
	const ele = document.createElement(t);
	if (content != undefined) ele.innerHTML = content;
	return ele;
}

async function remove_vote(id){
	const resp = await POST({"call": "remove_vote", "content": id});
	if (resp.status != 0){
		// TODO: update this implementation
		window.alert(resp.message);
		return;
	}
	
	return;
};

async function upvote_post(id){
	const resp = await POST({"call": "upvote_post", "content": id});
	if (resp.status != 0){
		// TODO: update this implementation
		window.alert(resp.message);
		return;
	}
	
	return;
};

async function downvote_post(id){
	const resp = await POST({"call": "downvote_post", "content": id});
	if (resp.status != 0){
		// TODO: update this implementation
		window.alert(resp.message);
		return;
	}
	
	return;
};

function format_table_row(row){
	// Generate the new table:
	const table_row = ce("tr", `
		<td>${row.id}</td>
	`);
	
	// Create the score counter
	const score_container = ce("td");
	let old_score = row.score;
	
	function load_score(){
		score_container.innerText = row.score;
		if (row.score > 0){
			score_container.classList.add("accent");
			score_container.classList.remove("warning");
			score_container.title = "Score is positive";
		}
		else if (row.score < 0){
			score_container.classList.add("warning");
			score_container.classList.remove("accent");
			score_container.title = "Score is negative";
		} else {
			score_container.classList.remove("accent");
			score_container.classList.remove("warning");
			score_container.title = "Post has no score.. Yet";
		}
	}; load_score();
	
	// Load the buttons
	const button_container = ce("td", `
		<button class="up"></button>
		<button class="down"></button>
	`);
	button_container.classList.add("auth");
	
	// Vote button(s) functionality
	const button_up = button_container.querySelector(".up");
	const button_down = button_container.querySelector(".down");
	
	function _button_up(send_req){
		row.score = old_score;
		if (button_up.classList.contains("active")){
			button_up.classList.remove("active");
			if (send_req != false)
				remove_vote(row.id);
		} else {
			button_down.classList.remove("active");
			button_up.classList.add("active");
			row.score += 2;
			if (send_req != false)
				upvote_post(row.id);
		}
		
		load_score();
	}
	
	// when button up is clicked
	button_up.addEventListener("click", _button_up);
	
	function _button_down(send_req){
		row.score = old_score;
		if (button_down.classList.contains("active")){
			button_down.classList.remove("active");
			if (send_req != false)
				remove_vote(row.id);
		} else {
			button_up.classList.remove("active");
			button_down.classList.add("active");
			row.score -= 1;
			if (send_req != false)
				downvote_post(row.id);
		}
		
		load_score();
	}
	
	// when button down is clicked
	button_down.addEventListener("click", _button_down);
	
	if (_is_valid && row.user_vote){
		if (row.user_vote.is_upvote){
			old_score = row.score -= 2;
			_button_up(false);
		}
		else if (row.user_vote.is_downvote){
			old_score = row.score += 1;
			_button_down(false);

		}
	}
	
	const user_container = ce("td");
	if (is_deleted_user(row))
		user_container.innerHTML = "Deleted User";
	else {
		const user_link = ce("a", resolve_name(row));
		user_link.addEventListener("click", ()=>{
			open_profile(`?user_id=${row.user_id}`);
		});
		user_container.append(user_link);
	}
		
	
	const description_container = ce("td",`<code><pre>${row.description}</pre></code>`);
	const time_container = ce("td", time_since(row.time_issued));
	const action_container = ce("td", `
		<button onclick="open_feed('?post_id=${row.id}')" ${row.comment_count == 0 ? "title=\"There are no comments\"" : ""}>
			Open Comments (${row.comment_count})
		</button>
	`);
	
	// Allows the owner of a post to delete said post
	if (row.owned_by_user || _is_admin){
		// Create a delete button:
		const delete_post = ce("button", "Delete Post");
		delete_post.classList.add("warning");
		action_container.append(delete_post);
		
		// When the button is clicked:
		delete_post.addEventListener("click", async ()=>{
			
			// Confirm that the user knows what they are doing
			if (!window.confirm("Are you sure you want to delete this post?"))
				return;
			
			// Send the deletion request to the server
			const resp = await POST({"call": _is_admin ? "delete_post_admin" : "delete_post", "content" : row.id});
			if (resp.status == 1)
				// Show errors
				window.alert(resp.message);
			
			// Reload the feed page (will visually remove the post)
			setTimeout(()=>{
				load_feed_page();
			}, 1000);
		});
	}
	/*
	if (row.comment_count == 0){
		const create_comment = ce("button", "Comment First");
		create_comment.addEventListener("click", async () => {
			open_feed
		});
	}
	*/
	
	// Append prior to the table
	table_row.append(button_container);
	table_row.append(score_container);
	table_row.append(user_container);
	table_row.append(description_container);
	table_row.append(time_container);
	table_row.append(action_container);
	
	// Return the generated table
	return table_row;
}

// Load the feed from a certain id root
async function load_feed_from_id(id){
	// Get the new post data
	const posts = await POST({"call": "get_posts", "content": id});
	
	// Generate a new table
	const new_table = ce("tbody");
	if (!Array.isArray(posts.message) || posts.message.length === 0) {
		new_table.append("Post has no comments, press the \"Create\" button on the navbar to add one!");
	} else {
		posts.message.forEach((post, index) => {
			new_table.append(
				format_table_row(post)
			);
		});
	}
	
	// Set the new table
	_feed_tbody.replaceWith(new_table);
	_feed_tbody = new_table;
	
	// Ensure the table is sortable
	make_sortable_table("feed");
	
	// Shows the upvotes only if the user is signed in
	init_auth_elements();
}

async function load_post_data(id){
	const post = await POST({"call": "get_post", "content": id});
	if (post.status != 0)
		open_error(post.message, 404);
	
	_body.innerHTML = `
		<!-- Beautiful template code! -->
		<p>Post Information:</p>
		<i><blockquote>
			<h2>Post #${post.message.id}</h2>
			<sub>
				Posted by <b>${resolve_name(post.message)}</b>,
				<b title=${post.message.time_issued}>${time_since(post.message.time_issued)} ago</b>.
			</sub>
			<br>
			<code>
				Current Score: <span class="${post.message.score > 0 ? 'accent' : post.message.score < 0 ? 'warning' : ''}">${post.message.score}</span>
				<br>
				<a onclick="open_feed(${post.message.parent_id ? `'?post_id=${post.message.parent_id}'` : ''})">
					${post.message.parent_id ? "Open Parent" : "Back"}
				</a>
			</code>
		</blockquote></i>
		<pre>${post.message.description}</pre>
		<hr>
		<p>This post has the following comments:</p>
		<br>`
}

async function load_feed_page(){
	_feed_tbody.innerHTML = "";
	// If post_id is set in the URLSearchParams then
	// load it as the root feed ID. This allows for
	// static recursion, where each branch is loaded
	// statically like it was the root	
	const params = new URLSearchParams(String(location.href).split("?")[1]);
	let post_id = -1;
	for (let pair of params.entries())
		if (pair[0] == "post_id") post_id = Number(pair[1]);
	
	const create = document.getElementById("create");
	create.href = "";
	create.addEventListener("click", (e)=>{
		e.preventDefault();
		if (post_id != -1)
			open_create("?post_id=" + post_id);
		else open_create();
	});
	
	if (post_id == -1)
		// If not then load the root feed (posts with no parent).
		await load_feed_from_id(null);
	else {
		await load_feed_from_id(Number(post_id));
		await load_post_data(post_id);
	}
	// Reload the page authentication visiblity, if the user is signed in 
	// then the "vote" section will be hidden from rows
}

// Analyse session and load feed:
(async () => {
	await validate_session_permanence(()=>{});
	load_feed_page();
})();