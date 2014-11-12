var list = [];
var current = -1;
var preloaders = [];

var max_preload = 10;

// we load list.json which lists all images so far

function updateCurrent(preload_forward) {
	var im = document.getElementById("image");
	if (current >= 0) {
		var element = document.getElementById("current_id");
		if (element.textContent != undefined)
			element.textContent = current+" ("+list[current]+")";
		else
			element.innerText = current+" ("+list[current]+")";
		im.src = "img/"+list[current];
		window.location.hash = ""+current;
	}

	var new_preloaders = [];
	var i;

	for (i = 1 ; i <= max_preload ; i++) {
		var preload_idx = preload_forward ? (current + i) : (current - i);
		var preload = list[preload_idx];

		if (!preload) {
			break;
		}

		var image = new Image();
		image.src = "img/" + preload;
		new_preloaders.push(image);
	}
	preloaders = new_preloaders;
}

function update_list(play_sound) {
	var deferred = $.getJSON( "list.json?r="+Math.random(), function(data) {
		var do_go_last = false;

		if (list.length > 0 && current >= list.length - 1) {
			do_go_last = true;
		}

		list = data;

		if (current >= list.length - 1) {
			do_go_last = true;
		} else if (current < 0) {
			do_go_last = true;
		}

		if ((do_go_last) && (current != list.length - 1)) {
			go_last();
			if ((document.hasFocus) && (!document.hasFocus())) {
				document.title = "[!] xkcd 1446";
			}
			if (play_sound)
				$.playSound('sound');
		}
	});
	setTimeout(function() { update_list(true); }, 60000);
	return deferred;
}

function go_prev() {
	if (current <= 0) return;
	current--;
	updateCurrent(false);
}

function go_next() {
	if (current >= (list.length-1)) return;
	current++;
	updateCurrent(true);
}

function go_first() {
	current = 0;
	updateCurrent(true);
}

function go_last() {
	current = list.length-1;
	updateCurrent(false);
}

$(document).keydown(function(e) {
	switch(e.which) {
		case 37: // left
			go_prev();
			break;
		case 38: // up
			go_first();
			break;
		case 39: // right
			go_next();
			break;
		case 40: // down
			go_last();
			break;
		default: return;
	}
	e.preventDefault();
});

if (window.location.hash) {
	current = parseInt(window.location.hash.substr(1), 10);
	if (current < 0 || isNaN(current) ) {
		current = -1;
	}
}

update_list(false).then(updateCurrent);
$(window).focus(function(){document.title = "xkcd 1446";});
