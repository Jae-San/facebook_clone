$(function() {
    // Basculer vers le formulaire d'inscription
    $("#signup").on("click", function() {
        $("#first").slideUp("slow", function() {
            $("#second").slideDown("slow");
        });
    });

    // Basculer vers le formulaire de connexion
    $("#signin").on("click", function() {
        $("#second").slideUp("slow", function() {
            $("#first").slideDown("slow");
        });
    });

    // Login AJAX très simple
    $("#first form").on("submit", function(e) {
        e.preventDefault();
        var form = this;
        var formData = $(form).serialize() + '&login_button=Login';
        $.ajax({
            url: '/Facebook-clone/api/login_handler.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    sessionStorage.setItem('username', response.username);
                    window.location.href = '/Facebook-clone/index.php';
                } else {
                    $(form).find('.login-error').remove();
                    $(form).prepend('<div class="login-error" style="color:red;">' + response.error + '</div>');
                }
            },
            error: function() {
                $(form).find('.login-error').remove();
                $(form).prepend('<div class="login-error" style="color:red;">Erreur de connexion au serveur.</div>');
            }
        });
    });
});
