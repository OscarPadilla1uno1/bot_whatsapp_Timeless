<x-app-layout> 


    <div class="container">
        <h2 class="mb-4">Administrar Menú Diario</h2>

        <!-- Mostrar los platillos ya en el menú -->
        <h4>Platillos en el Menú</h4>
        <ul>
            @foreach ($platillosEnMenu as $platillo)
                <li>{{ $platillo->nombre }} - Cantidad Máxima: {{ $platillo->cantidad_maxima }}</li>
            @endforeach
        </ul>

        <hr>

        <!-- Formulario para agregar platillos al menú -->
        <h4>Agregar Platillo al Menú</h4>
        <form action="{{ route('admin.menu.agregar') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="platillo" class="form-label">Selecciona un Platillo:</label>
                <select name="platillo_id" id="platillo" class="form-control">
                    @foreach ($todosLosPlatillos as $platillo)
                        <option value="{{ $platillo->id }}">{{ $platillo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad Máxima:</label>
                <input type="number" name="cantidad" id="cantidad" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Agregar al Menú</button>
        </form>
    </div>


</x-app-layout>