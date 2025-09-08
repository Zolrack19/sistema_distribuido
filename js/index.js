const loginForm = document.getElementById("login-form");

loginForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  const formData = new FormData(loginForm);
  fetch("/php/login.php", {
    method: "POST",
    body: formData
  }).then(resp => {
    if (resp.ok) {
      return resp.json();
    } else {
      alert("error en la respuesta del servidor")
    }
  }).then(data => {
    if (!data.ok) {
      alert(data.mensajeError);
      return;
    }
    window.location.href = "/php/menu.php";
  })
})