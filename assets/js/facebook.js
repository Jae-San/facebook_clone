$(document).ready(function() {
    // Button for profile post
    $('#submit_profile_post').click(function() {
        $.ajax({
            type: "POST",
            url: "/Facebook-clone/api/ajax_submit_profile_post.php",
            data: $('form.profile_post').serialize(),
            success: function(msg) {
                $('#post_form').modal('hide');
                // Recharger dynamiquement les posts sans recharger la page
                loadPostsAjax();
                $('form.profile_post textarea').val('');
                $('form.profile_post input[type="file"]').val('');
            },
            error: function () {
                alert("Failed to post!");
            }
        });
    });

    // Fonction simple pour recharger les posts en AJAX
    function loadPostsAjax() {
        var userLoggedIn = sessionStorage.getItem('username');
        $.ajax({
            url: '/Facebook-clone/api/ajax_load_posts.php',
            type: 'POST',
            data: { page: 1, userLoggedIn: userLoggedIn },
            success: function(data) {
                $('.posts_area').html(data);
            }
        });
    }
});

function getUsers(value, user) {
    $.post("/Facebook-clone/api/ajax_friend_search.php", {query: value, userLoggedIn: user}, function(data) {
        $(".results").html(data);
    });
}

function getDropDownData(user, type) {
    if ($(".dropdown_data_window").css("height") == "0px") {
        let pageName;

        if(type === "notification") {
            pageName = "/Facebook-clone/api/ajax_load_notifications.php";
            $("span").remove("#unread_notification");
        } else if (type === "message") {
            pageName = "/Facebook-clone/api/ajax_load_messages.php";
            $("span").remove("#unread_message");
        }

        $.ajax({
            url: pageName,
            type: "POST",
            data: "page=1&userLoggedIn=" + user,
            cache: false,
            success: function(response) {
                $(".dropdown_data_window").html(response);
                $(".dropdown_data_window").css({
                    "padding": "0px",
                    "height": "280px",
                    "border": "1px solid #DADADA"
                });
                $("#dropdown_data_type").val(type);
            }
        });
    } else {
        $(".dropdown_data_window").html("");
        $(".dropdown_data_window").css({
            "padding": "0px",
            "height": "0px",
            "border": "none"
        });
    }
}

// Expand search input when focused (on large screens)
$('#search_text_input').focus(function() {
    if (window.matchMedia("(min-width: 800px)").matches) {
        $(this).animate({width: '500px'}, 500);
    }
});

// Submit search form on icon/button click
$('.button_holder').on("click", function() {
    document.search_form.submit();
});

// Live search users
function getLiveSearchUsers(value, user) {
    $.post("/Facebook-clone/api/ajax_search.php", {query: value, userLoggedIn: user}, function(data) {

        if ($(".search_results_footer_empty")[0]) {
            $(".search_results_footer_empty").addClass("search_results_footer").removeClass("search_results_footer_empty");
        }

        $(".search_results").html(data);
        $(".search_results_footer").html("<a href='/Facebook-clone/search.php?q=" + value + "'>See All Results</a>");

        if (data.trim() === "") {
            $(".search_results").html("");
            $(".search_results_footer").html("").removeClass("search_results_footer").addClass("search_results_footer_empty");
        }
    });
}

// Hide search & dropdown on click outside
$(document).click(function (e) {
    if (!$(e.target).closest('.search_results, #search_text_input').length) {
        $(".search_results").html("");
        $(".search_results_footer").html("").removeClass("search_results_footer").addClass("search_results_footer_empty");
    }

    if (!$(e.target).closest('.dropdown_data_window').length) {
        $(".dropdown_data_window").html("");
        $(".dropdown_data_window").css({"padding": "0px", "height": "0px", "border": "none"});
    }
});
