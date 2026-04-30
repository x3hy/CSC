const _feed_table = document.getElementById("feed");
const _feed_tbody = _feed_table.querySelector("tbody");
const _body = document.getElementById("body");
const _path = document.getElementById("post-path");

function format_table_row(row, title){
	return `
	<tr>
		<td>${row.id}</td>
		<td>${row.comment_count}</td>
		<td>
			<span ${row.score < 0 
				? "class=\"warning\"" // negative score
				: row.score > 0 
					? "class=\"accent\"" // positive score
					: ""}>${row.score}
			</span>
		</td>
		<td>
			<a>${row.display 
				? row.display 
				: row.username}
			<a>
		</td>
		<td>${row.description}</td>
		<td>${row.time_issued}</td>
		<td>
			<button onclick="open_post('?post_id=${row.id}&title=${title}-${row.id}')" ${row.comment_count == 0 ? "disabled title=\"There are no comments\"" : ""}>
				Open Comments
			</button>
		</td>
	</tr>`;
}

// Load the feed from a certain id root
async function load_feed_from_id(id, title){
	const posts = await POST({"call": "get_posts", "content": id});
	if (posts.status != 0)
		_feed_tbody.innerHTML = "Post has no comments";
	
	let output_table = "";
	posts.message.forEach((post, index) => {
		output_table += format_table_row(post, title != undefined ? title : "");
	});
	
	_feed_tbody.innerHTML = output_table;
	make_sortable_table("feed");

}

// Load the root feed:
function load_root_feed(){
	load_feed_from_id(null);
}

async function load_post_data(id){
	const post = await POST({"call": "get_post", "content": id});
	if (post.status != 0)
		open_error(post.message, 404);
	_body.innerHTML = `
<pre>
Posted by  : ${post.message.display ? post.message.display : post.message.username}
Posted on  : ${post.message.time_issued}
Post Score : ${post.message.score}
Post ID    : ${post.message.id}
-----------+

${post.message.description}
</pre>`
}

// Analyse session and load feed:
(async () => {
	await validate_session_permanence(()=>{});
		
	// If post_id is set in the URLSearchParams then
	// load it as the root feed ID. This allows for
	// static recursion, where each branch is loaded
	// statically like it was the root.
	const params = new URLSearchParams(String(location.href).split("?")[1]);
	let post_id = -1;
	let path_str = undefined;
	for (let pair of params.entries())
		if (pair[0] == "post_id") post_id = pair[1];
		else if (pair[0] == "title") path_str = pair[1];
	
	// not implemented:
	if (path_str != undefined){
		let output="";
		path_str.split("-").forEach((part)=>{
			output+=`&gt<a href="?post_id=${part}>${part}</a>`;
		})
	}
	if (post_id == -1)
		// If not then load the root feed (posts with no parent).
		load_root_feed();
	else {
		load_feed_from_id(Number(post_id), path_str);
		load_post_data(post_id);
	}
})();
