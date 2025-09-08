const storage_ip = "172.19.0.4";

const form = document.getElementById("form-transaccion");
const tbodyTransacciones = document.getElementById("tbody-transacciones");

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const formData = new FormData(form);
  fetch("/php/menu-procesar.php", {
    method: "POST",
    body: formData
  }).then(resp => {
    if (resp.ok) {
      return resp.json();
    } else {
      alert("error en la respuesta del servidor")
    }
  }).then(data => {
    console.log(data)
    if (!data.ok) {
      alert(data.mensajeError);
      return;
    } 
    const nuevaFila = document.createElement("tr");
    nuevaFila.innerHTML = `
      <td>${data["emisor"]}</td>
      <td>${data["receptor"]}</td>
      <td>${data["monto"]}</td>
      <td>${data["fecha"]}</td>
      <td><span class="link-operacion">${data["filesustento"]}</span></td>
    `;
    tbodyTransacciones.insertBefore(nuevaFila, tbodyTransacciones.firstChild);
  })
})

tbodyTransacciones.addEventListener("click", (e) => {
  if (e.target.matches("span")) {

    // para cuando sea público el servicof storage
    const linkOperacion = e.target.innerHTML;
    const temp_a = document.createElement("a");
    temp_a.href = `http://${storage_ip}/uploads/${linkOperacion}`
    temp_a.download = linkOperacion;
    temp_a.target = "blank";
    document.body.appendChild(temp_a);
    temp_a.click();
    document.body.removeChild(temp_a);
  }
})