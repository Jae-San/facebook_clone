document.addEventListener("DOMContentLoaded", () => {
  const jcropTarget = document.getElementById('jcrop_target');

  if (jcropTarget) {
      $(jcropTarget).Jcrop({
          aspectRatio: 1,
          setSelect: [200, 200, 37, 49],
          onSelect: updateCoords
      });
  }

  function updateCoords(c) {
      document.getElementById('x').value = c.x;
      document.getElementById('y').value = c.y;
      document.getElementById('w').value = c.w;
      document.getElementById('h').value = c.h;
  }

  window.checkCoords = () => {
      const w = parseInt(document.getElementById('w').value, 10);
      if (w) return true;

      showAlert("Veuillez sélectionner une zone de découpe avant de valider.");
      return false;
  };

  window.cancelCrop = () => {
      window.location.href = '/Facebook-clone/vues/clients/upload.php';
      return false;
  };

  function showAlert(message) {
      // Si tu veux garder une alerte classique, décommente cette ligne :
      // alert(message);

      // Sinon, meilleure UX : injecte un message dans la page
      const alertBox = document.createElement("div");
      alertBox.textContent = message;
      alertBox.style.cssText = `
          position: fixed;
          top: 20px;
          left: 50%;
          transform: translateX(-50%);
          background-color: #f44336;
          color: white;
          padding: 12px 24px;
          border-radius: 6px;
          z-index: 9999;
          box-shadow: 0 2px 8px rgba(0,0,0,0.2);
          font-family: sans-serif;
      `;
      document.body.appendChild(alertBox);

      setTimeout(() => {
          alertBox.remove();
      }, 3000);
  }
});
