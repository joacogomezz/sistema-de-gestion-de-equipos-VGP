// SCRIPT DE SEGURIDAD PARA NAVEGACIÓN - VERSIÓN SIMPLE
console.log('Script de seguridad cargado');

// Función para limpiar el historial
function limpiarHistorial() {
    if (window.history && window.history.pushState) {
        // Agregar entradas al historial
        for (var i = 0; i < 5; i++) {
            window.history.pushState(null, null, window.location.href);
        }
        // Reemplazar la entrada actual
        window.history.replaceState(null, null, window.location.href);
    }
}

// Función para redirigir al login
function redirigirAlLogin() {
    console.log('Redirigiendo al login por navegación hacia atrás');
    
    // Limpiar historial
    limpiarHistorial();
    
    // Mostrar mensaje
    if (typeof alertify !== 'undefined') {
        alertify.error('Sesión finalizada. Por favor, inicia sesión nuevamente.');
    } else {
        alert('Sesión finalizada. Por favor, inicia sesión nuevamente.');
    }
    
    // Redirigir al login
    window.location.replace('../index.php?mensaje=sesion_finalizada');
}

// Interceptar navegación hacia atrás
window.addEventListener('popstate', function(event) {
    console.log('Navegación hacia atrás detectada');
    // Solo interceptar si no estamos en proceso de logout
    if (!window.location.href.includes('salir.php')) {
        redirigirAlLogin();
    }
});

// Interceptar teclas de navegación
document.addEventListener('keydown', function(e) {
    // Alt + Flecha Izquierda (navegación hacia atrás)
    if (e.altKey && e.keyCode === 37) {
        e.preventDefault();
        console.log('Alt + Flecha Izquierda detectada');
        redirigirAlLogin();
    }
    
    // Alt + Flecha Derecha (navegación hacia adelante)
    if (e.altKey && e.keyCode === 39) {
        e.preventDefault();
        console.log('Alt + Flecha Derecha detectada');
        redirigirAlLogin();
    }
});

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    console.log('DOM listo, inicializando seguridad');
    
    // Limpiar historial al cargar la página
    limpiarHistorial();
    
    // Limpiar historial cada 3 segundos
    setInterval(function() {
        limpiarHistorial();
    }, 3000);
    
    console.log('Seguridad de navegación inicializada');
});

// Exponer funciones globalmente
window.seguridadNavegacion = {
    permitirNavegacion: function() {
        console.log('Navegación permitida para logout');
    },
    redirigirAlLogin: redirigirAlLogin
};
