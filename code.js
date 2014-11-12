var list = [];
var current = -1;

// we load list.json which lists all images so far

function updateCurrent() {
	var im = document.getElementById("image");
	if (current >= 0) {
		document.getElementById("current_id").innerText = current+" ("+list[current]+")";
		im.src = "img/"+list[current];
	}
}

function update_list() {
	$.getJSON( "list.json?r="+Math.random(), function(data) {
		var do_go_last = false;
		if (current == list.length - 1) do_go_last = true;
		list = data;
		if (do_go_last)
			go_last();
	});

	setTimeout(update_list, 60000);
}

function go_prev() {
	if (current <= 0) return;
	current--;
	updateCurrent();
}

function go_next() {
	if (current >= (list.length-1)) return;
	current++;
	updateCurrent();
}

function go_first() {
	current = 0;
	updateCurrent();
}

function go_last() {
	current = list.length-1;
	updateCurrent();
}

update_list();
