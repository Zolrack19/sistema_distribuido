const maquinas = [
  {
    "ip": "172.19.0.3",
    "cpu": {
      "id_dom": "svweb-inicio-cpu",
      "span_nombre": null,
      "span_cores": null,
      "span_uso": null,
      "grafico": null,
    },
    "ram": {
      "id_dom": "svweb-inicio-ram",
      "span_total": null,
      "span_uso": null,
      "grafico": null
    },
    "dis": {
      "id_dom": "svweb-inicio-dis",
    }
  },
  {
    "ip": "172.19.0.2",
    "cpu": {
      "id_dom": "svweb-princi-cpu",
      "span_nombre": null,
      "span_cores": null,
      "span_uso": null,
      "grafico": null
    },
    "ram": {
      "id_dom": "svweb-princi-ram",
      "span_total": null,
      "span_uso": null,
      "grafico": null
    },
    "dis": {
      "id_dom": "svweb-princi-dis",
    }
  },
  {
    "ip": "172.19.0.5",
    "cpu": {
      "id_dom": "svdb-cpu",
      "span_nombre": null,
      "span_cores": null,
      "span_uso": null,
      "grafico": null
    },
    "ram": {
      "id_dom": "svdb-ram",
      "span_total": null,
      "span_uso": null,
      "grafico": null
    },
    "dis": {
      "id_dom": "svdb-dis",
    }
  }
]


function crearDona(ctx, etiquetas, valores, colores) {
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: etiquetas,
      datasets: [{
        data: valores,
        backgroundColor: colores,
        borderColor: '#fff',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });
}

function instanciar(maquina, objData) {
  let div_contenedor = document.getElementById(maquina["cpu"]["id_dom"]);
  let ctx = div_contenedor.querySelector("canvas").getContext("2d");
  const grafCPU = crearDona(ctx, ['En uso', 'Disponible'], [objData.CPU.LoadPercentage, 100], [
    'rgba(21, 68, 112, 0.92)',
    'rgba(210, 237, 255, 0.7)',
  ]);
  maquina.cpu.span_cores = div_contenedor.querySelector("span[class='cores']") 
  maquina.cpu.span_uso = div_contenedor.querySelector("span[class='cpu_uso']")
  maquina.cpu.span_cores.innerHTML = objData.CPU.NumberOfCores
  maquina.cpu.span_uso.innerHTML = objData.CPU.LoadPercentage + " %"
  maquina["cpu"]["grafico"] = grafCPU

  div_contenedor = document.getElementById(maquina["ram"]["id_dom"]);
  ctx = div_contenedor.querySelector("canvas").getContext("2d")
  const grafRAM = crearDona(ctx, ['En uso', 'Disponible'], [objData.RAM.TotalGB, objData.RAM.TotalGB - objData.RAM.FreeGB], [
    'rgba(255, 170, 100, 0.78)',
    'rgba(255, 231, 230, 0.84)'
  ]);
  maquina.ram.span_total = div_contenedor.querySelector("span[class='ram_total']") 
  maquina.ram.span_uso = div_contenedor.querySelector("span[class='ram_uso']")
  maquina.ram.span_total.innerHTML = objData.RAM.TotalGB + " GB"
  maquina.ram.span_uso.innerHTML = objData.RAM.TotalGB - objData.RAM.FreeGB + " GB"
  maquina["ram"]["grafico"] = grafRAM

  let disks = objData.Disks;
  if (!Array.isArray(disks)) {
    disks = [disks];
  }
  
  const container = document.getElementById(maquina["dis"]["id_dom"]);

  disks.forEach(disk => {
    const usado = disk.TotalGB - disk.FreeGB;
    const usadoPercent = Math.round((usado / disk.TotalGB) * 100);

    const diskDiv = document.createElement("div");

    const title = document.createElement("h3");
    title.textContent = `Disco ${disk.DeviceID}`;

    const progress = document.createElement("div");
    progress.className = "progress";

    const bar = document.createElement("div");
    bar.className = "progress-bar";
    bar.style.width = usadoPercent + "%";

    progress.appendChild(bar);

    const info = document.createElement("div");
    info.className = "info";
    info.textContent = `Usado: ${usado.toFixed(2)} GB | Libre: ${disk.FreeGB.toFixed(2)} GB | Total: ${disk.TotalGB} GB`;

    diskDiv.appendChild(title);
    diskDiv.appendChild(progress);
    diskDiv.appendChild(info);

    container.appendChild(diskDiv);
  });
}

function init() {
  maquinas.forEach(maquina => {
    fetch(`http://${maquina.ip}/php/prueba.php`).then(rsp => {
      return rsp.json();
    }).then(data => {
      instanciar(maquina, data)
    })
  });
  

  setInterval(() => {
    maquinas.forEach(maquina => {
      fetch(`http://${maquina.ip}/php/prueba.php`).then(rsp => {
        return rsp.json();
      }).then(data => {
        maquina.cpu.grafico.data.datasets[0].data = [data.CPU.LoadPercentage, 100];
        maquina.cpu.span_cores.innerHTML = data.CPU.NumberOfCores
        maquina.cpu.span_uso.innerHTML = data.CPU.LoadPercentage + " %"

        maquina.ram.grafico.data.datasets[0].data = [data.RAM.TotalGB, data.RAM.TotalGB - data.RAM.FreeGB]
        maquina.ram.span_total.innerHTML = data.RAM.TotalGB + " GB"
        maquina.ram.span_uso.innerHTML = data.RAM.TotalGB - data.RAM.FreeGB + " GB"

        maquina.cpu.grafico.update();
        maquina.ram.grafico.update();
      })
    });
  }, 3000);
}
init();
