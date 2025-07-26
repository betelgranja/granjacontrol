<?php
// Función para formatear moneda en Pesos Colombianos
function formatearPesos($valor) {
    return '$' . number_format($valor, 0, ',', '.') . ' COP';
}

function formatearPesosDecimales($valor) {
    return '$' . number_format($valor, 2, ',', '.') . ' COP';
}

// Función para validar y limpiar datos
function limpiarDato($dato) {
    return trim(htmlspecialchars($dato));
}
?>