function obtenerFechaHoyTegucigalpa() {
    const ahora = new Date();

    // Obtener la hora UTC
    const utc = ahora.getTime() + ahora.getTimezoneOffset() * 60000;

    // Aplicar el offset de Tegucigalpa, que es UTC-6
    const tegucigalpa = new Date(utc + 3600000 * -6);

    const year = tegucigalpa.getFullYear();
    const month = String(tegucigalpa.getMonth() + 1).padStart(2, "0");
    const day = String(tegucigalpa.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function esFechaPasada(fechaSeleccionada) {
    const hoy = new Date(obtenerFechaHoyTegucigalpa());
    hoy.setHours(0, 0, 0, 0);

    const fecha = new Date(fechaSeleccionada);
    fecha.setHours(0, 0, 0, 0);

    return fecha < hoy;
}
function manejarEstadoFormulario(fechaPasada) {
    const formulario = document.getElementById("agregar-platillo-form-menu");
    const botonesEliminar = document.querySelectorAll(
        "#tabla-platillos-menu button"
    );
    const inputsCantidad = document.querySelectorAll(".cantidad-input");
    const botonesGuardar = document.querySelectorAll(".guardar-cantidad");
    if (formulario) {
        formulario.style.display = fechaPasada ? "none" : "block";
    }
    botonesEliminar.forEach((btn) => {
        btn.disabled = fechaPasada;
        if (fechaPasada) {
            btn.classList.add("opacity-50", "cursor-not-allowed");
        } else {
            btn.classList.remove("opacity-50", "cursor-not-allowed");
        }
    });

    inputsCantidad.forEach((input) => {
        input.disabled = fechaPasada;
    });

    botonesGuardar.forEach((btn) => {
        btn.disabled = fechaPasada;
    });
}

function renderizarTabla(platillos) {
    const tabla = document.getElementById("body-tabla-platillos-menu");
    const fechaSeleccionada = document.getElementById("fecha")?.value;
    const fechaEsPasada = esFechaPasada(fechaSeleccionada);
    tabla.innerHTML = "";

    if (platillos.length > 0) {
        platillos.forEach((platillo, index) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
    <td class="px-6 py-4 whitespace-nowrap">${index + 1}</td>
    <td class="px-6 py-4 whitespace-nowrap">${platillo.nombre}</td>
    <td class="px-4 py-2 relative">
        <span class="cantidad-text">${platillo.cantidad_disponible}</span>
        ${
            fechaEsPasada
                ? ""
                : `
        <input type="number" class="cantidad-input hidden w-20 border rounded px-1" data-id="${platillo.id}"
            data-fecha="${fechaSeleccionada}" value="${platillo.cantidad_disponible}">
        <button
            class="guardar-cantidad hidden absolute right-4 top-1/2 -translate-y-1/2 text-green-600 font-semibold text-sm"
            title="Guardar">
            Guardar
        </button>
        `
        }
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        ${
            fechaEsPasada
                ? `
        <span class="text-gray-400 italic text-sm">Acciones no disponibles</span>
        `
                : `
        <button onclick="eliminarPlatilloMenu(${platillo.id})"
            class="text-red-600 hover:text-red-800 font-semibold text-sm">
            Eliminar
        </button>
        `
        }
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

    manejarEstadoFormulario(fechaEsPasada);
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
                Swal.fire({
                    icon: "warning",
                    title: "Cantidad inválida",
                    text: "Ingresa una cantidad válida mayor a 0.",
                    confirmButtonText: "Aceptar",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });
                return;
            }
            console.log(
                "Guardando cantidad:",
                platilloId,
                nuevaCantidad,
                fecha
            );
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

            const imagenInput = document.getElementById("imagen");
            const file = imagenInput.files[0];

            if (!file) {
                Swal.fire({
                    icon: "warning",
                    title: "Imagen requerida",
                    text: "Por favor, selecciona una imagen antes de guardar el platillo.",
                    confirmButtonText: "Aceptar",
                });
                return;
            }

            if (!file.type.startsWith("image/")) {
                Swal.fire({
                    icon: "error",
                    title: "Archivo no válido",
                    text: "El archivo seleccionado no es una imagen.",
                    confirmButtonText: "Aceptar",
                });
                return;
            }

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
                            allowOutsideClick: false,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
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

function registrarNuevoUsuario(formId, endpointUrl) {
    const form = document.getElementById(formId);

    if (!form) {
        console.error(`Formulario con ID "${formId}" no encontrado.`);
        return;
    }

    // Obtener el permiso seleccionado (solo uno)

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const permisoSeleccionado = form.querySelector(
            'input[name="permiso"]:checked'
        )?.value;

        if (!permisoSeleccionado) {
            Swal.fire({
                icon: "warning",
                title: "Permiso requerido",
                text: "Por favor selecciona un permiso para el nuevo usuario.",
            });
            return;
        }

        const formData = new FormData(form);
        formData.append("permiso", permisoSeleccionado);

        try {
            const response = await fetch(endpointUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": form.querySelector('input[name="_token"]')
                        .value,
                    Accept: "application/json",
                },
                body: formData,
            });

            if (response.status === 201) {
                Swal.fire({
                    title: "¡Usuario creado!",
                    text: "El nuevo usuario ha sido registrado exitosamente.",
                    icon: "success",
                    confirmButtonColor: "#fb923c",
                    confirmButtonText: "Aceptar",
                }).then(() => {
                    location.reload(); // Recarga total
                });
            } else if (response.status === 422) {
                const errorData = await response.json();
                let mensaje = Object.values(errorData.errors)
                    .flat()
                    .join("<br>");

                Swal.fire({
                    title: "Error de validación",
                    html: mensaje,
                    icon: "error",
                    confirmButtonColor: "#fb923c",
                });
            } else {
                const errorText = await response.text();
                console.error("Error:", response.status, errorText);

                throw new Error(
                    `Error inesperado al registrar el usuario. Código: ${response.status}`
                );
            }
        } catch (err) {
            Swal.fire({
                title: "Error",
                text: err.message,
                icon: "error",
                confirmButtonColor: "#fb923c",
            });
        }
    });
}

function initEditarUsuario() {
    // Ocultar el formulario inicialmente y mostrar el mensaje
    document.getElementById("form-editar-usuario").style.display = "none";
    document.getElementById("no-user-selected").style.display = "block";

    // Agregar SweetAlert si no está ya incluido
    if (!window.Swal && !document.querySelector('script[src*="sweetalert2"]')) {
        const sweetalertScript = document.createElement("script");
        sweetalertScript.src = "https://cdn.jsdelivr.net/npm/sweetalert2@11";
        document.head.appendChild(sweetalertScript);
    }

    document.querySelectorAll(".btn-editar-usuario").forEach((btn) => {
        btn.addEventListener("click", () => {
            const userId = btn.dataset.id;

            // Limpiar mensajes de error previos
            document.querySelectorAll(".error-message").forEach((el) => {
                el.textContent = "";
            });

            fetch(`/admin/users/${userId}`)
                .then((res) => res.json())
                .then((user) => {
                    // Mostrar el formulario y ocultar el mensaje inicial
                    document.getElementById(
                        "form-editar-usuario"
                    ).style.display = "block";
                    document.getElementById("no-user-selected").style.display =
                        "none";

                    document.getElementById("edit-id").value = user.id;
                    document.getElementById("edit-name").value = user.name;
                    document.getElementById("edit-email").value = user.email;

                    document
                        .querySelectorAll(".permiso-radio")
                        .forEach((radio) => {
                            radio.checked = radio.value === user.permiso;
                        });

                    document.getElementById(
                        "form-editar-usuario"
                    ).action = `/admin/users/${user.id}`;
                })
                .catch((err) => {
                    console.error("Error al cargar el usuario:", err);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Error al cargar los datos del usuario",
                    });
                });
        });
    });

    // Interceptar el envío del formulario para hacerlo por AJAX
    document
        .getElementById("form-editar-usuario")
        .addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const userId = document.getElementById("edit-id").value;

            fetch(`/admin/users/${userId}`, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Éxito",
                            text: data.message,
                            confirmButtonText: "Aceptar",
                        }).then((result) => {
                            // Solo recargar cuando el usuario haga clic en el botón de confirmación
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        // Mostrar errores de validación
                        if (data.errors) {
                            Object.keys(data.errors).forEach((field) => {
                                const errorElement = document.getElementById(
                                    `error-${field}`
                                );
                                if (errorElement) {
                                    errorElement.textContent =
                                        data.errors[field][0];
                                }
                            });
                        }
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Ocurrió un error al procesar la solicitud",
                    });
                });
        });
}

function inicializarBotonesEliminarUsuario(idUsuarioActual) {
    const botonesEliminar = document.querySelectorAll(".btn-eliminar-usuario");

    botonesEliminar.forEach((btn) => {
        btn.addEventListener("click", function () {
            const userId = this.dataset.id;

            if (parseInt(userId) === parseInt(idUsuarioActual)) {
                btn.disabled = true;
                btn.classList.add("opacity-50", "cursor-not-allowed");
                btn.title = "No puedes eliminar tu propio usuario";
                return; // No agregamos el listener
            }

            Swal.fire({
                title: "¿Estás seguro?",
                text: "¡Este usuario será eliminado permanentemente!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#e3342f",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, eliminar",
                cancelButtonText: "Cancelar",
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/usuarios/${userId}`, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                            Accept: "application/json",
                        },
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                Swal.fire(
                                    "¡Eliminado!",
                                    data.message,
                                    "success"
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    "Error",
                                    data.message ||
                                        "No se pudo eliminar el usuario.",
                                    "error"
                                );
                            }
                        })
                        .catch((error) => {
                            console.error("Error:", error);
                            Swal.fire(
                                "Error",
                                "Hubo un problema al eliminar.",
                                "error"
                            );
                        });
                }
            });
        });
    });
}

function mostrarFormularioPedido(menu) {
    const contenedor = document.getElementById("form-platillos-programar");
    contenedor.innerHTML = ""; // Limpiar

    //console.log(menu); // Verificar que el menú se está pasando correctamente

    if (menu.length === 0) {
        contenedor.innerHTML =
            '<p class="text-gray-500">No hay platillos disponibles para esta fecha.</p>';
        return;
    }

    menu.forEach((item) => {
        const card = document.createElement("div");
        card.className = "p-4 border rounded shadow mb-2";

        card.innerHTML = `
        <div class="flex justify-between items-center">
            <div>
                <h4 class="font-semibold">${item.nombre}</h4>
                <p class="text-sm text-gray-500">Disponible: ${
                    item.cantidad_disponible
                }</p>
                <p class="text-sm text-gray-500">Precio: L. ${parseFloat(
                    item.precio_base
                ).toFixed(2)}</p>
            </div>
            <div>
                <input type="number" name="cantidad_${item.id}" min="0" max="${
            item.cantidad_disponible
        }"
                    class="w-20 border rounded p-1" placeholder="0">
            </div>
        </div>
        `;

        contenedor.appendChild(card);
    });
}

function cargarPedidosPorFecha(fecha) {
    const contenedorPedidos = document.getElementById("contenedor-pedidos");
    const contenedorMenu = document.getElementById("contenedor-menu-programar");

    if ($.fn.DataTable.isDataTable("#tabla-pedidos")) {
        $("#tabla-pedidos").DataTable().clear().draw();
    }

    // Obtener pedidos programados
    fetch(`/admin/pedidos/por-fecha?fecha=${fecha}`)
        .then((response) => response.json())
        .then((data) => {
            const filas =
                data.pedidos && data.pedidos.length > 0
                    ? data.pedidos.map((pedido, index) => {
                          return [
                              index + 1,
                              pedido.cliente.nombre,
                              pedido.estado,
                              `LPS. ${parseFloat(pedido.total).toFixed(2)}`,
                              `<button class="text-blue-600 hover:text-blue-800 font-semibold text-xs"
            onclick="abrirModalEditarPedido(${pedido.id})">Editar</button>
        <button class="text-red-600 hover:text-red-800 font-semibold text-xs ml-2"
            onclick="eliminarPedido(${pedido.id})">Eliminar</button>`,
                          ];
                      })
                    : [];

            if ($.fn.DataTable.isDataTable("#tabla-pedidos")) {
                const tabla = $("#tabla-pedidos").DataTable();
                tabla.clear().rows.add(filas).draw();
            }
        })
        .catch((error) => {
            console.error("Error al cargar pedidos:", error);
        });

    // Obtener menú de platillos
    fetch(`/admin/menu/fecha?fecha=${fecha}`)
        .then((response) => response.json())
        .then((data) => {
            contenedorMenu.innerHTML = "";

            if (!data.platillos || data.platillos.length === 0) {
                contenedorMenu.innerHTML =
                    '<p class="text-gray-500">No hay platillos para este día.</p>';

                document.getElementById("form-platillos-programar").innerHTML =
                    '<p class="text-gray-500">No hay platillos para este día.</p>';

                return;
            }

            let tablaHtml = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad Disponible
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    `;

            data.platillos.forEach((platillo, index) => {
                tablaHtml += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${
                            index + 1
                        }</td>
                        <td class="px-6 py-4 whitespace-nowrap">${
                            platillo.nombre
                        }</td>
                        <td class="px-6 py-4 whitespace-nowrap">${parseFloat(
                            platillo.precio_base
                        ).toFixed(2)} LPS</td>
                        <td class="px-6 py-4 whitespace-nowrap">${
                            platillo.cantidad_disponible
                        }</td>
                    </tr>
                    `;
            });

            tablaHtml += `
                </tbody>
            </table>
        </div>
        `;

            contenedorMenu.innerHTML = tablaHtml;
            mostrarFormularioPedido(data.platillos); // reconstruye el formulario con inputs
        })
        .catch((error) => {
            console.error("Error al cargar el menú:", error);
            contenedorMenu.innerHTML =
                '<p class="text-red-500">Error al obtener el menú.</p>';
        });
}

function abrirModalEditarPedido(pedidoId) {
    fetch(`/admin/pedidos/${pedidoId}/edit`)
        .then((response) => response.json())
        .then((data) => {
            console.log("Datos del pedido:", data);
            document
                .getElementById("modal-editar-pedido-programado")
                .classList.remove("hidden");

            // Rellenar campos fijos
            document.getElementById("nombre-edit").value = data.cliente.nombre;
            document.getElementById("telefono-edit").value =
                data.cliente.telefono;
            document.getElementById("google-maps-link-edit").value =
                data.url_maps || "";
            document.getElementById("latitud-edit").value = data.latitud;
            document.getElementById("longitud-edit").value = data.longitud;
            document.getElementById(
                "ubicacion-text-edit"
            ).value = `${data.latitud}, ${data.longitud}`;

            // Contenedor donde se inyectarán los campos dinámicos
            const contenedor = document.getElementById(
                "campos-form-pedido-editar"
            );

            // Eliminar contenido previo solo de método de pago y platillos
            contenedor
                .querySelectorAll(".dinamico")
                .forEach((el) => el.remove());

            // Método de pago
            const metodoPagoHtml = document.createElement("div");
            metodoPagoHtml.classList.add("mb-4", "dinamico");
            metodoPagoHtml.innerHTML = `
        <label class="block text-sm font-medium text-gray-700">Método de pago</label>
        <select id="editar-metodo-pago" class="mt-1 block w-full rounded border-gray-300 metodo_pago">
            <option value="efectivo" ${
                data.metodo_pago === "efectivo" ? "selected" : ""
            }>Efectivo</option>
            <option value="tarjeta" ${
                data.metodo_pago === "tarjeta" ? "selected" : ""
            }>Tarjeta</option>
            <option value="transferencia" ${
                data.metodo_pago === "transferencia" ? "selected" : ""
            }>Transferencia
            </option>
        </select>
        `;
            contenedor.appendChild(metodoPagoHtml);

            // Platillos ya seleccionados
            data.platillos.forEach((platillo, index) => {
                const menuItem = data.menu_dia.find(
                    (m) => m.id === platillo.platillo_id
                );
                const maxDisponible = menuItem
                    ? menuItem.cantidad_disponible
                    : 100;

                const platilloHtml = document.createElement("div");
                platilloHtml.classList.add(
                    "mb-4",
                    "border",
                    "p-4",
                    "rounded",
                    "dinamico"
                );
                platilloHtml.innerHTML = `
        <div class="mb-2 font-medium">${platillo.nombre}</div>
        <input type="hidden" name="platillos[${index}][platillo_id]" value="${platillo.platillo_id}">
        <label class="block text-sm text-gray-600">Cantidad</label>
        <input type="number" name="platillos[${index}][cantidad]" value="${platillo.cantidad}" min="0"
            max="${maxDisponible}" class="mt-1 block w-full rounded border-gray-300"
            data-platillo-id="${platillo.platillo_id}" data-precio="${platillo.precio}">

        <input type="hidden" name="platillos[${index}][precio]" value="${platillo.precio}">
        <p class="text-sm text-gray-500 mt-1">Máximo disponible: ${maxDisponible}</p>
        `;
                contenedor.appendChild(platilloHtml);
            });

            // Platillos del menú no seleccionados
            const idsExistentes = data.platillos.map((p) => p.platillo_id);
            let indexNuevo = data.platillos.length;

            data.menu_dia.forEach((platillo) => {
                if (!idsExistentes.includes(platillo.id)) {
                    const platilloHtml = document.createElement("div");
                    platilloHtml.classList.add(
                        "mb-4",
                        "border",
                        "p-4",
                        "rounded",
                        "bg-green-50",
                        "dinamico"
                    );
                    platilloHtml.innerHTML = `
        <div class="mb-2 font-medium">${platillo.nombre} (nuevo)</div>
        <input type="hidden" name="platillos[${indexNuevo}][platillo_id]" value="${platillo.id}">
        <label class="block text-sm text-gray-600">Cantidad</label>
        <input type="number" name="platillos[${indexNuevo}][cantidad]" value="0" min="0"
            max="${platillo.cantidad_disponible}" class="mt-1 block w-full rounded border-gray-300"
            data-platillo-id="${platillo.id}" data-precio="${platillo.precio}">
        <input type="hidden" name="platillos[${indexNuevo}][precio]" value="${platillo.precio}">
        <p class="text-sm text-gray-500 mt-1">Máximo disponible: ${platillo.cantidad_disponible}</p>
        `;
                    contenedor.appendChild(platilloHtml);
                    indexNuevo++;
                }
            });

            // Guardar ID del pedido
            document.getElementById(
                "form-editar-pedido-programar"
            ).dataset.pedidoId = pedidoId;

            // Agregar el ID del pedido como un campo oculto
            const pedidoIdInput = document.createElement("input");
            pedidoIdInput.type = "hidden";
            pedidoIdInput.className = "pedido_id";
            pedidoIdInput.value = pedidoId;
            contenedor.appendChild(pedidoIdInput);

            // Asegurarse de que la fecha sea visible
            const fechaInput = document.createElement("input");
            fechaInput.type = "hidden";
            fechaInput.className = "fecha-menu-editar";
            fechaInput.value = data.fecha || "";
            contenedor.appendChild(fechaInput);
        })
        .catch((error) => {
            console.error("Error al cargar los datos del pedido:", error);
            Swal.fire(
                "Error",
                "No se pudo cargar el pedido para edición.",
                "error"
            );
        });
}

async function actualizarPedidoProgramado(form) {
    const fecha = document.getElementById("fecha-menu-programar").value;
    const nombre = document.getElementById("nombre-edit").value;
    const telefono = document.getElementById("telefono-edit").value;
    const latitud = parseFloat(document.getElementById("latitud-edit").value);
    const longitud = parseFloat(document.getElementById("longitud-edit").value);
    const mapaUrl = document.getElementById("google-maps-link-edit").value;
    const metodo_pago = document.getElementById("editar-metodo-pago").value;
    const pedido_id = form.querySelector(".pedido_id")?.value;

    //console.log("pedido_id:", pedido_id);
    //console.log(telefono, nombre, latitud, longitud, mapaUrl, metodo_pago);
    //return;

    const platillos = [];

    // Recolectar TODOS los inputs de cantidad, no solo los que tienen la clase 'cantidad-platillo'
    form.querySelectorAll(
        'input[name^="platillos"][name$="[cantidad]"]'
    ).forEach((input) => {
        const cantidad = parseInt(input.value);
        const platilloId = parseInt(input.dataset.platilloId);
        const precio = parseFloat(input.dataset.precio);

        console.log("platillo id:", platilloId, "cantidad:", cantidad);

        if (cantidad > 0) {
            platillos.push({ platillo_id: platilloId, cantidad, precio });
        }
    });

    console.log("platillos recolectados:", platillos);

    if (platillos.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin platillos",
            text: "Debes seleccionar al menos un platillo.",
        });
        return;
    }

    // Validaciones
    if (!fecha) {
        Swal.fire({
            icon: "warning",
            title: "Fecha requerida",
            text: "Debes seleccionar una fecha para el pedido.",
        });
        return;
    }

    if (!nombre?.trim()) {
        Swal.fire({
            icon: "warning",
            title: "Nombre requerido",
            text: "El nombre del cliente no puede estar vacío.",
        });
        return;
    }

    if (!telefono?.trim()) {
        Swal.fire({
            icon: "warning",
            title: "Teléfono requerido",
            text: "Debes ingresar el número de teléfono.",
        });
        return;
    }

    if (!mapaUrl?.trim()) {
        Swal.fire({
            icon: "warning",
            title: "Link de Google Maps requerido",
            text: "Por favor proporciona un enlace válido de ubicación.",
        });
        return;
    }

    if (!latitud || isNaN(latitud) || !longitud || isNaN(longitud)) {
        Swal.fire({
            icon: "warning",
            title: "Coordenadas no detectadas",
            text: "No se detectaron coordenadas válidas en el enlace de Google Maps.",
        });
        return;
    }

    if (!metodo_pago) {
        Swal.fire({
            icon: "warning",
            title: "Método de pago requerido",
            text: "Debes seleccionar un método de pago.",
        });
        return;
    }

    const payload = {
        pedido_id,
        fecha,
        nombre,
        telefono,
        mapa_url: mapaUrl,
        latitud,
        longitud,
        metodo_pago,
        platillos,
    };

    console.log("Payload:", payload);

    const token = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    try {
        const res = await fetch(`/admin/pedidos/${pedido_id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
            },
            body: JSON.stringify(payload),
            credentials: "same-origin",
        });

        const data = await res.json();

        if (res.ok) {
            Swal.fire({
                icon: "success",
                title: "¡Pedido actualizado!",
                text: "Los cambios fueron guardados correctamente.",
            });

            cargarPedidosPorFecha(fecha);
            document
                .getElementById("modal-editar-pedido-programado")
                .classList.add("hidden");
        } else {
            Swal.fire({
                icon: "error",
                title: "Error al actualizar",
                text: data.mensaje || "Ocurrió un error desconocido.",
            });
        }
    } catch (error) {
        console.error("Error al actualizar:", error);
        Swal.fire({
            icon: "error",
            title: "Error de red",
            text: "No se pudo conectar con el servidor.",
        });
    }
}

function eliminarPedido(id) {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción cancelará el pedido y restaurará el stock del menú diario.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No, conservar",
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/pedidos/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(
                            error.mensaje ||
                                "Ocurrió un error al cancelar el pedido."
                        );
                    }
                    return response.json();
                })
                .then((data) => {
                    Swal.fire({
                        title: "¡Cancelado!",
                        text: data.mensaje,
                        icon: "success",
                    }).then(() => {
                        const fecha = document.getElementById(
                            "fecha-menu-programar"
                        );
                        if (fecha && fecha.value) {
                            cargarPedidosPorFecha(fecha.value);
                        } else {
                            location.reload();
                        }
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Error",
                        text: error.message,
                        icon: "error",
                    });
                });
        }
    });
}
