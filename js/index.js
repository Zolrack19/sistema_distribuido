const urlRespaldo = "http://172.19.0.4";
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
  }).then(async data => {
    if (!data.ok) {
      alert(data.mensajeError);
      return;
    }
    let servidorActivo = (await checkServer(data.servidor)) ? data.servidor : (await checkServer(urlRespaldo) ? urlRespaldo : false);
    if (servidorActivo) {
      window.location.href = `${servidorActivo}?usuario=${data.usuario}&clave=${data.clave}`;

      const form = document.createElement("form");
      form.method = "POST";
      form.action = servidorActivo;
      for (const key in data) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = data[key];
        form.appendChild(input);
      }
      document.body.appendChild(form);
      form.submit();


    } else {
      alert("No es posible establecer una conexión, por favor intente más tarde")
    }

  })
})

async function checkServer(url, ms = 2000) {
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), ms);
  try {
    const resp = await fetch(url, {method: "HEAD", signal: controller.signal });
    return resp.ok;
  } catch (e) {
    return false; // si falla o hace timeout
  } finally {
    clearTimeout(id);
  }
}