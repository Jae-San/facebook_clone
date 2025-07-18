// Protection d'accès back-office côté client (UX)
(function() {
    // Liste des pages back-office à protéger
    var backOfficePages = [
        '/Facebook-clone/vues/back-office/dashboard.html',
        '/Facebook-clone/vues/back-office/manage_users.html',
        '/Facebook-clone/vues/back-office/manage_articles.html'
    ];
    var currentPath = window.location.pathname;
    if (backOfficePages.includes(currentPath)) {
        var role = sessionStorage.getItem('role');
        if (role !== 'admin' && role !== 'moderator') {
            alert('Accès réservé aux administrateurs et modérateurs.');
            window.location.href = '/Facebook-clone/index.php';
        }
    }
})();
