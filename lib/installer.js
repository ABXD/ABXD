page = 0;


function setStep(page) {
	$("#progress").html("Step&nbsp;"+page+"&nbsp;of&nbsp;"+numPages);
	$("#progress").animate({width: ((page/ numPages) * 100)+"%"}, 200);
	$(".page").slideUp(200);
	$("#page"+page).slideDown(200);
}

window.onload = function() {
	$pages = $("#installPager div.page");
	numPages = $pages.length;
	$('.page').hide();
//	$('.page').css("position", "absolute");
	$('#installUI').fadeIn(100);
	$('#progress').css("width", "0%");
	page++;
	setStep(1);
	$("#prevPageButton").click(function() {
		if (page > 1) {
			page--;
			setStep(page);
		} 
		if (page == 1) $("#nextPageButton").attr("disabled");
		$("#nextPageButton").removeAttr("disabled");

	});
	$("#nextPageButton").click(function() {
		if (page < numPages) {
			page++;
			setStep(page);
		} 
		if (page == numPages) $("#nextPageButton").attr("disabled");
		$("#prevPageButton").removeAttr("disabled");
	});
	$("#installButton").click(function() { doInstall(); });
}

function checkSqlConnection(attemptCreate) {
	url = "install/checksql.php";
	if (attemptCreate == true) url += "?attemptCreate=true";
	$.post(url, {
		sqlServerAddress: $('#sqlServerAddress').val(),
		sqlUserName: $('#sqlUserName').val(),
		sqlPassword: $('#sqlPassword').val(),
		sqlDbName: $('#sqlDbName').val()
	}, function(data) {
		$('#sqlStatus').html(data);
		$('#sqlStatus').fadeIn(200);
	});
}

success = "";
function doInstall() {
	$("#prevPageButton, #nextPageButton").slideUp(250);
	$("#progress").slideUp(250);
	$("#page4").html('<div class="center" style="padding-top: 100px; font-style: italic;"><div class="pollbarContainer" style="width: 50%; margin: 12pt auto;">Installing. Please wait.</div></div>');
	$.post("install/doinstall.php", {
		action: "Install",
		dbserv: $('#sqlServerAddress').val(),
		dbuser: $('#sqlUserName').val(),
		dbpass: $('#sqlPassword').val(),
		dbname: $('#sqlDbName').val(),
		dbpref: $('#sqlTablePrefix').val(),
		boardname: $("#boardName").val(),
		logoalt: $("boardLogoAlt").val(),
		logotitle: $("boardLogoTitle").val(),
		defaultgroups: $("setUpDefaultUserGroups").val(),
		addbase: $("createDefaultForums").val(),
		htmltidy: $("useHTMLTidy").val()
	}, function(data) {
		$("#page4").html(data);
	});
}

function reenableControls() {
	$('#prevPageButton, #nextPageButton').show(250);
	$('#progress').show(250);
}
