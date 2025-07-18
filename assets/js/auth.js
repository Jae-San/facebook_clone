// Gestion de la session utilisateur via sessionStorage
const AuthSession = {
    setUser: function(username) {
        sessionStorage.setItem('username', username);
    },
    getUser: function() {
        return sessionStorage.getItem('username');
    },
    clearUser: function() {
        sessionStorage.removeItem('username');
    },
    isLoggedIn: function() {
        return !!sessionStorage.getItem('username');
    }
};
