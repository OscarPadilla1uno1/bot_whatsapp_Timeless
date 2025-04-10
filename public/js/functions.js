function renderizarTabla(platillos) {
    const tabla = document.getElementById("body-tabla-platillos-menu");
    const fechaSeleccionada = document.getElementById('fecha')?.value;
    tabla.innerHTML = "";

    if (platillos.length > 0) {
        platillos.forEach((platillo, index) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">${index + 1}</td>
                <td class="px-6 py-4 whitespace-nowrap">${platillo.nombre}</td>
                <td class="px-4 py-2 relative">
                    <span class="cantidad-text">${platillo.cantidad_disponible}</span>
                    <input type="number" class="cantidad-input hidden w-20 border rounded px-1"
                        data-id="${platillo.id}"
                        data-fecha="${fechaSeleccionada}"
                        value="${platillo.cantidad_disponible}">
                    <button
                    class="guardar-cantidad hidden absolute right-4 top-1/2 -translate-y-1/2 text-green-600 font-semibold text-sm"
                    title="Guardar">
                    Guardar
                    </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <button onclick="eliminarPlatilloMenu(${platillo.id})"
                            class="text-red-600 hover:text-red-800 font-semibold text-sm">
                        Eliminar
                    </button>
                </td>
            `;
            tabla.appendChild(tr);
        });
    } else {
        tabla.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay platillos para esta fecha.</td>
            </tr>
        `;
    }
}

function eliminarPlatilloMenu(platilloId) {
    const fecha = document.getElementById("fecha").value;

    Swal.fire({
        title: "¿Estás seguro?",
        text: "Este platillo será eliminado del menú.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(window.routes.eliminar, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ platillo_id: platilloId, fecha: fecha }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Platillo eliminado",
                            text: "El platillo fue eliminado del menú correctamente.",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                        cargarMenuPorFecha(fecha);
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error al eliminar",
                            text: "El platillo no se pudo eliminar",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Error al eliminar",
                        text: "El platillo no se pudo eliminar, error del servidor",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                });
        }
    });
}

function cargarMenuPorFecha(fecha) {
    fetch(`${window.routes.porFecha}?fecha=${fecha}`, {
        headers: {
            "X-CSRF-TOKEN": window.csrfToken,
            Accept: "application/json",
        },
    })
        .then((res) => res.json())
        .then((data) => renderizarTabla(data.platillos))
        .catch((err) => console.error("Error al cargar menú:", err));
}

document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tabla-platillos-menu");

    tabla?.addEventListener("click", function (e) {
        if (e.target.classList.contains("cantidad-text")) {
            const cell = e.target.closest("td");
            const span = cell.querySelector(".cantidad-text");
            const input = cell.querySelector(".cantidad-input");
            const btn = cell.querySelector(".guardar-cantidad");

            span.classList.add("hidden");
            input.classList.remove("hidden");
            btn.classList.remove("hidden");
            input.focus();
        }

        if (e.target.classList.contains("guardar-cantidad")) {
            const cell = e.target.closest("td");
            const input = cell.querySelector(".cantidad-input");
            const span = cell.querySelector(".cantidad-text");
            const btn = cell.querySelector(".guardar-cantidad");

            const nuevaCantidad = input.value.trim();
            const platilloId = input.dataset.id;
            const fecha = input.dataset.fecha;

            if (
                !nuevaCantidad ||
                isNaN(nuevaCantidad) ||
                parseInt(nuevaCantidad) <= 0
            ) {
                alert("Ingresa una cantidad válida mayor a 0.");
                return;
            }


            console.log("Guardando cantidad:", platilloId, nuevaCantidad, fecha);
            fetch(window.routes.actualizarCantidad, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": window.csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    platillo_id: platilloId,
                    cantidad: nuevaCantidad,
                    fecha: fecha,
                }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        span.textContent = nuevaCantidad;
                        span.classList.remove("hidden");
                        input.classList.add("hidden");
                        btn.classList.add("hidden");
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error al actualizar",
                            text: "El platillo no se pudo actualizar",
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Error al actualizar",
                        text: "El platillo no se pudo actualizar, error del servidor",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                });
        }
    });
});

//////////////////////////////////////////////////////////////////////////////////////////////
function editarPlatillo(id) {
    // Obtener datos desde la fila de la tabla
    const fila = document
        .querySelector(
            `#tabla-platillos-body tr td button[onclick="editarPlatillo(${id})"]`
        )
        .closest("tr");
    const nombre = fila.children[1].textContent.trim();
    const descripcion = fila.children[2].getAttribute("title");
    const precio = parseFloat(fila.children[3].textContent.replace("LPS.", ""));

    // Obtener la URL de la imagen desde el atributo data-imagen-url del botón "Ver Imagen"
    const imagenUrl = fila
        .querySelector("button[data-imagen-url]")
        ?.getAttribute("data-imagen-url");

    // Rellenar el formulario
    document.getElementById("edit-id").value = id;
    document.getElementById("edit-nombre").value = nombre;
    document.getElementById("edit-descripcion").value = descripcion;
    document.getElementById("edit-precio_base").value = precio;

    // Si hay imagen, mostrar la imagen previa
    const imagenPreviaContainer = document.getElementById("imagen-previa");
    if (imagenUrl) {
        // Limpiar cualquier contenido previo en el contenedor de la imagen
        imagenPreviaContainer.innerHTML = "Imagen actual:";

        // Crear el elemento <img> y asignar la URL de la imagen
        const imagenElement = document.createElement("img");
        imagenElement.src = imagenUrl;
        imagenElement.alt = "Imagen Platillo";
        imagenElement.classList.add("w-32", "h-32", "object-cover"); // Puedes agregar más clases según lo necesites

        // Añadir la imagen al contenedor
        imagenPreviaContainer.appendChild(imagenElement);

        // Mostrar el botón para eliminar la imagen
        document.getElementById("eliminar-imagen").classList.remove("hidden");
        document.getElementById("eliminar-imagen").onclick = function () {
            eliminarImagen(id);
        };
    } else {
        // Si no hay imagen, mostrar el texto "No hay imagen registrada"
        imagenPreviaContainer.textContent = "No hay imagen registrada";
        document.getElementById("eliminar-imagen").classList.add("hidden");
    }

    // Mostrar el modal
    document.getElementById("modal-editar-platillo").classList.remove("hidden");
}

// Función para eliminar la imagen
function eliminarImagen(id) {
    const form = document.getElementById("form-editar-platillo");
    const inputImagen = document.getElementById("edit-imagen");
    inputImagen.value = ""; // Limpiar el campo de la imagen
    document.getElementById("imagen-previa").textContent =
        "No hay imagen registrada";
    document.getElementById("eliminar-imagen").classList.add("hidden");
    // Añadir un campo oculto para indicar que la imagen debe eliminarse
    const inputEliminarImagen = document.createElement("input");
    inputEliminarImagen.type = "hidden";
    inputEliminarImagen.name = "eliminar_imagen";
    inputEliminarImagen.value = "1";
    form.appendChild(inputEliminarImagen);
}

function cerrarModalEditar() {
    document.getElementById("modal-editar-platillo").classList.add("hidden");
}

function eliminarPlatillo(id) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Este platillo será eliminado del catálogo.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("/admin/platillos/eliminar", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({ id: id }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            title: "¡Eliminado!",
                            text: "El platillo ha sido eliminado correctamente.",
                            icon: "success",
                            confirmButtonText: "Aceptar",
                        }).then(() => {
                            location.reload();
                        });
                    }
                })
                .catch((error) => {
                    console.error("Error al eliminar el platillo:", error);
                    Swal.fire(
                        "Error",
                        "Ocurrió un error al eliminar el platillo.",
                        "error"
                    );
                });
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const formEditar = document.getElementById("form-editar-platillo");
    const formAgregar = document.getElementById("agregar-platillo-form");

    if (formEditar) {
        formEditar.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch("/admin/platillos/actualizar", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire(
                            "Actualizado",
                            "El platillo se actualizó correctamente.",
                            "success"
                        ).then(() => location.reload());
                    } else {
                        Swal.fire(
                            "Error",
                            "Hubo un problema al actualizar.",
                            "error"
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    Swal.fire("Error", "Ocurrió un error inesperado.", "error");
                });
        });
    }

    if (formAgregar) {
        formAgregar.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(this);
            const paginaActual =
                new URLSearchParams(window.location.search).get("page") || 1;
            formData.append("page", paginaActual);

            fetch("/admin/platillos/crear", {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            title: "¡Éxito!",
                            text: "Platillo guardado correctamente.",
                            icon: "success",
                            confirmButtonText: "Aceptar",
                        }).then(() => location.reload());
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        });
    }

    document.querySelectorAll(".descripcion-cell").forEach((cell) => {
        cell.addEventListener("click", () => {
            const div = cell.querySelector("div");
            if (div.classList.contains("truncate")) {
                div.classList.remove("truncate");
                div.classList.add("whitespace-normal");
            } else {
                div.classList.add("truncate");
                div.classList.remove("whitespace-normal");
            }
        });
    });
});

// Función para mostrar la imagen en un modal
function showImage(imagePath) {
    const imageModal = document.getElementById("imageModal");
    const fullImage = document.getElementById("full-image");

    if (imagePath) {
        fullImage.src = imagePath;
        imageModal.classList.remove("hidden");
    }
}

// Función para cerrar el modal de imagen
function closeImageModal() {
    const imageModal = document.getElementById("imageModal");
    imageModal.classList.add("hidden");
}

function updateButtonText() {
    const inputFile = document.getElementById("imagen");
    const labelText = document.getElementById("imagen-text");

    if (inputFile.files.length > 0) {
        labelText.textContent = "Cambiar Imagen";
    } else {
        labelText.textContent = "Seleccionar Imagen";
    }
}
