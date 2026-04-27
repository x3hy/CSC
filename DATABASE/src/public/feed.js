const _feed_table = document.getElementById("feed");
const _feed_tbody = _feed_table.querySelector("tbody");
const _body = document.getElementById("body");

function format_table_row(row){
	return `
	<tr>
		<td>${row.id}</td>
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
		<td>${row.comment_count}</td>
		<td>
			<button onclick="open_post(${row.id})" ${row.comment_count == 0 ? "disabled title=\"There are no comments\"" : ""}>
				Open Comments
			</button>
		</td>
	</tr>`;
}

// Load the feed from a certain id root
async function load_feed_from_id(id){
	const posts = await POST({"call": "get_posts", "content": id});
	if (posts.status != 0)
		_feed_tbody.innerHTML = "Post has no comments";
	
	let output_table = "";
	posts.message.forEach((post, index) => {
		output_table += format_table_row(post);
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
	return;
		
	// If post_id is set in the URLSearchParams then
	// load it as the root feed ID. This allows for
	// static recursion, where each branch is loaded
	// statically like it was the root.
	const params = new URLSearchParams(String(location.href).split("?")[1]);
	for (let pair of params.entries())
		if (pair[0] == "post_id"){
			load_feed_from_id(Number(pair[1]));
			load_post_data(pair[1]);
			return;
		}
	
	// If not then load the root feed (posts with no parent).
	load_root_feed();
})();
